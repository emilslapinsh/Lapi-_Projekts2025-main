<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Izdevumu kategorija (vienkāršoti kā teksts)
            $table->string('type'); // Degviela / Serviss / Remonts / Apdrošināšana / Nodokļi / Cits

            // Summa EUR
            $table->decimal('amount', 10, 2);

            // Datums
            $table->date('date');

            // Nobraukums pie izdevuma (nav obligāts)
            $table->integer('mileage')->nullable();

            // Apraksts/komentārs (nav obligāts)
            $table->string('description')->nullable();

            $table->timestamps();

            // Indeksi ātrākai atlasei/filtrēšanai
            $table->index(['car_id', 'date']);
            $table->index(['car_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
