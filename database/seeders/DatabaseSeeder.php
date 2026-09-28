<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || \App\Models\Management::count() > 1) {
            throw new \RuntimeException('Il seeder dimostrativo non può azzerare un ambiente operativo o con più gestioni.');
        }
        \App\Models\ActivityLog::truncate();
        \Illuminate\Support\Facades\DB::table('management_user')->delete();

        // Disabilita i controlli delle chiavi esterne
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // Pulisce le tabelle (ordine rispettando le FK, anche se disabilitate è buona pratica)
        \App\Models\Payment::truncate();
        \App\Models\Reservation::truncate();
        \App\Models\AvailabilityBlock::truncate();
        \App\Models\ParkingCapacityAllocation::truncate();
        \Illuminate\Support\Facades\DB::table('platform_product_mappings')->truncate();
        \App\Models\ParkingProduct::truncate();
        \App\Models\ParkingListing::truncate();
        \App\Models\SyncLog::truncate();
        \App\Models\Platform::truncate();
        \App\Models\Parking::truncate();
        \App\Models\User::truncate();

        // Riabilita i controlli delle chiavi esterne
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        $management = \App\Models\Management::where('is_initial', true)->firstOrFail();
        $context = app(\App\Tenancy\ParkingContext::class);
        try {
            $this->call(UserSeeder::class);
            foreach (\App\Models\User::all() as $user) {
                $management->users()->syncWithoutDetaching([$user->id => ['role' => $user->role?->value ?? 'staff']]);
            }
            $context->select(null, $management);
            $this->call(ParkingSeeder::class);
            $parking = \App\Models\Parking::withoutGlobalScopes()->where('management_id', $management->id)->firstOrFail();
            $context->select($parking);
            $parking->is_default_public = true;
            $parking->save();
            $this->call([ParkingProductsSeeder::class, PlatformSeeder::class]);
            foreach (\App\Models\Platform::all() as $platform) {
                $platform->uses_legacy_credentials = true;
                $platform->save();
            }
            $this->call([ParkingListingSeeder::class, PlatformProductMappingSeeder::class, ParkosMappingSeeder::class]);
        } finally {
            $context->clear();
        }
    }
}
