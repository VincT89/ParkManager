<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlatformProductMapping;
use App\Models\Platform;

$platform = Platform::where('slug', 'parking-my-car')->first();

if ($platform) {
    PlatformProductMapping::updateOrCreate(
        [
            'platform_id' => $platform->id,
            'parking_product_id' => 2, // Auto/Moto coperto
            'external_ref' => '2307',
        ],
        [
            'external_name' => 'navetta coperto',
        ]
    );
    echo "Mappato Product 2 (coperto) su model_id 2307.\n";
} else {
    echo "Piattaforma non trovata.\n";
}
