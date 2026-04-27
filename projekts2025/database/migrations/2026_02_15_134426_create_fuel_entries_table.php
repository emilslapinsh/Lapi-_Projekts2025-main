<?php

// database/migrations/xxxx_xx_xx_create_fuel_entries_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->unsignedInteger('odometer_km'); // odometrs uzpildes brīdī

            $table->decimal('liters', 8, 2);       // uzpildītais daudzums
            $table->decimal('total_eur', 10, 2);   // samaksāts kopā

            $table->string('fuel_type', 30)->default('Dīzelis'); // Dīzelis / Benzīns / LPG / Elektro (u.c.)
            $table->boolean('is_full_tank')->default(true);      // “pilna bāka” patēriņa aprēķinam

            $table->string('station', 80)->nullable();           // Circle K, Virši, Neste...
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['car_id', 'date']);
            $table->index(['car_id', 'odometer_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_entries');
    }
};
