<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Apstiprinātie auto (redzami izdevumu sadaļā)
        $cars = auth()->user()->cars()
            ->wherePivot('confirmed', true)
            ->with('users')
            ->get();

        // ✅ Neapstiprinātie koplietošanas pieprasījumi (svarīgais fix)
        $pendingCars = auth()->user()->cars()
            ->wherePivot('confirmed', false)
            ->get();

        $selectedCar = null;
        $expenses = collect();

        $stats = [
            'total' => 0,
            'month' => 0,
            'per_km' => null,
            'last_mileage' => null,
        ];

        // Ja ir vismaz viens apstiprināts auto, rādām izdevumus/statistiku
        if ($cars->isNotEmpty()) {
            $carId = $request->query('car_id') ?? $cars->first()->id;
            $selectedCar = $cars->firstWhere('id', (int)$carId) ?? $cars->first();

            $expenses = $selectedCar->expenses()
                ->with('user')
                ->orderByDesc('date')
                ->limit(50)
                ->get();

            $stats['total'] = (float) $selectedCar->expenses()->sum('amount');

            $stats['month'] = (float) $selectedCar->expenses()
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('amount');

            $stats['last_mileage'] = $selectedCar->expenses()
                ->whereNotNull('mileage')
                ->max('mileage');

            $minMileage = $selectedCar->expenses()->whereNotNull('mileage')->min('mileage');
            $maxMileage = $selectedCar->expenses()->whereNotNull('mileage')->max('mileage');

            if ($minMileage !== null && $maxMileage !== null && $maxMileage > $minMileage) {
                $km = $maxMileage - $minMileage;
                $stats['per_km'] = $km > 0 ? round($stats['total'] / $km, 4) : null;
            }
        }

        return view('dashboard.izdevumi', compact('cars', 'pendingCars', 'selectedCar', 'expenses', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'mileage' => 'required|integer',
        ]);

        $car = Car::create($validated);
        $car->users()->attach(Auth::id(), ['confirmed' => true]);

        return redirect()->route('izdevumi.index')->with('success', 'Auto pievienots veiksmīgi.');
    }

    public function share(Request $request, Car $car)
    {
        $validated = $request->validate([
            'user_email' => 'required|email|exists:users,email',
        ]);

        // (Labs papildus drošības solis) Pārbaudām, vai pašam lietotājam ir piekļuve šim auto
        $hasAccess = $car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        $user = User::where('email', $validated['user_email'])->first();

        if (!$car->users()->where('user_id', $user->id)->exists()) {
            $car->users()->attach($user->id, ['confirmed' => false]);
        }

        return redirect()->back()->with('success', 'Koplietošanas pieprasījums nosūtīts.');
    }

    public function confirmShare(Car $car)
    {
        $userId = Auth::id();

        if ($car->users()->where('user_id', $userId)->exists()) {
            $car->users()->updateExistingPivot($userId, ['confirmed' => true]);
            return redirect()->route('izdevumi.index')->with('success', 'Koplietošana apstiprināta.');
        }

        return redirect()->back()->with('success', 'Nav ko apstiprināt.');
    }
}
