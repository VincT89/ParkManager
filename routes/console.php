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
})->everyFiveMinutes()->name('sync_all_active_listings')->withoutOverlapping(4);

\Illuminate\Support\Facades\Schedule::command('app:cancel-expired-pending-reservations')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('parkos:auth-test', function () {
    $client = app(\App\Integrations\Support\ParkosClient::class);

    $result = $client->authenticate();

    $this->info('Autenticazione Parkos OK');
    $this->line(json_encode($result, JSON_PRETTY_PRINT));
});

Artisan::command('parkos:bookings-test {merchant=1895}', function ($merchant) {
    $client = app(\App\Integrations\Support\ParkosClient::class);

    $from = now()->subDays(30);
    $to = now()->addDays(180);

    $records = $client->findBookingsByPeriodType($from, $to, 'arrival', $merchant);

    $this->info('Prenotazioni trovate: '.count($records));

    foreach (array_slice($records, 0, 10) as $record) {
        $this->line(json_encode([
            'code' => $record['code'] ?? null,
            'merchant_id' => $record['merchant_id'] ?? null,
            'parking_type' => $record['parking_type'] ?? null,
            'location_type' => $record['location_type'] ?? null,
            'arrival_date' => $record['arrival_date'] ?? null,
            'departure_date' => $record['departure_date'] ?? null,
        ], JSON_PRETTY_PRINT));
    }
});
