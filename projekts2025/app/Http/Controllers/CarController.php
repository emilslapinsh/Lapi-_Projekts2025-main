<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Expense;
use App\Models\User;
use App\Support\ExpenseTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
//auto sadaļas “galvenais kontrolieris” izdevumu modulī (auto izvēle, filtri, izdevumu saraksts,
//kopsavilkumi/analītika, auto koplietošana un auto dzēšana)
class CarController extends Controller
{
    // Galvenā izdevumu lapa: auto izvēle, filtri, analītika un saraksts
    public function index(Request $request)
    {
        // Pašreizējais lietotājs
        $user = Auth::user();

        // Apstiprinātie auto
        $cars = $user->cars()
            ->wherePivot('confirmed', true)
            ->with('users')
            ->get();

        // Pending koplietošanas pieprasījumi
        $pendingCars = $user->cars()
            ->wherePivot('confirmed', false)
            ->get();

        // Izvēlētais tabs (izdevumi / auto)
        $tab = $request->query('tab', 'izdevumi');
        if (! in_array($tab, ['izdevumi', 'auto'], true)) {
            $tab = 'izdevumi';
        }

        // Vērtības, ja nav izvēlēta auto
        $selectedCar = null;
        $expensesPaginated = null;

        // Kopsavilkuma statistika
        $stats = [
            'total' => 0.0,
            'month' => 0.0,
            'per_km' => null,
            'last_mileage' => null,
        ];

        // Analītika un filtri
        $filteredSubtotal = 0.0;
        $filterTypeTotals = collect();
        $distinctTypes = collect();
        $insights = [
            'monthly_bars' => [],
            'biggest_filtered' => null,
            'count_filtered' => 0,
        ];

        // Ja lietotājam ir vismaz viens auto, gatavo datus izvēlētajam auto
        if ($cars->isNotEmpty()) {
            // Izvēlas auto pēc `car_id` vai paņem pirmo sarakstā
            $carId = (int) ($request->query('car_id') ?? $cars->first()->id);
            $selectedCar = $cars->firstWhere('id', $carId) ?? $cars->first();

            // Kopējie izdevumi visam periodam
            $stats['total'] = (float) $selectedCar->expenses()->sum('amount');

            // Izdevumi šajā mēnesī
            $stats['month'] = (float) $selectedCar->expenses()
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('amount');

            // Pēdējais zināmais nobraukums no izdevumu ierakstiem
            $stats['last_mileage'] = $selectedCar->expenses()
                ->whereNotNull('mileage')
                ->max('mileage');

            // Atrod mazāko un lielāko nobraukumu (lai zinātu nobraukto attālumu)
            $minMileage = $selectedCar->expenses()->whereNotNull('mileage')->min('mileage');
            $maxMileage = $selectedCar->expenses()->whereNotNull('mileage')->max('mileage');

            if ($minMileage !== null && $maxMileage !== null && $maxMileage > $minMileage) {
                // Nobrauktais attālums pēc izdevumu ierakstu odometra
                $km = $maxMileage - $minMileage;

                // Aprēķina izmaksas uz 1 km (kopējie izdevumi / nobrauktie km)
                $stats['per_km'] = $km > 0 ? round($stats['total'] / $km, 4) : null;
            }

            // Unikālie izdevumu tipi (dropdownam)
            $distinctTypes = Expense::query()
                ->where('car_id', $selectedCar->id)
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->filter()
                ->values();

            // Aprēķina filtrēto izdevumu kopsummu
            $filteredSubtotal = (float) $this->filteredExpenseBuilder($request, $selectedCar)->sum('amount');

            // Aprēķina filtrēto izdevumu skaitu
            $insights['count_filtered'] = (int) $this->filteredExpenseBuilder($request, $selectedCar)->count();

            // Summas pa izdevumu tipiem (pēc filtriem)
            $filterTypeTotals = $this->filteredExpenseBuilder($request, $selectedCar)
                ->selectRaw('type, SUM(amount) as total')
                ->groupBy('type')
                ->orderByDesc('total')
                ->get();

            // Lielākais izdevums (pēc filtriem)
            $insights['biggest_filtered'] = $this->filteredExpenseBuilder($request, $selectedCar)
                ->orderByDesc('amount')
                ->orderByDesc('date')
                ->first();

            // Analītika: pēdējo 6 mēnešu izdevumi
            $monthlyBars = [];
            for ($i = 5; $i >= 0; $i--) {
                $d = now()->copy()->subMonths($i)->startOfMonth();

                // Mēneša kopsumma konkrētajam gadam un mēnesim
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

            // Kārtošanas izvēle (datums/summa, augoši/dilstoši)
            $sort = $request->query('sort', 'date_desc');
            if (! in_array($sort, ['date_desc', 'date_asc', 'amount_desc', 'amount_asc'], true)) {
                $sort = 'date_desc';
            }

            // Requests izdevumu tabulai ar filtriem
            $listQuery = $this->filteredExpenseBuilder($request, $selectedCar)->with('user');

            // Pielieto kārtošanu
            match ($sort) {
                'date_asc' => $listQuery->orderBy('date')->orderBy('id'),
                'amount_desc' => $listQuery->orderByDesc('amount')->orderByDesc('date'),
                'amount_asc' => $listQuery->orderBy('amount')->orderByDesc('date'),
                default => $listQuery->orderByDesc('date')->orderByDesc('id'),
            };

            // Sakārto izdevumus lappusēs (20 ieraksti vienā lapā)
            $expensesPaginated = $listQuery->paginate(20)->withQueryString();
        }

        // Atgriež panelim visus vajadzīgos datus
        return view('dashboard.expenses', [
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

    // Uzbūvē izdevumu request pēc filtriem (tips, periods, datums)
    private function filteredExpenseBuilder(Request $request, Car $car): \Illuminate\Database\Eloquent\Builder
    {
        // Izdevumi tikai konkrētajam auto
        $q = Expense::query()->where('car_id', $car->id);

        // Filtrs pēc tipa
        if ($request->filled('type')) {
            $q->where('type', $request->query('type'));
        }

        // Filtrs pēc perioda (šis mēnesis / šis gads / viss)
        $period = $request->query('period', 'all');
        if ($period === 'this_month') {
            $q->whereYear('date', now()->year)->whereMonth('date', now()->month);
        } elseif ($period === 'this_year') {
            $q->whereYear('date', now()->year);
        }

        // Filtrs pēc datuma no/līdz
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->query('date_to'));
        }

        return $q;
    }

    // Pievieno jaunu auto un piesaista to lietotājam
    public function store(Request $request)
    {
        // Apstiprina auto pamatdatus
        $validated = $request->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'mileage' => 'required|integer',
        ]);

        // Izveido auto un piešķir pieeju īpašniekam
        $car = Car::query()->create($validated);
        $car->users()->attach(Auth::id(), ['confirmed' => true]);

        return redirect()->route('izdevumi.index')->with('success', 'Auto pievienots veiksmīgi.');
    }

    // Nosūta auto koplietošanas pieprasījumu citam lietotājam (pēc e-pasta)
    public function share(Request $request, Car $car)
    {
        // Pārbauda, vai lietotājam ir pieeja šim auto
        $hasAccess = $car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        // Nosaka, vai atbildēt ar JSON (mazs ziņojums bez refresh) vai ar refresh
        $wantsJson = $request->wantsJson()
            || $request->expectsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');

        // Validē e-pastu un pārbauda, vai lietotājs eksistē
        $validator = Validator::make(
            $request->all(),
            ['user_email' => ['required', 'email', 'exists:users,email']],
            ['user_email.exists' => 'Lietotājs ar šo e-pastu nav reģistrējies.']
        );

        // Atgriež validācijas kļūdu
        if ($validator->fails()) {
            $message = $validator->errors()->first();

            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->withErrors($validator);
        }

        // Atrod lietotāju pēc e-pasta
        $email = $validator->validated()['user_email'];
        $user = User::query()->where('email', $email)->firstOrFail();

        // Neļauj koplietot auto ar sevi
        if ((int) $user->id === (int) Auth::id()) {
            $message = 'Nevar koplietot auto ar sevi.';

            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        // Ja lietotājs jau ir piesaistīts auto, atgriež atbilstošu paziņojumu
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

        // Izveido koplietošanas saiti ar statusu "gaida apstiprinājumu"
        $car->users()->attach($user->id, ['confirmed' => false]);

        $message = 'Koplietošanas pieprasījums nosūtīts. Otrs lietotājs to redzēs savā izdevumu lapā un varēs to apstiprināt.';

        // Atgriež rezultātu
        if ($wantsJson) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    // Apstiprina koplietošanu lietotājam, kuram ir nosūtīts pieprasījums
    public function confirmShare(Car $car)
    {
        // Pašreizējais lietotājs
        $userId = Auth::id();

        // Ja saite eksistē, atjaunina lauku confirmed
        if ($car->users()->where('users.id', $userId)->exists()) {
            $car->users()->updateExistingPivot($userId, ['confirmed' => true]);

            return redirect()->route('izdevumi.index')->with('success', 'Koplietošana apstiprināta.');
        }

        return redirect()->back()->with('error', 'Nav ko apstiprināt.');
    }

    // Dzēš auto (un visus saistītos datus)
    public function destroy(Request $request, Car $car)
    {
        // Pārbauda, vai lietotājam ir apstiprināta pieeja auto
        $hasAccess = $car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        // Izveido auto nosaukumu paziņojumam
        $label = $car->brand.' '.$car->model.' ('.$car->year.')';

        // Dzēš auto
        $car->delete();

        // Saglabā, uz kuru tabu atgriezties
        $tab = $request->input('tab', $request->query('tab', 'auto'));
        if (! in_array($tab, ['izdevumi', 'auto'], true)) {
            $tab = 'auto';
        }

        // Novirza atpakaļ ar paziņojumu
        return redirect()
            ->route('izdevumi.index', ['tab' => $tab])
            ->with('success', 'Auto „'.$label.'” un visi saistītie dati (izdevumi, degviela, koplietošana) ir dzēsti.');
    }
}
