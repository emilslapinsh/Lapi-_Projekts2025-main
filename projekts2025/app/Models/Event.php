<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Kalendāra notikuma modelis
// Glabā notikuma tipu, datumu un aprakstu, pieder konkrētam lietotājam
class Event extends Model
{
    // Rūpnīcas atbalsts testiem un seed datiem
    use HasFactory;

    // Masveida aizpildāmie lauki
    protected $fillable = ['title', 'description', 'date', 'user_id'];

    // Datuma lauks tiek apstrādāts kā date objekts
    protected $casts = [
        'date' => 'date',
    ];

    // Saite uz lietotāju, kuram pieder notikums
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
