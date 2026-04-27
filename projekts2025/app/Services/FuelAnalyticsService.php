<?php

namespace App\Services;

use App\Models\FuelEntry;
use Illuminate\Support\Collection;

/**
 * Degvielas žurnāla analītika: pilnas bākas intervāli, L/100km, diagrammas dati.
 */
class FuelAnalyticsService
{
    /**
     * @param  Collection<int, FuelEntry>  $chronological  Visi ieraksti augošā secībā (datums, tad odometrs).
     * @return array{
     *     intervals: list<array{date:string, km:int, liters:float, l100:float, eurl:?float, odometer_from:int, odometer_to:int}>,
     *     stats: array<string, float|int|null>,
     *     chart: array{labels: list<string>, l100: list<float>, eurl: list<float|null>},
     *     anomalies: list<array{date:string, l100:float, avg_l100:float}>,
     *     meta: array{full_tank_rows: int, intervals_usable: int}
     * }
     */
    public function analyze(Collection $chronological): array
    {
        $intervals = [];
        $prevFull = null;

        foreach ($chronological as $e) {
            if (! $e->is_full_tank) {
                continue;
            }

            if ($prevFull) {
                $km = (int) $e->odometer_km - (int) $prevFull->odometer_km;

                if ($km > 0) {
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

            $prevFull = $e;
        }

        $fullTankRows = $chronological->where('is_full_tank', true)->count();

        $stats = [
            'avg_l100' => null,
            'last_l100' => null,
            'eur_per_100' => null,
            'last_price_per_l' => null,
            'anomaly_count' => 0,
        ];

        $chart = [
            'labels' => [],
            'l100' => [],
            'eurl' => [],
        ];

        $anomalies = [];

        if (count($intervals) > 0) {
            $avg = array_sum(array_column($intervals, 'l100')) / count($intervals);
            $last = $intervals[count($intervals) - 1];

            $stats['avg_l100'] = $avg;
            $stats['last_l100'] = $last['l100'];
            $stats['eur_per_100'] = $last['eurl'] !== null ? ($last['eurl'] * $last['l100']) : null;
            $stats['anomaly_count'] = count(array_filter(
                $intervals,
                fn ($p) => $p['l100'] > $avg * 1.3
            ));

            foreach ($intervals as $p) {
                if ($p['l100'] > $avg * 1.3) {
                    $anomalies[] = [
                        'date' => $p['date'],
                        'l100' => $p['l100'],
                        'avg_l100' => $avg,
                    ];
                }
            }

            foreach ($intervals as $p) {
                $chart['labels'][] = $p['date'];
                $chart['l100'][] = round($p['l100'], 2);
                $chart['eurl'][] = $p['eurl'] !== null ? round($p['eurl'], 3) : null;
            }
        }

        $lastEntry = $chronological->last();
        if ($lastEntry) {
            $stats['last_price_per_l'] = $lastEntry->price_per_liter;
        }

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
