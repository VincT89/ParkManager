<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

$reservations = Reservation::where('parking_listing_id', 1)->get();
$keysCount = [];
$flightValues = [];

foreach ($reservations as $res) {
    $raw = $res->raw_data ?? [];
    if (is_string($raw)) {
        $raw = json_decode($raw, true);
    }
    
    foreach ($raw as $key => $val) {
        if (!isset($keysCount[$key])) {
            $keysCount[$key] = 0;
        }
        $keysCount[$key]++;
        
        if (str_contains(strtolower($key), 'flight') || str_contains(strtolower($key), 'volo') || str_contains(strtolower($key), 'airport')) {
            if ($val !== null && $val !== '') {
                $flightValues[$key][] = is_scalar($val) ? $val : json_encode($val);
            }
        }
    }
}

echo "Tutte le chiavi presenti in raw_data (e numero occorrenze):\n";
foreach ($keysCount as $k => $c) {
    echo "- $k: $c\n";
}

echo "\nValori trovati per chiavi sospette ('flight', 'volo', 'airport'):\n";
foreach ($flightValues as $k => $vals) {
    echo "- $k: " . count($vals) . " non-vuoti. Esempi: " . implode(", ", array_slice(array_unique($vals), 0, 5)) . "\n";
}
