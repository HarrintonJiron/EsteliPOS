<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ConfigurationSeeder::class,
            AccountingSeeder::class,
            DeviceBrandSeeder::class,
            RepairServiceSeeder::class,
        ]);
    }
}
