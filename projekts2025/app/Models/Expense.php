<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Izdevuma ieraksta modelis
// Glabā izdevuma datus, pieder auto un lietotājam
class Expense extends Model
{
    // Masveida aizpildāmie lauki
    protected $fillable = [
        'car_id',
        'user_id',
        'type',
        'amount',
        'date',
        'mileage',
        'description',
    ];

    // Datuma lauks tiek apstrādāts kā date objekts
    protected $casts = [
        'date' => 'date',
    ];

    // Saite uz auto, kuram pieder izdevums
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Saite uz lietotāju, kurš pievienoja izdevumu
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
