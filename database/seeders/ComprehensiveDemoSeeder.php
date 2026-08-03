<?php

namespace Database\Seeders;

use App\Models\Arqueo;
use App\Models\CajaSession;
use App\Models\Category;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\CreditPayment;
use App\Models\Employee;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\RepairService;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComprehensiveDemoSeeder extends Seeder
{
    private const MARKER = 'demo.comprehensive.loaded_at';

    private User $operator;

    private InventoryService $inventory;

    private AccountingService $accounting;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('Los datos integrales de prueba solo pueden cargarse en local o testing.');

            return;
        }

        if (Setting::where('key', self::MARKER)->exists()) {
            $this->command?->warn('El conjunto integral de pruebas ya fue cargado; no se duplicaron registros.');

            return;
        }

        $this->inventory = app(InventoryService::class);
        $this->accounting = app(AccountingService::class);
        $this->operator = User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->first()
            ?? User::firstOrFail();

        DB::transaction(function () {
            $this->seedUsers();
            $this->seedCategories();
            $this->seedCostCenters();
            $suppliers = $this->seedSuppliers();
            $clients = $this->seedClients();
            $products = $this->seedProducts($suppliers);
            $this->seedEmployeesAndPayrolls();
            $this->seedPurchases($suppliers, $products);
            $sales = $this->seedSales($clients, $products);
            $this->seedCreditPayments($sales);
            $this->seedProformas($clients, $products);
            $this->seedRepairs($clients, $products);
            $this->seedAdjustments($products);
            $this->seedCashClosings($sales);

            Setting::set(
                self::MARKER,
                now()->toIso8601String(),
                'string',
                'demo',
                'Marca interna del conjunto integral de datos de prueba'
            );
        });

        $this->command?->info('Datos integrales de prueba cargados correctamente.');
        $this->command?->line('Usuarios demo: cajero, vendedor, bodega, compras, contabilidad y técnico.');
        $this->command?->line('Contraseña temporal común: Prueba2026!');
    }

    private function seedUsers(): void
    {
        $users = [
            ['role' => 'cajero', 'name' => 'Cajero de Prueba', 'username' => 'prueba.cajero', 'email' => 'cajero.prueba@local.test', 'phone' => '8700-1001'],
            ['role' => 'vendedor', 'name' => 'Vendedor de Prueba', 'username' => 'prueba.vendedor', 'email' => 'vendedor.prueba@local.test', 'phone' => '8700-1002'],
            ['role' => 'bodega', 'name' => 'Bodega de Prueba', 'username' => 'prueba.bodega', 'email' => 'bodega.prueba@local.test', 'phone' => '8700-1003'],
            ['role' => 'compras', 'name' => 'Compras de Prueba', 'username' => 'prueba.compras', 'email' => 'compras.prueba@local.test', 'phone' => '8700-1004'],
            ['role' => 'contabilidad', 'name' => 'Contabilidad de Prueba', 'username' => 'prueba.contabilidad', 'email' => 'contabilidad.prueba@local.test', 'phone' => '8700-1005'],
            ['role' => 'supervisor', 'name' => 'Técnico de Prueba', 'username' => 'prueba.tecnico', 'email' => 'tecnico.prueba@local.test', 'phone' => '8700-1006'],
        ];

        foreach ($users as $data) {
            $role = Role::where('slug', $data['role'])->firstOrFail();
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => $data['role'],
                'password' => 'Prueba2026!',
                'is_active' => true,
                'last_login_at' => now()->subDays(2),
                'force_password_change' => false,
                'password_changed_at' => now()->subDays(5),
            ]);
            $user->roles()->sync([$role->id]);
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['Fertilizantes', 'Fertilizantes granulados y foliares'],
            ['Semillas', 'Semillas certificadas para diferentes cultivos'],
            ['Insecticidas', 'Control de insectos y plagas'],
            ['Fungicidas', 'Prevención y control de enfermedades'],
            ['Herbicidas', 'Control selectivo y total de malezas'],
            ['Herramientas', 'Herramientas para campo y jardín'],
            ['Riego', 'Accesorios y equipos para sistemas de riego'],
            ['Veterinaria', 'Productos básicos de uso veterinario'],
            ['Repuestos', 'Repuestos utilizados en reparaciones'],
            ['Prueba Exenta', 'Productos configurados sin IVA'],
        ];

        foreach ($categories as [$name, $description]) {
            Category::create(['name' => $name, 'description' => $description.' — DATO DE PRUEBA']);
        }
    }

    private function seedCostCenters(): void
    {
        $rows = [
            ['CC-PR-01', 'Tienda principal', 'sucursal'],
            ['CC-PR-02', 'Bodega de pruebas', 'departamento'],
            ['CC-PR-03', 'Proyecto demostración', 'proyecto'],
            ['CC-PR-04', 'Servicio técnico', 'area'],
        ];

        foreach ($rows as [$code, $name, $type]) {
            CostCenter::create([
                'code' => $code,
                'name' => $name,
                'description' => 'Centro de costo creado para pruebas integrales',
                'type' => $type,
                'is_active' => true,
            ]);
        }
    }

    private function seedSuppliers(): array
    {
        $cities = ['Estelí', 'Managua', 'León', 'Matagalpa', 'Jinotega'];
        $suppliers = [];

        for ($i = 1; $i <= 10; $i++) {
            $suppliers[] = Supplier::create([
                'code' => 'PRV-PR-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => "Proveedor de Prueba {$i}",
                'business_name' => "Distribuidora Demostración {$i}, S.A.",
                'ruc' => 'J03100000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'contact_name' => "Contacto Prueba {$i}",
                'phone' => '8800-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'email' => "proveedor{$i}.prueba@local.test",
                'city' => $cities[($i - 1) % count($cities)],
                'address' => "Bodega de prueba {$i}, sector industrial",
                'type' => $i % 2 === 0 ? 'minorista' : 'mayorista',
                'payment_condition' => ['contado', 'credito_15', 'credito_30', 'credito_60'][($i - 1) % 4],
                'credit_limit' => $i % 4 === 1 ? 0 : 25000 + ($i * 5000),
                'status' => $i === 10 ? 'inactive' : 'active',
            ]);
        }

        return $suppliers;
    }

    private function seedClients(): array
    {
        $names = ['Ana Pérez', 'Carlos López', 'María Rodríguez', 'José García', 'Lucía Martínez', 'Finca El Roble', 'Cooperativa Nueva Vida', 'Hacienda San José', 'Pedro Castillo', 'Rosa Hernández', 'Agropecuaria La Unión', 'Miguel Flores', 'Sofía Ruiz', 'Luis Mendoza', 'Comercial El Campo'];
        $clients = [];

        foreach ($names as $index => $name) {
            $i = $index + 1;
            $credit = $i % 3 !== 0;
            $clients[] = Client::create([
                'code' => 'CLI-PR-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => $name.' — PRUEBA',
                'business_name' => "Razón Social Demostración {$i}",
                'ruc' => '001'.str_pad((string) $i, 10, '0', STR_PAD_LEFT).'A',
                'phone' => '8900-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'email' => "cliente{$i}.prueba@local.test",
                'address' => "Dirección completa del cliente de prueba {$i}, Estelí",
                'credit_enabled' => $credit,
                'credit_limit' => $credit ? 10000 + ($i * 1500) : 0,
                'credit_days' => $credit ? [15, 30, 45][($i - 1) % 3] : 0,
                'mora_enabled' => $credit && $i % 2 === 0,
                'mora_rate' => $credit ? 2.5 : 0,
                'mora_grace_days' => $credit ? 5 : 0,
                'mora_max_pct' => $credit ? 15 : 0,
            ]);
        }

        return $clients;
    }

    private function seedProducts(array $suppliers): array
    {
        $iva = Tax::where('code', 'IVA-15')->firstOrFail();
        $exempt = Tax::where('code', 'EXENTO')->firstOrFail();
        $categories = Category::pluck('id', 'name');
        $names = [
            ['Fertilizantes', 'Fertilizante NPK 15-15-15', 'quintal'],
            ['Fertilizantes', 'Urea 46% granulada', 'quintal'],
            ['Semillas', 'Semilla de maíz híbrido', 'kilogramo'],
            ['Semillas', 'Semilla de frijol rojo', 'kilogramo'],
            ['Insecticidas', 'Insecticida de amplio espectro', 'litro'],
            ['Insecticidas', 'Control biológico de plagas', 'litro'],
            ['Fungicidas', 'Fungicida preventivo 80 WP', 'kilogramo'],
            ['Fungicidas', 'Fungicida sistémico 25 EC', 'litro'],
            ['Herbicidas', 'Herbicida selectivo', 'litro'],
            ['Herbicidas', 'Herbicida total 48 SL', 'litro'],
            ['Herramientas', 'Machete agrícola 22 pulgadas', 'unidad'],
            ['Herramientas', 'Pala cuadrada reforzada', 'unidad'],
            ['Riego', 'Manguera de riego 25 metros', 'rollo'],
            ['Riego', 'Aspersor circular ajustable', 'unidad'],
            ['Veterinaria', 'Complejo vitamínico veterinario', 'frasco'],
            ['Veterinaria', 'Desparasitante oral', 'frasco'],
            ['Repuestos', 'Conector de carga universal', 'unidad'],
            ['Repuestos', 'Batería de teléfono genérica', 'unidad'],
            ['Prueba Exenta', 'Semilla exenta de prueba', 'bolsa'],
            ['Prueba Exenta', 'Producto sin IVA de demostración', 'unidad'],
        ];

        Storage::disk('public')->put(
            'products/demo-product.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z7KsAAAAASUVORK5CYII=')
        );

        $products = [];
        foreach ($names as $index => [$category, $name, $unit]) {
            $i = $index + 1;
            $purchasePrice = 80 + ($i * 17.5);
            $salePrice = round($purchasePrice * 1.35, 2);
            $product = Product::create([
                'category_id' => $categories[$category],
                'name' => $name.' — PRUEBA',
                'code' => 'PROD-PR-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'description' => "Descripción completa del producto de prueba {$i}",
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'stock' => 0,
                'unit' => $unit,
                'lot' => 'LOTE-PR-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'expiry_date' => now()->addMonths(6 + $i)->toDateString(),
                'location' => 'Bodega '.(($i % 3) + 1).', estante '.(($i % 5) + 1),
                'low_stock_threshold' => 8 + ($i % 5),
                'registration_number' => 'REG-MAG-PR-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'active_ingredient' => "Ingrediente activo de prueba {$i}",
                'concentration' => (10 + $i).'% formulación de prueba',
                'status' => $i === 20 ? 'discontinued' : 'active',
                'observations' => 'No vender como producto real. Registro creado para validar todos los campos.',
                'image_url' => 'products/demo-product.png',
                'discount_pct' => $i % 4 === 0 ? 5 : 0,
                'discount_label' => $i % 4 === 0 ? 'Promoción de prueba 5%' : null,
                'tax_id' => $category === 'Prueba Exenta' ? $exempt->id : $iva->id,
            ]);

            $initialStock = 35 + ($i * 2);
            $this->inventory->stockIn($product, $initialStock, "DEMO-INICIAL-{$i}", 'Existencia inicial de prueba', $this->operator->id);
            $supplier = $suppliers[$index % count($suppliers)];
            $product->suppliers()->attach($supplier->id, [
                'purchase_price' => $purchasePrice,
                'supplier_code' => 'COD-PRV-'.$i,
                'preferred' => true,
            ]);
            $products[] = $product->fresh();
        }

        return $products;
    }

    private function seedEmployeesAndPayrolls(): void
    {
        $positions = ['Cajero', 'Vendedor', 'Encargado de bodega', 'Auxiliar contable', 'Técnico', 'Comprador', 'Supervisor', 'Repartidor', 'Asistente', 'Administrador de tienda'];

        foreach ($positions as $index => $position) {
            $i = $index + 1;
            $salary = 8500 + ($i * 650);
            $employee = Employee::create([
                'name' => "Empleado de Prueba {$i}",
                'position' => $position,
                'salary' => $salary,
                'phone' => '8600-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'address' => "Dirección del empleado de prueba {$i}, Estelí",
            ]);

            foreach ([now()->subMonth(), now()] as $month) {
                $bonus = $i % 2 === 0 ? 500 : 250;
                $deduction = 150 + ($i * 10);
                Payroll::create([
                    'employee_id' => $employee->id,
                    'month' => $month->translatedFormat('F'),
                    'year' => (int) $month->format('Y'),
                    'base_salary' => $salary,
                    'bonuses' => $bonus,
                    'deductions' => $deduction,
                    'net_salary' => $salary + $bonus - $deduction,
                ]);
            }
        }
    }

    private function seedPurchases(array $suppliers, array $products): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $date = now()->subDays(40 - ($i * 3));
            $purchase = Purchase::create([
                'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                'user_id' => $this->operator->id,
                'date' => $date,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'status' => 'completed',
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            foreach ([$products[($i - 1) % 20], $products[($i + 5) % 20]] as $product) {
                $quantity = 5 + $i;
                $price = (float) $product->purchase_price;
                $lineSubtotal = round($quantity * $price, 2);
                $taxRate = $product->effectiveTaxRate();
                $taxAmount = round($lineSubtotal * $taxRate, 2);
                $purchase->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                ]);
                $this->inventory->stockIn($product, $quantity, "DEMO-COMPRA-{$i}", "Compra de prueba #{$i}", $this->operator->id);
                $subtotal += $lineSubtotal;
                $taxTotal += $taxAmount;
            }

            $purchase->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($subtotal + $taxTotal, 2),
            ]);
            $this->accounting->recordPurchase($purchase->fresh());
        }
    }

    private function seedSales(array $clients, array $products): array
    {
        $sales = [];
        // El POS conserva los pagos con tarjeta como transferencia y deja la
        // identificación de tarjeta en notas para mantener compatibilidad con MySQL.
        $paymentTypes = ['cash', 'credit', 'transfer', 'transfer'];

        for ($i = 1; $i <= 15; $i++) {
            $date = now()->subDays(30 - ($i * 2));
            $client = $clients[($i - 1) % count($clients)];
            $paymentType = $paymentTypes[($i - 1) % count($paymentTypes)];
            if ($paymentType === 'credit' && ! $client->credit_enabled) {
                $paymentType = 'cash';
            }

            $sale = Sale::create([
                'invoice_number' => 'FAC-PR-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'user_id' => $this->operator->id,
                'billing_name' => $client->name,
                'billing_business_name' => $client->business_name,
                'billing_ruc' => $client->ruc,
                'billing_phone' => $client->phone,
                'billing_email' => $client->email,
                'billing_address' => $client->address,
                'date' => $date,
                'due_date' => $paymentType === 'credit' ? $date->copy()->addDays($client->credit_days)->toDateString() : null,
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_amount' => 0,
                'discount_percentage' => $i % 5 === 0 ? 5 : 0,
                'total' => 0,
                'payment_type' => $paymentType,
                'tax_included' => false,
                'tax_rate' => 0.15,
                'status' => 'completed',
                'notes' => ($i % 4 === 3 ? 'Pago con tarjeta | ' : '')."Venta integral de prueba {$i}; no corresponde a una operación real.",
            ]);

            $grossSubtotal = 0;
            $weightedTax = 0;
            foreach ([$products[($i * 2) % 20], $products[(($i * 2) + 3) % 20]] as $product) {
                $quantity = 1 + ($i % 3);
                $price = (float) $product->effectivePrice();
                $lineSubtotal = round($quantity * $price, 2);
                $taxRate = $product->effectiveTaxRate();
                $lineTax = round($lineSubtotal * $taxRate, 2);
                $sale->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                ]);
                $this->inventory->stockOut($product, $quantity, "DEMO-VENTA-{$i}", "Venta de prueba #{$i}", $this->operator->id);
                $grossSubtotal += $lineSubtotal;
                $weightedTax += $lineTax;
            }

            $discountPct = (float) $sale->discount_percentage;
            $discountAmount = round($grossSubtotal * ($discountPct / 100), 2);
            $netSubtotal = round($grossSubtotal - $discountAmount, 2);
            $taxTotal = round($weightedTax * (1 - $discountPct / 100), 2);
            $sale->update([
                'subtotal' => $netSubtotal,
                'tax_total' => $taxTotal,
                'discount_amount' => $discountAmount,
                'total' => round($netSubtotal + $taxTotal, 2),
            ]);
            $this->accounting->recordSale($sale->fresh());
            $sales[] = $sale->fresh();
        }

        return $sales;
    }

    private function seedCreditPayments(array $sales): void
    {
        foreach (collect($sales)->where('payment_type', 'credit')->take(3) as $index => $sale) {
            $payment = CreditPayment::create([
                'client_id' => $sale->client_id,
                'sale_id' => $sale->id,
                'amount' => round((float) $sale->total * 0.35, 2),
                'payment_date' => now()->subDays(3 - $index),
                'payment_type' => $index % 2 === 0 ? 'cash' : 'transfer',
                'reference_number' => 'ABONO-PR-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'notes' => 'Abono parcial generado para probar la cartera de clientes.',
                'user_id' => $this->operator->id,
            ]);
            $this->accounting->recordCreditPayment($payment);
        }
    }

    private function seedProformas(array $clients, array $products): void
    {
        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

        for ($i = 1; $i <= 10; $i++) {
            $client = $clients[($i + 2) % count($clients)];
            $date = now()->subDays(12 - $i);
            $proforma = Proforma::create([
                'proforma_number' => 'COT-PR-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'user_id' => $this->operator->id,
                'client_name' => $client->name,
                'client_phone' => $client->phone,
                'client_email' => $client->email,
                'client_address' => $client->address,
                'date' => $date,
                'expiry_date' => $date->copy()->addDays(15),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'tax_rate' => 0.15,
                'tax_included' => false,
                'status' => $statuses[($i - 1) % count($statuses)],
                'notes' => "Cotización de prueba {$i} con todos los datos del cliente.",
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            foreach ([$products[$i % 20], $products[($i + 7) % 20]] as $line => $product) {
                $quantity = $line + 1;
                $price = (float) $product->sale_price;
                $discount = $line === 1 ? 5 : 0;
                $lineSubtotal = round($quantity * $price * (1 - $discount / 100), 2);
                $proforma->details()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discount,
                    'subtotal' => $lineSubtotal,
                ]);
                $subtotal += $lineSubtotal;
                $taxTotal += round($lineSubtotal * $product->effectiveTaxRate(), 2);
            }
            $proforma->update(['subtotal' => $subtotal, 'tax_total' => $taxTotal, 'total' => $subtotal + $taxTotal]);
        }
    }

    private function seedRepairs(array $clients, array $products): void
    {
        $brands = ['Samsung', 'Apple', 'Xiaomi', 'Motorola', 'Huawei'];
        $statuses = ['received', 'diagnosing', 'waiting_parts', 'in_repair', 'ready', 'delivered', 'cancelled'];
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $technician = User::where('email', 'tecnico.prueba@local.test')->firstOrFail();
        $services = RepairService::active()->get();

        for ($i = 1; $i <= 10; $i++) {
            $client = $clients[($i + 4) % count($clients)];
            $status = $statuses[($i - 1) % count($statuses)];
            $labor = 250 + ($i * 50);
            $parts = 180 + ($i * 35);
            $discountPct = $i % 4 === 0 ? 10 : 0;
            $subtotal = $labor + $parts;
            $discount = round($subtotal * ($discountPct / 100), 2);
            $total = $subtotal - $discount;
            $advance = $status === 'delivered' ? $total : round($total * 0.30, 2);
            $received = now()->subDays(11 - $i);

            $repair = RepairOrder::create([
                'order_number' => 'REP-PR-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_phone' => $client->phone,
                'client_email' => $client->email,
                'device_brand' => $brands[($i - 1) % count($brands)],
                'device_model' => "Modelo de prueba {$i}",
                'device_color' => ['Negro', 'Azul', 'Blanco', 'Rojo'][($i - 1) % 4],
                'device_imei' => '350000000'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'device_password' => $i % 3 === 0 ? null : ($i % 2 === 0 ? '1-2-5-8' : '2580'),
                'lock_type' => $i % 3 === 0 ? 'none' : ($i % 2 === 0 ? 'pattern' : 'password'),
                'accessories' => 'Cargador, funda y tarjeta SIM de prueba',
                'problem_description' => "Falla reportada por el cliente para la orden de prueba {$i}.",
                'diagnosis' => "Diagnóstico técnico completo de prueba {$i}.",
                'repair_notes' => 'Verificar sellos, conectores y funcionamiento antes de entregar.',
                'status' => $status,
                'priority' => $priorities[($i - 1) % count($priorities)],
                'technician_id' => $technician->id,
                'user_id' => $this->operator->id,
                'received_date' => $received,
                'received_time' => sprintf('%02d:%02d', 8 + (($i - 1) % 9), ($i * 7) % 60),
                'estimated_date' => $received->copy()->addDays(3),
                'delivered_date' => $status === 'delivered' ? $received->copy()->addDays(2) : null,
                'delivered_time' => $status === 'delivered' ? sprintf('%02d:%02d', 14 + ($i % 3), ($i * 11) % 60) : null,
                'labor_cost' => $labor,
                'parts_cost' => $parts,
                'total' => $subtotal,
                'discount_amount' => $discount,
                'discount_percentage' => $discountPct,
                'advance_payment' => $advance,
                'payment_type' => ['cash', 'card', 'transfer'][($i - 1) % 3],
                'payment_status' => $status === 'delivered' ? 'paid' : ($advance > 0 ? 'partial' : 'pending'),
            ]);

            $service = $services[($i - 1) % $services->count()];
            $repair->items()->create([
                'service_id' => $service->id,
                'description' => $service->name,
                'quantity' => 1,
                'price' => $labor,
                'subtotal' => $labor,
                'item_type' => 'service',
                'device_brand' => $repair->device_brand,
            ]);
            $part = $products[16 + ($i % 2)];
            $repair->items()->create([
                'product_id' => $part->id,
                'description' => $part->name,
                'quantity' => 1,
                'price' => $parts,
                'subtotal' => $parts,
                'item_type' => 'product',
                'device_brand' => $repair->device_brand,
            ]);
        }
    }

    private function seedAdjustments(array $products): void
    {
        $types = ['increase', 'decrease', 'count', 'increase', 'decrease', 'count'];

        foreach ($types as $index => $type) {
            $product = $products[$index];
            $stockBefore = (int) $product->fresh()->stock;
            $quantity = $type === 'decrease' ? -2 : ($type === 'increase' ? 3 : 1);
            $stockAfter = max(0, $stockBefore + $quantity);
            $adjustment = InventoryAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $this->operator->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => "Ajuste integral de prueba por {$type}",
                'reference' => 'AJU-PR-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ]);
            $product->update(['stock' => $stockAfter]);
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => $quantity >= 0 ? 'in' : 'out',
                'quantity' => abs($quantity),
                'stock_after' => $stockAfter,
                'reference' => 'adjustment:'.$adjustment->id,
                'note' => $adjustment->reason,
                'user_id' => $this->operator->id,
            ]);
            $this->accounting->recordInventoryAdjustment($adjustment);
        }
    }

    private function seedCashClosings(array $sales): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $date = now()->subDays(5 - $i)->startOfDay();
            $daySales = collect($sales)->filter(fn (Sale $sale) => $sale->date->isSameDay($date));
            $amount = round((float) $daySales->sum('total'), 2);
            CajaSession::create([
                'date' => $date,
                'opened_at' => $date->copy()->setTime(8, 0),
                'opened_by' => $this->operator->id,
                'closed_at' => $date->copy()->setTime(18, 0),
                'closed_by' => $this->operator->id,
                'status' => 'closed',
            ]);
            Arqueo::create([
                'date' => $date,
                'user_id' => $this->operator->id,
                'total_sales_count' => $daySales->count(),
                'total_sales_amount' => $amount,
                'cash_total' => $amount,
                'credit_payments_total' => 0,
                'physical_total' => $amount,
                'difference' => 0,
                'details' => [
                    'source' => 'conjunto_integral_prueba',
                    'notes' => "Arqueo de demostración {$i}",
                ],
            ]);
        }
    }
}
