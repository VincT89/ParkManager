<?php

namespace App\Http\Controllers;

use App\Jobs\SyncListingJob;
use App\Models\ParkingListing;
use Illuminate\Http\RedirectResponse;

class ManualPlatformSyncController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $listings = ParkingListing::with('platform')
            ->where('is_active', true)
            ->whereHas('platform', function ($q) {
                $q->where('is_active', true)
                  ->where('slug', '!=', 'website');
            })
            ->get();

        foreach ($listings as $listing) {
            SyncListingJob::dispatch($listing);
        }

        return back()->with(
            'success',
            'Sincronizzazione piattaforme avviata. Le prenotazioni verranno aggiornate tra pochi istanti.'
        );
    }
}
