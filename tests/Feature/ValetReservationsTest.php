<?php

namespace Tests\Feature;

use App\Exports\ReservationsExport;
use App\Integrations\Adapters\ParkosAdapter;
use App\Integrations\ReservationImportPayloadFactory;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class ValetReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->fixtures = ManagementFixtures::create();
    }

    private function booking(string $parking, array $attributes = []): Reservation
    {
        $park = $this->fixtures['parks'][$parking];
        return Reservation::create(array_merge([
            'parking_id' => $park['parking']->id,
            'parking_product_id' => $park['product']->id,
            'parking_listing_id' => $park['apiListing']->id,
            'customer_name' => 'Prenotazione filtro (test)',
            'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(11),
            'spots' => 1, 'status' => 'confirmed', 'price' => 20,
        ], $attributes));
    }

    public function test_valet_filter_excludes_other_parkings_ordinary_and_unknown_bookings(): void
    {
        $this->booking('A', ['is_valet' => true, 'customer_name' => 'Valet privato A (test)']);
        $valet = $this->booking('B', ['is_valet' => true]);
        $ordinary = $this->booking('B', ['is_valet' => false]);
        $unknown = $this->booking('B');
        $this->actingAs($this->fixtures['partner']);

        $this->get(route('reservations.index', ['only_valet' => 1, 'parking_id' => $this->fixtures['parks']['A']['parking']->id]))
            ->assertOk()->assertSee('Solo valet')->assertDontSee('Valet privato A (test)')
            ->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->all() === [$valet->id]);
        $this->get(route('reservations.index', ['only_valet' => 0]))->assertOk()
            ->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->contains($ordinary->id) && $rows->pluck('id')->contains($unknown->id));
    }

    public function test_daily_calendar_and_dashboard_mark_only_valet_reservations(): void
    {
        $arrival = now()->addDays(10)->startOfDay();
        $departure = $arrival->copy()->addDays(2);
        foreach ([false, true, null] as $index => $isValet) {
            $this->booking('B', [
                'customer_name' => "Cliente calendario {$index}", 'is_valet' => $isValet,
                'starts_at' => $arrival->copy()->setHour(10 + $index),
                'ends_at' => $departure->copy()->setHour(10 + $index),
            ]);
        }

        $this->actingAs($this->fixtures['partner']);
        $pages = [
            route('calendar.day', ['type' => 'entries', 'date' => $arrival->toDateString()]),
            route('calendar.day', ['type' => 'exits', 'date' => $departure->toDateString()]),
            route('dashboard'),
        ];
        foreach ($pages as $page) {
            $response = $this->get($page);
            $response->assertOk()->assertSeeTextInOrder(['Cliente calendario 0', 'Cliente calendario 1', 'Car valet', 'Cliente calendario 2']);
            $this->assertSame(1, substr_count($response->getContent(), 'Car valet'));
        }
    }

    public function test_entry_summary_counts_valet_arrivals_for_the_day_and_active_parking(): void
    {
        $arrival = now()->addWeek()->startOfDay();
        $stay = ['starts_at' => $arrival, 'ends_at' => $arrival->copy()->addDays(2)];
        $this->booking('B', $stay + ['is_valet' => true, 'spots' => 2, 'passengers_count' => 3]);
        $this->booking('B', $stay + ['is_valet' => true]);
        $this->booking('B', $stay + ['is_valet' => false]);
        $this->booking('B', $stay);
        $this->booking('B', $stay + ['is_valet' => true, 'status' => 'cancelled']);
        $this->booking('A', $stay + ['is_valet' => true]);

        $this->actingAs($this->fixtures['partner']);
        $this->get(route('calendar.day', ['type' => 'entries', 'date' => $arrival->toDateString()]))
            ->assertOk()->assertViewHas('reservationsCount', 4)->assertSee('calendar-valet-subtotal')
            ->assertSeeTextInOrder(['di cui 2 con car valet', 'Clienti in entrata'])->assertDontSeeText('Macchine in entrata car valet');
        $this->get(route('calendar.day', ['type' => 'entries', 'date' => $arrival->copy()->addDay()->toDateString()]))
            ->assertOk()->assertViewHas('reservationsCount', 0)->assertDontSee('calendar-valet-subtotal')
            ->assertSeeText('di cui 0 con car valet')->assertDontSeeText('Macchine in entrata car valet');
        $this->get(route('calendar.day', ['type' => 'exits', 'date' => $stay['ends_at']->toDateString()]))
            ->assertOk()->assertDontSeeText('con car valet');
    }

    public function test_valet_filter_combines_with_search_dates_status_and_platform(): void
    {
        $park = $this->fixtures['parks']['B'];
        $target = $this->booking('B', ['is_valet' => true, 'customer_name' => 'Cliente cercato (test)']);
        $this->booking('B', ['is_valet' => true, 'status' => 'cancelled', 'customer_name' => 'Cliente cercato (test)']);
        $this->booking('B', ['is_valet' => true, 'customer_name' => 'Altro cliente (test)']);
        $this->booking('B', ['is_valet' => false, 'customer_name' => 'Cliente cercato (test)']);
        $this->booking('B', ['is_valet' => true, 'customer_name' => 'Cliente cercato (test)', 'starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(21)]);

        $this->actingAs($this->fixtures['partner'])->get(route('reservations.index', [
            'only_valet' => 1, 'search' => 'Cliente cercato', 'status' => 'confirmed',
            'platform_id' => $park['apiPlatform']->id, 'date_from' => now()->addDays(9)->toDateString(),
            'date_to' => now()->addDays(12)->toDateString(),
        ]))->assertOk()->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->all() === [$target->id]);
    }

    public function test_pagination_and_excel_preserve_the_valet_filter(): void
    {
        $this->freezeTime();
        $ids = [];
        for ($i = 0; $i < 21; $i++) {
            $ids[] = $this->booking('B', ['is_valet' => true])->id;
        }
        $this->booking('B', ['is_valet' => false]);
        $this->booking('A', ['is_valet' => true]);
        $this->actingAs($this->fixtures['partner']);
        $this->get(route('reservations.index', ['only_valet' => 1, 'page' => 2]))->assertOk()
            ->assertViewHas('reservations', fn ($rows) => $rows->total() === 21 && $rows->count() === 1 && str_contains($rows->url(1), 'only_valet=1'));

        Excel::fake();
        $this->get(route('reservations.export', ['only_valet' => 1]))->assertOk();
        Excel::assertDownloaded('prenotazioni_'.now()->format('Y-m-d_His').'.xlsx', function (ReservationsExport $export) use ($ids) {
            $this->assertEqualsCanonicalizing($ids, $export->query()->pluck('id')->all());
            return true;
        });
    }

    public function test_import_persists_valet_and_manual_edits_do_not_erase_it(): void
    {
        config(['services.parkos.fixture_mode' => true, 'services.parkos.fixture_file' => 'unmapped_product.json']);
        $park = $this->fixtures['parks']['B'];
        $dto = app(ParkosAdapter::class)->fetchReservations($park['apiListing'], now(), now())[0];
        $payload = app(ReservationImportPayloadFactory::class)->makePayload($dto, $park['product']);
        $service = app(ReservationService::class);
        $result = $service->importFromExternal($park['apiListing'], $payload);
        $this->assertTrue($result->isSuccess());
        $reservation = $result->reservation;
        $this->assertTrue($reservation->is_valet);
        $this->assertEquals($dto->raw_data, $reservation->raw_data);

        $edited = $service->update($reservation, ['notes' => 'Nota operativa (test)']);
        $this->assertTrue($edited->success);
        $this->assertTrue($reservation->fresh()->is_valet);

        $payload['is_valet'] = null;
        $this->assertTrue($service->importFromExternal($park['apiListing'], $payload)->isSuccess());
        $this->assertNull($reservation->fresh()->is_valet);

        $payload['is_valet'] = false;
        $this->assertTrue($service->importFromExternal($park['apiListing'], $payload)->isSuccess());
        $this->assertFalse($reservation->fresh()->is_valet);
        $this->assertSame(1, Reservation::where('parking_listing_id', $park['apiListing']->id)->where('external_id', $dto->external_id)->count());

        $cancellation = app(ReservationImportPayloadFactory::class)->makeCancellationPayload($dto);
        $this->assertTrue($service->importFromExternal($park['apiListing'], $cancellation)->isSuccess());
        $this->assertTrue($reservation->fresh()->is_valet);
        $this->assertSame('cancelled', $reservation->fresh()->status->value);
    }

    public function test_migration_classifies_saved_payloads_without_changing_other_booking_data(): void
    {
        $valet = $this->booking('B', ['raw_data' => ['parking_type' => 'valet'], 'notes' => 'Nota da conservare (test)']);
        $ordinary = $this->booking('B', ['raw_data' => ['parking_type' => 'shuttle']]);
        $unknown = $this->booking('B', ['raw_data' => ['notes' => 'valet']]);
        $stamp = $valet->getRawOriginal('updated_at');
        $migration = require database_path('migrations/2026_10_26_000001_add_is_valet_to_reservations.php');
        $migration->down();
        $migration->up();

        $this->assertTrue($valet->fresh()->is_valet);
        $this->assertFalse($ordinary->fresh()->is_valet);
        $this->assertNull($unknown->fresh()->is_valet);
        $this->assertSame($stamp, $valet->fresh()->getRawOriginal('updated_at'));
        $this->assertSame('Nota da conservare (test)', $valet->fresh()->notes);
        $this->assertSame('20.00', $valet->fresh()->price);
        Http::assertNothingSent();
    }

    public function test_backfill_is_scoped_supports_dry_run_and_leaves_verified_values_unchanged(): void
    {
        $a = $this->booking('A', ['raw_data' => ['parking_type' => 'valet']]);
        $b = $this->booking('B', ['raw_data' => ['parking_type' => 'valet']]);
        $verified = $this->booking('B', ['is_valet' => false, 'raw_data' => ['parking_type' => 'valet']]);
        $options = ['parking' => $this->fixtures['parks']['B']['parking']->id];
        $stamp = $b->getRawOriginal('updated_at');

        $this->artisan('reservations:backfill-valet', $options + ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($b->fresh()->is_valet);
        $this->artisan('reservations:backfill-valet', $options)->assertSuccessful();
        $this->assertNull($a->fresh()->is_valet);
        $this->assertTrue($b->fresh()->is_valet);
        $this->assertFalse($verified->fresh()->is_valet);
        $this->assertSame($stamp, $b->fresh()->getRawOriginal('updated_at'));
        Http::assertNothingSent();
    }

    public function test_roosh_backfill_uses_the_selected_management_credentials_and_catalog(): void
    {
        $platform = $this->fixtures['parks']['B']['apiPlatform'];
        $platform->forceFill(['slug' => 'vologio', 'uses_legacy_credentials' => false, 'connection_settings' => [
            'base_url' => 'https://roosh-catalog.test/provider/v1', 'api_key' => 'synthetic-key-b', 'client_id' => 'synthetic-client-b',
        ]])->save();
        $b = $this->booking('B', ['raw_data' => ['service_id' => 'valet-b-test', 'service_location_id' => 'location-b-test']]);
        Http::fake(['https://roosh-catalog.test/provider/v1/service-locations/' => Http::response(['serviceLocations' => [[
            'id' => 'location-b-test', 'parking_type_name' => 'Valet',
            'services' => [['id' => 'valet-b-test', 'enabled' => false]],
        ]]])]);

        $this->artisan('reservations:backfill-valet', ['parking' => $this->fixtures['parks']['B']['parking']->id, '--with-catalog' => true])->assertSuccessful();
        $this->assertTrue($b->fresh()->is_valet);
        Http::assertSent(fn ($request) => $request->hasHeader('X-ROOSH-API-KEY', 'synthetic-key-b') && $request->hasHeader('X-ROOSH-CLIENT-ID', 'synthetic-client-b'));
    }
}
