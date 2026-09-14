<?php

namespace Database\Seeders;

use App\Models\RepairService;
use Illuminate\Database\Seeder;

class RepairServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cambio de Pantalla',
                'description' => 'Reemplazo de pantalla LCD/AMOLED',
                'price' => 800.00,
            ],
            [
                'name' => 'Cambio de Batería',
                'description' => 'Reemplazo de batería interna',
                'price' => 400.00,
            ],
            [
                'name' => 'Formateo de Software',
                'description' => 'Restauración de fábrica y configuración',
                'price' => 300.00,
            ],
            [
                'name' => 'Limpieza de Puertos de Carga',
                'description' => 'Limpieza y reparación de puerto de carga',
                'price' => 250.00,
            ],
            [
                'name' => 'Cambio de Conector de Carga',
                'description' => 'Reemplazo completo del conector',
                'price' => 350.00,
            ],
            [
                'name' => 'Reparación de Altavoz',
                'description' => 'Reemplazo o reparación de altavoz',
                'price' => 250.00,
            ],
            [
                'name' => 'Cambio de Micrófono',
                'description' => 'Reemplazo de micrófono',
                'price' => 300.00,
            ],
            [
                'name' => 'Reparación de Cámara',
                'description' => 'Reemplazo de cámara frontal o trasera',
                'price' => 500.00,
            ],
            [
                'name' => 'Diagnóstico Técnico',
                'description' => 'Inspección completa del equipo',
                'price' => 100.00,
            ],
            [
                'name' => 'Desbloqueo de Contraseña',
                'description' => 'Eliminación de contraseña/patrón',
                'price' => 200.00,
            ],
            [
                'name' => 'Cambio de Sensor de Huella',
                'description' => 'Reemplazo de sensor de huella dactilar',
                'price' => 400.00,
            ],
            [
                'name' => 'Cambio de Face ID',
                'description' => 'Reemplazo de módulo Face ID',
                'price' => 600.00,
            ],
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
