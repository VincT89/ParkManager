<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Parking;
use App\Models\ParkingListing;
use App\Models\ParkingProduct;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ReservationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Seeder demo bloccato fuori da local/testing.');
            return;
        }

        $parkings = Parking::where('is_active', true)->get();

        if ($parkings->isEmpty()) {
            $this->command?->warn('Nessun parcheggio attivo trovato. Seeder prenotazioni saltato.');
            return;
        }

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Reservation::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $created = 0;

        foreach ($parkings as $parking) {
            $listings = ParkingListing::query()
                ->where('parking_id', $parking->id)
                ->where('is_active', true)
                ->with('platform')
                ->get();

            $products = ParkingProduct::query()
                ->where('parking_id', $parking->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('code');

            if ($listings->isEmpty() || $products->isEmpty()) {
                continue;
            }

            $targetCount = 600; // 600 per parking (spalmati su 6 mesi)
            $weightedProducts = $this->buildWeightedProducts($products);

            for ($i = 0; $i < $targetCount; $i++) {
                /** @var ParkingProduct $product */
                $product = $weightedProducts->random();

                /** @var ParkingListing $listing */
                $listing = $this->pickCompatibleListing($listings);

                [$startsAt, $endsAt] = $this->generateDateRange();

                $status = $this->pickStatus();
                $spots = 1;

                $basePrice = (float) $product->price;
                $finalPrice = $this->priceWithSmallVariance($basePrice, $status);

                $expiresAt = null;
                if ($status === ReservationStatus::Pending) {
                    $expiresAt = now()->addMinutes(rand(-10, 60)); // Mix di scadute e non scadute
                }

                Reservation::create([
                    'parking_id' => $parking->id,
                    'parking_listing_id' => $listing->id,
                    'parking_product_id' => $product->id,
                    'external_id' => 'demo_' . $parking->id . '_' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                    'customer_name' => fake('it_IT')->name(),
                    'customer_email' => fake('it_IT')->safeEmail(),
                    'customer_phone' => fake('it_IT')->numerify('3#########'),
                    'license_plate' => strtoupper(fake('it_IT')->bothify('??###??')),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'spots' => $spots,
                    'status' => $status->value,
                    'expires_at' => $expiresAt,
                    'price' => $finalPrice,
                    'notes' => $this->buildNotes($product, $listing->platform->name),
                    'raw_data' => [
                        'source' => 'demo_seeder',
                        'platform' => $listing->platform->slug,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                    ],
                    'created_at' => $startsAt->copy()->subDays(rand(1, 20)),
                    'updated_at' => now(),
                ]);

                $created++;
            }
        }

        $this->command?->info("Creati {$created} record demo coerenti con il nuovo dominio per " . $parkings->count() . " parcheggi.");
    }

    /**
     * Costruisce una distribuzione pesata dei prodotti.
     */
    private function buildWeightedProducts(Collection $products): Collection
    {
        $weights = [
            'auto_open' => 55,
            'auto_covered' => 25,
            'truck_open' => 12,
            'truck_covered' => 8,
        ];

        $weighted = collect();

        foreach ($products as $code => $product) {
            $repeat = $weights[$code] ?? 10;

            for ($i = 0; $i < $repeat; $i++) {
                $weighted->push($product);
            }
        }

        return $weighted->isNotEmpty() ? $weighted : $products->values();
    }

    /**
     * Seleziona un listing attivo qualsiasi.
     * In futuro potrai specializzare la compatibilità per piattaforma.
     */
    private function pickCompatibleListing(Collection $listings): ParkingListing
    {
        return $listings->random();
    }

    private function generateDateRange(): array
    {
        // Distribuiamo su 6 mesi passati + 1 mese futuro per avere uno storico veritiero
        $startBase = Carbon::now()->subDays(rand(-30, 180));
        $startsAt = $startBase->copy()->setTime(rand(0, 20), [0, 15, 30, 45][rand(0, 3)]);
        $durationDays = rand(1, 7);
        $endsAt = $startsAt->copy()->addDays($durationDays)->setTime(rand(6, 23), [0, 15, 30, 45][rand(0, 3)]);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $endsAt = $startsAt->copy()->addDay();
        }

        return [$startsAt, $endsAt];
    }

    /**
     * Stati demo plausibili.
     */
    private function pickStatus(): ReservationStatus
    {
        $pool = [
            ReservationStatus::Confirmed,
            ReservationStatus::Confirmed,
            ReservationStatus::Confirmed,
            ReservationStatus::Confirmed,
            ReservationStatus::Pending,
            ReservationStatus::Modified,
            ReservationStatus::Cancelled,
        ];

        return $pool[array_rand($pool)];
    }

    /**
     * Piccola variazione per simulare arrotondamenti o promo.
     * I cancellati possono tenere comunque il prezzo storico.
     */
    private function priceWithSmallVariance(float $basePrice, ReservationStatus $status): float
    {
        $variance = [0, 0, 0, 0.50, -0.50, 1.00, -1.00];
        $price = max(0, $basePrice + $variance[array_rand($variance)]);

        return round($price, 2);
    }

    private function buildNotes(ParkingProduct $product, string $platformName): string
    {
        $notes = [
            "Prenotazione demo {$product->name}",
            "Origine canale: {$platformName}",
            "Record generato per test realistico dashboard e availability",
        ];

        return implode(' | ', $notes);
    }
}
