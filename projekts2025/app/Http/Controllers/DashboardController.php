<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Event;
use App\Models\Expense;
use App\Models\FuelEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
//sākuma panelis pēc pieslēgšanās (kopsavilkums par izdevumiem/degvielu, vidējais patēriņš,
//tuvākie notikumi un pēdējās aktivitātes)
class DashboardController extends Controller
{
    // Lietotāja panelis ar kopsavilkumu un atgādinājumiem
    public function index(Request $request)
    {
        // Pašreizējais lietotājs
        $user = Auth::user();

        // Apstiprinātie auto, kuriem lietotājam ir pieeja
        $confirmedCarsQuery = $user->cars()->wherePivot('confirmed', true);
        $confirmedCarIds = $confirmedCarsQuery->pluck('cars.id')->all();

        // Saskaita, cik auto ir pieejami lietotājam
        $carCount = count($confirmedCarIds);

        // Kopējie šī mēneša izdevumi (izdevumi + degviela)
        $monthlyExpenses = 0.0;
        if ($carCount > 0) {
            // Saskaita izdevumu summu šim mēnesim
            $expenseSum = (float) Expense::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('amount');

            // Saskaita degvielas summu šim mēnesim
            $fuelSum = (float) FuelEntry::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('total_eur');

            // Kopā = izdevumi + degviela
            $monthlyExpenses = $expenseSum + $fuelSum;
        }

        // Vidējais patēriņš visiem apstiprinātajiem auto
        $averageFuelConsumption = $this->computeAverageFuelConsumption($confirmedCarIds);

        // Nākamais kalendāra notikums no šodienas
        $nextEvent = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->first();

        // Nākamā apkopes/notikuma datums kā teksts
        $nextMaintenanceDate = null;
        if ($nextEvent) {
            $date = $nextEvent->date instanceof Carbon ? $nextEvent->date : Carbon::parse($nextEvent->date);
            // Datuma pārveide uz tekstu priekš attēlošanas
            $nextMaintenanceDate = $date->format('Y-m-d');
        }

        // Tuvākie 5 notikumi ar statusu
        $upcomingEvents = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get()
            ->map(function (Event $e) {
                $date = $e->date instanceof Carbon ? $e->date : Carbon::parse($e->date);
                // Izrēķina, cik dienas palikušas līdz notikumam
                $daysUntil = now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
                $status = ($daysUntil >= 0 && $daysUntil <= 7)
                    ? 'steidzami'
                    : 'plānots';

                return [
                    'title' => $e->title,
                    'date' => $date->format('Y-m-d'),
                    'status' => $status,
                ];
            })
            ->values()
            ->all();

        // Laika logs "steidzami" (nākamās 7 dienas)
        // Pieskaita 7 dienas no šodienas, lai iegūtu robežu
        $urgentWindowEnd = now()->copy()->startOfDay()->addDays(7)->toDateString();

        // Cik notikumu ir nākamo 7 dienu laikā
        // Saskaita notikumus, kuru datums iekrīt šajā 7 dienu logā
        $urgentUpcomingCount = (int) Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDate('date', '<=', $urgentWindowEnd)
            ->count();

        // Nākamais steidzamais notikums
        $nextUrgent = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDate('date', '<=', $urgentWindowEnd)
            ->orderBy('date')
            ->first();

        // Steidzamā notikuma nosaukums
        $nextUrgentTitle = $nextUrgent?->title;

        // Pēdējās aktivitātes (izdevumi, uzpildes, notikumi)
        $recentActivities = $this->buildRecentActivities($user->id, $confirmedCarIds);

        // Atgriež paneli ar sagatavotajiem datiem
        return view('dashboard.home', compact(
            'carCount',
            'monthlyExpenses',
            'averageFuelConsumption',
            'nextMaintenanceDate',
            'upcomingEvents',
            'urgentUpcomingCount',
            'nextUrgentTitle',
            'recentActivities'
        ));
    }

    // Aprēķina vidējo patēriņu (L/100km) pēc "pilna bāka" pāriem
    private function computeAverageFuelConsumption(array $carIds): string
    {
        // Ja nav auto, atgriež 0
        if (count($carIds) === 0) {
            return '0.0';
        }

        // Uzkrāj pāru skaitu un summu (priekš degvielas aprēķiniem)
        // pairsCount = cik reizes varēja izrēķināt patēriņu
        // pairsSum = visu patēriņu summa
        $pairsCount = 0;
        $pairsSum = 0.0;

        // Iet cauri katram auto atsevišķi
        foreach ($carIds as $carId) {
            $entries = FuelEntry::query()
                ->where('car_id', $carId)
                ->orderBy('date')
                ->orderBy('odometer_km')
                ->get();

            // Iepriekšējā "pilna bāka" ieraksts
            $prevFull = null;

            // Meklē pilnas bākas pārus un rēķina patēriņu
            foreach ($entries as $e) {
                if (! $e->is_full_tank) {
                    continue;
                }

                if ($prevFull) {
                    // Izrēķina nobraukumu starp divām pilnām bākām
                    $km = (int) $e->odometer_km - (int) $prevFull->odometer_km;
                    if ($km > 0) {
                        // Patēriņš L/100km = (litri / km) * 100
                        $l100 = ((float) $e->liters / (float) $km) * 100.0;
                        // Pieskaita patēriņu vidējā aprēķinam
                        $pairsSum += $l100;
                        $pairsCount++;
                    }
                }

                // Saglabā pēdējo pilno bāku nākamajam salīdzinājumam
                $prevFull = $e;
            }
        }

        // Ja nav neviena derīga pāra, atgriež 0
        if ($pairsCount === 0) {
            return '0.0';
        }

        // Izrēķina un formatē vidējo patēriņu ar 1 zīmi aiz komata
        return number_format($pairsSum / $pairsCount, 1, '.', '');
    }

    // Uzbūvē sarakstu ar pēdējām lietotāja aktivitātēm panelim
    private function buildRecentActivities(int $userId, array $confirmedCarIds): array
    {
        // Kopējais saraksts, ko pēc tam sakārto pēc laika
        $items = collect();

        // Ja ir auto, pievieno izdevumus un uzpildes
        if (count($confirmedCarIds) > 0) {
            // Auto dati, lai var salikt nosaukumus
            $cars = Car::query()
                ->whereIn('id', $confirmedCarIds)
                ->get()
                ->keyBy('id');

            // Pēdējie izdevumi
            $expenses = Expense::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            foreach ($expenses as $e) {
                $car = $cars->get($e->car_id);
                $carLabel = $car ? ($car->brand.' '.$car->model) : 'Auto';

                $items->push([
                    'ts' => $e->created_at ?? now(),
                    'title' => 'Izdevums: '.$e->type,
                    // Noformē summu ar 2 zīmēm aiz komata
                    'subtitle' => $carLabel.' — '.number_format((float) $e->amount, 2).' €',
                ]);
            }

            // Pēdējās uzpildes
            $fuels = FuelEntry::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            foreach ($fuels as $f) {
                $car = $cars->get($f->car_id);
                $carLabel = $car ? ($car->brand.' '.$car->model) : 'Auto';

                $items->push([
                    'ts' => $f->created_at ?? now(),
                    'title' => 'Uzpilde',
                    // Noformē litrus un summu ar 2 zīmēm aiz komata
                    'subtitle' => $carLabel.' — '.number_format((float) $f->liters, 2).' L, '.number_format((float) $f->total_eur, 2).' €',
                ]);
            }
        }

        // Pēdējie notikumi 
        $events = Event::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($events as $ev) {
            $date = $ev->date instanceof Carbon ? $ev->date : Carbon::parse($ev->date);

            $items->push([
                'ts' => $ev->created_at ?? now(),
                'title' => 'Notikums: '.$ev->title,
                'subtitle' => $date->format('Y-m-d').' — '.(string) $ev->description,
            ]);
        }

        // Sakārto un atstāj tikai 6 jaunākos
        return $items
            ->sortByDesc('ts')
            ->take(6)
            ->values()
            ->map(fn ($i) => [
                'title' => $i['title'],
                'subtitle' => $i['subtitle'],
                'time' => Carbon::parse($i['ts'])->diffForHumans(),
            ])
            ->all();
    }
}
