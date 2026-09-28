<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\ManagementFixtures;

// This helper is intentionally limited to a disposable SQLite database.
$root = dirname(__DIR__, 2);
$expected = str_replace('\\', '/', $root.'/storage/framework/testing/management-browser.sqlite');
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite'
    || str_replace('\\', '/', getenv('DB_DATABASE') ?: '') !== $expected) {
    throw new RuntimeException('Browser fixtures require the isolated testing database.');
}
require $root.'/vendor/autoload.php';
if (! is_dir(dirname($expected))) {
    mkdir(dirname($expected), 0777, true);
}
if (! file_exists($expected)) {
    touch($expected);
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('migrate', ['--force' => true]);
if (User::count() > 0) {
    throw new RuntimeException('Browser fixture database already contains users. Reuse it or select a fresh test workspace.');
}
$fixtures = ManagementFixtures::create();
$result = [];
foreach ($fixtures['parks'] as $key => $park) {
    $result[$key] = ['parking' => $park['parking']->id, 'reservation' => $park['reservation']->id, 'public_key' => $park['parking']->public_booking_key];
}
file_put_contents(dirname($expected).'/management-browser-fixtures.json', json_encode($result, JSON_PRETTY_PRINT));
echo "Isolated browser fixtures ready.\n";
