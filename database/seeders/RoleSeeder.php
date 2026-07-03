<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            ['name' => 'Admin', 'description' => 'Administrador del sistema'],
            ['name' => 'Vendedor', 'description' => 'Encargado de ventas'],
            ['name' => 'Contable', 'description' => 'Encargado de finanzas'],
        ]);
    }
}