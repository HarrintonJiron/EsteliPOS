<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario admin - updateOrCreate evita duplicados
        User::updateOrCreate(
            ['email' => 'admin@agroservicio.com'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => Hash::make('password')
            ]
        );

        // Usuario común
        User::updateOrCreate(
            ['email' => 'usuario@agroservicio.com'],
            [
                'name' => 'Usuario Ventas',
                'role' => 'user',
                'password' => Hash::make('password')
            ]
        );
    }
}
