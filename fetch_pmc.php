<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ParkingListing;
use App\Integrations\AdapterRegistry;
use App\Services\PlatformSyncService;
use Carbon\Carbon;

$listing = ParkingListing::where('id', 1)->first(); // PMC
$adapter = app(AdapterRegistry::class)->forPlatform($listing->platform);
$syncService = app(PlatformSyncService::class);

$from = Carbon::now()->subDays(30);
$to = Carbon::now();

echo "Fetching PMC reservations from {$from} to {$to}...\n";

$result = $syncService->syncListingReservations($listing, $from, $to);

echo "Sync completato:\n";
echo "Creati: " . $result['created'] . "\n";
echo "Aggiornati: " . $result['updated'] . "\n";
echo "Saltati: " . $result['skipped'] . "\n";
echo "Falliti: " . $result['failed'] . "\n";

foreach ($result['errors'] as $error) {
    echo "Errore: {$error}\n";
}
