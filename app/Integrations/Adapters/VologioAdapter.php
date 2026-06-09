<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;
use App\Integrations\Support\RooshProviderClient;
use App\Integrations\DTO\NormalizedReservation;

class VologioAdapter extends AbstractPlatformAdapter
{
    private RooshProviderClient $client;

    public function __construct(RooshProviderClient $client)
    {
        $this->client = $client;
    }

    public function getName(): string
    {
        return 'Vologio';
    }

    public function getPlatformSlug(): string
    {
        return 'vologio';
    }

    public function defaultSyncWindow(): array
    {
        return [
            Carbon::now()->subHours(2),
            Carbon::now(),
        ];
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        $externalLocationId = $listing->external_id;

        if (empty($externalLocationId)) {
            throw new \RuntimeException("ParkingListing (ID: {$listing->id}) non ha un external_id configurato.");
        }

        $bookings = $this->client->findBookingsByModification($from, $to);

        $normalized = [];

        foreach ($bookings as $record) {
            if (($record['service_location_id'] ?? null) !== $externalLocationId) {
                continue;
            }

            $normalized[] = $this->normalizeRecord($record);
        }

        return $normalized;
    }

    protected function normalizeRecord(array $record): NormalizedReservation
    {
        if (empty($record['id'])) {
            throw new \RuntimeException('Missing required field: id');
        }

        if (empty($record['service_id'])) {
            throw new \RuntimeException("Missing required field: service_id for {$record['id']}");
        }

        if (empty($record['start']) || empty($record['end'])) {
            throw new \RuntimeException("Missing period dates for {$record['id']}");
        }

        $startsAt = Carbon::parse($record['start']);
        $endsAt = Carbon::parse($record['end']);

        $customerName = trim(($record['customer']['first_name'] ?? '') . ' ' . ($record['customer']['last_name'] ?? ''));
        if (empty($customerName)) {
            $customerName = 'Sconosciuto';
        }

        $departureFlight = $record['journey']['departure_flight_number'] ?? null;
        $arrivalFlight = $record['journey']['arrival_flight_number'] ?? null;
        $flightReference = collect([$departureFlight, $arrivalFlight])->filter()->join(' / ') ?: null;

        $spots = 1;

        $rawStatus = strtolower((string) ($record['status'] ?? ''));
        $mappedStatus = match($rawStatus) {
            'cancelled', 'canceled' => 'cancelled',
            'pending' => 'pending',
            default => 'confirmed',
        };

        return new NormalizedReservation(
            external_id: (string) $record['id'],
            external_product_ref: (string) $record['service_id'],
            external_product_name: null,
            customer_name: $customerName,
            customer_email: $record['customer']['email'] ?? null,
            customer_phone: $record['customer']['phone'] ?? null,
            license_plate: $record['journey']['car']['license_plate'] ?? null,
            starts_at: $startsAt,
            ends_at: $endsAt,
            spots: $spots,
            price: isset($record['price']['amount']) ? (float) $record['price']['amount'] : null,
            currency: $record['price']['currency'] ?? 'EUR',
            notes: $record['remarks'] ?? null,
            raw_data: $record,
            status: $mappedStatus,
            flight_reference: $flightReference
        );
    }
}
