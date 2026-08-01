<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'description' => 'Administrador del sistema', 'slug' => 'admin', 'is_system' => true],
            ['name' => 'Vendedor', 'description' => 'Encargado de ventas', 'slug' => 'vendedor', 'is_system' => true],
            ['name' => 'Contable', 'description' => 'Encargado de finanzas', 'slug' => 'contable', 'is_system' => true],
        ];

        foreach ($roles as $roleData) {
            $data = [
                'name' => $roleData['name'],
                'description' => $roleData['description'],
            ];

            if (Schema::hasColumn('roles', 'slug')) {
                $data['slug'] = $roleData['slug'];
            }

            if (Schema::hasColumn('roles', 'is_system')) {
                $data['is_system'] = $roleData['is_system'];
            }

            Role::updateOrCreate(['name' => $roleData['name']], $data);
        }
    }
}
