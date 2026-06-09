<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\ParkingListing::with(['parking.products', 'platform'])->get() as $l) {
    if ($l->platform->slug === 'parking-my-car') {
        echo "Listing ID: {$l->id} - {$l->parking->name}\n";
        foreach($l->parking->products as $p) {
            echo "  Product ID: {$p->id} - {$p->name}\n";
        }
    }
}
