<?php

namespace App\Support;

/**
 * Izdevumu veidi izdevumu sadaļā (bez degvielas — tā ir atsevišķa lapa).
 */
final class ExpenseTypes
{
    public const TYPES = [
        'Serviss',
        'Remonts',
        'Apdrošināšana',
        'Nodokļi',
        'Cits',
    ];

    public static function ruleIn(): string
    {
        return implode(',', self::TYPES);
    }

    /**
     * @return array<string, string>
     */
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
