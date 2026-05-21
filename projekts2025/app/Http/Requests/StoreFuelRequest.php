<?php

namespace App\Http\Requests;

use App\Models\FuelEntry;
use Illuminate\Foundation\Http\FormRequest;
//centralizē degvielas ieraksta ievades validāciju 
// (lai noteikumi un kļūdu ziņas nebūtu izkaisītas pa kontrolieriem)
class StoreFuelRequest extends FormRequest
{
    // Atļauja degvielas ieraksta saglabāšanai
    public function authorize(): bool
    {
        return true;
    }

    // Validācijas noteikumi degvielas formai
    public function rules(): array
    {
        return [
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'date' => ['required', 'date'],
            'odometer_km' => [
                'required',
                'integer',
                'min:0',
                // Papildu pārbaude, lai odometrs neiet atpakaļ
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $carId = (int) $this->input('car_id');
                    if ($carId < 1) {
                        return;
                    }

                    // Salīdzina ar lielāko esošo odometra vērtību šim auto
                    $max = FuelEntry::query()->where('car_id', $carId)->max('odometer_km');
                    if ($max !== null && (int) $value < (int) $max) {
                        $fail('Odometrs nevar būt mazāks par lielāko esošo vērtību šim auto (šobrīd '.$max.' km).');
                    }
                },
            ],
            'liters' => ['required', 'numeric', 'min:0.01'],
            'total_eur' => ['required', 'numeric', 'min:0'],
            'fuel_type' => ['required', 'string', 'max:30'],
            'is_full_tank' => ['nullable', 'boolean'],
            'station' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    // Lietotājam saprotami kļūdu teksti
    public function messages(): array
    {
        return [
            'car_id.required' => 'Izvēlies auto.',
            'car_id.exists' => 'Izvēlētais auto neeksistē.',
            'date.required' => 'Norādi datumu.',
            'date.date' => 'Datuma formāts nav pareizs.',
            'odometer_km.required' => 'Norādi odometru (km).',
            'odometer_km.integer' => 'Odometrs jānorāda kā skaitlis.',
            'liters.required' => 'Norādi uzpildīto litru daudzumu.',
            'liters.numeric' => 'Litri jānorāda kā skaitlis.',
            'total_eur.required' => 'Norādi kopējo summu (€).',
            'total_eur.numeric' => 'Summa jānorāda kā skaitlis.',
            'fuel_type.required' => 'Izvēlies degvielas veidu.',
        ];
    }
}
