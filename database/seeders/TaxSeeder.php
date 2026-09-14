<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        Tax::updateOrCreate(
            ['code' => 'IVA-15'],
            ['name' => 'IVA General', 'rate' => 0.15, 'is_default' => true, 'is_active' => true]
        );

        Tax::updateOrCreate(
            ['code' => 'EXENTO'],
            ['name' => 'Exento de IVA', 'rate' => 0, 'is_default' => false, 'is_active' => true]
        );
    }
}
