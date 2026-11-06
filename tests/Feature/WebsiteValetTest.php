<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class WebsiteValetTest extends TestCase
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

    private function publicData(): array
    {
        return [
            'parking' => $this->fixtures['parks']['B']['parking']->public_booking_key,
            'product_code' => 'auto_open',
            'arrival_date' => now()->addDays(60)->toDateString(), 'arrival_time' => '10:00',
            'departure_date' => now()->addDays(62)->toDateString(), 'departure_time' => '10:00',
            'spots' => 1, 'passengers_count' => 1,
            'customer_name' => 'Scelta servizio (test)', 'customer_email' => 'servizio@example.test',
            'customer_phone' => '0000000000', 'license_plate' => 'TEST-VALET',
        ];
    }

    private function manualData(): array
    {
        $park = $this->fixtures['parks']['B'];

        return [
            'parking_listing_id' => $park['listing']->id, 'parking_product_id' => $park['product']->id,
            'customer_name' => 'Servizio manuale (test)',
            'starts_at' => now()->addDays(60)->format('Y-m-d H:i'),
            'ends_at' => now()->addDays(62)->format('Y-m-d H:i'),
            'spots' => 1, 'passengers_count' => 1, 'price' => 30, 'status' => 'confirmed',
        ];
    }

    public function test_public_valet_surcharge_is_quoted_saved_and_charged_once(): void
    {
        $park = $this->fixtures['parks']['B'];
        $park['parking']->update(['total_spots' => 2]);
        $park['product']->update(['capacity' => 2]);
        $data = $this->publicData();
        $quote = $this->postJson(route('public.booking.check'), $data)->assertOk()->assertJson(['available' => true])->json('total_price');
        $valetId = null;

        foreach (['0' => 'Parcheggio standard', '1' => 'Car valet'] as $choice => $label) {
            $expectedPrice = $quote + ($choice === 1 ? 10 : 0);
            $this->postJson(route('public.booking.check'), $data + ['is_valet' => (string) $choice])
                ->assertOk()->assertJson(['available' => true, 'total_price' => $expectedPrice]);
            $response = $this->post(route('public.booking.store'), $data + ['is_valet' => (string) $choice]);
            $response->assertSessionHasNoErrors();
            $reservation = Reservation::where('customer_email', $data['customer_email'])->latest('id')->firstOrFail();
            $response->assertRedirect(route('public.booking.payment', $reservation->external_id));
            $this->assertSame((bool) $choice, $reservation->is_valet);
            $this->assertSame($park['parking']->id, $reservation->parking_id);
            $this->assertEquals($expectedPrice, $reservation->price);
            $this->assertSame(1, $reservation->spots);
            $this->get(route('public.booking.payment', $reservation->external_id))->assertOk()->assertSee($label);
            $this->post(route('public.booking.onsite.confirm', $reservation->external_id))
                ->assertRedirect(route('public.booking.success', $reservation->external_id));
            $this->assertEquals($expectedPrice, $reservation->payments()->sole()->amount);
            $this->assertEquals($expectedPrice, $reservation->fresh()->price);
            $this->get(route('public.booking.success', $reservation->external_id))->assertOk()->assertSee($label);
            if ($choice === 1) {
                $valetId = $reservation->id;
            }
        }

        $this->post(route('public.booking.store'), $data + ['is_valet' => '1'])->assertSessionHas('error');
        $this->assertSame(2, Reservation::where('customer_email', $data['customer_email'])->count());
        $this->actingAs($this->fixtures['partner'])->get(route('reservations.index', ['only_valet' => 1]))
            ->assertOk()->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->all() === [$valetId]);
    }

    public function test_public_surcharge_is_fixed_for_multiple_days_and_spots_and_cannot_be_overridden(): void
    {
        $data = array_replace($this->publicData(), ['spots' => 2, 'passengers_count' => 4]);
        $this->postJson(route('public.booking.check'), $data + ['is_valet' => '0'])
            ->assertOk()->assertJson(['total_price' => 60, 'valet_surcharge' => 0]);
        $this->postJson(route('public.booking.check'), $data + ['is_valet' => '1'])
            ->assertOk()->assertJson(['total_price' => 70, 'valet_surcharge' => 10]);
        $this->post(route('public.booking.store'), $data + ['is_valet' => '1', 'price' => 1, 'valet_surcharge' => 0])
            ->assertSessionHasNoErrors();
        $reservation = Reservation::where('customer_email', $data['customer_email'])->firstOrFail();
        $this->assertSame('70.00', $reservation->price);
        $this->assertEquals(10, $reservation->raw_data['valet_surcharge']);
        $this->assertSame(7000, app(\App\Services\PricingService::class)->amountForReservation($reservation));
        $this->get(route('public.booking.payment', $reservation->external_id))->assertOk()
            ->assertSeeText('Supplemento Car valet di 10,00 € incluso nel totale.');
        $this->get(route('public.booking.payment', $reservation->external_id))->assertOk();
        $this->assertSame('70.00', $reservation->fresh()->price);
        $this->post(route('public.booking.onsite.confirm', $reservation->external_id))->assertRedirect();
        $this->assertEquals(70, $reservation->payments()->sole()->amount);
        $this->get(route('public.booking.success', $reservation->external_id))->assertOk()
            ->assertSeeText('Supplemento Car valet di 10,00 € incluso nel totale.');
    }

    public static function invalidChoices(): array
    {
        return [['valet'], [['1']]];
    }

    public function test_loading_an_existing_valet_booking_uses_its_saved_total(): void
    {
        $reservation = $this->fixtures['parks']['B']['reservation'];
        $reservation->update([
            'starts_at' => now()->addDays(60), 'ends_at' => now()->addDays(62),
            'is_valet' => true, 'status' => 'pending', 'expires_at' => now()->addMinutes(15),
            'raw_data' => ['source' => 'website'],
        ]);
        $this->get(route('public.booking.payment', $reservation->external_id))->assertOk()
            ->assertSeeText('€ 30,00')->assertDontSeeText('Supplemento Car valet');
        $this->assertSame('30.00', $reservation->fresh()->price);
        $this->assertSame(3000, app(\App\Services\PricingService::class)->amountForReservation($reservation));
    }

    #[DataProvider('invalidChoices')]
    public function test_public_invalid_choice_is_rejected_and_the_form_can_render_validation_errors(mixed $invalid): void
    {
        $this->postJson(route('public.booking.check'), $this->publicData() + ['is_valet' => $invalid])
            ->assertUnprocessable()->assertJsonValidationErrors('is_valet');
        $form = route('public.booking.form', ['parking' => $this->publicData()['parking']]);
        $this->from($form)->post(route('public.booking.store'), $this->publicData() + ['is_valet' => $invalid])
            ->assertRedirect($form)->assertSessionHasErrors(['is_valet' => 'Seleziona Parcheggio standard oppure Car valet.']);
        /** Carry the session cookie across the redirect as a browser does. */
        $response = $this->withCookie(session()->getName(), session()->getId())->get($form)->assertOk();
        $this->assertTrue(str_contains($response->getContent(), 'Seleziona Parcheggio standard oppure Car valet.'), 'Il modulo deve mostrare il messaggio di validazione.');
        $this->assertDatabaseMissing('reservations', ['customer_email' => 'servizio@example.test']);
    }

    public function test_manual_choice_can_be_created_changed_and_cannot_target_another_parking(): void
    {
        $park = $this->fixtures['parks']['B'];
        $this->actingAs($this->fixtures['partner'])->withHeader('X-Parking-Context', (string) $park['parking']->id);
        $data = $this->manualData();
        $this->post(route('reservations.store'), $data + ['is_valet' => '1'])->assertRedirect(route('reservations.index'));
        $reservation = Reservation::where('customer_name', $data['customer_name'])->firstOrFail();
        $this->assertTrue($reservation->is_valet);
        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee('Car valet');
        $this->put(route('reservations.update', $reservation), $data + ['is_valet' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($reservation->fresh()->is_valet);
        $this->assertSame('30.00', $reservation->fresh()->price);

        $other = $this->fixtures['parks']['A']['reservation'];
        $this->put(route('reservations.update', $other), $data + ['is_valet' => '1'])->assertNotFound();
        $this->assertNull($other->fresh()->is_valet);
    }

    public function test_editing_unclassified_history_preserves_unknown_and_rejects_invalid_choices(): void
    {
        $park = $this->fixtures['parks']['B'];
        $reservation = $park['reservation'];
        $this->actingAs($this->fixtures['partner'])->withHeader('X-Parking-Context', (string) $park['parking']->id);
        $data = $this->manualData();
        $this->put(route('reservations.update', $reservation), $data + ['is_valet' => ''])->assertSessionHasNoErrors();
        $this->assertNull($reservation->fresh()->is_valet);
        $this->put(route('reservations.update', $reservation), $data)->assertSessionHasNoErrors();
        $this->assertNull($reservation->fresh()->is_valet);

        $edit = route('reservations.edit', $reservation);
        $this->from($edit)->put(route('reservations.update', $reservation), $data + ['is_valet' => ['1']])->assertSessionHasErrors('is_valet');
        $this->get($edit)->assertOk();
        $this->assertNull($reservation->fresh()->is_valet);
        $create = route('reservations.create');
        $this->from($create)->post(route('reservations.store'), $data + ['is_valet' => 'invalid'])->assertSessionHasErrors('is_valet');
        $this->get($create)->assertOk();
    }
}
