<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

$reservations = Reservation::whereIn('id', [2110, 2111])->get();

foreach ($reservations as $res) {
    echo "\n=== RECORD ID: {$res->id} ===\n";
    echo "External ID: {$res->external_id}\n";
    $raw = $res->raw_data;
    echo "PMC ID: " . ($raw['id'] ?? 'N/A') . "\n";
    echo "PMC Created: " . ($raw['created'] ?? 'N/A') . "\n";
}
