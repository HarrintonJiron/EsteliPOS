<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::insert([
            ['name' => 'Carlos Gómez', 'position' => 'Encargado de almacén', 'salary' => 450.00, 'phone' => '555-1010', 'address' => 'Calle A'],
            ['name' => 'Ana Torres', 'position' => 'Vendedora', 'salary' => 400.00, 'phone' => '555-1020', 'address' => 'Calle B'],
        ]);
    }
}