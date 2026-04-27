<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Expense;
use App\Support\ExpenseTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'type' => ['required', 'string', 'max:50', Rule::in(ExpenseTypes::TYPES)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $car = Car::query()->where('id', $validated['car_id'])
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                    ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        Expense::query()->create([
            'car_id' => $car->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'mileage' => $validated['mileage'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $preserve = $request->only(['tab', 'period', 'date_from', 'date_to', 'sort']);
        if ($request->filled('filter_type')) {
            $preserve['type'] = $request->string('filter_type')->toString();
        }

        return redirect()
            ->route('izdevumi.index', ['car_id' => $car->id] + $preserve)
            ->with('success', 'Izdevums pievienots.');
    }

    public function edit(Expense $expense)
    {
        $this->authorizeExpenseCar($expense);

        return view('dashboard.expense-edit', [
            'expense' => $expense->load('car'),
            'expenseTypes' => ExpenseTypes::TYPES,
            'typeHints' => ExpenseTypes::formHints(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpenseCar($expense);

        $allowedTypes = array_values(array_unique(array_merge(
            ExpenseTypes::TYPES,
            [(string) $expense->type],
        )));

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50', Rule::in($allowedTypes)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $expense->update([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'mileage' => $validated['mileage'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $preserve = $request->only(['tab', 'period', 'date_from', 'date_to', 'sort']);
        if ($request->filled('filter_type')) {
            $preserve['type'] = $request->string('filter_type')->toString();
        }

        return redirect()
            ->route('izdevumi.index', ['car_id' => $expense->car_id] + $preserve)
            ->with('success', 'Izdevums atjaunināts.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeExpenseCar($expense);

        $carId = $expense->car_id;
        $expense->delete();

        return redirect()
            ->route('izdevumi.index', ['car_id' => $carId] + $request->only(['tab', 'type', 'period', 'date_from', 'date_to', 'sort']))
            ->with('success', 'Izdevums dzēsts.');
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
        ]);

        $car = Car::query()->where('id', $request->car_id)
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                    ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        $expenses = $car->expenses()->with('user')->orderByDesc('date')->orderByDesc('id')->get();

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $car->brand.'_'.$car->model) ?: 'auto';
        $filename = 'izdevumi_'.$safeName.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($expenses, $car) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Auto', $car->brand.' '.$car->model.' ('.$car->year.')']);
            fputcsv($out, []);

            fputcsv($out, [
                'Datums',
                'Tips',
                'Summa_EUR',
                'Nobraukums_km',
                'Apraksts',
                'Pievienoja',
            ]);

            foreach ($expenses as $e) {
                fputcsv($out, [
                    $e->date,
                    $e->type,
                    $e->amount,
                    $e->mileage ?? '',
                    $e->description ?? '',
                    optional($e->user)->username ?? '—',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeExpenseCar(Expense $expense): void
    {
        $hasAccess = $expense->car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);
    }
}
