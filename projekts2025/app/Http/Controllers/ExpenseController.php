<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Expense;
use App\Support\ExpenseTypes;
use App\Support\FormattedSpreadsheetExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Izdevumu ierakstu darbības
// Pievieno, atjaunina, dzēš izdevumus, eksportē Excel un pārbauda piekļuvi auto
class ExpenseController extends Controller
{
    // Saglabā jaunu izdevumu ierakstu
    public function store(Request $request)
    {
        // Validē izdevuma ievadi
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'type' => ['required', 'string', 'max:50', Rule::in(ExpenseTypes::TYPES)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Atrod auto un pārbauda, vai lietotājam ir apstiprināta pieeja
        $car = Car::query()->where('id', $validated['car_id'])
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                    ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        // Izveido izdevumu un piesaista to lietotājam
        Expense::query()->create([
            'car_id' => $car->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'mileage' => $validated['mileage'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Saglabā filtrus, lai pēc pievienošanas atgrieztos tajā pašā skatā
        $preserve = $request->only(['tab', 'period', 'date_from', 'date_to', 'sort']);
        if ($request->filled('filter_type')) {
            // UI filtram lieto filter_type, bet URL parametrs paliek type
            $preserve['type'] = $request->string('filter_type')->toString();
        }

        // Novirza atpakaļ uz izdevumu lapu
        return redirect()
            ->route('izdevumi.index', ['car_id' => $car->id] + $preserve)
            ->with('success', 'Izdevums pievienots.');
    }

    // Atjaunina izdevuma ierakstu
    public function update(Request $request, Expense $expense)
    {
        // Pārbauda pieeju auto, pie kura pieder izdevums
        $this->authorizeExpenseCar($expense);

        // Atļauj arī veco tipu, ja tas jau ir saglabāts datubāzē
        $allowedTypes = array_values(array_unique(array_merge(
            ExpenseTypes::TYPES,
            [(string) $expense->type],
        )));

        // Validē labošanas formu
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50', Rule::in($allowedTypes)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Saglabā izmaiņas
        $expense->update([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'mileage' => $validated['mileage'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Saglabā filtrus, lai pēc atjaunināšanas atgrieztos tajā pašā skatā
        $preserve = $request->only(['tab', 'period', 'date_from', 'date_to', 'sort']);
        if ($request->filled('filter_type')) {
            // UI filtram lieto filter_type, bet URL parametrs paliek type
            $preserve['type'] = $request->string('filter_type')->toString();
        }

        // Novirza atpakaļ uz izdevumu lapu
        return redirect()
            ->route('izdevumi.index', ['car_id' => $expense->car_id] + $preserve)
            ->with('success', 'Izdevums atjaunināts.');
    }

    // Dzēš izdevuma ierakstu
    public function destroy(Request $request, Expense $expense)
    {
        // Pārbauda pieeju auto, pie kura pieder izdevums
        $this->authorizeExpenseCar($expense);

        // Dzēš izdevumu un atceras auto ID atgriešanai
        $carId = $expense->car_id;
        $expense->delete();

        // Novirza atpakaļ uz izdevumu lapu ar tiem pašiem filtriem
        return redirect()
            ->route('izdevumi.index', ['car_id' => $carId] + $request->only(['tab', 'type', 'period', 'date_from', 'date_to', 'sort']))
            ->with('success', 'Izdevums dzēsts.');
    }

    // Eksportē izdevumus formatētā Excel tabulā
    public function export(Request $request): StreamedResponse
    {
        // Pārbauda, ka ir norādīts auto
        $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
        ]);

        // Atrod auto un pārbauda pieeju
        $car = Car::query()->where('id', $request->car_id)
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                    ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        // Ielasa visus izdevumus eksportam
        $expenses = $car->expenses()->with('user')->orderByDesc('date')->orderByDesc('id')->get();

        // Izveido drošu faila nosaukumu
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $car->brand.'_'.$car->model) ?: 'auto';
        $filename = 'izdevumi_'.$safeName.'_'.now()->format('Ymd_His').'.xls';

        $rows = [];
        foreach ($expenses as $e) {
            $rows[] = [
                $e->date?->format('d.m.Y') ?? '',
                $e->type,
                number_format((float) $e->amount, 2, '.', ''),
                $e->mileage !== null ? number_format((int) $e->mileage, 0, '', ' ') : '',
                $e->description ?? '',
                optional($e->user)->username ?? '—',
            ];
        }

        return FormattedSpreadsheetExport::download(
            $filename,
            'Izdevumu pārskats',
            [
                ['Auto', $car->brand.' '.$car->model.' ('.$car->year.')'],
                ['Eksportēts', now()->format('d.m.Y H:i')],
                ['Ierakstu skaits', (string) $expenses->count()],
            ],
            ['Datums', 'Tips', 'Summa (EUR)', 'Nobraukums (km)', 'Apraksts', 'Pievienoja'],
            $rows,
            ['left', 'left', 'right', 'right', 'left', 'left'],
        );
    }

    // Pārbauda, vai lietotājam ir pieeja izdevuma auto
    private function authorizeExpenseCar(Expense $expense): void
    {
        // Atļauj, ja lietotājs ir apstiprināts pie auto
        $hasAccess = $expense->car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        // Bloķē, ja piekļuves nav
        abort_unless($hasAccess, 403);
    }
}
