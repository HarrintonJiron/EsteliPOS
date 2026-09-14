<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Administrador del sistema', 'is_system' => true],
            ['name' => 'Vendedor', 'slug' => 'vendedor', 'description' => 'Encargado de ventas', 'is_system' => true],
            ['name' => 'Contable', 'slug' => 'contable', 'description' => 'Encargado de finanzas', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
