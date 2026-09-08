<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::insert([
            ['name' => 'Juan Pérez', 'phone' => '555-0001', 'email' => 'juan@example.com', 'address' => 'Calle 1, Managua'],
            ['name' => 'Maria López', 'phone' => '555-0002', 'email' => 'maria@example.com', 'address' => 'Calle 2, León'],
        ]);
    }
}