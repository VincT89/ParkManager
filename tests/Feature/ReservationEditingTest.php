<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Integrations\DTO\NormalizedReservation;
use App\Integrations\ReservationImportPayloadFactory;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Tenancy\ParkingContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class ReservationEditingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private array $fixtures;
    private array $park;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $this->fixtures = ManagementFixtures::create();
        $this->park = $this->fixtures['parks']['B'];
        $this->reservation = $this->park['reservation'];
        $this->reservation->update([
            'parking_listing_id' => $this->park['apiListing']->id,
            'starts_at' => now()->addDays(10)->setTime(10, 0),
            'ends_at' => now()->addDays(12)->setTime(10, 0),
            'notes' => 'Nota originale della piattaforma',
        ]);
        $this->actingAs($this->fixtures['partner']);
    }

    private function formData(array $changes = []): array
    {
        $reservation = $this->reservation->fresh();

        return array_replace([
            ...$reservation->only(['parking_product_id', 'customer_name', 'customer_email', 'customer_phone', 'license_plate', 'flight_reference', 'spots', 'passengers_count', 'price', 'notes', 'is_valet']),
            '_parking_context' => $this->park['parking']->id,
            'starts_at' => $reservation->starts_at->format('Y-m-d\TH:i'),
            'ends_at' => $reservation->ends_at->format('Y-m-d\TH:i'),
            'status' => $reservation->status->value,
        ], $changes);
    }

    private function providerPayload(array $changes = []): array
    {
        $dto = new NormalizedReservation(
            external_id: $this->reservation->external_id,
            external_product_ref: 'same-product-code',
            external_product_name: null,
            customer_name: 'Nome inviato dalla piattaforma',
            customer_email: 'aggiornato@example.test',
            customer_phone: '0000000001',
            license_plate: 'API-TEST',
            starts_at: $this->reservation->starts_at,
            ends_at: $this->reservation->ends_at,
            price: 45,
            notes: null,
            raw_data: ['source' => 'provider fixture'],
            status: 'confirmed',
            flight_reference: null,
            passengers_count: 3,
            is_valet: true,
        );

        return array_replace(app(ReservationImportPayloadFactory::class)->makePayload($dto, $this->park['product']), $changes);
    }

    private function import(array $changes = []): Reservation
    {
        $result = app(ParkingContext::class)->run($this->park['parking'], fn () => app(ReservationService::class)
            ->importFromExternal($this->park['apiListing'], $this->providerPayload($changes)));
        $this->assertTrue($result->isSuccess(), $result->error ?? 'Importazione fallita');

        return $result->reservation;
    }

    public function test_manual_corrections_survive_repeated_imports_while_other_fields_update(): void
    {
        $this->put(route('reservations.update', $this->reservation), $this->formData([
            'customer_name' => 'Nome corretto in struttura',
            'customer_phone' => '0000000002',
            'license_plate' => 'MANUALE',
            'flight_reference' => 'az1602',
            'passengers_count' => 2,
            'notes' => 'Nota inserita in struttura',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('reservations.index'));

        foreach ([null, 'FR999'] as $providerFlight) {
            $reservation = $this->import(['flight_reference' => $providerFlight]);
            $this->assertSame('Nome corretto in struttura', $reservation->customer_name);
            $this->assertSame('0000000002', $reservation->customer_phone);
            $this->assertSame('MANUALE', $reservation->license_plate);
            $this->assertSame('AZ1602', $reservation->flight_reference);
            $this->assertSame(2, $reservation->passengers_count);
            $this->assertSame('Nota inserita in struttura', $reservation->notes);
            $this->assertSame('aggiornato@example.test', $reservation->customer_email);
            $this->assertSame('45.00', $reservation->price);
            $this->assertTrue($reservation->is_valet);
            $this->assertSame(['source' => 'provider fixture'], $reservation->raw_data);
        }

        $this->get(route('reservations.edit', $reservation))->assertOk()->assertSee('AZ1602')->assertSee('Nota inserita in struttura');
        $this->assertEqualsCanonicalizing(['customer_name', 'customer_phone', 'license_plate', 'flight_reference', 'passengers_count', 'notes'], $reservation->manually_edited_fields);
    }

    public function test_cleared_values_and_later_manual_corrections_remain_authoritative(): void
    {
        $this->reservation->update(['flight_reference' => 'FR123']);
        $this->put(route('reservations.update', $this->reservation), $this->formData([
            'flight_reference' => '', 'notes' => '', 'customer_email' => '',
        ]))->assertSessionHasNoErrors();
        $reservation = $this->import(['flight_reference' => 'FR456', 'notes' => 'Nota piattaforma']);
        $this->assertNull($reservation->flight_reference);
        $this->assertNull($reservation->notes);
        $this->assertNull($reservation->customer_email);

        $this->put(route('reservations.update', $this->reservation), $this->formData(['flight_reference' => 'fr789']))->assertSessionHasNoErrors();
        $this->assertSame('FR789', $this->import()->flight_reference);
        $reservation = $this->import(['status' => 'cancelled']);
        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
        $this->assertSame('FR789', $reservation->flight_reference);
        $this->assertNull($reservation->notes);
    }

    public function test_saving_unchanged_values_does_not_block_provider_updates_or_accept_client_flags(): void
    {
        $this->put(route('reservations.update', $this->reservation), $this->formData([
            'manually_edited_fields' => ['customer_name', 'flight_reference', 'price'],
        ]))->assertSessionHasNoErrors();
        $this->assertSame([], $this->reservation->fresh()->manually_edited_fields);
        $reservation = $this->import(['flight_reference' => 'FR456']);
        $this->assertSame('Nome inviato dalla piattaforma', $reservation->customer_name);
        $this->assertSame('FR456', $reservation->flight_reference);
    }

    public static function capacityModes(): array
    {
        return [['shared'], ['per_product']];
    }

    private function exceedCapacity(string $mode): void
    {
        $this->park['parking']->update(['capacity_mode' => $mode, 'total_spots' => 1]);
        $this->park['product']->update(['capacity' => 1]);
        $other = $this->reservation->replicate();
        $other->external_id = 'CAPACITY-OTHER';
        $other->save();
    }

    #[DataProvider('capacityModes')]
    public function test_metadata_saves_over_capacity_but_occupancy_changes_still_fail(string $mode): void
    {
        $this->exceedCapacity($mode);
        $this->put(route('reservations.update', $this->reservation), $this->formData([
            'customer_name' => 'Correzione con parcheggio pieno', 'flight_reference' => 'az1602', 'notes' => 'Nota manuale',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('reservations.index'));
        $this->assertSame('AZ1602', $this->reservation->fresh()->flight_reference);
        $this->assertSame('AZ1602', $this->import()->flight_reference);

        foreach ([['spots' => 2], ['ends_at' => $this->reservation->ends_at->copy()->addDay()->format('Y-m-d\TH:i')]] as $change) {
            $this->put(route('reservations.update', $this->reservation), $this->formData($change + ['flight_reference' => 'FR999']))
                ->assertSessionHasErrors('availability');
            $reservation = $this->reservation->fresh();
            $this->assertSame(1, $reservation->spots);
            $this->assertTrue($reservation->ends_at->equalTo($this->reservation->ends_at));
            $this->assertSame('AZ1602', $reservation->flight_reference);
        }

        $this->put(route('reservations.update', $this->reservation), $this->formData(['status' => 'cancelled']))->assertSessionHasNoErrors();
        $this->put(route('reservations.update', $this->reservation), $this->formData(['status' => 'confirmed']))->assertSessionHasErrors('availability');
        $this->assertSame(ReservationStatus::Cancelled, $this->reservation->fresh()->status);
    }

    public function test_reactivating_expired_pending_reservations_checks_capacity(): void
    {
        $this->exceedCapacity('shared');
        $this->reservation->update(['status' => 'pending', 'expires_at' => now()->subMinute()]);
        $this->put(route('reservations.update', $this->reservation), $this->formData(['flight_reference' => 'FR123']))->assertSessionHasNoErrors();
        $this->put(route('reservations.update', $this->reservation), $this->formData(['status' => 'confirmed']))->assertSessionHasErrors('availability');
        $this->assertSame(ReservationStatus::Pending, $this->reservation->fresh()->status);
    }

    public function test_metadata_edits_preserve_the_seconds_of_existing_imported_dates(): void
    {
        $this->reservation->update([
            'starts_at' => $this->reservation->starts_at->copy()->setSecond(25),
            'ends_at' => $this->reservation->ends_at->copy()->setSecond(45),
        ]);
        $this->exceedCapacity('shared');
        $this->put(route('reservations.update', $this->reservation), $this->formData(['flight_reference' => 'FR123']))->assertSessionHasNoErrors();
        $reservation = $this->reservation->fresh();
        $this->assertSame('FR123', $reservation->flight_reference);
        $this->assertTrue($reservation->starts_at->equalTo($this->reservation->starts_at));
        $this->assertTrue($reservation->ends_at->equalTo($this->reservation->ends_at));
    }

    public function test_stale_import_objects_reload_manual_corrections_inside_the_transaction(): void
    {
        $staleReservation = $this->reservation->fresh();
        $this->put(route('reservations.update', $this->reservation), $this->formData(['flight_reference' => 'FR123']))->assertSessionHasNoErrors();
        $result = app(ParkingContext::class)->run($this->park['parking'], fn () => app(ReservationService::class)
            ->update($staleReservation, $this->providerPayload(), fromExternal: true));
        $this->assertTrue($result->success, $result->error ?? 'Aggiornamento fallito');
        $this->assertSame('FR123', $result->reservation->flight_reference);
    }

    public function test_foreign_parking_and_invalid_status_are_rejected(): void
    {
        $foreign = $this->fixtures['parks']['A'];
        $this->put(route('reservations.update', $foreign['reservation']), $this->formData(['flight_reference' => 'FR123']))->assertNotFound();
        $this->put(route('reservations.update', $this->reservation), $this->formData(['parking_product_id' => $foreign['product']->id]))->assertSessionHasErrors('parking_product_id');
        $this->put(route('reservations.update', $this->reservation), $this->formData(['status' => 'invalid']))->assertSessionHasErrors('status');
        $this->assertNull($this->reservation->fresh()->flight_reference);
        $this->assertNull($foreign['reservation']->fresh()->flight_reference);
    }
}
