<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

$reservations = Reservation::where('customer_name', 'like', '%Francesco Guida%')->get();

foreach ($reservations as $res) {
    echo "ID: {$res->id} | External ID: {$res->external_id} | Listing: {$res->parking_listing_id} | Created: {$res->created_at} | Updated: {$res->updated_at}\n";
}
