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
        $this->call([
            ConfigurationSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            ClientSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,
            DemoDataSeeder::class,
            CreditHeavySeeder::class,
            AgroProductSeeder::class,
            RichDemoSeeder::class,
        ]);
    }
}
