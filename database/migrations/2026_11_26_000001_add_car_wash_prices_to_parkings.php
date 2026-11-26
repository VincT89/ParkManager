<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parkings', function (Blueprint $table): void {
            /** I valori iniziali mantengono le tariffe concordate; gli importi delle prenotazioni restano salvati separatamente. */
            $table->decimal('car_wash_interior_price', 10, 2)->default(10);
            $table->decimal('car_wash_exterior_price', 10, 2)->default(10);
            $table->decimal('car_wash_both_price', 10, 2)->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('parkings', function (Blueprint $table): void {
            $table->dropColumn(['car_wash_interior_price', 'car_wash_exterior_price', 'car_wash_both_price']);
        });
    }
};
