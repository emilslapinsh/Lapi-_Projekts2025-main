<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Auto modelis
// Glabā auto pamatdatus un attiecības ar lietotājiem un izdevumiem
class Car extends Model
{
    // Masveida aizpildāmie lauki
    protected $fillable = ['brand', 'model', 'year', 'mileage'];

    // Lietotāji, kas ir piesaistīti auto (koplietošana caur car_user)
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('confirmed')
            ->withTimestamps();
    }

    // Izdevumi, kas piesaistīti šim auto
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
