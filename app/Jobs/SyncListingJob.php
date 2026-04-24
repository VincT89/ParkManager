<?php

namespace App\Jobs;

use App\Actions\SyncListingAction;
use App\Models\ParkingListing;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ParkingListing $listing
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SyncListingAction $action): void
    {
        if (!$this->listing->is_active || !$this->listing->platform->is_active) {
            return;
        }

        $from = Carbon::today()->subDays(30);
        $to = Carbon::today()->addDays(90);

        $stats = $action->execute($this->listing, $from, $to, dryRun: false);

        $status = empty($stats['errors']) ? 'success' : 'failed';

        // Log the sync result
        SyncLog::create([
            'platform_id'          => $this->listing->platform_id,
            'parking_listing_id'   => $this->listing->id,
            'source'               => 'job',
            'status'               => $status,
            'is_dry_run'           => false,
            'reservations_created' => $stats['created'],
            'reservations_updated' => $stats['updated'],
            'reservations_failed'  => $stats['failed'],
            'reservations_skipped' => $stats['skipped'],
            'notes'                => empty($stats['errors']) ? null : implode("\n", $stats['errors']),
        ]);
    }
}
