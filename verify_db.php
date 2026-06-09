<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\SyncLog;

echo "--- Ultimi 5 Reservations importati ---\n";
$reservations = Reservation::whereHas('parkingListing.platform', function($q) {
    $q->where('slug', 'parking-my-car');
})->orderBy('created_at', 'desc')->take(5)->get();

foreach($reservations as $res) {
    $status = $res->status->value ?? 'N/A';
    echo "ID: {$res->external_id} | Status: {$status} | Dal: {$res->starts_at} | Al: {$res->ends_at} | Cliente: {$res->customer_name}\n";
}

echo "\n--- Errori in Sync Logs ---\n";
$logs = SyncLog::whereNotNull('notes')->orderBy('created_at', 'desc')->take(5)->get();
foreach($logs as $log) {
    echo "Log [{$log->created_at}] - Falliti: {$log->reservations_failed}\nNote: {$log->notes}\n";
}
