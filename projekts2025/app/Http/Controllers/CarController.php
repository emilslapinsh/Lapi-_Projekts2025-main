<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Expense;
use App\Models\User;
use App\Support\ExpenseTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $cars = $user->cars()
            ->wherePivot('confirmed', true)
            ->with('users')
            ->get();

        $pendingCars = $user->cars()
            ->wherePivot('confirmed', false)
            ->get();

        $tab = $request->query('tab', 'izdevumi');
        if (! in_array($tab, ['izdevumi', 'auto'], true)) {
            $tab = 'izdevumi';
        }

        $selectedCar = null;
        $expensesPaginated = null;

        $stats = [
            'total' => 0.0,
            'month' => 0.0,
            'per_km' => null,
            'last_mileage' => null,
        ];

        $filteredSubtotal = 0.0;
        $filterTypeTotals = collect();
        $distinctTypes = collect();
        $insights = [
            'monthly_bars' => [],
            'biggest_filtered' => null,
            'count_filtered' => 0,
        ];

        if ($cars->isNotEmpty()) {
            $carId = (int) ($request->query('car_id') ?? $cars->first()->id);
            $selectedCar = $cars->firstWhere('id', $carId) ?? $cars->first();

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

            $distinctTypes = Expense::query()
                ->where('car_id', $selectedCar->id)
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->filter()
                ->values();

            $filteredSubtotal = (float) $this->filteredExpenseBuilder($request, $selectedCar)->sum('amount');
            $insights['count_filtered'] = (int) $this->filteredExpenseBuilder($request, $selectedCar)->count();

            $filterTypeTotals = $this->filteredExpenseBuilder($request, $selectedCar)
                ->selectRaw('type, SUM(amount) as total')
                ->groupBy('type')
                ->orderByDesc('total')
                ->get();

            $insights['biggest_filtered'] = $this->filteredExpenseBuilder($request, $selectedCar)
                ->orderByDesc('amount')
                ->orderByDesc('date')
                ->first();

            $monthlyBars = [];
            for ($i = 5; $i >= 0; $i--) {
                $d = now()->copy()->subMonths($i)->startOfMonth();
                $sum = (float) $selectedCar->expenses()
                    ->whereYear('date', $d->year)
                    ->whereMonth('date', $d->month)
                    ->sum('amount');
                $monthlyBars[] = [
                    'key' => $d->format('Y-m'),
                    'label' => $d->copy()->locale('lv')->translatedFormat('Y. F'),
                    'total' => $sum,
                ];
            }
            $insights['monthly_bars'] = $monthlyBars;

            $sort = $request->query('sort', 'date_desc');
            if (! in_array($sort, ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'], true)) {
                $sort = 'date_desc';
            }

            $listQuery = $this->filteredExpenseBuilder($request, $selectedCar)->with('user');

            match ($sort) {
                'date_asc' => $listQuery->orderBy('date')->orderBy('id'),
                'amount_desc' => $listQuery->orderByDesc('amount')->orderByDesc('date'),
                'amount_asc' => $listQuery->orderBy('amount')->orderByDesc('date'),
                default => $listQuery->orderByDesc('date')->orderByDesc('id'),
            };

            $expensesPaginated = $listQuery->paginate(20)->withQueryString();
        }

        return view('dashboard.izdevumi', [
            'cars' => $cars,
            'pendingCars' => $pendingCars,
            'selectedCar' => $selectedCar,
            'expensesPaginated' => $expensesPaginated,
            'stats' => $stats,
            'tab' => $tab,
            'filteredSubtotal' => $filteredSubtotal,
            'filterTypeTotals' => $filterTypeTotals,
            'distinctTypes' => $distinctTypes,
            'insights' => $insights,
            'expenseTypes' => ExpenseTypes::TYPES,
            'sort' => $request->query('sort', 'date_desc'),
            'filterType' => $request->query('type', ''),
            'filterPeriod' => $request->query('period', 'all'),
            'filterDateFrom' => $request->query('date_from', ''),
            'filterDateTo' => $request->query('date_to', ''),
            'typeHints' => ExpenseTypes::formHints(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Expense>
     */
    private function filteredExpenseBuilder(Request $request, Car $car): \Illuminate\Database\Eloquent\Builder
    {
        $q = Expense::query()->where('car_id', $car->id);

        if ($request->filled('type')) {
            $q->where('type', $request->query('type'));
        }

        $period = $request->query('period', 'all');
        if ($period === 'this_month') {
            $q->whereYear('date', now()->year)->whereMonth('date', now()->month);
        } elseif ($period === 'this_year') {
            $q->whereYear('date', now()->year);
        }

        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->query('date_to'));
        }

        return $q;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'mileage' => 'required|integer',
        ]);

        $car = Car::query()->create($validated);
        $car->users()->attach(Auth::id(), ['confirmed' => true]);

        return redirect()->route('izdevumi.index')->with('success', 'Auto pievienots veiksmīgi.');
    }

    public function share(Request $request, Car $car)
    {
        $hasAccess = $car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        $wantsJson = $request->wantsJson()
            || $request->expectsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');

        $validator = Validator::make(
            $request->all(),
            ['user_email' => ['required', 'email', 'exists:users,email']],
            ['user_email.exists' => 'Lietotājs ar šo e-pastu nav reģistrējies.']
        );

        if ($validator->fails()) {
            $message = $validator->errors()->first();

            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->withErrors($validator);
        }

        $email = $validator->validated()['user_email'];
        $user = User::query()->where('email', $email)->firstOrFail();

        if ((int) $user->id === (int) Auth::id()) {
            $message = 'Nevar koplietot auto ar sevi.';

            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $existing = $car->users()->where('users.id', $user->id)->first();

        if ($existing) {
            $confirmed = (bool) $existing->pivot->confirmed;
            $message = $confirmed
                ? 'Šis lietotājs jau ir apstiprināts pie šī auto.'
                : 'Koplietošanas pieprasījums šim lietotājam jau ir nosūtīts un gaida apstiprinājumu.';

            if ($wantsJson) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        }

        $car->users()->attach($user->id, ['confirmed' => false]);

        $message = 'Koplietošanas pieprasījums nosūtīts. Otrs lietotājs to redzēs savā izdevumu lapā un varēs to apstiprināt.';

        if ($wantsJson) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function confirmShare(Car $car)
    {
        $userId = Auth::id();

        if ($car->users()->where('users.id', $userId)->exists()) {
            $car->users()->updateExistingPivot($userId, ['confirmed' => true]);

            return redirect()->route('izdevumi.index')->with('success', 'Koplietošana apstiprināta.');
        }

        return redirect()->back()->with('error', 'Nav ko apstiprināt.');
    }

    public function destroy(Request $request, Car $car)
    {
        $hasAccess = $car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        $label = $car->brand.' '.$car->model.' ('.$car->year.')';
        $car->delete();

        $tab = $request->input('tab', $request->query('tab', 'auto'));
        if (! in_array($tab, ['izdevumi', 'auto'], true)) {
            $tab = 'auto';
        }

        return redirect()
            ->route('izdevumi.index', ['tab' => $tab])
            ->with('success', 'Auto „'.$label.'” un visi saistītie dati (izdevumi, degviela, koplietošana) ir dzēsti.');
    }
}
