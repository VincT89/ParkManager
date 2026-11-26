<?php

namespace Tests\Feature;

use App\Enums\CarWashType;
use App\Exports\ReservationsExport;
use App\Models\Reservation;
use App\Services\PricingService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class CarWashBookingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $this->fixtures = ManagementFixtures::create();
    }

    private function bookingData(array $changes = []): array
    {
        return array_replace([
            'parking' => $this->fixtures['parks']['B']['parking']->public_booking_key,
            'product_code' => 'auto_open',
            'arrival_date' => now()->addDays(30)->toDateString(), 'arrival_time' => '10:00',
            'departure_date' => now()->addDays(32)->toDateString(), 'departure_time' => '10:00',
            'spots' => 2, 'passengers_count' => 4,
            'customer_name' => 'Cliente lavaggio dimostrativo', 'customer_email' => 'lavaggio@example.test',
            'customer_phone' => '0000000000', 'license_plate' => 'TEST-WASH',
        ], $changes);
    }

    public static function choices(): array
    {
        return [
            'standard' => [null, 0, false],
            'interno' => ['interior', 10, false],
            'esterno' => ['exterior', 10, false],
            'entrambi' => ['both', 20, false],
            'valet' => [null, 0, true],
            'valet interno' => ['interior', 10, true],
            'valet esterno' => ['exterior', 10, true],
            'valet entrambi' => ['both', 20, true],
        ];
    }

    #[DataProvider('choices')]
    public function test_quote_save_and_onsite_payment_charge_the_selected_wash_only_once(?string $choice, int $surcharge, bool $valet): void
    {
        $data = $this->bookingData(['car_wash' => $choice, 'is_valet' => (int) $valet]);
        $expected = 60 + $surcharge + ($valet ? 10 : 0);

        $this->postJson(route('public.booking.check'), $data)->assertOk()->assertJson([
            'available' => true, 'total_price' => $expected,
            'car_wash_surcharge' => $surcharge, 'valet_surcharge' => $valet ? 10 : 0,
        ]);
        $this->post(route('public.booking.store'), $data + ['price' => 1, 'car_wash_surcharge' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();

        $reservation = Reservation::where('customer_email', $data['customer_email'])->sole();
        $this->assertSame($choice, $reservation->car_wash?->value);
        $this->assertEquals($surcharge, $reservation->car_wash_surcharge);
        $this->assertEquals($expected, $reservation->price);
        $this->assertSame(2, $reservation->spots);
        $this->assertSame(4, $reservation->passengers_count);
        $this->assertSame($this->fixtures['parks']['B']['parking']->id, $reservation->parking_id);
        $this->assertSame($expected * 100, app(PricingService::class)->amountForReservation($reservation));

        $paymentUrl = route('public.booking.payment', $reservation->external_id);
        $label = CarWashType::tryFrom($choice ?? '')?->label();
        foreach (range(1, 2) as $reload) {
            $response = $this->get($paymentUrl)->assertOk()->assertSeeText('€ '.number_format($expected, 2, ',', '.'));
            if ($label) {
                $response->assertSeeText($label)->assertSeeText('Supplemento di '.number_format($surcharge, 2, ',', '.').' € incluso nel totale.');
            } else {
                $response->assertDontSeeText('Lavaggio interno')->assertDontSeeText('Lavaggio esterno');
            }
        }
        $this->post(route('public.booking.onsite.confirm', $reservation->external_id))
            ->assertRedirect(route('public.booking.success', $reservation->external_id));
        $this->assertEquals($expected, $reservation->payments()->sole()->amount);
        $this->assertEquals($expected, $reservation->fresh()->price);
        $success = $this->get(route('public.booking.success', $reservation->external_id))->assertOk();
        if ($label) {
            $success->assertSeeText($label);
        }
    }

    public function test_missing_and_empty_options_keep_existing_pricing_and_invalid_choices_are_rejected(): void
    {
        foreach ([[], ['car_wash' => '']] as $choice) {
            $this->postJson(route('public.booking.check'), $this->bookingData($choice))
                ->assertOk()->assertJson(['total_price' => 60, 'car_wash_surcharge' => 0]);
        }

        $form = route('public.booking.form', ['parking' => $this->bookingData()['parking']]);
        foreach (['invalid', 'interior,exterior', ['both']] as $choice) {
            $data = $this->bookingData(['car_wash' => $choice]);
            $this->postJson(route('public.booking.check'), $data)->assertUnprocessable()->assertJsonValidationErrors('car_wash');
            $this->from($form)->post(route('public.booking.store'), $data)->assertRedirect($form)->assertSessionHasErrors('car_wash');
        }
        $this->withCookie(session()->getName(), session()->getId())->get($form)
            ->assertOk()->assertSeeText('Seleziona un’opzione di lavaggio valida.');
        $this->assertDatabaseMissing('reservations', ['customer_email' => 'lavaggio@example.test']);
    }

    private function reservation(string $park = 'B', ?CarWashType $wash = null, array $changes = []): Reservation
    {
        $fixture = $this->fixtures['parks'][$park];
        $surcharge = $wash ? $fixture['parking']->carWashPrice($wash) : 0;

        return Reservation::create(array_replace([
            'parking_id' => $fixture['parking']->id,
            'parking_listing_id' => $fixture['listing']->id,
            'parking_product_id' => $fixture['product']->id,
            'customer_name' => 'Lavaggio dimostrativo',
            'starts_at' => now()->subDay(), 'ends_at' => now()->setTime(18, 0),
            'status' => 'confirmed', 'spots' => 1, 'price' => 30 + $surcharge,
            'car_wash' => $wash, 'car_wash_surcharge' => $surcharge,
        ], $changes));
    }

    public function test_labels_identify_the_service_in_reservations_departures_and_dashboard_without_leaking_other_parks(): void
    {
        $this->freezeTime();
        foreach (CarWashType::cases() as $wash) {
            $this->reservation(wash: $wash, changes: ['is_valet' => $wash === CarWashType::Interior]);
        }
        $this->reservation('A', CarWashType::Both, ['customer_name' => 'Lavaggio riservato gestione A']);
        $this->reservation(changes: ['customer_name' => 'Cliente senza extra']);
        $this->actingAs($this->fixtures['partner']);

        foreach ([
            route('reservations.index'),
            route('calendar.day', ['type' => 'exits', 'date' => now()->toDateString()]),
            route('dashboard'),
        ] as $page) {
            $response = $this->get($page)->assertOk()->assertDontSeeText('Lavaggio riservato gestione A');
            foreach (CarWashType::cases() as $wash) {
                $response->assertSeeText($wash->label());
            }
            $this->assertSame(3, substr_count($response->getContent(), 'pm-car-wash-label'));
        }
    }

    public function test_filter_combines_with_valet_status_dates_and_platform_and_is_kept_in_excel(): void
    {
        $this->freezeTime();
        $target = $this->reservation(wash: CarWashType::Both, changes: ['is_valet' => true]);
        $this->reservation(wash: CarWashType::Both, changes: ['status' => 'cancelled', 'is_valet' => true]);
        $this->reservation(wash: CarWashType::Interior, changes: ['is_valet' => false]);
        $this->reservation(changes: ['is_valet' => true]);
        $foreign = $this->reservation('A', CarWashType::Both, ['is_valet' => true]);
        $filters = [
            'with_car_wash' => 1, 'only_valet' => 1, 'status' => 'confirmed',
            'date_filter_type' => 'ends_at', 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString(),
            'platform_id' => $this->fixtures['parks']['B']['platform']->id,
        ];
        $this->actingAs($this->fixtures['partner']);
        $this->get(route('reservations.index', $filters))->assertOk()->assertSeeText('Con lavaggio')
            ->assertViewHas('reservations', fn ($rows) => $rows->modelKeys() === [$target->id]);
        Excel::fake();
        $this->get(route('reservations.export', $filters))->assertOk();
        Excel::assertDownloaded('prenotazioni_'.now()->format('Y-m-d_His').'.xlsx', function (ReservationsExport $export) use ($target): bool {
            $this->assertSame([$target->id], $export->query()->pluck('id')->all());
            $this->assertSame('Lavaggio', collect($export->headings())->last());
            $this->assertSame(CarWashType::Both->label(), collect($export->map($target))->last());

            return true;
        });
        $this->get(route('reservations.show', $foreign))->assertNotFound();
    }

    public function test_wash_filter_keeps_pagination_and_includes_all_three_choices(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $this->reservation(wash: CarWashType::cases()[$i % 3]);
        }
        $this->reservation();
        $this->reservation('A', CarWashType::Both);
        $this->actingAs($this->fixtures['partner']);
        $this->get(route('reservations.index', ['with_car_wash' => 1, 'page' => 2]))->assertOk()
            ->assertViewHas('reservations', fn ($rows) => $rows->total() === 21 && $rows->count() === 1 && str_contains($rows->url(1), 'with_car_wash=1'));
    }

    public function test_ordinary_edits_keep_the_chosen_wash_and_its_saved_total(): void
    {
        $reservation = $this->reservation(wash: CarWashType::Both);
        $this->actingAs($this->fixtures['partner'])->withHeader('X-Parking-Context', (string) $reservation->parking_id);
        $this->put(route('reservations.update', $reservation), [
            ...$reservation->only(['parking_product_id', 'customer_name', 'customer_email', 'customer_phone', 'spots', 'price']),
            'starts_at' => $reservation->starts_at->format('Y-m-d\TH:i'),
            'ends_at' => $reservation->ends_at->format('Y-m-d\TH:i'),
            'status' => 'confirmed', 'passengers_count' => 1, 'flight_reference' => 'AZ1602',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $saved = $reservation->fresh();
        $this->assertSame(CarWashType::Both, $saved->car_wash);
        $this->assertSame('20.00', $saved->car_wash_surcharge);
        $this->assertSame('50.00', $saved->price);
        $this->get(route('reservations.edit', $saved))->assertOk()->assertSeeText('Lavaggio prenotato')->assertSeeText(CarWashType::Both->label());
        $this->get(route('reservations.show', $saved))->assertOk()->assertSeeText(CarWashType::Both->label());
    }

    public function test_legacy_bookings_and_platform_imports_keep_their_prices_and_services(): void
    {
        $legacy = $this->fixtures['parks']['B']['reservation'];
        $legacy->update(['status' => 'pending', 'expires_at' => now()->addMinutes(15)]);
        $this->get(route('public.booking.payment', $legacy->external_id))->assertOk()->assertSeeText('€ 30,00')->assertDontSeeText('Lavaggio interno');
        $this->assertNull($legacy->fresh()->car_wash);
        $this->assertSame('0.00', $legacy->fresh()->car_wash_surcharge);
        $park = $this->fixtures['parks']['B'];
        $result = app(ReservationService::class)->importFromExternal($park['apiListing'], [
            'external_id' => 'DEMO-IMPORT-WASH', 'parking_product_id' => $park['product']->id,
            'customer_name' => 'Importazione dimostrativa', 'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(12), 'spots' => 1, 'price' => 55,
            'notes' => 'lavaggio interno esterno', 'raw_data' => ['wash' => 'both'],
        ]);
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->reservation->car_wash);
        $this->assertSame('55.00', $result->reservation->price);
    }
}
