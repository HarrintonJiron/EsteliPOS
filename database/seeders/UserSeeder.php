<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrador',
                'email' => 'admin@agroservicio.com',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'name' => 'Vendedor Demo',
                'email' => 'vendedor@agroservicio.com',
                'role' => 'user',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
