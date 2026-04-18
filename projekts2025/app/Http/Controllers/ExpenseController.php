<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
            'type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'mileage' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Drošība: izdevumus drīkst pievienot tikai apstiprinātam auto
        $car = Car::where('id', $validated['car_id'])
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                  ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        Expense::create([
            'car_id' => $car->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'mileage' => $validated['mileage'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('izdevumi.index', ['car_id' => $car->id])
            ->with('success', 'Izdevums pievienots!');
    }

    public function destroy(Expense $expense)
    {
        $hasAccess = $expense->car->users()
            ->where('users.id', Auth::id())
            ->where('car_user.confirmed', true)
            ->exists();

        abort_unless($hasAccess, 403);

        $carId = $expense->car_id;
        $expense->delete();

        return redirect()->route('izdevumi.index', ['car_id' => $carId])
            ->with('success', 'Izdevums dzēsts.');
    }

    public function export(Request $request)
    {
        $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
        ]);

        $car = Car::where('id', $request->car_id)
            ->whereHas('users', function ($q) {
                $q->where('users.id', Auth::id())
                  ->where('car_user.confirmed', true);
            })
            ->firstOrFail();

        $expenses = $car->expenses()->with('user')->orderByDesc('date')->get();

        $filename = 'izdevumi_' . $car->brand . '_' . $car->model . '.csv';
        $filename = str_replace(' ', '_', $filename);

        $lines = [];
        $lines[] = "Datums,Tips,Summa(EUR),Nobraukums,Apraksts,Pievienoja";

        foreach ($expenses as $e) {
            $lines[] = sprintf(
                "%s,%s,%.2f,%s,%s,%s",
                $e->date,
                $this->csvSafe($e->type),
                $e->amount,
                $e->mileage ?? '',
                $this->csvSafe($e->description ?? ''),
                $this->csvSafe(optional($e->user)->username ?? '—')
            );
        }

        $csv = implode("\n", $lines);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function csvSafe(string $value): string
    {
        $v = str_replace('"', '""', $value);
        return '"' . $v . '"';
    }
}
