<?php

namespace Tests\Feature\Integrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Platform;
use App\Models\Parking;
use App\Models\ParkingListing;
use App\Models\ParkingProduct;
use App\Models\PlatformProductMapping;
use App\Models\Reservation;
use App\Actions\SyncListingAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class SyncListingActionTest extends TestCase
{
    use RefreshDatabase;

    private ParkingListing $listing;
    private SyncListingAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $platform = Platform::create(['name' => 'Parkos', 'slug' => 'parkos', 'is_active' => true]);
        $parking = Parking::create(['name' => 'Test Parking', 'total_spots' => 100, 'is_active' => true]);
        
        $this->listing = ParkingListing::create([
            'platform_id' => $platform->id,
            'parking_id' => $parking->id,
            'is_active' => true
        ]);

        $product = ParkingProduct::create([
            'parking_id' => $parking->id,
            'name' => 'Open Air',
            'code' => 'OPEN_AIR_INT',
            'capacity' => 10,
            'price' => 10,
            'is_active' => true
        ]);

        PlatformProductMapping::create([
            'platform_id' => $platform->id,
            'parking_product_id' => $product->id,
            'external_ref' => 'OPEN_AIR',
            'is_active' => true
        ]);

        Config::set('services.parkos.fixture_mode', true);

        $this->action = app(SyncListingAction::class);
    }

    public function test_sync_creates_reservation()
    {
        Config::set('services.parkos.fixture_file', 'reservations_success.json');

        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        $this->assertEquals(1, $stats['created']);
        $this->assertEquals(0, $stats['updated']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['skipped']);
        $this->assertEmpty($stats['errors']);

        $this->assertDatabaseHas('reservations', [
            'parking_listing_id' => $this->listing->id,
            'external_id' => 'PKS-10001',
            'customer_name' => 'Mario Rossi'
        ]);
    }

    public function test_sync_updates_existing_reservation()
    {
        Config::set('services.parkos.fixture_file', 'reservations_success.json');

        // First execution creates it
        $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        // Second execution should update it
        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        $this->assertEquals(0, $stats['created']);
        $this->assertEquals(1, $stats['updated']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['skipped']);
    }

    public function test_sync_dry_run_identifies_created_and_updated()
    {
        Config::set('services.parkos.fixture_file', 'reservations_success.json');

        // Dry run first
        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), true);
        
        $this->assertEquals(1, $stats['created']);
        $this->assertEquals(0, $stats['updated']);
        
        // Assert nothing was actually saved
        $this->assertDatabaseMissing('reservations', ['external_id' => 'PKS-10001']);

        // Save it for real
        $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        // Dry run again
        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), true);
        $this->assertEquals(0, $stats['created']);
        $this->assertEquals(1, $stats['updated']);
    }

    public function test_sync_fails_on_unmapped_product()
    {
        Config::set('services.parkos.fixture_file', 'reservations_unmapped_product.json');

        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        $this->assertEquals(0, $stats['created']);
        $this->assertEquals(0, $stats['updated']);
        $this->assertEquals(0, $stats['skipped']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertCount(1, $stats['errors']);
        $this->assertStringContainsString('Nessun mapping attivo trovato', $stats['errors'][0]);
    }

    public function test_sync_captures_invalid_shape_exception()
    {
        Config::set('services.parkos.fixture_file', 'reservations_bad_shape.json');

        $stats = $this->action->execute($this->listing, Carbon::today(), Carbon::tomorrow(), false);

        // The adapter throws an exception during fetch, which should be caught globally by the action
        $this->assertEquals(1, $stats['failed']);
        $this->assertCount(1, $stats['errors']);
        $this->assertStringContainsString('Invalid shape', $stats['errors'][0]);
    }
}
