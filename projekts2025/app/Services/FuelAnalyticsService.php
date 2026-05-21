<?php

namespace App\Services;

use App\Models\FuelEntry;
use Illuminate\Support\Collection;

// Degvielas analītikas serviss
// No hronoloģiskiem ierakstiem veido pilnas bākas intervālus, rēķina L/100 km, meklē anomālijas un sagatavo grafikam
class FuelAnalyticsService
{
    // Aprēķina intervālus, vidējo patēriņu un anomālijas
    public function analyze(Collection $chronological): array
    {
        // Intervāli starp pilnām bākām
        $intervals = [];
        $prevFull = null;

        // Iet cauri ierakstiem hronoloģiski
        foreach ($chronological as $e) {
            // Patēriņu rēķina tikai pilnai bākai
            if (! $e->is_full_tank) {
                continue;
            }

            // Ja ir iepriekšējā pilnā bāka, var izveidot pāri
            if ($prevFull) {
                // Nobraukums starp divām pilnām bākām
                $km = (int) $e->odometer_km - (int) $prevFull->odometer_km;

                // Ja km ir derīgi, aprēķina L/100km
                if ($km > 0) {
                    // L/100 km = (litri / km) * 100
                    $l100 = ((float) $e->liters / (float) $km) * 100.0;
                    $eurl = $e->price_per_liter;

                    $intervals[] = [
                        'date' => $e->date->format('Y-m-d'),
                        'km' => $km,
                        'liters' => (float) $e->liters,
                        'l100' => $l100,
                        'eurl' => $eurl,
                        'odometer_from' => (int) $prevFull->odometer_km,
                        'odometer_to' => (int) $e->odometer_km,
                    ];
                }
            }

            // Atceras pēdējo pilno bāku
            $prevFull = $e;
        }

        // Skaita pilnas bākas ierakstus
        $fullTankRows = $chronological->where('is_full_tank', true)->count();

        // Kopsavilkuma rādītāji
        $stats = [
            'avg_l100' => null,
            'last_l100' => null,
            'eur_per_100' => null,
            'last_price_per_l' => null,
            'anomaly_count' => 0,
        ];

        // Diagrammas dati
        $chart = [
            'labels' => [],
            'l100' => [],
            'eurl' => [],
        ];

        // Saraksts ar anomālijām
        $anomalies = [];

        // Ja ir intervāli, rēķina vidējo un aizpilda grafiku
        if (count($intervals) > 0) {
            // Vidējais L/100 km pār visiem intervāliem
            $avg = array_sum(array_column($intervals, 'l100')) / count($intervals);
            $last = $intervals[count($intervals) - 1];

            // Galvenie rādītāji
            $stats['avg_l100'] = $avg;
            $stats['last_l100'] = $last['l100'];
            // EUR uz 100 km ≈ EUR par litru * L/100 km
            $stats['eur_per_100'] = $last['eurl'] !== null ? ($last['eurl'] * $last['l100']) : null;
            // Anomālija, ja L/100 km pārsniedz vidējo par 30%
            $stats['anomaly_count'] = count(array_filter(
                $intervals,
                fn ($p) => $p['l100'] > $avg * 1.3
            ));

            // Anomālija ir tad, ja patēriņš ir virs 130% no vidējā
            foreach ($intervals as $p) {
                if ($p['l100'] > $avg * 1.3) {
                    $anomalies[] = [
                        'date' => $p['date'],
                        'l100' => $p['l100'],
                        'avg_l100' => $avg,
                    ];
                }
            }

            // Sagatavo diagrammas elementus (asu vērtības noapaļo ērtām decimālām vietām)
            foreach ($intervals as $p) {
                $chart['labels'][] = $p['date'];
                $chart['l100'][] = round($p['l100'], 2);
                $chart['eurl'][] = $p['eurl'] !== null ? round($p['eurl'], 3) : null;
            }
        }

        // Pēdējā cena par litru no pēdējā ieraksta
        $lastEntry = $chronological->last();
        if ($lastEntry) {
            $stats['last_price_per_l'] = $lastEntry->price_per_liter;
        }

        // Atgriež analītikas rezultātu
        return [
            'intervals' => $intervals,
            'stats' => $stats,
            'chart' => $chart,
            'anomalies' => $anomalies,
            'meta' => [
                'full_tank_rows' => $fullTankRows,
                'intervals_usable' => count($intervals),
            ],
        ];
    }
}
