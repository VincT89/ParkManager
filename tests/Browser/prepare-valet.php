<?php

use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

$root = dirname(__DIR__, 2);
$expected = str_replace('\\', '/', $root.'/storage/framework/testing/management-browser.sqlite');
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite'
    || str_replace('\\', '/', getenv('DB_DATABASE') ?: '') !== $expected) {
    throw new RuntimeException('Valet browser fixtures require the isolated testing database.');
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || str_replace('\\', '/', config('database.connections.sqlite.database')) !== $expected) {
    throw new RuntimeException('Unexpected test database configuration.');
}
$fixtures = json_decode(file_get_contents(dirname($expected).'/management-browser-fixtures.json'), true, 512, JSON_THROW_ON_ERROR);
Artisan::call('migrate', ['--force' => true]);
foreach (['A', 'B'] as $letter) {
    $parking = Parking::findOrFail($fixtures[$letter]['parking']);
    if (! str_contains($parking->name, '(test)')) {
        throw new RuntimeException('Expected a demonstration parking.');
    }
    $listing = $parking->listings()->whereHas('platform', fn ($query) => $query->where('slug', 'parkos'))->firstOrFail();
    $product = $parking->products()->firstOrFail();
    $count = $letter === 'B' ? 25 : 1;
    for ($index = 1; $index <= $count + 2; $index++) {
        $valet = $index <= $count ? true : ($index === $count + 1 ? false : null);
        $name = $valet ? 'Valet '.$letter.' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT).' (test)'
            : ($valet === false ? 'Sosta ordinaria '.$letter.' (test)' : 'Servizio sconosciuto '.$letter.' (test)');
        Reservation::updateOrCreate(['parking_listing_id' => $listing->id, 'external_id' => 'VALET-BROWSER-'.$letter.'-'.$index], [
            'parking_id' => $parking->id, 'parking_product_id' => $product->id,
            'customer_name' => $name, 'starts_at' => now()->addDays($index + 1), 'ends_at' => now()->addDays($index + 2),
            'spots' => 1, 'passengers_count' => 1, 'status' => 'confirmed', 'price' => 20,
            'is_valet' => $valet, 'raw_data' => $valet === null ? [] : ['parking_type' => $valet ? 'valet' : 'shuttle'],
        ]);
    }
}
echo "Isolated valet browser fixtures ready.\n";
