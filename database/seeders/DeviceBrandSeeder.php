<?php

namespace Database\Seeders;

use App\Models\DeviceBrand;
use Illuminate\Database\Seeder;

class DeviceBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Anillo',
            'Argolla',
            'Aretes',
            'Brazalete',
            'Cadena',
            'Dije',
            'Esclava',
            'Pulsera',
            'Reloj',
            'Rosario',
            'Tobillera',
            'Otra joya',
        ];

        foreach ($brands as $brand) {
            DeviceBrand::firstOrCreate(
                ['name' => $brand],
                ['is_active' => true]
            );
        }
    }
}
