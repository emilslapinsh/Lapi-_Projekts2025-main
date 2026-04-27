<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'car_id',
        'user_id',
        'type',
        'amount',
        'date',
        'mileage',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
