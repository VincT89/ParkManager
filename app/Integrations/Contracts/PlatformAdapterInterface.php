<?php

namespace App\Integrations\Contracts;

use App\Models\ParkingListing;
use Carbon\Carbon;

interface PlatformAdapterInterface
{
    /**
     * Get the human-readable name of the platform adapter.
     */
    public function getName(): string;

    /**
     * Get the platform slug this adapter handles.
     */
    public function getPlatformSlug(): string;

    /**
     * Fetch reservations from the external platform for a specific listing within a date range.
     * 
     * @return \App\Integrations\DTO\NormalizedReservation[]
     */
    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array;
}
