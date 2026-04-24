<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;

class VologioAdapter extends AbstractPlatformAdapter
{
    public function getName(): string
    {
        return 'Vologio';
    }

    public function getPlatformSlug(): string
    {
        return 'vologio';
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        throw new \Exception("Doc API non disponibile per Vologio.");
    }
}
