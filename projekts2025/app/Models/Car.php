<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $fillable = ['brand', 'model', 'year', 'mileage'];

    // Lietotāji, kas ir piesaistīti auto (koplietošana)
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
