<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Degvielas uzpildes ieraksta modelis
// Glabā uzpildes datus, pieder auto un lietotājam, nodrošina vienkāršu cenu aprēķinu
class FuelEntry extends Model
{
    // Masveida aizpildāmie lauki
    protected $fillable = [
        'car_id',
        'user_id',
        'date',
        'odometer_km',
        'liters',
        'total_eur',
        'fuel_type',
        'is_full_tank',
        'station',
        'note',
    ];

    // Lauku tipi un automātiskās pārvēršanas
    protected $casts = [
        'date' => 'date',
        'is_full_tank' => 'boolean',
        'liters' => 'decimal:2',
        'total_eur' => 'decimal:2',
    ];

    // Saite uz auto, kuram pieder uzpilde
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Saite uz lietotāju, kurš pievienoja uzpildi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Aprēķina cenu par litru no kopējās summas un litriem
    public function getPricePerLiterAttribute(): ?float
    {
        // Ja litri ir 0, dalīšanu neveic
        $liters = (float) $this->liters;
        if ($liters <= 0) {
            return null;
        }

        // total_eur / liters
        return (float) $this->total_eur / $liters;
    }
}
