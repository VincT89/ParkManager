<?php

namespace App\Console\Commands;

use App\Actions\SyncListingAction;
use App\Models\ParkingListing;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPlatformsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:platforms 
                            {--platform= : Platform slug to sync (optional)} 
                            {--listing= : Specific listing ID to sync (optional)}
                            {--dry-run : Only fetch and output, do not save}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync reservations from external platforms';

    public function handle(SyncListingAction $action): int
    {
        $platformSlug = $this->option('platform');
        $listingId = $this->option('listing');
        $dryRun = $this->option('dry-run');

        $query = ParkingListing::with(['platform', 'parking'])
            ->active()
            ->whereHas('platform', function ($q) {
                $q->active();
            });

        if ($platformSlug) {
            $query->whereHas('platform', function ($q) use ($platformSlug) {
                $q->where('slug', $platformSlug);
            });
        }

        if ($listingId) {
            $query->where('id', $listingId);
        }

        $listings = $query->get();

        if ($listings->isEmpty()) {
            $this->info('No active listings found for the specified criteria.');
            return 0;
        }

        $from = Carbon::today()->subDays(30);
        $to = Carbon::today()->addDays(90);

        $this->info("Starting sync from {$from->toDateString()} to {$to->toDateString()} " . ($dryRun ? '[DRY-RUN]' : ''));

        foreach ($listings as $listing) {
            $this->info("Syncing listing ID: {$listing->id} (Platform: {$listing->platform->name}, Parking: {$listing->parking->name})...");

            $stats = $action->execute($listing, $from, $to, $dryRun);

            $status = empty($stats['errors']) ? 'success' : 'failed';

            \App\Models\SyncLog::create([
                'platform_id'          => $listing->platform_id,
                'parking_listing_id'   => $listing->id,
                'source'               => 'command',
                'status'               => $status,
                'is_dry_run'           => $dryRun,
                'reservations_created' => $stats['created'],
                'reservations_updated' => $stats['updated'],
                'reservations_failed'  => $stats['failed'],
                'reservations_skipped' => $stats['skipped'],
                'notes'                => empty($stats['errors']) ? null : implode("\n", $stats['errors']),
            ]);

            $this->table(
                ['Created', 'Updated', 'Skipped', 'Failed'],
                [[$stats['created'], $stats['updated'], $stats['skipped'], $stats['failed']]]
            );

            if (!empty($stats['errors'])) {
                $this->error("Errors encountered:");
                foreach ($stats['errors'] as $error) {
                    $this->line("- $error");
                }
            }
            $this->newLine();
        }

        $this->info('Sync completed.');
        return 0;
    }
}
