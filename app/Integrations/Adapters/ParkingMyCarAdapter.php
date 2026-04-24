<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;

class ParkingMyCarAdapter extends AbstractPlatformAdapter
{
    public function getName(): string
    {
        return 'ParkingMyCar';
    }

    public function getPlatformSlug(): string
    {
        return 'parking-my-car';
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        throw new \Exception("Doc API non disponibile per ParkingMyCar.");
    }
}
