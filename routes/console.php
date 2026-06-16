<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::call(function () {
    $listings = \App\Models\ParkingListing::with('platform')
        ->where('is_active', true)
        ->whereHas('platform', function ($q) {
            $q->where('is_active', true)
              ->where('slug', '!=', 'website');
        })
        ->get();
    
    foreach ($listings as $listing) {
        \App\Jobs\SyncListingJob::dispatch($listing);
    }
})->everyFifteenMinutes()->name('sync_all_active_listings')->withoutOverlapping(14);

\Illuminate\Support\Facades\Schedule::command('app:cancel-expired-pending-reservations')
    ->everyMinute()
    ->withoutOverlapping();
