<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Support\RooshProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RooshProviderClientTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private function client(): RooshProviderClient
    {
        Http::preventStrayRequests();

        return new RooshProviderClient([
            'base_url' => 'https://roosh-client.test/provider/v1',
            'api_key' => 'synthetic-key',
            'client_id' => 'synthetic-client',
        ]);
    }

    public function test_services_are_queried_individually_without_losing_valet_or_duplicate_bookings(): void
    {
        Http::fake(function ($request) {
            $rows = match ($request['service_id']) {
                'shuttle-test' => [['id' => 'shuttle-booking-test'], ['id' => 'shared-booking-test']],
                'valet-test' => [['id' => 'valet-booking-test'], ['id' => 'shared-booking-test']],
                default => [],
            };

            return Http::response(['bookingsByService' => $rows]);
        });

        $rows = $this->client()->findBookingsByServiceId(['shuttle-test', 'valet-test', 'shuttle-test']);

        $this->assertSame(['shuttle-booking-test', 'shared-booking-test', 'valet-booking-test'], array_column($rows, 'id'));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['service_id'] === 'shuttle-test');
        Http::assertSent(fn ($request) => $request['service_id'] === 'valet-test');
        Http::assertNotSent(fn ($request) => str_contains($request['service_id'], ','));
    }

    public function test_pagination_restarts_for_each_service_and_keeps_later_pages(): void
    {
        Http::fake(function ($request) {
            if ($request['service_id'] === 'shuttle-test' && (int) $request['page'] === 1) {
                return Http::response(['bookingsByService' => array_map(fn ($id) => ['id' => 'shuttle-test-'.$id], range(1, 100))]);
            }
            if ($request['service_id'] === 'shuttle-test') {
                return Http::response(['bookingsByServiceId' => [['id' => 'shuttle-last-test']]]);
            }

            return Http::response([['id' => 'valet-test']]);
        });

        $rows = $this->client()->findBookingsByServiceId(['shuttle-test', 'valet-test']);

        $this->assertCount(102, $rows);
        $this->assertSame('shuttle-last-test', $rows[100]['id']);
        $this->assertSame('valet-test', $rows[101]['id']);
        $this->assertSame([
            ['shuttle-test', 1], ['shuttle-test', 2], ['valet-test', 1],
        ], Http::recorded()->map(fn ($pair) => [$pair[0]['service_id'], (int) $pair[0]['page']])->all());
    }

    public function test_empty_service_selection_does_not_query_all_services(): void
    {
        Http::fake();

        $this->assertSame([], $this->client()->findBookingsByServiceId([]));
        $this->assertSame([], $this->client()->findBookingsByServiceId(['']));
        Http::assertNothingSent();
    }
}
