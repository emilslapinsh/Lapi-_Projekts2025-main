<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Event;
use App\Models\Expense;
use App\Models\FuelEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $confirmedCarsQuery = $user->cars()->wherePivot('confirmed', true);
        $confirmedCarIds = $confirmedCarsQuery->pluck('cars.id')->all();

        $carCount = count($confirmedCarIds);

        $monthlyExpenses = 0.0;
        if ($carCount > 0) {
            $expenseSum = (float) Expense::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('amount');

            $fuelSum = (float) FuelEntry::query()
                ->whereIn('car_id', $confirmedCarIds)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('total_eur');

            $monthlyExpenses = $expenseSum + $fuelSum;
        }

        $averageFuelConsumption = $this->computeAverageFuelConsumption($confirmedCarIds);

        $nextEvent = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->first();

        $nextMaintenanceDate = null;
        if ($nextEvent) {
            $date = $nextEvent->date instanceof Carbon ? $nextEvent->date : Carbon::parse($nextEvent->date);
            $nextMaintenanceDate = $date->format('Y-m-d');
        }

        $upcomingEvents = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->limit(5)
            ->get()
            ->map(function (Event $e) {
                $date = $e->date instanceof Carbon ? $e->date : Carbon::parse($e->date);
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

        $urgentWindowEnd = now()->copy()->startOfDay()->addDays(7)->toDateString();

        $urgentUpcomingCount = (int) Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDate('date', '<=', $urgentWindowEnd)
            ->count();

        $nextUrgent = Event::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDate('date', '<=', $urgentWindowEnd)
            ->orderBy('date')
            ->first();

        $nextUrgentTitle = $nextUrgent?->title;

        $recentActivities = $this->buildRecentActivities($user->id, $confirmedCarIds);

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

    /**
     * Vidējais patēriņš (L/100km) visiem apstiprinātajiem auto,
     * balstoties uz “pilna bāka” pāriem.
     */
    private function computeAverageFuelConsumption(array $carIds): string
    {
        if (count($carIds) === 0) {
            return '0.0';
        }

        $pairsCount = 0;
        $pairsSum = 0.0;

        foreach ($carIds as $carId) {
            $entries = FuelEntry::query()
                ->where('car_id', $carId)
                ->orderBy('date')
                ->orderBy('odometer_km')
                ->get();

            $prevFull = null;

            foreach ($entries as $e) {
                if (! $e->is_full_tank) {
                    continue;
                }

                if ($prevFull) {
                    $km = (int) $e->odometer_km - (int) $prevFull->odometer_km;
                    if ($km > 0) {
                        $l100 = ((float) $e->liters / (float) $km) * 100.0;
                        $pairsSum += $l100;
                        $pairsCount++;
                    }
                }

                $prevFull = $e;
            }
        }

        if ($pairsCount === 0) {
            return '0.0';
        }

        return number_format($pairsSum / $pairsCount, 1, '.', '');
    }

    private function buildRecentActivities(int $userId, array $confirmedCarIds): array
    {
        $items = collect();

        if (count($confirmedCarIds) > 0) {
            $cars = Car::query()
                ->whereIn('id', $confirmedCarIds)
                ->get()
                ->keyBy('id');

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
                    'subtitle' => $carLabel.' — '.number_format((float) $e->amount, 2).' €',
                ]);
            }

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
                    'subtitle' => $carLabel.' — '.number_format((float) $f->liters, 2).' L, '.number_format((float) $f->total_eur, 2).' €',
                ]);
            }
        }

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
