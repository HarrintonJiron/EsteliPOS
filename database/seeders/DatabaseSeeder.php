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

        if (! config('app.seed_demo_data')) {
            return;
        }

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Los datos de demostración solo pueden cargarse en local o testing.');

            return;
        }

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            ClientSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,
            DemoDataSeeder::class,
            CreditHeavySeeder::class,
            AgroProductSeeder::class,
        ]);
    }
}
