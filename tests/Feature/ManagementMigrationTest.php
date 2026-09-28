<?php

namespace Tests\Feature;

use App\Models\Management;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManagementMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    public function test_upgrade_preserves_existing_ids_and_grants_only_legacy_access(): void
    {
        $migration = require database_path('migrations/2026_09_17_000001_create_management_boundaries.php');
        $migration->down();
        $parkingId = DB::table('parkings')->insertGetId(['name' => 'Parcheggio esistente (test)', 'total_spots' => 50, 'capacity_mode' => 'shared', 'is_active' => true]);
        $userId = DB::table('users')->insertGetId(['name' => 'Utente esistente (test)', 'email' => 'legacy@example.test', 'password' => 'test-hash', 'role' => 'admin']);
        $platformId = DB::table('platforms')->insertGetId(['name' => 'Canale esistente (test)', 'slug' => 'parkos', 'is_active' => true]);
        $migration->up();
        $management = Management::where('is_initial', true)->firstOrFail();
        $this->assertDatabaseHas('parkings', ['id' => $parkingId, 'management_id' => $management->id, 'name' => 'Parcheggio esistente (test)', 'is_default_public' => true]);
        $this->assertDatabaseHas('platforms', ['id' => $platformId, 'management_id' => $management->id, 'uses_legacy_credentials' => true]);
        $this->assertDatabaseHas('management_user', ['user_id' => $userId, 'management_id' => $management->id, 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['id' => $userId, 'password' => 'test-hash']);
        $this->assertNotNull(DB::table('parkings')->where('id', $parkingId)->value('public_booking_key'));
    }

    public function test_rollback_refuses_to_remove_boundaries_when_multiple_managements_exist(): void
    {
        Management::create(['name' => 'Seconda gestione (test)']);
        $migration = require database_path('migrations/2026_09_17_000001_create_management_boundaries.php');
        try {
            $migration->down();
            $this->fail('Rollback should preserve boundaries.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Rollback non consentito', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasTable('management_user'));
        $this->assertDatabaseCount('managements', 2);
    }
}
