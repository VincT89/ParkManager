<?php

namespace Database\Seeders;

use App\Models\Parking;
use App\Models\Platform;
use App\Models\ParkingListing;
use Illuminate\Database\Seeder;

class ParkingListingSeeder extends Seeder
{
    public function run(): void
    {
        $parkings = Parking::all();
        $platforms = Platform::all();

        foreach ($parkings as $parking) {
            foreach ($platforms as $platform) {
                ParkingListing::firstOrCreate([
                    'parking_id'     => $parking->id,
                    'platform_id'    => $platform->id,
                ], [
                    'is_active'      => true,
                ]);
            }
        }
    }
}