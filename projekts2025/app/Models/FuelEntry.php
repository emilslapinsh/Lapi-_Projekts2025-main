<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelEntry extends Model
{
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

    protected $casts = [
        'date' => 'date',
        'is_full_tank' => 'boolean',
        'liters' => 'decimal:2',
        'total_eur' => 'decimal:2',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPricePerLiterAttribute(): ?float
    {
        $liters = (float) $this->liters;
        if ($liters <= 0) return null;

        return (float) $this->total_eur / $liters;
    }
}
