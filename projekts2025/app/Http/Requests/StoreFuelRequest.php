<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'date' => ['required', 'date'],
            'odometer_km' => ['required', 'integer', 'min:0'],
            'liters' => ['required', 'numeric', 'min:0.01'],
            'total_eur' => ['required', 'numeric', 'min:0'],
            'fuel_type' => ['required', 'string', 'max:30'],
            'is_full_tank' => ['nullable', 'boolean'],
            'station' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

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
