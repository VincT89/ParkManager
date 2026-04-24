<?php

namespace Tests\Unit\Integrations;

use Tests\TestCase;
use App\Integrations\Adapters\ParkosAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ParkosAdapterTest extends TestCase
{
    private ParkosAdapter $adapter;
    private ParkingListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adapter = new ParkosAdapter();
        // create a dummy listing (we don't need real DB relationships for unit tests if we don't save)
        // actually we can just pass a mock or empty listing
        $this->listing = new ParkingListing(['id' => 1]);
        
        Config::set('services.parkos.fixture_mode', true);
    }

    public function test_reads_success_fixture_and_normalizes_properly()
    {
        Config::set('services.parkos.fixture_file', 'reservations_success.json');
        
        $reservations = $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());

        $this->assertCount(1, $reservations);
        $res = $reservations[0];
        
        $this->assertEquals('PKS-10001', $res->external_id);
        $this->assertEquals('OPEN_AIR', $res->external_product_ref);
        $this->assertEquals('Mario Rossi', $res->customer_name);
        $this->assertEquals(1, $res->spots);
        $this->assertEquals(49.90, $res->price);
        $this->assertEquals('EUR', $res->currency);
        $this->assertTrue($res->starts_at->isSameDay(Carbon::parse('2026-05-01')));
    }

    public function test_throws_exception_on_missing_fields()
    {
        Config::set('services.parkos.fixture_file', 'reservations_missing_fields.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing period dates for PKS-10002');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_invalid_dates()
    {
        Config::set('services.parkos.fixture_file', 'reservations_invalid_dates.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid dates: ends_at before starts_at for PKS-10005');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_bad_shape()
    {
        Config::set('services.parkos.fixture_file', 'reservations_bad_shape.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid shape: missing or invalid "reservations" key.');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_malformed_json()
    {
        Config::set('services.parkos.fixture_file', 'reservations_malformed_json.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON in fixture/');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_missing_customer_name()
    {
        // Missing fields fixture already drops customer.name, or we can make a specific one.
        // Actually reservations_missing_fields.json doesn't drop customer name, it drops ends_at and price.
        // Wait, reservations_missing_fields.json has customer.name = "Luigi Bianchi"
        // Let's create a new fixture for missing customer name inline or use missing_fields.
        Config::set('services.parkos.fixture_file', 'reservations_missing_customer.json');
        
        // Write the fixture inline for this test
        file_put_contents(base_path('tests/Fixtures/Integrations/Parkos/reservations_missing_customer.json'), json_encode([
            "reservations" => [
                [
                    "id" => "PKS-999",
                    "product_code" => "OPEN",
                    "period" => ["starts_at" => "2026-05-01T08:00:00+02:00", "ends_at" => "2026-05-05T08:00:00+02:00"],
                    "spots" => 1,
                    "customer" => ["email" => "test@test.com"]
                ]
            ]
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: customer.name for PKS-999');
        
        try {
            $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
        } finally {
            unlink(base_path('tests/Fixtures/Integrations/Parkos/reservations_missing_customer.json'));
        }
    }

    public function test_throws_exception_if_fixture_file_missing()
    {
        Config::set('services.parkos.fixture_file', 'does_not_exist.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Fixture file not found at path:/');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_when_fixture_mode_is_false()
    {
        Config::set('services.parkos.fixture_mode', false);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Parkos non ancora implementata.');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }
}
