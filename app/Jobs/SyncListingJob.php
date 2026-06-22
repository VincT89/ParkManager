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

    public int $timeout = 300;
    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 180, 300];
    }

    public function middleware(): array
    {
        return [
            new \Illuminate\Queue\Middleware\WithoutOverlapping('sync-listing-'.$this->listing->id)
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ParkingListing $listing,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly string $source = 'job',
        public readonly string $mode = 'modified',
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SyncListingAction $action): void
    {
        if (!$this->listing->is_active || !$this->listing->platform->is_active) {
            return;
        }

        if ($this->listing->platform->slug === 'website') {
            return;
        }

        $adapter = app(\App\Integrations\AdapterRegistry::class)->forPlatform($this->listing->platform);
        
        if ($this->from && $this->to) {
            $from = Carbon::parse($this->from);
            $to = Carbon::parse($this->to);
        } else {
            [$from, $to] = $adapter->defaultSyncWindow();
        }

        $stats = $action->execute($this->listing, $from, $to, dryRun: false, mode: $this->mode);

        $status = empty($stats['errors']) ? 'success' : 'failed';

        // Log the sync result
        SyncLog::create([
            'platform_id'          => $this->listing->platform_id,
            'parking_listing_id'   => $this->listing->id,
            'source'               => $this->source,
            'status'               => $status,
            'is_dry_run'           => false,
            'reservations_created' => $stats['created'],
            'reservations_updated' => $stats['updated'],
            'reservations_failed'  => $stats['failed'],
            'reservations_skipped' => $stats['skipped'],
            'notes'                => empty($stats['errors']) ? null : substr(implode("\n", $stats['errors']), 0, 1000),
            'window_from'          => $from,
            'window_to'            => $to,
        ]);
    }
}
