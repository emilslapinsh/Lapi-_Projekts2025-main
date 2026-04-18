<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelRequest;
use App\Models\Car;
use App\Models\FuelEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuelController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ✅ EXACTLY like CarController@index: confirmed cars + pending requests
        $cars = $user->cars()
            ->wherePivot('confirmed', true)
            ->orderBy('brand')
            ->get();

        $pendingCars = $user->cars()
            ->wherePivot('confirmed', false)
            ->get();

        $selectedCar = null;
        $entries = collect();

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

        if ($cars->isNotEmpty()) {
            $carId = (int) ($request->query('car_id') ?? $cars->first()->id);
            $selectedCar = $cars->firstWhere('id', $carId) ?? $cars->first();

            // Last 50 entries
            $entries = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderByDesc('date')
                ->orderByDesc('odometer_km')
                ->limit(50)
                ->get();

            // For consumption calculations we need chronological order
            $all = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderBy('date')
                ->orderBy('odometer_km')
                ->get();

            // full-to-full pairs
            $pairs = [];
            $prevFull = null;

            foreach ($all as $e) {
                if (!$e->is_full_tank) {
                    continue;
                }

                if ($prevFull) {
                    $km = (int) $e->odometer_km - (int) $prevFull->odometer_km;

                    if ($km > 0) {
                        $l100 = ((float) $e->liters / (float) $km) * 100.0;
                        $eurl = $e->price_per_liter;

                        $pairs[] = [
                            'date' => $e->date->format('Y-m-d'),
                            'l100' => $l100,
                            'eurl' => $eurl,
                        ];
                    }
                }

                $prevFull = $e;
            }

            if (count($pairs) > 0) {
                $avg = array_sum(array_column($pairs, 'l100')) / count($pairs);
                $last = $pairs[count($pairs) - 1];

                $stats['avg_l100'] = $avg;
                $stats['last_l100'] = $last['l100'];

                // €/100km = (€/l) * (L/100km)
                $stats['eur_per_100'] = $last['eurl'] !== null ? ($last['eurl'] * $last['l100']) : null;

                // Anomaly: >30% over average
                $stats['anomaly_count'] = count(array_filter(
                    $pairs,
                    fn ($p) => $p['l100'] > $avg * 1.3
                ));

                foreach ($pairs as $p) {
                    $chart['labels'][] = $p['date'];
                    $chart['l100'][] = round($p['l100'], 2);
                    $chart['eurl'][] = $p['eurl'] !== null ? round($p['eurl'], 3) : null;
                }
            }

            // last price/l from latest entry (chronological last)
            $lastEntry = $all->last();
            if ($lastEntry) {
                $stats['last_price_per_l'] = $lastEntry->price_per_liter;
            }
        }

        return view('dashboard.degviela', compact(
            'cars',
            'pendingCars',
            'selectedCar',
            'entries',
            'stats',
            'chart'
        ));
    }

    public function store(StoreFuelRequest $request)
    {
        $user = Auth::user();
        $carId = (int) $request->input('car_id');

        // ✅ Security: user must have confirmed access to this car (pivot confirmed=true)
        $hasAccess = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $carId)
            ->exists();

        abort_unless($hasAccess, 403);

        FuelEntry::create([
            'car_id' => $carId,
            'user_id' => $user->id,
            'date' => $request->input('date'),
            'odometer_km' => (int) $request->input('odometer_km'),
            'liters' => $request->input('liters'),
            'total_eur' => $request->input('total_eur'),
            'fuel_type' => $request->input('fuel_type'),
            'is_full_tank' => (bool) $request->boolean('is_full_tank'),
            'station' => $request->input('station'),
            'note' => $request->input('note'),
        ]);

        return redirect()
            ->route('degviela.index', ['car_id' => $carId])
            ->with('success', 'Uzpildes ieraksts pievienots.');
    }

    public function destroy(FuelEntry $fuel)
    {
        $user = Auth::user();

        // ✅ Security: must have confirmed access to the car
        $hasAccess = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $fuel->car_id)
            ->exists();

        abort_unless($hasAccess, 403);

        // ✅ Strict mode: only creator can delete (change if you want team-delete)
        abort_unless($fuel->user_id === $user->id, 403);

        $carId = $fuel->car_id;
        $fuel->delete();

        return redirect()
            ->route('degviela.index', ['car_id' => $carId])
            ->with('success', 'Uzpildes ieraksts dzēsts.');
    }

    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $carId = (int) $request->query('car_id');

        // ✅ Security: confirmed access required
        $hasAccess = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $carId)
            ->exists();

        abort_unless($hasAccess, 403);

        $rows = FuelEntry::query()
            ->where('car_id', $carId)
            ->orderBy('date')
            ->orderBy('odometer_km')
            ->get();

        $filename = 'degviela_' . $carId . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, [
                'Datums',
                'Odometrs_km',
                'Litri',
                'Summa_EUR',
                'EUR_par_litru',
                'Degvielas_veids',
                'Pilna_baka',
                'Stacija',
                'Piezime',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->date?->format('Y-m-d'),
                    $r->odometer_km,
                    $r->liters,
                    $r->total_eur,
                    $r->price_per_liter,
                    $r->fuel_type,
                    $r->is_full_tank ? 'Jā' : 'Nē',
                    $r->station,
                    $r->note,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
