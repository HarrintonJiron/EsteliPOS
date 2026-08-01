<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (! filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Los datos de demostración solo pueden cargarse en local o testing.');

            return;
        }

        $this->call(ComprehensiveDemoSeeder::class);
    }
}
