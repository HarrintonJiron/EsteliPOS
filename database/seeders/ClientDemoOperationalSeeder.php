<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Arqueo;
use App\Models\Bonus;
use App\Models\Branch;
use App\Models\CajaSession;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\CreditPayment;
use App\Models\Deduction;
use App\Models\DeviceBrand;
use App\Models\Employee;
use App\Models\FiscalPeriod;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\NumberSequence;
use App\Models\OperationalExpense;
use App\Models\Payroll;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\ProformaDetail;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\RepairOrderItem;
use App\Models\RepairService;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStock;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PayrollService;
use App\Services\PeriodClosingService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Throwable;

class ClientDemoOperationalSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! Product::query()->exists()) {
            $this->command?->warn('No hay productos; omitiendo módulos operativos de demostración.');

            return;
        }

        mt_srand(20260819);

        $this->seedShelvesAndLocations();
        $this->seedWholesalePrices();
        $this->seedRepairCatalog();
        $this->seedBranches();
        $this->seedEmployeesAndPayroll();
        $this->assignEmployeesToBranches();
        $this->seedProformas();
        $this->seedRepairOrders();
        $this->seedInventoryAdjustments();
        $this->seedWarehouseTransfers();
        $this->seedCashAndExpenses();
        $this->seedAccounting();
        $this->seedAccountingDemonstration();
    }

    private function seedBranches(): void
    {
        $this->seedCostCenters();

        $principal = Warehouse::query()->where('code', 'BOD-01')->first();
        $materiales = Warehouse::query()->where('code', 'BOD-02')->first();
        $patio = Warehouse::query()->where('code', 'BOD-03')->first();
        $mostrador = CostCenter::query()->where('code', 'CC-01')->first();
        $bodega = CostCenter::query()->where('code', 'CC-02')->first();
        $admin = CostCenter::query()->where('code', 'CC-04')->first();
        $patioCc = CostCenter::query()->where('code', 'CC-05')->first();

        foreach ([
            ['code' => 'SUC-01', 'name' => 'Casa matriz Centro', 'type' => 'matriz', 'city' => 'Estelí', 'address' => 'Costado norte del mercado, Estelí', 'phone' => '2713-4500', 'manager_name' => 'Roberto Cano', 'warehouse_id' => $principal?->id, 'cost_center_id' => $mostrador?->id, 'share_percent' => 48, 'notes' => 'Punto de venta principal, caja y mostrador.'],
            ['code' => 'SUC-02', 'name' => 'Patio de materiales', 'type' => 'patio', 'city' => 'Estelí', 'address' => 'Salida a Condega, zona industrial', 'phone' => '2713-4510', 'manager_name' => 'Adán Gámez', 'warehouse_id' => $patio?->id, 'cost_center_id' => $patioCc?->id, 'share_percent' => 18, 'notes' => 'Láminas, varilla, cemento y patio.'],
            ['code' => 'SUC-03', 'name' => 'Bodega industrial', 'type' => 'bodega', 'city' => 'Estelí', 'address' => 'Carretera a León, km 1.5', 'phone' => '2713-4520', 'manager_name' => 'Elmer Zeledón', 'warehouse_id' => $materiales?->id, 'cost_center_id' => $bodega?->id, 'share_percent' => 14, 'notes' => 'Almacén mayorista y despacho a obra.'],
            ['code' => 'SUC-04', 'name' => 'Sucursal Condega', 'type' => 'sucursal', 'city' => 'Condega', 'address' => 'Frente al parque central, Condega', 'phone' => '2719-2210', 'manager_name' => 'Karina López', 'warehouse_id' => null, 'cost_center_id' => $admin?->id, 'share_percent' => 12, 'notes' => 'Atención a constructoras del norte.'],
            ['code' => 'SUC-05', 'name' => 'Sucursal Pueblo Nuevo', 'type' => 'sucursal', 'city' => 'Pueblo Nuevo', 'address' => 'Entrada principal, Pueblo Nuevo', 'phone' => '2719-3310', 'manager_name' => 'Xiomara Pérez', 'warehouse_id' => null, 'cost_center_id' => $admin?->id, 'share_percent' => 8, 'notes' => 'Mostrador rural y despacho a fincas.'],
        ] as $branch) {
            Branch::query()->updateOrCreate(
                ['code' => $branch['code']],
                $branch + ['is_active' => true]
            );
        }
    }

    private function assignEmployeesToBranches(): void
    {
        $branches = Branch::query()->orderBy('id')->get();
        if ($branches->isEmpty()) {
            return;
        }

        Employee::query()->orderBy('id')->get()->each(function (Employee $employee, int $index) use ($branches): void {
            if ($employee->branch_id) {
                return;
            }

            $employee->forceFill([
                'branch_id' => $branches[$index % $branches->count()]->id,
            ])->saveQuietly();
        });
    }

    private function seedShelvesAndLocations(): void
    {
        $principal = Warehouse::query()->where('code', 'BOD-01')->first();
        $materiales = Warehouse::query()->where('code', 'BOD-02')->first();
        $patio = Warehouse::query()->where('code', 'BOD-03')->first();

        $shelves = [
            [$principal, 'A1', 'Tornillería'],
            [$principal, 'A2', 'Herramientas de mano'],
            [$principal, 'B1', 'Pintura'],
            [$principal, 'B2', 'Electricidad'],
            [$principal, 'C1', 'Mostrador / miscelánea'],
            [$principal, 'C2', 'Cerrajería y seguridad'],
            [$materiales, 'M1', 'Cemento y agregados'],
            [$materiales, 'M2', 'Varilla y mallas'],
            [$materiales, 'M3', 'PVC y plomería'],
            [$patio, 'P1', 'Láminas y techos'],
            [$patio, 'P2', 'Jardinería y patio'],
        ];

        foreach ($shelves as [$warehouse, $code, $name]) {
            if (! $warehouse) {
                continue;
            }

            WarehouseShelf::query()->firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }

        $aisleByCategory = [
            'Ferretería' => 'A2',
            'Construcción' => 'M1',
            'Plomería' => 'M3',
            'Electricidad' => 'B2',
            'Pintura' => 'B1',
            'Jardinería' => 'P2',
            'Limpieza' => 'C1',
            'Soldadura' => 'A2',
            'Seguridad' => 'C2',
            'Cerrajería' => 'C2',
            'Techos' => 'P1',
            'Miscelánea' => 'C1',
        ];

        Product::query()->with('category')->orderBy('id')->get()->each(function (Product $product) use ($aisleByCategory): void {
            $aisle = $aisleByCategory[$product->category?->name] ?? 'C1';
            if (! $product->location) {
                $product->forceFill(['location' => $aisle])->saveQuietly();
            }

            WarehouseStock::query()
                ->where('product_id', $product->id)
                ->whereNull('aisle')
                ->update(['aisle' => $aisle]);
        });
    }

    private function seedWholesalePrices(): void
    {
        $wholesale = PriceList::query()->where('code', 'MAYOR')->first();
        if (! $wholesale) {
            return;
        }

        if (PriceListItem::query()->where('price_list_id', $wholesale->id)->exists()) {
            return;
        }

        Product::query()->orderBy('id')->get()->each(function (Product $product) use ($wholesale): void {
            PriceListItem::query()->updateOrCreate(
                [
                    'price_list_id' => $wholesale->id,
                    'product_id' => $product->id,
                    'unit_id' => $product->base_unit_id,
                ],
                [
                    'unit_price' => round((float) $product->sale_price * 0.88, 2),
                    'min_quantity' => 6,
                ]
            );
        });
    }

    private function seedRepairCatalog(): void
    {
        foreach ([
            'DeWalt', 'Bosch', 'Makita', 'Stanley', 'Truper',
            'Black+Decker', 'Milwaukee', 'Ingco', 'Pretul', 'Irwin',
        ] as $brand) {
            DeviceBrand::query()->firstOrCreate(
                ['name' => $brand],
                ['is_active' => true]
            );
        }

        foreach ([
            ['name' => 'Duplicado de llave', 'description' => 'Copia de llave de casa, candado o vehículo', 'price' => 80],
            ['name' => 'Afilado de machete', 'description' => 'Afilado y balanceo de machete o cuchilla', 'price' => 50],
            ['name' => 'Afilado de cuchilla', 'description' => 'Afilado de cuchillas de jardín o cocina', 'price' => 40],
            ['name' => 'Diagnóstico de herramienta eléctrica', 'description' => 'Revisión de taladro, esmeril o sierra', 'price' => 150],
            ['name' => 'Cambio de carbones', 'description' => 'Reemplazo de carbones en motor de herramienta', 'price' => 280],
            ['name' => 'Reparación de taladro', 'description' => 'Reparación de chuck, interruptor o motor', 'price' => 650],
            ['name' => 'Reparación de esmeril', 'description' => 'Cambio de guardas, switch o rodamientos', 'price' => 550],
            ['name' => 'Instalación de cerradura', 'description' => 'Colocación de cerradura de pomo o embutir', 'price' => 350],
            ['name' => 'Soldadura menor', 'description' => 'Puntos de soldadura en portones o herramientas', 'price' => 200],
            ['name' => 'Corte de vidrio', 'description' => 'Corte a medida de vidrio o espejo pequeño', 'price' => 120],
            ['name' => 'Armado de llave de tubo', 'description' => 'Ajuste y armado de dados o extensiones', 'price' => 90],
            ['name' => 'Cambio de manguera de jardín', 'description' => 'Empalme y cambio de conector de manguera', 'price' => 70],
        ] as $service) {
            RepairService::query()->firstOrCreate(
                ['name' => $service['name']],
                ['description' => $service['description'], 'price' => $service['price'], 'is_active' => true]
            );
        }
    }

    private function seedEmployeesAndPayroll(): void
    {
        if (Employee::query()->exists()) {
            return;
        }

        $user = User::query()->first();
        $employees = [
            ['name' => 'Roberto Cano', 'cedula' => '161-120175-0001A', 'position' => 'Gerente de tienda', 'salary' => 18500, 'phone' => '8888-2101', 'address' => 'Barrio El Rosario, Estelí', 'hire_date' => '2019-03-12', 'bank_account' => '1002003001', 'bank_name' => 'Banpro'],
            ['name' => 'Marisol Vargas', 'cedula' => '161-080288-0002B', 'position' => 'Cajera', 'salary' => 9500, 'phone' => '8888-2102', 'address' => 'Oscar Gamez, Estelí', 'hire_date' => '2021-06-01', 'bank_account' => '1002003002', 'bank_name' => 'Lafise'],
            ['name' => 'Elmer Zeledón', 'cedula' => '161-150182-0003C', 'position' => 'Bodeguero', 'salary' => 9000, 'phone' => '8888-2103', 'address' => 'Villa Libertad, Estelí', 'hire_date' => '2020-11-15', 'bank_account' => '1002003003', 'bank_name' => 'BAC'],
            ['name' => 'Karina López', 'cedula' => '161-220495-0004D', 'position' => 'Vendedora de mostrador', 'salary' => 8500, 'phone' => '8888-2104', 'address' => 'El Calvario, Estelí', 'hire_date' => '2022-02-08', 'bank_account' => '1002003004', 'bank_name' => 'Banpro'],
            ['name' => 'Donaldo Ruiz', 'cedula' => '161-040178-0005E', 'position' => 'Técnico de herramientas', 'salary' => 11000, 'phone' => '8888-2105', 'address' => 'Salida a Condega', 'hire_date' => '2018-09-20', 'bank_account' => '1002003005', 'bank_name' => 'Lafise'],
            ['name' => 'Xiomara Pérez', 'cedula' => '161-170390-0006F', 'position' => 'Asistente administrativa', 'salary' => 10000, 'phone' => '8888-2106', 'address' => 'Centro, Estelí', 'hire_date' => '2021-01-10', 'bank_account' => '1002003006', 'bank_name' => 'BAC'],
            ['name' => 'Adán Gámez', 'cedula' => '161-280165-0007G', 'position' => 'Auxiliar de patio', 'salary' => 8000, 'phone' => '8888-2107', 'address' => 'Barrio 14 de Abril', 'hire_date' => '2023-04-03', 'bank_account' => '1002003007', 'bank_name' => 'Banpro'],
            ['name' => 'Lucía Blandón', 'cedula' => '161-090398-0008H', 'position' => 'Auxiliar de caja', 'salary' => 8200, 'phone' => '8888-2108', 'address' => 'San Nicolás', 'hire_date' => '2024-08-12', 'bank_account' => '1002003008', 'bank_name' => 'Lafise'],
        ];

        foreach ($employees as $employee) {
            Employee::query()->create([
                ...$employee,
                'contract_type' => 'full_time',
                'payment_frequency' => 'monthly',
                'is_active' => true,
                'emergency_contact' => 'Familiar de '.$employee['name'],
                'emergency_phone' => '2713-4599',
            ]);
        }

        $staff = Employee::query()->orderBy('id')->get();
        $cashier = $staff[1];
        $warehouse = $staff[2];
        $technician = $staff[4];
        $admin = $staff[5];

        $june = now()->subMonths(2)->startOfMonth();
        $july = now()->subMonth()->startOfMonth();

        Bonus::query()->create([
            'employee_id' => $cashier->id,
            'type' => 'sales',
            'amount' => 800,
            'date' => $june->copy()->day(28),
            'reason' => 'Meta de mostrador junio',
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => $june->copy()->day(28),
        ]);
        Bonus::query()->create([
            'employee_id' => $technician->id,
            'type' => 'productivity',
            'amount' => 600,
            'date' => $july->copy()->day(20),
            'reason' => 'Reparaciones entregadas a tiempo',
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => $july->copy()->day(20),
        ]);
        Bonus::query()->create([
            'employee_id' => $admin->id,
            'type' => 'attendance',
            'amount' => 400,
            'date' => $july->copy()->day(30),
            'reason' => 'Asistencia perfecta',
            'status' => 'pending',
        ]);

        Deduction::query()->create([
            'employee_id' => $warehouse->id,
            'type' => 'uniform',
            'amount' => 250,
            'date' => $june->copy()->day(10),
            'reason' => 'Camisa y gorra de ferretería',
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => $june->copy()->day(10),
        ]);
        Deduction::query()->create([
            'employee_id' => $cashier->id,
            'type' => 'late',
            'amount' => 100,
            'date' => $july->copy()->day(8),
            'reason' => 'Tardanza en apertura de caja',
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => $july->copy()->day(8),
        ]);

        Loan::query()->create([
            'employee_id' => $warehouse->id,
            'type' => 'advance',
            'amount' => 3000,
            'monthly_payment' => 750,
            'months' => 4,
            'start_date' => $july->toDateString(),
            'end_date' => $july->copy()->addMonths(3)->endOfMonth()->toDateString(),
            'remaining_balance' => 3000,
            'reason' => 'Anticipo para materiales de vivienda',
            'status' => 'active',
            'approved_by' => $user?->id,
            'approved_at' => $july,
        ]);

        LeaveRequest::query()->create([
            'employee_id' => $technician->id,
            'type' => 'vacation',
            'start_date' => $july->copy()->day(14),
            'end_date' => $july->copy()->day(16),
            'days' => 3,
            'reason' => 'Vacaciones cortas de media semana',
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => $july->copy()->day(10),
        ]);
        LeaveRequest::query()->create([
            'employee_id' => $cashier->id,
            'type' => 'sick',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 2,
            'reason' => 'Consulta médica',
            'status' => 'pending',
        ]);
        LeaveRequest::query()->create([
            'employee_id' => $admin->id,
            'type' => 'personal',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'days' => 1,
            'reason' => 'Trámite en alcaldía',
            'status' => 'pending',
        ]);

        $payroll = app(PayrollService::class);
        if ($user) {
            try {
                $payroll->payPayroll($june, $june->copy()->endOfMonth(), $user->id);
                $payroll->payPayroll($july, $july->copy()->endOfMonth(), $user->id);
            } catch (Throwable $exception) {
                $this->command?->warn('No se pudo generar la planilla de demostración: '.$exception->getMessage());
            }
        }
    }

    private function seedProformas(): void
    {
        if (Proforma::query()->exists()) {
            return;
        }

        $user = User::query()->first();
        $clients = Client::query()->orderBy('id')->get();
        $products = Product::query()->orderBy('id')->get();
        $taxRate = Tax::defaultRate();

        if (! $user || $clients->isEmpty() || $products->isEmpty()) {
            return;
        }

        $statuses = ['draft', 'sent', 'sent', 'accepted', 'accepted', 'rejected', 'expired', 'draft'];

        for ($index = 0; $index < 18; $index++) {
            $date = now()->subDays(40 - $index);
            $client = $clients[$index % $clients->count()];
            $status = $statuses[$index % count($statuses)];
            $lines = $products->slice($index * 2, 3);
            if ($lines->count() < 2) {
                $lines = $products->random(min(3, $products->count()));
            }

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $details = [];
            foreach ($lines as $product) {
                $quantity = 2 + ($index % 4);
                $price = (float) $product->sale_price;
                $lineGross = round($quantity * $price, 2);
                $lineNet = $taxRate > 0 ? round($lineGross / (1 + $taxRate), 2) : $lineGross;
                $lineTax = round($lineGross - $lineNet, 2);
                $subtotal += $lineNet;
                $taxTotal += $lineTax;
                $details[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => 0,
                    'subtotal' => $lineGross,
                ];
            }

            $proforma = Proforma::query()->create([
                'proforma_number' => NumberSequence::getNext('proforma'),
                'client_id' => $client->id,
                'user_id' => $user->id,
                'client_name' => $client->name,
                'client_phone' => $client->phone,
                'client_email' => $client->email,
                'client_address' => $client->address,
                'date' => $date->toDateString(),
                'expiry_date' => $date->copy()->addDays(15)->toDateString(),
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($subtotal + $taxTotal, 2),
                'tax_rate' => $taxRate,
                'tax_included' => true,
                'status' => $status,
                'notes' => 'Cotización de demostración para obra / ferretería',
            ]);

            foreach ($details as $detail) {
                ProformaDetail::query()->create($detail + ['proforma_id' => $proforma->id]);
            }
        }
    }

    private function seedRepairOrders(): void
    {
        if (RepairOrder::query()->exists()) {
            return;
        }

        $user = User::query()->first();
        $clients = Client::query()->orderBy('id')->get();
        $services = RepairService::query()->orderBy('id')->get();
        $parts = Product::query()->whereHas('category', function ($query): void {
            $query->whereIn('name', ['Ferretería', 'Electricidad', 'Cerrajería']);
        })->orderBy('id')->get();

        if (! $user || $clients->isEmpty() || $services->isEmpty()) {
            return;
        }

        $jobs = [
            ['DeWalt', 'DCD771 Taladro 20V', 'No enciende', 'Carbones gastados', 'in_repair', 'high'],
            ['Makita', 'Esmeril GA4530', 'Huele a quemado', 'Switch dañado', 'diagnosing', 'normal'],
            ['Bosch', 'Sierra circular GKS 190', 'Disco trabado', 'Guardas flojas', 'waiting_parts', 'urgent'],
            ['Stanley', 'Cerradura 11-990', 'No gira el cilindro', 'Cilindro gastado', 'ready', 'normal'],
            ['Truper', 'Machete 24"', 'Filo mellado', 'Requiere afilado', 'delivered', 'low'],
            ['Ingco', 'Taladro de banco', 'Chuck flojo', 'Cambio de chuck', 'received', 'normal'],
            ['Pretul', 'Candado 50mm', 'Llave perdida', 'Duplicado y cambio de combinación', 'delivered', 'low'],
            ['Milwaukee', 'Atornillador M18', 'Batería no carga', 'Conector de carga', 'in_repair', 'high'],
            ['Black+Decker', 'Pistola de calor', 'No calienta', 'Resistencia abierta', 'cancelled', 'normal'],
            ['Irwin', 'Juego de dados', 'Extensión doblada', 'Enderezado', 'ready', 'low'],
            ['DeWalt', 'Sierra sable DCS380', 'Hoja no avanza', 'Guía interna', 'diagnosing', 'high'],
            ['Bosch', 'Lijadora GSS 140', 'Vibra en exceso', 'Base desbalanceada', 'delivered', 'normal'],
        ];

        $statusesPayment = ['pending', 'partial', 'paid', 'paid'];

        foreach ($jobs as $index => $job) {
            [$brand, $model, $problem, $diagnosis, $status, $priority] = $job;
            $client = $clients[$index % $clients->count()];
            $service = $services[$index % $services->count()];
            $received = now()->subDays(20 - $index);
            $labor = (float) $service->price;
            $part = $parts->isNotEmpty() ? $parts[$index % $parts->count()] : null;
            $partQty = $part ? 1 : 0;
            $partPrice = $part ? (float) $part->sale_price : 0;
            $partsCost = round($partQty * $partPrice, 2);
            $total = round($labor + $partsCost, 2);
            $advance = in_array($status, ['delivered', 'ready'], true) ? $total : round($total * 0.4, 2);

            $order = RepairOrder::query()->create([
                'order_number' => NumberSequence::getNext('reparacion'),
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_phone' => $client->phone,
                'client_email' => $client->email,
                'device_brand' => $brand,
                'device_model' => $model,
                'device_color' => ['amarillo', 'azul', 'verde', 'negro'][$index % 4],
                'lock_type' => 'none',
                'accessories' => $index % 2 === 0 ? 'Estuche y cargador' : 'Sin accesorios',
                'problem_description' => $problem,
                'diagnosis' => $diagnosis,
                'repair_notes' => 'Orden de taller de ferretería (demostración)',
                'status' => $status,
                'priority' => $priority,
                'technician_id' => $user->id,
                'user_id' => $user->id,
                'received_date' => $received->toDateString(),
                'received_time' => '09:15',
                'estimated_date' => $received->copy()->addDays(3)->toDateString(),
                'delivered_date' => $status === 'delivered' ? $received->copy()->addDays(2)->toDateString() : null,
                'delivered_time' => $status === 'delivered' ? '16:40' : null,
                'labor_cost' => $labor,
                'parts_cost' => $partsCost,
                'total' => $total,
                'discount_amount' => 0,
                'discount_percentage' => 0,
                'advance_payment' => $status === 'cancelled' ? 0 : $advance,
                'payment_type' => ['cash', 'transfer', 'cash'][$index % 3],
                'payment_status' => $status === 'cancelled' ? 'pending' : $statusesPayment[$index % count($statusesPayment)],
                'warranty_enabled' => true,
                'warranty_text' => 'Garantía de 15 días sobre mano de obra del taller.',
            ]);

            RepairOrderItem::query()->create([
                'repair_order_id' => $order->id,
                'service_id' => $service->id,
                'description' => $service->name,
                'quantity' => 1,
                'price' => $labor,
                'subtotal' => $labor,
                'item_type' => 'service',
                'device_brand' => $brand,
            ]);

            if ($part) {
                RepairOrderItem::query()->create([
                    'repair_order_id' => $order->id,
                    'product_id' => $part->id,
                    'description' => $part->name,
                    'quantity' => $partQty,
                    'price' => $partPrice,
                    'subtotal' => $partsCost,
                    'item_type' => 'part',
                    'device_brand' => $brand,
                ]);
            }
        }
    }

    private function seedInventoryAdjustments(): void
    {
        if (InventoryAdjustment::query()->exists()) {
            return;
        }

        $user = User::query()->first();
        $warehouse = Warehouse::default();
        $inventory = app(InventoryService::class);
        $accounting = app(AccountingService::class);

        if (! $user || ! $warehouse) {
            return;
        }

        $products = Product::query()
            ->where('stock', '>=', 12)
            ->orderBy('id')
            ->limit(12)
            ->get();

        $types = ['increase', 'decrease', 'count', 'decrease', 'increase', 'count'];

        foreach ($products as $index => $product) {
            $type = $types[$index % count($types)];
            $product = $product->fresh();
            $stockBefore = (float) $product->stock;
            $warehouseQty = $product->stockInWarehouse($warehouse->id);
            $quantity = match ($type) {
                'increase' => 4.0,
                'decrease' => 2.0,
                default => max(1, $warehouseQty + (($index % 2 === 0) ? 1 : -1)),
            };

            $adjustment = InventoryAdjustment::query()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => 0,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore,
                'reason' => match ($type) {
                    'increase' => 'Sobrante encontrado en conteo de ferretería',
                    'decrease' => 'Faltante / merma de mostrador',
                    default => 'Conteo físico de bodega principal',
                },
                'reference' => NumberSequence::getNext('ajuste'),
            ]);

            $reference = 'adjustment:'.$adjustment->id;
            $delta = 0.0;

            try {
                if ($type === 'increase') {
                    $delta = $quantity;
                    $inventory->stockIn($product, $quantity, $reference, $adjustment->reason, $user->id, $warehouse->id);
                } elseif ($type === 'decrease') {
                    $delta = -$quantity;
                    $inventory->stockOut($product, $quantity, $reference, $adjustment->reason, $user->id, false, $warehouse->id);
                } else {
                    $current = $product->stockInWarehouse($warehouse->id);
                    $delta = $quantity - $current;
                    if ($delta > 0) {
                        $inventory->stockIn($product, $delta, $reference, 'Conteo físico: '.$adjustment->reason, $user->id, $warehouse->id);
                    } elseif ($delta < 0) {
                        $inventory->stockOut($product, abs($delta), $reference, 'Conteo físico: '.$adjustment->reason, $user->id, false, $warehouse->id);
                    }
                }
            } catch (Throwable $exception) {
                $adjustment->delete();

                continue;
            }

            $product->refresh();
            $adjustment->update([
                'quantity' => $delta,
                'stock_after' => (float) $product->stock,
            ]);

            try {
                $accounting->recordInventoryAdjustment($adjustment->fresh());
            } catch (Throwable) {
                // El asiento no debe impedir el resto de la demostración.
            }
        }
    }

    private function seedWarehouseTransfers(): void
    {
        if (InventoryMovement::query()->where('reference', 'like', 'transfer:%')->exists()) {
            return;
        }

        $user = User::query()->first();
        $from = Warehouse::query()->where('code', 'BOD-01')->first();
        $toMateriales = Warehouse::query()->where('code', 'BOD-02')->first();
        $toPatio = Warehouse::query()->where('code', 'BOD-03')->first();
        $inventory = app(InventoryService::class);

        if (! $user || ! $from || ! $toMateriales || ! $toPatio) {
            return;
        }

        $products = Product::query()
            ->where('stock', '>=', 20)
            ->orderBy('id')
            ->limit(16)
            ->get();

        foreach ($products as $index => $product) {
            $to = $index % 2 === 0 ? $toMateriales : $toPatio;
            $quantity = 3 + ($index % 5);
            $available = $product->stockInWarehouse($from->id);
            if ($available < $quantity) {
                continue;
            }

            $reference = 'transfer:'.$from->id.'-'.$to->id.':demo-'.$product->id;

            try {
                $inventory->stockOut(
                    $product,
                    (float) $quantity,
                    $reference,
                    'Salida por transferencia a '.$to->name.' · reposición interna',
                    $user->id,
                    false,
                    $from->id,
                );
                $inventory->stockIn(
                    $product->fresh(),
                    (float) $quantity,
                    $reference,
                    'Entrada por transferencia desde '.$from->name.' · reposición interna',
                    $user->id,
                    $to->id,
                );
            } catch (Throwable) {
                continue;
            }
        }
    }

    private function seedCashAndExpenses(): void
    {
        $user = User::query()->first();
        if (! $user) {
            return;
        }

        $accounts = Account::query()
            ->whereIn('code', ['6.1.02', '6.1.03', '6.1.04', '6.1.05', '6.1.99', '6.2.01', '6.2.03'])
            ->get()
            ->keyBy('code');

        if (! CajaSession::query()->exists()) {
            for ($daysAgo = 12; $daysAgo >= 1; $daysAgo--) {
                $date = now()->subDays($daysAgo);
                $opening = 4500 + ($daysAgo * 80);
                $session = CajaSession::query()->create([
                    'date' => $date->toDateString(),
                    'opened_at' => $date->copy()->setTime(7, 45),
                    'opened_by' => $user->id,
                    'opening_amount' => $opening,
                    'closed_at' => $date->copy()->setTime(18, 10),
                    'closed_by' => $user->id,
                    'status' => 'closed',
                    'open_guard' => null,
                ]);

                $cashSales = (float) Sale::query()
                    ->whereDate('date', $date->toDateString())
                    ->where('status', 'completed')
                    ->where('payment_type', 'cash')
                    ->sum('total');
                $salesCount = (int) Sale::query()
                    ->whereDate('date', $date->toDateString())
                    ->where('status', 'completed')
                    ->count();
                $salesAmount = (float) Sale::query()
                    ->whereDate('date', $date->toDateString())
                    ->where('status', 'completed')
                    ->sum('total');
                $creditPayments = (float) CreditPayment::query()
                    ->whereDate('payment_date', $date->toDateString())
                    ->sum('amount');

                $expenseAmount = 0.0;
                if ($daysAgo % 2 === 0 && $accounts->isNotEmpty()) {
                    $account = $accounts->values()[$daysAgo % $accounts->count()];
                    $expenseAmount = [180, 320, 95, 450, 210][$daysAgo % 5];
                    OperationalExpense::query()->create([
                        'user_id' => $user->id,
                        'caja_session_id' => $session->id,
                        'account_id' => $account->id,
                        'description' => match ($account->code) {
                            '6.1.02' => 'Adelanto de alquiler del local',
                            '6.1.03' => 'Pago de energía eléctrica',
                            '6.1.04' => 'Recibo de agua potable',
                            '6.1.05' => 'Combustible para flete de varilla',
                            '6.2.01' => 'Papelería y rollos para tickets',
                            '6.2.03' => 'Mantenimiento de estantería',
                            default => 'Gasto operativo de ferretería',
                        },
                        'amount' => $expenseAmount,
                        'expense_date' => $date->toDateString(),
                        'payment_method' => 'cash',
                        'notes' => 'Gasto de demostración',
                        'status' => OperationalExpense::STATUS_REGISTERED,
                    ]);
                }

                $cashTotal = $opening + $cashSales - $expenseAmount;
                $physical = round($cashTotal + (($daysAgo % 3 === 0) ? -25 : 10), 2);

                Arqueo::query()->create([
                    'caja_session_id' => $session->id,
                    'date' => $date->toDateString(),
                    'user_id' => $user->id,
                    'total_sales_count' => $salesCount,
                    'total_sales_amount' => $salesAmount,
                    'cash_total' => $cashTotal,
                    'credit_payments_total' => $creditPayments,
                    'physical_total' => $physical,
                    'difference' => round($physical - $cashTotal, 2),
                    'details' => [
                        'opening_amount' => $opening,
                        'physical_counts' => [
                            ['amount' => 1000, 'qty' => (int) floor($physical / 1000)],
                            ['amount' => 100, 'qty' => 8],
                            ['amount' => 50, 'qty' => 6],
                        ],
                    ],
                ]);
            }
        }

        if (! CajaSession::query()->where('status', 'open')->exists()) {
            CajaSession::query()->create([
                'date' => now()->toDateString(),
                'opened_at' => now()->copy()->setTime(7, 50),
                'opened_by' => $user->id,
                'opening_amount' => 5200,
                'status' => 'open',
                'open_guard' => 'OPEN',
            ]);
        }

        if (OperationalExpense::query()->where('payment_method', 'transfer')->doesntExist() && $accounts->isNotEmpty()) {
            OperationalExpense::query()->create([
                'user_id' => $user->id,
                'account_id' => $accounts->get('6.1.02')?->id ?? $accounts->first()->id,
                'description' => 'Alquiler mensual del local (transferencia)',
                'amount' => 12000,
                'expense_date' => now()->startOfMonth()->toDateString(),
                'payment_method' => 'transfer',
                'notes' => 'Pago a la dueña del local',
                'status' => OperationalExpense::STATUS_REGISTERED,
            ]);
            OperationalExpense::query()->create([
                'user_id' => $user->id,
                'account_id' => $accounts->get('6.1.05')?->id ?? $accounts->first()->id,
                'description' => 'Flete de láminas desde Managua',
                'amount' => 1850,
                'expense_date' => now()->subDays(4)->toDateString(),
                'payment_method' => 'transfer',
                'notes' => 'Transportista Independiente',
                'status' => OperationalExpense::STATUS_REGISTERED,
            ]);
            OperationalExpense::query()->create([
                'user_id' => $user->id,
                'description' => 'Compra menor de agua para el personal',
                'amount' => 60,
                'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'notes' => 'Borrador de caja chica',
                'status' => OperationalExpense::STATUS_DRAFT,
            ]);
        }
    }

    private function seedAccounting(): void
    {
        $accounting = app(AccountingService::class);

        Sale::query()
            ->where('status', '!=', 'canceled')
            ->where('total', '>', 0)
            ->orderBy('id')
            ->each(function (Sale $sale) use ($accounting): void {
                if ($this->hasJournal(Sale::class, $sale->id)) {
                    return;
                }

                try {
                    $accounting->recordSale($sale);
                } catch (Throwable) {
                    return;
                }
            });

        Purchase::query()
            ->where('status', '!=', 'canceled')
            ->where('total', '>', 0)
            ->orderBy('id')
            ->each(function (Purchase $purchase) use ($accounting): void {
                if ($this->hasJournal(Purchase::class, $purchase->id)) {
                    return;
                }

                try {
                    $accounting->recordPurchase($purchase);
                } catch (Throwable) {
                    return;
                }
            });

        CreditPayment::query()->orderBy('id')->each(function (CreditPayment $payment) use ($accounting): void {
            if ($this->hasJournal(CreditPayment::class, $payment->id)) {
                return;
            }

            try {
                $accounting->recordCreditPayment($payment);
            } catch (Throwable) {
                return;
            }
        });

        OperationalExpense::query()
            ->where('status', OperationalExpense::STATUS_REGISTERED)
            ->orderBy('id')
            ->each(function (OperationalExpense $expense) use ($accounting): void {
                if ($this->hasJournal(OperationalExpense::class, $expense->id)) {
                    return;
                }

                try {
                    $accounting->recordOperationalExpense($expense);
                } catch (Throwable) {
                    return;
                }
            });
    }

    private function hasJournal(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    private function seedAccountingDemonstration(): void
    {
        $this->seedCostCenters();
        $this->seedManualAccountingEntries();
        $this->seedFiscalPeriods();
    }

    private function seedCostCenters(): void
    {
        foreach ([
            ['code' => 'CC-01', 'name' => 'Mostrador', 'type' => 'departamento', 'description' => 'Ventas al público y caja'],
            ['code' => 'CC-02', 'name' => 'Bodega', 'type' => 'departamento', 'description' => 'Almacén principal y materiales'],
            ['code' => 'CC-03', 'name' => 'Taller', 'type' => 'departamento', 'description' => 'Reparación de herramientas y cerrajería'],
            ['code' => 'CC-04', 'name' => 'Administración', 'type' => 'area', 'description' => 'Gerencia, planilla y contabilidad'],
            ['code' => 'CC-05', 'name' => 'Patio y techos', 'type' => 'area', 'description' => 'Láminas, varilla y patio de ferretería'],
            ['code' => 'CC-06', 'name' => 'Obra Los Pinos', 'type' => 'proyecto', 'description' => 'Suministros a Constructora Los Pinos'],
        ] as $center) {
            CostCenter::query()->firstOrCreate(
                ['code' => $center['code']],
                $center + ['is_active' => true]
            );
        }
    }

    private function seedManualAccountingEntries(): void
    {
        if (JournalEntry::query()->where('reference', 'DEMO-CONT-CAPITAL')->exists()) {
            return;
        }

        $user = User::query()->first();
        $accounting = app(AccountingService::class);
        $accounts = Account::query()->whereIn('code', [
            '1.1.01', '1.1.02', '1.2.01', '1.2.02', '1.2.04',
            '2.1.02', '2.1.03', '2.1.04',
            '3.1',
            '6.1.01', '6.1.99', '6.2.02', '6.3.01', '7.1',
        ])->get()->keyBy('code');

        $centers = CostCenter::query()->get()->keyBy('code');
        $admin = $centers->get('CC-04')?->id;
        $counter = $centers->get('CC-01')?->id;
        $workshop = $centers->get('CC-03')?->id;

        if (! $user || $accounts->count() < 8) {
            return;
        }

        $year = now()->year;
        $id = fn (string $code): int => (int) $accounts->get($code)?->id;

        $this->postDemoEntry($accounting, $user, "{$year}-01-02", 'DEMO-CONT-CAPITAL', 'Aporte inicial de capital — Ferretería El Roble', [
            ['account_id' => $id('1.1.01'), 'cost_center_id' => $counter, 'detail' => 'Fondo de caja inicial', 'debit' => 50000, 'credit' => 0],
            ['account_id' => $id('1.1.02'), 'cost_center_id' => $admin, 'detail' => 'Cuenta Lafise empresa', 'debit' => 150000, 'credit' => 0],
            ['account_id' => $id('1.2.01'), 'cost_center_id' => $admin, 'detail' => 'Estantería y mostradores', 'debit' => 45000, 'credit' => 0],
            ['account_id' => $id('1.2.02'), 'cost_center_id' => $admin, 'detail' => 'Computadoras y POS', 'debit' => 28000, 'credit' => 0],
            ['account_id' => $id('3.1'), 'detail' => 'Capital social', 'debit' => 0, 'credit' => 273000],
        ], 'Póliza de apertura para demostrar balance general.');

        $this->postDemoEntry($accounting, $user, "{$year}-02-14", 'DEMO-CONT-MOBILIARIO', 'Compra de estantería adicional para tornillería', [
            ['account_id' => $id('1.2.01'), 'cost_center_id' => $centers->get('CC-02')?->id, 'detail' => 'Anaqueles bodega', 'debit' => 12500, 'credit' => 0],
            ['account_id' => $id('1.1.02'), 'detail' => 'Pago Banpro / Lafise', 'debit' => 0, 'credit' => 12500],
        ]);

        $this->postDemoEntry($accounting, $user, "{$year}-03-10", 'DEMO-CONT-POS', 'Compra de lector de códigos y monitor POS', [
            ['account_id' => $id('1.2.02'), 'cost_center_id' => $counter, 'detail' => 'Equipo de caja', 'debit' => 6400, 'credit' => 0],
            ['account_id' => $id('1.1.02'), 'detail' => 'Transferencia al proveedor', 'debit' => 0, 'credit' => 6400],
        ]);

        foreach (range(1, max(1, now()->month - 1)) as $month) {
            $date = now()->setDate($year, $month, 28)->toDateString();
            $this->postDemoEntry($accounting, $user, $date, 'DEMO-CONT-DEP-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT), 'Depreciación mensual de mobiliario y equipo', [
                ['account_id' => $id('6.2.02'), 'cost_center_id' => $admin, 'detail' => 'Depreciación '.$date, 'debit' => 1450, 'credit' => 0],
                ['account_id' => $id('1.2.04'), 'detail' => 'Depreciación acumulada', 'debit' => 0, 'credit' => 1450],
            ]);
        }

        foreach (['03' => 210.50, '05' => 185.00, '07' => 240.75] as $month => $amount) {
            $this->postDemoEntry($accounting, $user, "{$year}-{$month}-18", 'DEMO-CONT-COM-'.$month, 'Comisión bancaria Lafise', [
                ['account_id' => $id('6.3.01'), 'cost_center_id' => $admin, 'detail' => 'Comisión cuenta corriente', 'debit' => $amount, 'credit' => 0],
                ['account_id' => $id('1.1.02'), 'detail' => 'Cargo en estado de cuenta', 'debit' => 0, 'credit' => $amount],
            ]);
        }

        foreach (['04' => 18000, '06' => 25000] as $month => $amount) {
            $this->postDemoEntry($accounting, $user, "{$year}-{$month}-05", 'DEMO-CONT-TRAS-'.$month, 'Depósito de efectivo de caja a banco', [
                ['account_id' => $id('1.1.02'), 'detail' => 'Depósito Lafise', 'debit' => $amount, 'credit' => 0],
                ['account_id' => $id('1.1.01'), 'cost_center_id' => $counter, 'detail' => 'Salida de caja general', 'debit' => 0, 'credit' => $amount],
            ]);
        }

        $this->postDemoEntry($accounting, $user, "{$year}-07-31", 'DEMO-CONT-INT', 'Intereses ganados en cuenta Lafise', [
            ['account_id' => $id('1.1.02'), 'detail' => 'Abono de intereses', 'debit' => 420, 'credit' => 0],
            ['account_id' => $id('7.1'), 'cost_center_id' => $admin, 'detail' => 'Ingresos financieros', 'debit' => 0, 'credit' => 420],
        ]);

        $this->postDemoEntry($accounting, $user, "{$year}-06-30", 'DEMO-CONT-IVA-06', 'Pago de IVA al fisco (junio)', [
            ['account_id' => $id('2.1.02'), 'detail' => 'Liquidación IVA junio', 'debit' => 8500, 'credit' => 0],
            ['account_id' => $id('1.1.02'), 'detail' => 'Transferencia DGI', 'debit' => 0, 'credit' => 8500],
        ]);

        foreach (['06', '07'] as $month) {
            $payroll = Payroll::query()->where('year', $year)->where('month', $month);
            $gross = round((float) $payroll->sum('gross_salary'), 2);
            $inss = round((float) $payroll->sum('inss_deduction'), 2);
            $ir = round((float) $payroll->sum('ir_deduction'), 2);
            $net = round((float) $payroll->sum('net_salary'), 2);
            $other = round((float) $payroll->sum('deductions') + (float) $payroll->sum('loan_payments'), 2);

            if ($gross <= 0) {
                $gross = 82700;
                $inss = 5168.75;
                $ir = 0;
                $other = 250;
                $net = round($gross - $inss - $ir - $other, 2);
            }

            $withholdings = round($inss + $ir + $other, 2);
            $payable = round($gross - $withholdings, 2);
            if (abs($payable - $net) > 0.05) {
                $payable = $net;
                $withholdings = round($gross - $payable, 2);
            }

            $end = now()->setDate($year, (int) $month, 1)->endOfMonth()->toDateString();
            $this->postDemoEntry($accounting, $user, $end, 'DEMO-CONT-NOM-'.$month, 'Provisión de planilla '.$month.'/'.$year, [
                ['account_id' => $id('6.1.01'), 'cost_center_id' => $admin, 'detail' => 'Sueldos brutos', 'debit' => $gross, 'credit' => 0],
                ['account_id' => $id('2.1.03'), 'detail' => 'INSS / IR / otras retenciones', 'debit' => 0, 'credit' => $withholdings],
                ['account_id' => $id('2.1.04'), 'detail' => 'Sueldos por pagar', 'debit' => 0, 'credit' => $payable],
            ]);

            $this->postDemoEntry($accounting, $user, $end, 'DEMO-CONT-PAGONOM-'.$month, 'Pago de planilla '.$month.'/'.$year, [
                ['account_id' => $id('2.1.04'), 'detail' => 'Cancelación de sueldos', 'debit' => $payable, 'credit' => 0],
                ['account_id' => $id('1.1.01'), 'cost_center_id' => $counter, 'detail' => 'Pago en efectivo / cheque', 'debit' => 0, 'credit' => $payable],
            ]);
        }

        $duplicate = $this->postDemoEntry($accounting, $user, "{$year}-07-18", 'DEMO-CONT-COM-DUP', 'Comisión bancaria duplicada (se anula)', [
            ['account_id' => $id('6.3.01'), 'cost_center_id' => $admin, 'detail' => 'Cargo duplicado', 'debit' => 240.75, 'credit' => 0],
            ['account_id' => $id('1.1.02'), 'detail' => 'Cargo en estado de cuenta', 'debit' => 0, 'credit' => 240.75],
        ]);
        if ($duplicate) {
            try {
                $accounting->void($duplicate, 'Asiento duplicado de comisión; se deja anulado para la demostración.');
            } catch (Throwable) {
                // La anulación es ilustrativa; no detiene el resto de la carga.
            }
        }

        $today = now()->toDateString();
        $this->draftDemoEntry($accounting, $user, $today, 'DEMO-CONT-BORR-AGUINALDO', 'Provisión de 13° mes (pendiente de revisar)', [
            ['account_id' => $id('6.1.01'), 'cost_center_id' => $admin, 'detail' => 'Aguinaldo proporcional', 'debit' => 6891.67, 'credit' => 0],
            ['account_id' => $id('2.1.04'), 'detail' => 'Prestaciones por pagar', 'debit' => 0, 'credit' => 6891.67],
        ], 'Borrador para mostrar el flujo Contabilizar en asientos.');

        $this->draftDemoEntry($accounting, $user, $today, 'DEMO-CONT-BORR-RECLAS', 'Reclasificación de efectivo a banco', [
            ['account_id' => $id('1.1.02'), 'detail' => 'Depósito pendiente de confirmar', 'debit' => 8000, 'credit' => 0],
            ['account_id' => $id('1.1.01'), 'cost_center_id' => $counter, 'detail' => 'Caja general', 'debit' => 0, 'credit' => 8000],
        ]);

        $this->draftDemoEntry($accounting, $user, $today, 'DEMO-CONT-BORR-TALLER', 'Gasto de taller pendiente de documentación', [
            ['account_id' => $id('6.1.99'), 'cost_center_id' => $workshop, 'detail' => 'Consumibles de reparación', 'debit' => 750, 'credit' => 0],
            ['account_id' => $id('1.1.01'), 'detail' => 'Caja chica', 'debit' => 0, 'credit' => 750],
        ]);
    }

    private function seedFiscalPeriods(): void
    {
        $user = User::query()->first();
        if (! $user) {
            return;
        }

        $year = now()->year;
        FiscalPeriod::forYear($year);
        for ($month = 1; $month <= 12; $month++) {
            FiscalPeriod::forMonth($year, $month);
        }

        $closeUntil = now()->startOfMonth()->subMonths(2);
        $closing = app(PeriodClosingService::class);

        for ($cursor = now()->setDate($year, 1, 1)->startOfMonth(); $cursor->lte($closeUntil); $cursor->addMonth()) {
            $period = FiscalPeriod::forMonth((int) $cursor->year, (int) $cursor->month);
            if ($period->status === FiscalPeriod::STATUS_CLOSED || $period->end_date->isFuture()) {
                continue;
            }

            try {
                $closing->closeMonth($period, $user, 'Cierre de demostración Ferretería El Roble');
            } catch (Throwable $exception) {
                $this->command?->warn('No se cerró '.$cursor->format('Y-m').': '.$exception->getMessage());
            }
        }
    }

    /**
     * @param  list<array{account_id:int,cost_center_id?:int|null,detail?:string,debit:float,credit:float}>  $lines
     */
    private function postDemoEntry(
        AccountingService $accounting,
        User $user,
        string $date,
        string $reference,
        string $concept,
        array $lines,
        ?string $notes = null,
    ): ?JournalEntry {
        return $this->writeDemoEntry($accounting, $user, $date, $reference, $concept, $lines, $notes, true);
    }

    /**
     * @param  list<array{account_id:int,cost_center_id?:int|null,detail?:string,debit:float,credit:float}>  $lines
     */
    private function draftDemoEntry(
        AccountingService $accounting,
        User $user,
        string $date,
        string $reference,
        string $concept,
        array $lines,
        ?string $notes = null,
    ): ?JournalEntry {
        return $this->writeDemoEntry($accounting, $user, $date, $reference, $concept, $lines, $notes, false);
    }

    /**
     * @param  list<array{account_id:int,cost_center_id?:int|null,detail?:string,debit:float,credit:float}>  $lines
     */
    private function writeDemoEntry(
        AccountingService $accounting,
        User $user,
        string $date,
        string $reference,
        string $concept,
        array $lines,
        ?string $notes,
        bool $post,
    ): ?JournalEntry {
        if (JournalEntry::query()->where('reference', $reference)->exists()) {
            return JournalEntry::query()->where('reference', $reference)->first();
        }

        $clean = array_values(array_filter(
            $lines,
            fn (array $line): bool => ($line['account_id'] ?? 0) > 0
                && ((float) ($line['debit'] ?? 0) > 0 || (float) ($line['credit'] ?? 0) > 0)
        ));
        if (count($clean) < 2) {
            return null;
        }

        try {
            return $accounting->createEntry([
                'date' => $date,
                'concept' => $concept,
                'reference' => $reference,
                'notes' => $notes,
                'user_id' => $user->id,
                'lines' => $clean,
            ], post: $post);
        } catch (Throwable $exception) {
            $this->command?->warn("No se creó {$reference}: ".$exception->getMessage());

            return null;
        }
    }
}
