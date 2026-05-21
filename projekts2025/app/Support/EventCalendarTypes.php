<?php

namespace App\Support;

// Kalendāra notikumu tipu saraksts
// Izmanto validācijā frontend un backend pusē (notikumu veids kā title)
final class EventCalendarTypes
{
    // Atļautie notikumu veidi
    public const TYPES = [
        'Apskate',
        'Serviss',
        'Remonts',
        'Apdrošināšana',
        'Cits',
    ];
}
