<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function cars()
    {
        return $this->belongsToMany(\App\Models\Car::class)->withPivot('confirmed')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        $usernames = config('admin.usernames', []);
        if (! is_array($usernames) || count($usernames) === 0) {
            return false;
        }

        $mine = strtolower((string) $this->username);
        foreach ($usernames as $u) {
            if ($mine === strtolower((string) $u)) {
                return true;
            }
        }

        return false;
    }
}
