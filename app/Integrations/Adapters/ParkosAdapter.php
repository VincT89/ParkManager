<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;
use App\Integrations\DTO\NormalizedReservation;
use App\Integrations\Support\FixturePayloadReader;
use App\Integrations\Support\ParkosClient;

class ParkosAdapter extends AbstractPlatformAdapter
{
    public function __construct(
        private ParkosClient $client,
        private FixturePayloadReader $fixtureReader
    ) {}

    public function getName(): string
    {
        return 'Parkos';
    }

    public function getPlatformSlug(): string
    {
        return 'parkos';
    }

    public function defaultSyncWindow(): array
    {
        return [
            Carbon::now()->subHours((int) config('services.parkos.sync_lookback_hours', 2)),
            Carbon::now(),
        ];
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        if (config('services.parkos.fixture_mode')) {
            $fixtureFile = config('services.parkos.fixture_file', 'reservations_success.json');
            $payload = $this->fixtureReader->loadFixture('parkos', $fixtureFile);

            if (!isset($payload['data']) || !is_array($payload['data'])) {
                throw new \RuntimeException('Invalid shape: missing or invalid "data" key.');
            }

            return collect($payload['data'])
                ->map(fn (array $record) => $this->normalizeRecord($record))
                ->values()
                ->all();
        }

        if (empty($listing->external_id)) {
            throw new \RuntimeException("ParkingListing ID {$listing->id} non ha external_id configurato.");
        }

        $records = $this->client->findBookingsByModification($from, $to, $listing->external_id);

        return collect($records)
            ->map(fn (array $record) => $this->normalizeRecord($record))
            ->values()
            ->all();
    }

    protected function normalizeRecord(array $record): NormalizedReservation
    {
        if (empty($record['code'])) {
            throw new \RuntimeException('Missing required field: code');
        }

        if (empty($record['arrival_date']) || empty($record['arrival_time'])) {
            throw new \RuntimeException("Missing arrival dates for {$record['code']}");
        }
        
        if (empty($record['departure_date']) || empty($record['departure_time'])) {
            throw new \RuntimeException("Missing departure dates for {$record['code']}");
        }

        try {
            $startsAt = Carbon::parse($record['arrival_date'] . ' ' . $record['arrival_time']);
            $endsAt = Carbon::parse($record['departure_date'] . ' ' . $record['departure_time']);
        } catch (\Exception $e) {
            throw new \RuntimeException("Invalid date format for {$record['code']}");
        }

        if ($endsAt->isBefore($startsAt)) {
            throw new \RuntimeException("Invalid dates: ends_at before starts_at for {$record['code']}");
        }

        $merchantId = $record['merchant_id'] ?? '';
        $parkingType = $record['parking_type'] ?? '';
        $locationType = $record['location_type'] ?? '';
        $externalProductRef = "{$merchantId}:{$parkingType}:{$locationType}";

        $status = !empty($record['cancelled_at']) ? 'cancelled' : 'confirmed';

        $customerName = trim($record['name'] ?? 'Sconosciuto');

        $price = isset($record['total_price']) && is_numeric($record['total_price']) ? (float) $record['total_price'] : null;
        $currency = $record['currency'] ?? 'EUR';

        return new NormalizedReservation(
            external_id: (string) $record['code'],
            external_product_ref: $externalProductRef,
            external_product_name: null,
            customer_name: $customerName,
            customer_email: $record['email'] ?? null,
            customer_phone: $record['phone'] ?? null,
            license_plate: $record['car_license_plate'] ?? null,
            starts_at: $startsAt,
            ends_at: $endsAt,
            spots: (int) ($record['spots'] ?? 1),
            price: $price,
            currency: $currency,
            notes: $record['notes'] ?? null,
            raw_data: $record,
            status: $status
        );
    }
}
