<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;

class MyParkingAdapter extends AbstractPlatformAdapter
{
    public function getName(): string
    {
        return 'MyParking';
    }

    public function getPlatformSlug(): string
    {
        return 'my-parking';
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        throw new \Exception("Doc API non disponibile per MyParking.");
    }
}
