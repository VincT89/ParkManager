<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Adapters\VologioAdapter;
use App\Integrations\Support\RooshProviderClient;
use App\Models\PlatformProductMapping;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class VologioValetTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private array $park;

    private PlatformProductMapping $valetMapping;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->park = ManagementFixtures::create()['parks']['B'];
        $this->park['apiPlatform']->update(['slug' => 'vologio']);
        $this->park['apiListing']->update(['external_id' => 'location-main-test']);
        $this->valetMapping = PlatformProductMapping::create([
            'platform_id' => $this->park['apiPlatform']->id, 'parking_product_id' => $this->park['product']->id,
            'external_ref' => 'service-valet-test', 'is_active' => true,
        ]);
        $other = $this->park['management']->parkings()->create(['name' => 'Altro parcheggio (test)', 'total_spots' => 20, 'capacity_mode' => 'shared', 'is_active' => true]);
        $product = $other->products()->create(['name' => 'Posti altro parcheggio (test)', 'code' => 'auto_open', 'capacity' => 20, 'price' => 10, 'is_active' => true]);
        PlatformProductMapping::create([
            'platform_id' => $this->park['apiPlatform']->id, 'parking_product_id' => $product->id,
            'external_ref' => 'service-other-test', 'is_active' => true,
        ]);
    }

    private function adapter(): VologioAdapter
    {
        return new VologioAdapter(new RooshProviderClient([
            'base_url' => 'https://roosh-valet.test/provider/v1', 'api_key' => 'synthetic-key', 'client_id' => 'synthetic-client',
        ]));
    }

    private function catalog(): array
    {
        return ['serviceLocations' => [
            ['id' => 'location-main-test', 'parking_type_name' => 'Shuttle', 'services' => [['id' => 'service-main-test', 'enabled' => true]]],
            ['id' => 'location-valet-test', 'parking_type_name' => 'Valet', 'services' => [['id' => 'service-valet-test', 'enabled' => false]]],
            ['id' => 'location-other-test', 'parking_type_name' => 'Valet', 'services' => [['id' => 'service-other-test', 'enabled' => true]]],
        ]];
    }

    private function booking(string $id, string $service, string $location, array $extra = []): array
    {
        return array_merge([
            'id' => $id, 'service_id' => $service, 'service_location_id' => $location,
            'start' => '2026-11-10T10:00:00+01:00', 'end' => '2026-11-12T10:00:00+01:00',
            'customer' => ['name' => 'Cliente dimostrativo (test)'], 'status' => 'completed',
        ], $extra);
    }

    public function test_modified_import_includes_only_explicitly_mapped_valet_services_for_this_parking(): void
    {
        Http::fake([
            '*/service-locations/' => Http::response($this->catalog()),
            '*/bookings/findByModification*' => Http::response(['bookingsByModification' => [
                $this->booking('ordinary-test', 'service-main-test', 'location-main-test'),
                $this->booking('valet-test', 'service-valet-test', 'location-valet-test'),
                $this->booking('private-test', 'service-other-test', 'location-other-test'),
                $this->booking('mismatched-location-test', 'service-valet-test', 'location-other-test'),
            ]]),
        ]);

        $rows = $this->adapter()->fetchReservations($this->park['apiListing'], now()->subDay(), now());
        $this->assertSame(['ordinary-test', 'valet-test'], array_column($rows, 'external_id'));
        $this->assertFalse($rows[0]->is_valet);
        $this->assertTrue($rows[1]->is_valet);
        $this->assertSame('service-valet-test', $rows[1]->raw_data['service_id']);
    }

    public function test_stay_period_import_reads_the_real_service_response_wrapper_and_deduplicates(): void
    {
        $valet = $this->booking('valet-test', 'service-valet-test', 'location-valet-test');
        Http::fake([
            '*/service-locations/' => Http::response($this->catalog()),
            '*/bookings/findByServiceLocationId*' => Http::response(['bookingsByServiceLocation' => [
                $this->booking('ordinary-test', 'service-main-test', 'location-main-test'),
            ]]),
            '*/bookings/findByServiceId*' => Http::response(['bookingsByService' => [
                $valet, $valet,
                $this->booking('outside-period-test', 'service-valet-test', 'location-valet-test', ['start' => '2025-01-01', 'end' => '2025-01-03']),
            ]]),
        ]);

        $rows = $this->adapter()->fetchReservations($this->park['apiListing'], Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), 'stay_period');
        $this->assertSame(['ordinary-test', 'valet-test'], array_column($rows, 'external_id'));
        $this->assertFalse($rows[0]->is_valet);
        $this->assertTrue($rows[1]->is_valet);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/bookings/findByServiceId') && $request['service_id'] === 'service-valet-test');
    }

    public function test_catalog_presence_alone_does_not_authorize_another_location(): void
    {
        $this->valetMapping->update(['is_active' => false]);
        Http::fake([
            '*/service-locations/' => Http::response($this->catalog()),
            '*/bookings/findByModification*' => Http::response(['bookingsByModification' => [
                $this->booking('valet-test', 'service-valet-test', 'location-valet-test'),
                $this->booking('private-test', 'service-other-test', 'location-other-test'),
            ]]),
        ]);

        $this->assertSame([], $this->adapter()->fetchReservations($this->park['apiListing'], now()->subDay(), now()));
    }

    public function test_catalog_failure_keeps_ordinary_imports_and_does_not_guess_valet_locations(): void
    {
        Http::fake([
            '*/service-locations/' => Http::response([], 503),
            '*/bookings/findByModification*' => Http::response(['bookingsByModification' => [
                $this->booking('ordinary-test', 'service-main-test', 'location-main-test'),
                $this->booking('valet-test', 'service-valet-test', 'location-valet-test'),
            ]]),
        ]);

        $rows = $this->adapter()->fetchReservations($this->park['apiListing'], now()->subDay(), now());
        $this->assertCount(1, $rows);
        $this->assertSame('ordinary-test', $rows[0]->external_id);
        $this->assertNull($rows[0]->is_valet);
    }
}
