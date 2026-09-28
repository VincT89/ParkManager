<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->uuid('public_key')->unique();
            $table->boolean('is_initial')->default(false);
            $table->boolean('uses_legacy_payments')->default(false);
            $table->text('payment_settings')->nullable();
            $table->timestamps();
        });
        Schema::create('management_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('management_id')->constrained('managements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamps();
            $table->unique(['management_id', 'user_id']);
        });

        $managementId = DB::table('managements')->insertGetId([
            'name' => DB::table('parkings')->orderBy('id')->value('name') ?? 'Gestione iniziale',
            'public_key' => (string) Str::uuid(),
            'is_initial' => true,
            'uses_legacy_payments' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::table('parkings', function (Blueprint $table) {
            $table->foreignId('management_id')->nullable()->constrained('managements')->restrictOnDelete();
            $table->uuid('public_booking_key')->nullable()->unique();
            $table->boolean('is_default_public')->default(false);
        });
        Schema::table('platforms', function (Blueprint $table) {
            $table->foreignId('management_id')->nullable()->constrained('managements')->restrictOnDelete();
            $table->boolean('uses_legacy_credentials')->default(false);
            $table->text('connection_settings')->nullable();
            $table->dropUnique(['slug']);
            $table->unique(['management_id', 'slug']);
        });
        DB::table('parkings')->update(['management_id' => $managementId]);
        $defaultParkingId = DB::table('parkings')->where('is_active', true)->orderBy('id')->value('id');
        foreach (DB::table('parkings')->orderBy('id')->cursor() as $parking) {
            DB::table('parkings')->where('id', $parking->id)->update([
                'public_booking_key' => (string) Str::uuid(),
                'is_default_public' => $parking->id === $defaultParkingId,
            ]);
        }
        DB::table('platforms')->update([
            'management_id' => $managementId,
            'uses_legacy_credentials' => true,
        ]);
        foreach (DB::table('users')->orderBy('id')->cursor() as $user) {
            DB::table('management_user')->insert([
                'management_id' => $managementId,
                'user_id' => $user->id,
                'role' => $user->role === 'admin' ? 'admin' : 'staff',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Schema::table('parkings', fn (Blueprint $table) => $table->foreignId('management_id')->nullable(false)->change());
        Schema::table('platforms', fn (Blueprint $table) => $table->foreignId('management_id')->nullable(false)->change());

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('management_id')->constrained('managements')->restrictOnDelete();
            $table->foreignId('parking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['parking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('managements')->count() > 1) {
            throw new RuntimeException('Rollback non consentito con più gestioni: ripristinare il backup completo per conservare la separazione dei dati.');
        }
        Schema::dropIfExists('activity_logs');
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropUnique(['management_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('management_id');
            $table->dropColumn(['uses_legacy_credentials', 'connection_settings']);
        });
        Schema::table('parkings', function (Blueprint $table) {
            $table->dropUnique(['public_booking_key']);
            $table->dropConstrainedForeignId('management_id');
            $table->dropColumn(['public_booking_key', 'is_default_public']);
        });
        Schema::dropIfExists('management_user');
        Schema::dropIfExists('managements');
    }
};
