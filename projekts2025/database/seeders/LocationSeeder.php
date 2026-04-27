<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::create([
            'name' => 'Circle K Riga',
            'type' => 'gas_station',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
            'address' => 'Brīvības iela 90, Riga, Latvia',
            'info' => null,
        ]);

        Location::create([
            'name' => 'Auto Serviss Liepāja',
            'type' => 'service_center',
            'latitude' => 56.5047,
            'longitude' => 21.0108,
            'address' => 'Ganību iela 123, Liepāja, Latvia',
            'info' => null,
        ]);

    }
}
