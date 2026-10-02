<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\PhoneTradeIn;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class AllModulesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Los datos de demostración solo pueden cargarse en local o testing.');

            return;
        }

        $user = User::query()->firstOrFail();
        $clients = Client::query()->orderBy('id')->take(12)->get();
        $products = Product::query()->where('status', 'active')->orderBy('id')->take(20)->get();
        $branch = Branch::query()->first();
        $warehouse = Warehouse::query()->first();

        if ($clients->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('Se requieren clientes y productos antes de completar los módulos demo.');

            return;
        }

        $this->seedReservations($user, $clients, $products, $branch, $warehouse);
        $this->seedShipments($user, $clients);
        $this->seedTradeIns($products);
        $this->seedHumanResources($user);
        $this->seedSupplierPayments($user);
        $this->seedJewelryWorkshop($user, $clients);

        $this->command?->info('Datos complementarios de todos los módulos cargados correctamente.');
    }

    private function seedReservations(User $user, $clients, $products, ?Branch $branch, ?Warehouse $warehouse): void
    {
        $statuses = ['active', 'active', 'completed', 'cancelled', 'expired'];

        for ($i = 1; $i <= 12; $i++) {
            $product = $products[($i - 1) % $products->count()];
            $quantity = ($i % 3) + 1;
            $total = round((float) $product->sale_price * $quantity, 2);
            $deposit = round($total * [0.25, 0.5, 0.75][($i - 1) % 3], 2);
            $reservedAt = now()->subDays(24 - ($i * 2));

            $reservation = Reservation::query()->firstOrCreate(
                ['number' => 'APT-DEMO-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'client_id' => $clients[($i - 1) % $clients->count()]->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch?->id,
                    'warehouse_id' => $warehouse?->id,
                    'reserved_at' => $reservedAt,
                    'expires_at' => $reservedAt->copy()->addDays(30),
                    'total' => $total,
                    'deposit' => $deposit,
                    'status' => $statuses[($i - 1) % count($statuses)],
                    'notes' => 'Apartado de demostración para control de calidad',
                ],
            );

            $reservation->items()->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $quantity, 'unit_price' => $product->sale_price, 'subtotal' => $total],
            );

            $reservation->payments()->firstOrCreate(
                ['reference_number' => 'PAGO-APT-DEMO-'.$i],
                [
                    'user_id' => $user->id,
                    'type' => 'payment',
                    'amount' => round($total * 0.1, 2),
                    'payment_method' => ['cash', 'transfer', 'card'][($i - 1) % 3],
                    'notes' => 'Abono de demostración',
                    'paid_at' => $reservedAt,
                ],
            );
        }
    }

    private function seedShipments(User $user, $clients): void
    {
        $statuses = ['pending', 'prepared', 'shipped', 'delivered', 'cancelled'];
        $departments = ['Estelí', 'Madriz', 'Nueva Segovia', 'Matagalpa', 'León'];
        $sales = Sale::query()->where('status', 'completed')->orderByDesc('date')->take(15)->get();

        for ($i = 1; $i <= 15; $i++) {
            $status = $statuses[($i - 1) % count($statuses)];
            $date = now()->subDays(15 - $i);
            Shipment::query()->firstOrCreate(
                ['number' => 'ENV-DEMO-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'sale_id' => $sales->isEmpty() ? null : $sales[($i - 1) % $sales->count()]->id,
                    'client_id' => $clients[($i - 1) % $clients->count()]->id,
                    'user_id' => $user->id,
                    'recipient_name' => $clients[($i - 1) % $clients->count()]->name,
                    'recipient_phone' => $clients[($i - 1) % $clients->count()]->phone,
                    'department' => $departments[($i - 1) % count($departments)],
                    'municipality' => 'Municipio de prueba '.$i,
                    'address' => 'Dirección demostrativa para entrega '.$i,
                    'reference' => 'Frente al punto de referencia '.$i,
                    'carrier' => ['Moto propia', 'Cargotrans', 'Bus interlocal'][($i - 1) % 3],
                    'tracking_number' => 'TRACK-DEMO-'.$i,
                    'shipping_cost' => 60 + ($i * 10),
                    'status' => $status,
                    'shipped_at' => in_array($status, ['shipped', 'delivered'], true) ? $date : null,
                    'delivered_at' => $status === 'delivered' ? $date->copy()->addDay() : null,
                    'notes' => 'Envío de demostración',
                ],
            );
        }
    }

    private function seedTradeIns($products): void
    {
        $sales = Sale::query()->where('status', 'completed')->orderByDesc('id')->take(8)->get();
        foreach ($sales as $index => $sale) {
            PhoneTradeIn::query()->firstOrCreate(
                ['sale_id' => $sale->id],
                [
                    'product_id' => $products[$index % $products->count()]->id,
                    'trade_in_value' => 800 + ($index * 250),
                    'was_returning_phone' => $index % 2 === 0,
                    'notes' => 'Retoma demostrativa '.$index,
                ],
            );
        }
    }

    private function seedHumanResources(User $user): void
    {
        $employees = Employee::query()->where('is_active', true)->orderBy('id')->take(12)->get();
        foreach ($employees as $employeeIndex => $employee) {
            for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
                $date = now()->subDays($daysAgo);
                AttendanceRecord::query()->firstOrCreate(
                    ['employee_id' => $employee->id, 'work_date' => $date->toDateString()],
                    [
                        'status' => $daysAgo % 17 === 0 ? 'absent' : ($daysAgo % 7 === 0 ? 'late' : 'present'),
                        'check_in' => $daysAgo % 17 === 0 ? null : ($daysAgo % 7 === 0 ? '08:18' : '07:55'),
                        'check_out' => $daysAgo % 17 === 0 ? null : '17:05',
                        'notes' => 'Registro de demostración',
                        'recorded_by' => $user->id,
                    ],
                );
            }

            if (! PerformanceEvaluation::query()->where('employee_id', $employee->id)->where('period', 'DEMO-2026')->exists()) {
                PerformanceEvaluation::query()->create([
                    'employee_id' => $employee->id,
                    'evaluation_date' => now()->subDays($employeeIndex)->toDateString(),
                    'score' => 70 + (($employeeIndex * 3) % 30),
                    'period' => 'DEMO-2026',
                    'strengths' => 'Atención al cliente, puntualidad y cumplimiento de procesos.',
                    'improvements' => 'Continuar reforzando el conocimiento del catálogo.',
                    'comments' => 'Evaluación creada para demostración del módulo.',
                    'evaluator_id' => $user->id,
                ]);
            }
        }
    }

    private function seedSupplierPayments(User $user): void
    {
        Purchase::query()->with('supplier')->whereNotNull('supplier_id')->orderByDesc('date')->take(15)->get()
            ->each(function (Purchase $purchase, int $index) use ($user): void {
                SupplierPayment::query()->firstOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'supplier_id' => $purchase->supplier_id,
                        'user_id' => $user->id,
                        'amount' => min((float) $purchase->total, 500 + ($index * 175)),
                        'payment_type' => ['cash', 'transfer', 'check'][$index % 3],
                        'reference' => 'PAGO-PRV-DEMO-'.$purchase->id,
                        'paid_at' => now()->subDays($index),
                        'status' => 'registered',
                    ],
                );
            });
    }

    private function seedJewelryWorkshop(User $user, $clients): void
    {
        $jobs = ['Ajuste de talla', 'Soldadura de cadena', 'Montaje de piedra', 'Pulido profesional', 'Cambio de broche', 'Limpieza ultrasónica', 'Reparación de argolla', 'Grabado personalizado'];
        foreach ($jobs as $index => $job) {
            $i = $index + 1;
            $labor = 250 + ($i * 90);
            RepairOrder::query()->firstOrCreate(
                ['order_number' => 'JOY-DEMO-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'order_type' => 'jewelry',
                    'client_id' => $clients[$index % $clients->count()]->id,
                    'client_name' => $clients[$index % $clients->count()]->name,
                    'client_phone' => $clients[$index % $clients->count()]->phone,
                    'device_brand' => ['Anillo', 'Cadena', 'Aretes', 'Pulsera'][$index % 4],
                    'device_model' => ['Oro 14K', 'Plata 925', 'Acero', 'Oro 18K'][$index % 4],
                    'problem_description' => $job,
                    'diagnosis' => 'Evaluación de joyería para demostración.',
                    'status' => ['received', 'diagnosing', 'in_repair', 'ready', 'delivered'][$index % 5],
                    'priority' => ['low', 'normal', 'high'][$index % 3],
                    'user_id' => $user->id,
                    'received_date' => now()->subDays(10 - $i)->toDateString(),
                    'received_time' => '09:00',
                    'labor_cost' => $labor,
                    'parts_cost' => 0,
                    'total' => $labor,
                    'advance_payment' => round($labor * 0.4, 2),
                    'payment_type' => 'cash',
                    'payment_status' => 'partial',
                ],
            );
        }
    }
}
