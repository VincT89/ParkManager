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
        
        $this->adapter = app(ParkosAdapter::class);
        $this->listing = new ParkingListing(['id' => 1]);
        $this->listing->external_id = '15325';
        
        Config::set('services.parkos.fixture_mode', true);
    }

    public function test_reads_success_fixture_and_normalizes_properly()
    {
        Config::set('services.parkos.fixture_file', 'reservations_success.json');
        
        $reservations = $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());

        $this->assertCount(1, $reservations);
        $res = $reservations[0];
        
        $this->assertEquals('AA11BB22', $res->external_id);
        $this->assertEquals('15325:shuttle:outdoor', $res->external_product_ref);
        $this->assertEquals('Mario Rossi', $res->customer_name);
        $this->assertEquals(1, $res->spots);
        $this->assertEquals(99.0, $res->price);
        $this->assertEquals('EUR', $res->currency);
        $this->assertEquals('AB123CD', $res->license_plate);
        $this->assertEquals('+393331112233', $res->customer_phone);
        $this->assertTrue($res->starts_at->isSameDay(Carbon::parse('2026-06-15')));
    }

    public function test_throws_exception_on_missing_fields()
    {
        Config::set('services.parkos.fixture_file', 'missing_fields.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: code');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_invalid_dates()
    {
        Config::set('services.parkos.fixture_file', 'invalid_dates.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid dates: ends_at before starts_at for INVALIDDATES');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }

    public function test_throws_exception_on_bad_shape()
    {
        Config::set('services.parkos.fixture_file', 'bad_shape.json');
        
        file_put_contents(base_path('tests/Fixtures/Integrations/Parkos/bad_shape.json'), json_encode([
            "reservations" => [] // missing 'data'
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid shape: missing or invalid "data" key.');
        
        try {
            $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
        } finally {
            @unlink(base_path('tests/Fixtures/Integrations/Parkos/bad_shape.json'));
        }
    }

    public function test_throws_exception_if_fixture_file_missing()
    {
        Config::set('services.parkos.fixture_file', 'does_not_exist.json');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Fixture file not found at path:/');
        
        $this->adapter->fetchReservations($this->listing, Carbon::today(), Carbon::tomorrow());
    }
}
