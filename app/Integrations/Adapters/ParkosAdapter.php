<?php

namespace App\Integrations\Adapters;

use App\Integrations\AbstractPlatformAdapter;
use App\Models\ParkingListing;
use Carbon\Carbon;
use App\Integrations\DTO\NormalizedReservation;
use App\Integrations\Support\FixturePayloadReader;

class ParkosAdapter extends AbstractPlatformAdapter
{
    private FixturePayloadReader $fixtureReader;

    public function __construct()
    {
        $this->fixtureReader = new FixturePayloadReader();
    }

    public function getName(): string
    {
        return 'Parkos';
    }

    public function getPlatformSlug(): string
    {
        return 'parkos';
    }

    public function fetchReservations(ParkingListing $listing, Carbon $from, Carbon $to): array
    {
        if (config('services.parkos.fixture_mode')) {
            $fixtureFile = config('services.parkos.fixture_file', 'reservations_success.json');
            $payload = $this->fixtureReader->loadFixture('parkos', $fixtureFile);

            if (!isset($payload['reservations']) || !is_array($payload['reservations'])) {
                throw new \RuntimeException('Invalid shape: missing or invalid "reservations" key.');
            }

            return collect($payload['reservations'])
                ->map(fn (array $record) => $this->normalizeRecord($record))
                ->all();
        }

        throw new \RuntimeException('API Parkos non ancora implementata.');
    }

    protected function normalizeRecord(array $record): NormalizedReservation
    {
        if (empty($record['id'])) {
            throw new \RuntimeException('Missing required field: id');
        }

        if (empty($record['product_code'])) {
            throw new \RuntimeException("Missing required field: product_code for {$record['id']}");
        }

        if (empty($record['period']['starts_at']) || empty($record['period']['ends_at'])) {
            throw new \RuntimeException("Missing period dates for {$record['id']}");
        }

        try {
            $startsAt = Carbon::parse($record['period']['starts_at']);
            $endsAt = Carbon::parse($record['period']['ends_at']);
        } catch (\Exception $e) {
            throw new \RuntimeException("Invalid date format for {$record['id']}");
        }

        if ($endsAt->isBefore($startsAt)) {
            throw new \RuntimeException("Invalid dates: ends_at before starts_at for {$record['id']}");
        }

        $spots = (int) ($record['spots'] ?? 1);
        if ($spots < 1) {
            throw new \RuntimeException("Invalid spots count: {$spots} for {$record['id']}");
        }

        if (empty($record['customer']['name'])) {
            throw new \RuntimeException("Missing required field: customer.name for {$record['id']}");
        }

        $price = isset($record['price']['amount']) ? (float) $record['price']['amount'] : null;
        $currency = isset($record['price']['currency']) ? $record['price']['currency'] : 'EUR';

        return new NormalizedReservation(
            external_id: (string) $record['id'],
            external_product_ref: (string) $record['product_code'],
            external_product_name: $record['product_name'] ?? null,
            customer_name: (string) $record['customer']['name'],
            customer_email: $record['customer']['email'] ?? null,
            customer_phone: $record['customer']['phone'] ?? null,
            license_plate: $record['vehicle']['license_plate'] ?? null,
            starts_at: $startsAt,
            ends_at: $endsAt,
            spots: $spots,
            price: $price,
            currency: $currency,
            notes: $record['notes'] ?? null,
            raw_data: $record
        );
    }
}
