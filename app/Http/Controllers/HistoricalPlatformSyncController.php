<?php

namespace App\Http\Controllers;

use App\Jobs\SyncListingJob;
use App\Models\ParkingListing;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HistoricalPlatformSyncController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from']);
        $to = Carbon::parse($validated['to']);

        if ($from->diffInMonths($to) > 6) {
            return back()->withErrors([
                'to' => 'Il recupero storico non può superare 6 mesi per volta.',
            ]);
        }

        $listings = ParkingListing::with('platform')
            ->where('is_active', true)
            ->whereHas('platform', function ($q) {
                $q->where('is_active', true)
                  ->where('slug', '!=', 'website');
            })
            ->get();

        foreach ($listings as $listing) {
            SyncListingJob::dispatch(
                $listing,
                $validated['from'],
                $validated['to'],
                'storico'
            );
        }

        return back()->with(
            'success',
            'Recupero storico piattaforme avviato. Le prenotazioni verranno aggiornate a breve.'
        );
    }
}
