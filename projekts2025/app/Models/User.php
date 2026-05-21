<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Lietotāja modelis
// Glabā autentifikācijas datus un attiecības ar auto un notikumiem, kā arī admin pārbaudi
class User extends Authenticatable
{
    // Lietotāja modelis autentifikācijai
    use HasFactory, Notifiable;

    // Aizpildāmie lauki
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    // Lauki, kurus nesūta atpakaļ JSON atbildēs
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Lauku tipi un pārvēršanas
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Lietotāja kalendāra notikumi
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    // Lietotāja auto (koplietošana caur car_user)
    public function cars()
    {
        return $this->belongsToMany(\App\Models\Car::class)->withPivot('confirmed')->withTimestamps();
    }

    // Pārbauda vai lietotājs ir administrators
    public function isAdmin(): bool
    {
        // Adminu saraksts tiek glabāts config/admin.php
        $usernames = config('admin.usernames', []);
        if (! is_array($usernames) || count($usernames) === 0) {
            return false;
        }

        // Salīdzina pēc lietotājvārda (mazie burti)
        $mine = strtolower((string) $this->username);
        foreach ($usernames as $u) {
            if ($mine === strtolower((string) $u)) {
                return true;
            }
        }

        return false;
    }
}
