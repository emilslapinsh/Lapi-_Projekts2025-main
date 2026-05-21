<?php

namespace App\Support;

// Izdevumu tipu saraksts
// Izmanto validācijā un kā formas palīgtekstiem
final class ExpenseTypes
{
    // Atļautie izdevumu tipi (degviela ir atsevišķā sadaļā)
    public const TYPES = [
        'Serviss',
        'Remonts',
        'Apdrošināšana',
        'Nodokļi',
        'Cits',
    ];

    // Apvieno tipus vienā teksta virknē validācijas noteikumiem
    public static function ruleIn(): string
    {
        return implode(',', self::TYPES);
    }

    // Īsi paskaidrojumi formām pēc izvēlētā tipa
    public static function formHints(): array
    {
        return [
            'Serviss' => 'Norādi nobraukumu, ja vēlies jēdzīgāku €/km aprēķinu.',
            'Remonts' => "\u{012A}ss apraksts pal\u{012B}dz v\u{0113}l\u{0101}k atcer\u{0113}ties veikt\u{0101}s darb\u{012B}as.",
            'Apdrošināšana' => 'Aprakstā var norādīt polises periodu.',
            'Nodokļi' => 'Piemēram, CSDD, nodevas.',
            'Cits' => 'Piemēram, stāvvieta, mazgāšana.',
        ];
    }
}
