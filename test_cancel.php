<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ParkingListing;
use App\Models\Reservation;
use App\Integrations\AdapterRegistry;
use App\Actions\SyncListingAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

$listing = ParkingListing::where('id', 1)->first(); // PMC
$syncService = app(SyncListingAction::class);

$externalId = '99999999';

// 1. Creiamo una reservation mockata
Reservation::updateOrCreate([
    'parking_listing_id' => $listing->id,
    'external_id' => $externalId,
], [
    'customer_name' => 'Mario Rossi',
    'starts_at' => Carbon::now()->addDays(2),
    'ends_at' => Carbon::now()->addDays(5),
    'status' => 'confirmed',
    'spots' => 1,
]);

echo "Prenotazione di test {$externalId} creata come 'confirmed'.\n";

// Fake dell'endpoint
Http::fake([
    '*/pmc_rest/bookings_resource_updated*' => Http::response([
        'bookings' => [
            [
                'id' => $externalId,
                'parking_id' => 2248,
                'parking_model_id' => 2306,
                'status' => 'annullata', // Status da PMC
                'in_dttm' => Carbon::now()->addDays(2)->timestamp,
                'out_dttm' => Carbon::now()->addDays(5)->timestamp,
                'user' => 'Mario Rossi',
            ],
            // Una non esistente
            [
                'id' => '88888888',
                'parking_id' => 2248,
                'parking_model_id' => 2306,
                'status' => 'annullata',
                'in_dttm' => Carbon::now()->addDays(2)->timestamp,
                'out_dttm' => Carbon::now()->addDays(5)->timestamp,
                'user' => 'Luigi Verdi',
            ]
        ]
    ]),
    '*' => Http::response([], 200)
]);

$from = Carbon::now()->subHours(2);
$to = Carbon::now();

echo "Eseguo sync...\n";
$result = $syncService->execute($listing, $from, $to, false);

echo "Risultato Sync: Creati: {$result['created']}, Aggiornati: {$result['updated']}, Skippati: {$result['skipped']}, Falliti: {$result['failed']}\n";

$check = Reservation::where('external_id', $externalId)->first();
echo "Stato attuale nel DB della prenotazione {$externalId}: {$check->status->value}\n";

$checkMissing = Reservation::where('external_id', '88888888')->first();
echo "La prenotazione 88888888 esiste nel DB? " . ($checkMissing ? 'Sì' : 'No') . "\n";
