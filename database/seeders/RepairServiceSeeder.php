<?php

namespace Database\Seeders;

use App\Models\RepairService;
use Illuminate\Database\Seeder;

class RepairServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Limpieza y pulido', 'description' => 'Limpieza profesional y recuperación de brillo', 'price' => 200.00],
            ['name' => 'Soldadura', 'description' => 'Unión o reparación de pieza quebrada', 'price' => 300.00],
            ['name' => 'Ajuste de talla', 'description' => 'Ampliación o reducción de talla de anillo', 'price' => 350.00],
            ['name' => 'Engaste de piedra', 'description' => 'Ajuste o colocación de piedra', 'price' => 300.00],
            ['name' => 'Reposición de piedra', 'description' => 'Suministro y colocación de piedra', 'price' => 500.00],
            ['name' => 'Cambio de broche', 'description' => 'Reemplazo de broche en cadena o pulsera', 'price' => 250.00],
            ['name' => 'Cambio de argolla', 'description' => 'Reemplazo de argolla o unión', 'price' => 200.00],
            ['name' => 'Baño de oro', 'description' => 'Recubrimiento y acabado en baño de oro', 'price' => 600.00],
            ['name' => 'Rodinado', 'description' => 'Aplicación de rodio y acabado', 'price' => 600.00],
            ['name' => 'Grabado', 'description' => 'Grabado personalizado en la pieza', 'price' => 250.00],
            ['name' => 'Enderezado', 'description' => 'Corrección de deformaciones de la joya', 'price' => 250.00],
            ['name' => 'Evaluación del joyero', 'description' => 'Inspección de material, piedras, uniones y estado', 'price' => 100.00],
        ];

        foreach ($services as $service) {
            RepairService::firstOrCreate(
                ['name' => $service['name']],
                [
                    'description' => $service['description'],
                    'price' => $service['price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
