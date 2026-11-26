<?php

namespace Tests\Feature;

use App\Enums\CarWashType;
use App\Models\Parking;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class CarWashPricingTest extends TestCase
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

    private function parkingData(Parking $parking, array $changes = []): array
    {
        return array_replace($parking->only(['name', 'total_spots', 'capacity_mode', 'is_active']), $changes);
    }

    private function bookingData(string $park = 'B', array $changes = []): array
    {
        return array_replace([
            'parking' => $this->fixtures['parks'][$park]['parking']->public_booking_key,
            'product_code' => 'auto_open',
            'arrival_date' => now()->addDays(30)->toDateString(), 'arrival_time' => '10:00',
            'departure_date' => now()->addDays(32)->toDateString(), 'departure_time' => '10:00',
            'spots' => 2, 'passengers_count' => 4, 'is_valet' => 1,
            'customer_name' => 'Cliente prezzi dimostrativi', 'customer_email' => 'prezzi@example.test',
            'customer_phone' => '0000000000', 'license_plate' => 'TEST-PRICE',
        ], $changes);
    }

    private function savePrices(array $changes): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $this->actingAs($this->fixtures['partner'])
            ->withHeader('X-Parking-Context', (string) $parking->id)
            ->patch(route('parkings.update', $parking), $this->parkingData($parking, $changes))
            ->assertSessionHasNoErrors()->assertRedirect(route('parkings.edit', $parking));
    }

    public function test_admin_can_create_and_update_independent_prices_for_each_parking(): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $prices = ['car_wash_interior_price' => '12.75', 'car_wash_exterior_price' => '18.50', 'car_wash_both_price' => '25.25'];
        $this->savePrices($prices);
        $this->get(route('parkings.edit', $parking))->assertOk()->assertSeeText('Prezzi del lavaggio');
        foreach ($prices as $field => $price) {
            $this->assertSame($price, $parking->fresh()->getAttribute($field));
            $this->get(route('parkings.edit', $parking))->assertSee('name="'.$field.'"', false)->assertSee('value="'.$price.'"', false);
        }
        $other = $this->fixtures['parks']['A']['parking']->fresh();
        $this->assertSame('10.00', $other->car_wash_interior_price);
        $this->assertSame('10.00', $other->car_wash_exterior_price);
        $this->assertSame('20.00', $other->car_wash_both_price);

        $this->get(route('parkings.create'))->assertOk()->assertSee('value="10.00"', false)->assertSee('value="20.00"', false);
        $this->post(route('parkings.store'), $this->parkingData($parking, ['name' => 'Parcheggio prezzi (demo)', ...$prices]))
            ->assertSessionHasNoErrors()->assertRedirect(route('parkings.index'));
        $this->assertDatabaseHas('parkings', ['name' => 'Parcheggio prezzi (demo)', 'management_id' => $parking->management_id, ...$prices]);
    }

    public function test_zero_prices_are_allowed_and_omitted_prices_keep_the_current_settings(): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $this->savePrices(['car_wash_interior_price' => 0, 'car_wash_exterior_price' => '0.00', 'car_wash_both_price' => '0.01']);
        $this->savePrices(['name' => 'Parcheggio aggiornato (demo)']);
        $saved = $parking->fresh();
        $this->assertSame('0.00', $saved->car_wash_interior_price);
        $this->assertSame('0.00', $saved->car_wash_exterior_price);
        $this->assertSame('0.01', $saved->car_wash_both_price);
        $this->postJson(route('public.booking.check'), $this->bookingData(changes: ['car_wash' => 'interior']))
            ->assertOk()->assertJson(['available' => true, 'total_price' => 70, 'car_wash_surcharge' => 0]);
    }

    public static function invalidPrices(): array
    {
        return [
            'negativo' => ['-0.01'],
            'troppi decimali' => ['10.001'],
            'oltre il limite' => ['100000000.00'],
            'testo' => ['prezzo'],
            'vuoto' => [''],
            'nullo' => [null],
            'array' => [['10.00']],
            'esponenziale' => ['1e2'],
        ];
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_prices_are_rejected_on_create_and_update_without_partial_saves(mixed $invalid): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $this->actingAs($this->fixtures['partner'])->withHeader('X-Parking-Context', (string) $parking->id);
        foreach (CarWashType::cases() as $type) {
            $field = $type->priceAttribute();
            $data = $this->parkingData($parking, ['name' => 'Prezzo non valido (demo)', $field => $invalid]);
            $this->patch(route('parkings.update', $parking), $data)->assertSessionHasErrors($field);
            $this->post(route('parkings.store'), $data)->assertSessionHasErrors($field);
        }
        $this->assertDatabaseMissing('parkings', ['name' => 'Prezzo non valido (demo)']);
        $this->assertSame('20.00', $parking->fresh()->car_wash_both_price);
    }

    public function test_staff_and_admins_of_another_parking_cannot_change_prices(): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $data = $this->parkingData($parking, ['car_wash_both_price' => 1]);
        $this->actingAs($this->fixtures['staff'])->withHeader('X-Parking-Context', (string) $parking->id);
        $this->get(route('parkings.edit', $parking))->assertForbidden();
        $this->patch(route('parkings.update', $parking), $data)->assertForbidden();
        $this->post(route('parkings.store'), $data)->assertForbidden();
        $foreign = $this->fixtures['parks']['A']['parking'];
        $this->actingAs($this->fixtures['partner']);
        $this->get(route('parkings.edit', $foreign))->assertNotFound();
        $this->patch(route('parkings.update', $foreign), $this->parkingData($foreign, ['car_wash_both_price' => 1]))->assertNotFound();
        $this->patch(route('parkings.update', $parking), [...$data, '_parking_context' => $foreign->id])->assertStatus(409);
        $this->assertSame('20.00', $parking->fresh()->car_wash_both_price);
        $this->assertSame('20.00', $foreign->fresh()->car_wash_both_price);
    }

    public static function customPrices(): array
    {
        return [
            'interno' => ['interior', '12.75', '9.50', '17.25'],
            'esterno' => ['exterior', '18.50', '16.25', '21.25'],
            'entrambi' => ['both', '25.25', '22.75', '31.50'],
        ];
    }

    public static function parkingPrices(): array
    {
        return array_map(fn (array $prices): array => array_slice($prices, 0, 3), self::customPrices());
    }

    #[DataProvider('parkingPrices')]
    public function test_public_form_quotes_and_saves_use_only_the_selected_parking_prices(string $choice, string $priceB, string $priceA): void
    {
        $type = CarWashType::from($choice);
        foreach (['A' => $priceA, 'B' => $priceB] as $park => $price) {
            $parking = $this->fixtures['parks'][$park]['parking'];
            $parking->update([$type->priceAttribute() => $price]);
            $this->get(route('public.booking.form', ['parking' => $parking->public_booking_key]))
                ->assertOk()->assertSeeText('(+'.number_format((float) $price, 2, ',', '.').' €)');
            $data = $this->bookingData($park, ['car_wash' => $choice, 'customer_email' => 'prezzi-'.strtolower($park).'@example.test']);
            $expected = 70 + (float) $price;
            $this->postJson(route('public.booking.check'), $data)->assertOk()->assertJson([
                'available' => true, 'total_price' => $expected, 'car_wash_surcharge' => (float) $price,
            ]);
            $this->post(route('public.booking.store'), [...$data, 'price' => 1, 'car_wash_surcharge' => 1, $type->priceAttribute() => 1])
                ->assertSessionHasNoErrors()->assertRedirect();
            $reservation = Reservation::where('customer_email', $data['customer_email'])->sole();
            $this->assertSame($parking->id, $reservation->parking_id);
            $this->assertSame($price, $reservation->car_wash_surcharge);
            $this->assertEquals($expected, $reservation->price);
        }
    }

    #[DataProvider('customPrices')]
    public function test_price_changes_preserve_confirmed_and_pending_bookings_and_their_payments(string $choice, string $original, string $priceA, string $newPrice): void
    {
        $type = CarWashType::from($choice);
        $parking = $this->fixtures['parks']['B']['parking'];
        $parking->update([$type->priceAttribute() => $original]);
        $this->fixtures['parks']['A']['parking']->update([$type->priceAttribute() => $priceA]);
        foreach (['pending', 'confirmed'] as $status) {
            $data = $this->bookingData(changes: ['car_wash' => $choice, 'customer_email' => $status.'@example.test']);
            $this->post(route('public.booking.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
            $reservation = Reservation::where('customer_email', $data['customer_email'])->sole();
            if ($status === 'confirmed') {
                $this->post(route('public.booking.onsite.confirm', $reservation->external_id))->assertRedirect();
            }
        }
        $reservationsBefore = DB::table('reservations')->orderBy('id')->get()->toJson();
        $paymentsBefore = DB::table('payments')->orderBy('id')->get()->toJson();
        $this->savePrices([$type->priceAttribute() => $newPrice]);
        $this->assertSame($reservationsBefore, DB::table('reservations')->orderBy('id')->get()->toJson());
        $this->assertSame($paymentsBefore, DB::table('payments')->orderBy('id')->get()->toJson());

        $pending = Reservation::where('customer_email', 'pending@example.test')->sole();
        $expected = 70 + (float) $original;
        $this->get(route('public.booking.payment', $pending->external_id))->assertOk()
            ->assertSeeText('€ '.number_format($expected, 2, ',', '.'))
            ->assertSeeText('Supplemento di '.number_format((float) $original, 2, ',', '.').' € incluso nel totale.');
        $this->post(route('public.booking.onsite.confirm', $pending->external_id))->assertRedirect();
        $this->assertEquals($expected, $pending->payments()->sole()->amount);
        foreach (['pending', 'confirmed'] as $email) {
            $saved = Reservation::where('customer_email', $email.'@example.test')->sole();
            $this->assertSame($original, $saved->car_wash_surcharge);
            $this->assertEquals($expected, $saved->price);
            $this->get(route('public.booking.success', $saved->external_id))->assertOk()->assertSeeText('€ '.number_format($expected, 2, ',', '.'));
        }

        $data = $this->bookingData(changes: ['car_wash' => $choice, 'customer_email' => 'nuovo@example.test']);
        $this->postJson(route('public.booking.check'), $data)->assertOk()->assertJson(['total_price' => 70 + (float) $newPrice, 'car_wash_surcharge' => (float) $newPrice]);
        $this->post(route('public.booking.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($newPrice, Reservation::where('customer_email', 'nuovo@example.test')->sole()->car_wash_surcharge);
    }

    public function test_migration_initializes_existing_parkings_without_changing_reservations_or_payments(): void
    {
        $migration = require database_path('migrations/2026_11_26_000001_add_car_wash_prices_to_parkings.php');
        $reservations = DB::table('reservations')->orderBy('id')->get()->toJson();
        $payments = DB::table('payments')->orderBy('id')->get()->toJson();
        $migration->down();
        $parkings = DB::table('parkings')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $migration->up();
        foreach ($parkings as $before) {
            $saved = DB::table('parkings')->find($before['id']);
            $this->assertEquals(10, $saved->car_wash_interior_price);
            $this->assertEquals(10, $saved->car_wash_exterior_price);
            $this->assertEquals(20, $saved->car_wash_both_price);
            foreach ($before as $field => $value) {
                $this->assertSame($value, $saved->$field);
            }
        }
        $this->assertSame($reservations, DB::table('reservations')->orderBy('id')->get()->toJson());
        $this->assertSame($payments, DB::table('payments')->orderBy('id')->get()->toJson());
    }
}
