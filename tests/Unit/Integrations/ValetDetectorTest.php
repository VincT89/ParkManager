<?php

use App\Integrations\Support\ValetDetector;

it('classifies only explicit service labels', function (string $platform, array $payload, ?bool $expected) {
    expect(ValetDetector::fromPayload($platform, $payload))->toBe($expected);
})->with([
    ['parkos', ['parking_type' => 'valet'], true],
    ['parkos', ['parking_type' => 'shuttle'], false],
    ['parking-my-car', ['carvalet' => 'Parcheggio'], false],
    ['parking-my-car', ['carvalet' => 'Car Valet'], true],
    ['parking-my-car', ['carvalet' => 'Parcheggio + Car Valet', 'parking_type' => 'airport'], true],
    ['parking-my-car', ['carvalet' => '  PARCHEGGIO   +   CAR VALET  '], true],
    ['parking-my-car', ['carvalet' => ' VALET '], true],
    ['parking-my-car', ['carvalet' => 1], null],
    ['parking-my-car', ['carvalet' => 'No valet'], null],
    ['parking-my-car', ['carvalet' => 'Parcheggio senza Car Valet'], null],
    ['parking-my-car', ['carvalet' => ['valet']], null],
    ['parkos', ['notes' => 'Richiesta informazioni valet'], null],
    ['website', ['parking_type' => 'valet'], null],
    ['parkos', [], null],
]);

it('identifies historical roosh valet without inferring a different service or location', function () {
    $services = ValetDetector::rooshServices(['serviceLocations' => [[
        'id' => 'valet-location-test', 'parking_type_name' => 'Valet',
        'services' => [['id' => 'valet-service-test', 'enabled' => false]],
    ]]]);

    expect(ValetDetector::fromPayload('vologio', ['service_id' => 'valet-service-test', 'service_location_id' => 'valet-location-test'], $services))->toBeTrue()
        ->and(ValetDetector::fromPayload('vologio', ['service_id' => 'unknown-test'], $services))->toBeNull()
        ->and(ValetDetector::fromPayload('vologio', ['service_id' => 'valet-service-test', 'service_location_id' => 'other-test'], $services))->toBeNull();
});

it('does not classify ambiguous roosh service ids', function () {
    $services = ValetDetector::rooshServices(['serviceLocations' => [
        ['id' => 'location-a-test', 'parking_type_name' => 'Valet', 'services' => [['id' => 'duplicate-test']]],
        ['id' => 'location-b-test', 'parking_type_name' => 'Shuttle', 'services' => [['id' => 'duplicate-test']]],
    ]]);
    expect(ValetDetector::fromPayload('vologio', ['service_id' => 'duplicate-test'], $services))->toBeNull();
});
