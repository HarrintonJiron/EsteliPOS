<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario admin - updateOrCreate evita duplicados
        $admin = User::updateOrCreate(
            ['email' => 'admin@agroservicio.com'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => Hash::make('password')
            ]
        );
        if ($adminRole = Role::where('slug', 'admin')->first()) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Usuario común
        $user = User::updateOrCreate(
            ['email' => 'usuario@agroservicio.com'],
            [
                'name' => 'Usuario Ventas',
                'role' => 'user',
                'password' => Hash::make('password')
            ]
        );
        if ($userRole = Role::where('slug', 'user')->first()) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        }
    }
}
