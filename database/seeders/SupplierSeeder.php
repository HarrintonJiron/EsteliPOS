<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::insert([
            ['name' => 'AgroDistribuidor S.A.', 'phone' => '555-1234', 'email' => 'contacto@agrodistribuidor.com', 'address' => 'Km 5 Carretera Norte'],
            ['name' => 'Insumos del Campo', 'phone' => '555-5678', 'email' => 'ventas@insumoscampo.com', 'address' => 'Av. Central #120'],
        ]);
    }
}