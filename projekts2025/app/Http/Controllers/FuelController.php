<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelRequest;
use App\Models\FuelEntry;
use App\Services\FuelAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuelController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Apstiprinātie auto + gaidošās saites (pending) — kā izdevumu sadaļā
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

        $intervals = [];
        $anomalies = [];
        $fuelMeta = [
            'full_tank_rows' => 0,
            'intervals_usable' => 0,
        ];

        if ($cars->isNotEmpty()) {
            $carId = (int) ($request->query('car_id') ?? $cars->first()->id);
            $selectedCar = $cars->firstWhere('id', $carId) ?? $cars->first();

            $entries = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderByDesc('date')
                ->orderByDesc('odometer_km')
                ->limit(50)
                ->get();

            $all = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderBy('date')
                ->orderBy('odometer_km')
                ->get();

            $analytics = app(FuelAnalyticsService::class)->analyze($all);
            $stats = $analytics['stats'];
            $chart = $analytics['chart'];
            $intervals = $analytics['intervals'];
            $anomalies = $analytics['anomalies'];
            $fuelMeta = $analytics['meta'];
        }

        return view('dashboard.degviela', compact(
            'cars',
            'pendingCars',
            'selectedCar',
            'entries',
            'stats',
            'chart',
            'intervals',
            'anomalies',
            'fuelMeta'
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

        $filename = 'degviela_'.$carId.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — Excel LV labāk atpazīst latviešu burtus
            fwrite($out, "\xEF\xBB\xBF");

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
