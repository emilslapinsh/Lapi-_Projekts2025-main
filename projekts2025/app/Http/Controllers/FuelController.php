<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelRequest;
use App\Models\FuelEntry;
use App\Support\FormattedSpreadsheetExport;
use App\Services\FuelAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Degvielas sadaļas darbības
// Parāda uzpildes un analītiku, saglabā un dzēš ierakstus, eksportē Excel
class FuelController extends Controller
{
    // Degvielas lapa ar uzpildēm un analītiku
    public function index(Request $request)
    {
        // Pašreizējais lietotājs
        $user = Auth::user();

        // Apstiprinātie auto, kuriem lietotājam ir pieeja
        $cars = $user->cars()
            ->wherePivot('confirmed', true)
            ->orderBy('brand')
            ->get();

        // Gaidošie koplietošanas pieprasījumi
        $pendingCars = $user->cars()
            ->wherePivot('confirmed', false)
            ->get();

        // Noklusētās vērtības, ja nav izvēlēts auto
        $selectedCar = null;
        $entries = collect();

        // Kopsavilkuma rādītāji degvielai
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

        // Intervāli un anomālijas pilnas bākas metodei
        $intervals = [];
        $anomalies = [];
        $fuelMeta = [
            'full_tank_rows' => 0,
            'intervals_usable' => 0,
        ];

        // Ja ir auto, ielasa ierakstus izvēlētajam auto
        if ($cars->isNotEmpty()) {
            // Izvēlas auto pēc car_id vai paņem pirmo sarakstā
            $carId = (int) ($request->query('car_id') ?? $cars->first()->id);
            $selectedCar = $cars->firstWhere('id', $carId) ?? $cars->first();

            // Pēdējie 50 ieraksti tabulai
            $entries = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderByDesc('date')
                ->orderByDesc('odometer_km')
                ->limit(50)
                ->get();

            // Visi ieraksti analītikai (pilnas bākas intervāli)
            $all = FuelEntry::query()
                ->where('car_id', $selectedCar->id)
                ->orderBy('date')
                ->orderBy('odometer_km')
                ->get();

            // Aprēķina patēriņu, izmaksas un anomālijas
            $analytics = app(FuelAnalyticsService::class)->analyze($all);
            $stats = $analytics['stats'];
            $chart = $analytics['chart'];
            $intervals = $analytics['intervals'];
            $anomalies = $analytics['anomalies'];
            $fuelMeta = $analytics['meta'];
        }

        // Atgriež skatu ar sagatavotajiem datiem
        return view('dashboard.fuel', compact(
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

    // Saglabā jaunu uzpildes ierakstu
    public function store(StoreFuelRequest $request)
    {
        // Pašreizējais lietotājs un izvēlētais auto
        $user = Auth::user();
        $carId = (int) $request->input('car_id');

        // Pārbauda, vai lietotājam ir apstiprināta pieeja auto
        $hasAccess = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $carId)
            ->exists();

        // Bloķē, ja auto nav pieejams lietotājam
        abort_unless($hasAccess, 403);

        // Izveido uzpildes ierakstu
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

        // Novirza atpakaļ uz degvielas lapu
        return redirect()
            ->route('degviela.index', ['car_id' => $carId])
            ->with('success', 'Uzpildes ieraksts pievienots.');
    }

    // Dzēš uzpildes ierakstu
    public function destroy(FuelEntry $fuel)
    {
        // Pašreizējais lietotājs
        $user = Auth::user();

        // Pārbauda, vai lietotājam ir apstiprināta pieeja auto
        $hasAccess = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $fuel->car_id)
            ->exists();

        // Bloķē, ja auto nav pieejams lietotājam
        abort_unless($hasAccess, 403);

        // Dzēst drīkst tikai tas, kurš izveidoja ierakstu
        abort_unless($fuel->user_id === $user->id, 403);

        // Dzēš ierakstu un atceras auto ID
        $carId = $fuel->car_id;
        $fuel->delete();

        // Novirza atpakaļ uz degvielas lapu
        return redirect()
            ->route('degviela.index', ['car_id' => $carId])
            ->with('success', 'Uzpildes ieraksts dzēsts.');
    }

    // Eksportē uzpildes formatētā Excel tabulā
    public function export(Request $request): StreamedResponse
    {
        // Pašreizējais lietotājs un izvēlētais auto
        $user = Auth::user();
        $carId = (int) $request->query('car_id');

        // Pārbauda, vai lietotājam ir apstiprināta pieeja auto
        $car = $user->cars()
            ->wherePivot('confirmed', true)
            ->where('cars.id', $carId)
            ->first();

        // Bloķē, ja auto nav pieejams lietotājam
        abort_unless($car, 403);

        // Ielasa rindas eksportam
        $entries = FuelEntry::query()
            ->where('car_id', $carId)
            ->orderBy('date')
            ->orderBy('odometer_km')
            ->get();

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $car->brand.'_'.$car->model) ?: 'auto';
        $filename = 'degviela_'.$safeName.'_'.now()->format('Ymd_His').'.xls';

        $rows = [];
        foreach ($entries as $r) {
            $rows[] = [
                $r->date?->format('d.m.Y') ?? '',
                number_format((int) $r->odometer_km, 0, '', ' '),
                number_format((float) $r->liters, 2, '.', ''),
                number_format((float) $r->total_eur, 2, '.', ''),
                $r->price_per_liter !== null ? number_format((float) $r->price_per_liter, 3, '.', '') : '',
                $r->fuel_type ?? '',
                $r->is_full_tank ? 'Jā' : 'Nē',
                $r->station ?? '',
                $r->note ?? '',
            ];
        }

        return FormattedSpreadsheetExport::download(
            $filename,
            'Degvielas uzpildes',
            [
                ['Auto', $car->brand.' '.$car->model.' ('.$car->year.')'],
                ['Eksportēts', now()->format('d.m.Y H:i')],
                ['Ierakstu skaits', (string) $entries->count()],
            ],
            [
                'Datums',
                'Odometrs (km)',
                'Litri',
                'Summa (EUR)',
                'EUR/l',
                'Degvielas veids',
                'Pilna bāka',
                'Stacija',
                'Piezīme',
            ],
            $rows,
            ['left', 'right', 'right', 'right', 'right', 'left', 'center', 'left', 'left'],
        );
    }
}
