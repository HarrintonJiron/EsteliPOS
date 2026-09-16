<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\RepairService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JewelryRepairDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@agroservicio.com')->firstOrFail();

        $clients = collect([
            ['name' => 'María Fernanda López', 'phone' => '8888-1201', 'email' => 'maria.lopez@example.test'],
            ['name' => 'Carlos José Rivera', 'phone' => '8777-2312', 'email' => 'carlos.rivera@example.test'],
            ['name' => 'Ana Lucía Gutiérrez', 'phone' => '8666-3423', 'email' => 'ana.gutierrez@example.test'],
            ['name' => 'Roberto Antonio Mairena', 'phone' => '8555-4534', 'email' => 'roberto.mairena@example.test'],
            ['name' => 'Sofía Isabel Castillo', 'phone' => '8444-5645', 'email' => 'sofia.castillo@example.test'],
            ['name' => 'José Manuel Pérez', 'phone' => '8333-6756', 'email' => 'jose.perez@example.test'],
        ])->mapWithKeys(function (array $data): array {
            $client = Client::query()->firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'phone' => $data['phone']],
            );

            return [$data['email'] => $client];
        });

        $orders = [
            [
                'client' => 'maria.lopez@example.test', 'type' => 'Anillo', 'material' => 'Oro amarillo 14K',
                'finish' => 'Pulido brillante', 'identity' => '4.25 g · grabado MFL',
                'received' => 'Diamante central y 6 circones; piedra central floja',
                'request' => 'Reforzar el engaste y realizar limpieza completa.',
                'diagnosis' => 'Cuatro uñas presentan desgaste; piedras laterales firmes.',
                'status' => 'in_repair', 'priority' => 'high', 'days' => -2, 'delivery' => 2,
                'labor' => 450, 'advance' => 300, 'service' => 'Engaste de piedra',
            ],
            [
                'client' => 'carlos.rivera@example.test', 'type' => 'Cadena', 'material' => 'Plata 925',
                'finish' => 'Plata natural', 'identity' => '18.70 g · 55 cm',
                'received' => 'Cadena y broche suelto dentro de bolsa transparente',
                'request' => 'Soldar eslabón quebrado y cambiar broche.',
                'diagnosis' => 'Rotura limpia; no se observan otros eslabones debilitados.',
                'status' => 'ready', 'priority' => 'normal', 'days' => -4, 'delivery' => 0,
                'labor' => 350, 'advance' => 550, 'service' => 'Cambio de broche',
            ],
            [
                'client' => 'ana.gutierrez@example.test', 'type' => 'Argolla', 'material' => 'Oro blanco 10K',
                'finish' => 'Rodiado', 'identity' => '3.80 g · talla 6',
                'received' => 'Sin piedras; grabado interior A & J',
                'request' => 'Ampliar de talla 6 a talla 7 y renovar el rodinado.',
                'diagnosis' => null,
                'status' => 'diagnosing', 'priority' => 'normal', 'days' => -1, 'delivery' => 4,
                'labor' => 500, 'advance' => 250, 'service' => 'Ajuste de talla',
            ],
            [
                'client' => 'roberto.mairena@example.test', 'type' => 'Pulsera', 'material' => 'Oro amarillo 14K',
                'finish' => 'Mate y brillante', 'identity' => '12.40 g · 20 cm',
                'received' => 'Pulsera completa; argolla final deformada',
                'request' => 'Cambiar argolla, reforzar cierre y pulir.',
                'diagnosis' => 'Broche funcional; requiere argolla nueva de 5 mm.',
                'status' => 'waiting_parts', 'priority' => 'high', 'days' => -5, 'delivery' => 1,
                'labor' => 400, 'advance' => 200, 'service' => 'Cambio de argolla',
            ],
            [
                'client' => 'sofia.castillo@example.test', 'type' => 'Aretes', 'material' => 'Plata 925',
                'finish' => 'Rodinado', 'identity' => '6.10 g el par',
                'received' => 'Par de aretes con piedras azules; falta una mariposa',
                'request' => 'Limpieza, pulido y reposición de mariposa.',
                'diagnosis' => null,
                'status' => 'received', 'priority' => 'low', 'days' => 0, 'delivery' => 5,
                'labor' => 200, 'advance' => 0, 'service' => 'Limpieza y pulido',
            ],
            [
                'client' => 'jose.perez@example.test', 'type' => 'Dije', 'material' => 'Oro amarillo 18K',
                'finish' => 'Brillante', 'identity' => '7.35 g · inicial JP',
                'received' => 'Dije sin cadena; grabado frontal conservado',
                'request' => 'Soldar argolla superior y limpieza final.',
                'diagnosis' => 'Argolla con desgaste; soldadura y refuerzo completados.',
                'status' => 'delivered', 'priority' => 'normal', 'days' => -10, 'delivery' => -6,
                'labor' => 300, 'advance' => 600, 'service' => 'Soldadura',
            ],
        ];

        DB::transaction(function () use ($orders, $clients, $user): void {
            foreach ($orders as $data) {
                $client = $clients->get($data['client']);
                $service = RepairService::query()->where('name', $data['service'])->firstOrFail();
                $total = (float) $data['labor'] + (float) $service->price;

                $order = RepairOrder::query()->firstOrCreate(
                    [
                        'client_id' => $client->id,
                        'device_brand' => $data['type'],
                        'device_imei' => $data['identity'],
                    ],
                    [
                        'order_number' => NumberSequence::getNext('reparacion'),
                        'client_name' => $client->name,
                        'client_phone' => $client->phone,
                        'client_email' => $client->email,
                        'device_model' => $data['material'],
                        'device_color' => $data['finish'],
                        'device_password' => null,
                        'lock_type' => 'none',
                        'accessories' => $data['received'],
                        'problem_description' => $data['request'],
                        'diagnosis' => $data['diagnosis'],
                        'repair_notes' => 'Dato de demostración para pruebas del taller de joyería.',
                        'status' => $data['status'],
                        'priority' => $data['priority'],
                        'technician_id' => $user->id,
                        'user_id' => $user->id,
                        'received_date' => now()->addDays($data['days'])->toDateString(),
                        'received_time' => '09:30',
                        'estimated_date' => now()->addDays($data['delivery'])->toDateString(),
                        'estimated_delivery_time' => '16:00',
                        'delivered_date' => $data['status'] === 'delivered' ? now()->addDays($data['delivery'])->toDateString() : null,
                        'delivered_time' => $data['status'] === 'delivered' ? '15:20' : null,
                        'labor_cost' => $data['labor'],
                        'parts_cost' => $service->price,
                        'total' => $total,
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                        'advance_payment' => min($data['advance'], $total),
                        'payment_type' => 'cash',
                        'payment_status' => $data['advance'] >= $total ? 'paid' : ($data['advance'] > 0 ? 'partial' : 'pending'),
                        'warranty_enabled' => true,
                    ],
                );

                RepairOrderItem::query()->firstOrCreate(
                    ['repair_order_id' => $order->id, 'service_id' => $service->id],
                    [
                        'description' => $service->name,
                        'quantity' => 1,
                        'price' => $service->price,
                        'subtotal' => $service->price,
                        'item_type' => 'service',
                        'device_brand' => $data['type'],
                    ],
                );
            }
        });
    }
}
