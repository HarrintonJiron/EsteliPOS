<?php

use App\Models\Account;
use App\Models\Bonus;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\CreditPayment;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\InventoryAdjustment;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\OperationalExpense;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Proforma;
use App\Models\Purchase;
use App\Models\RepairOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function (): void {
    disableDemoSeederData();
});

function productionSeededAdmin(): User
{
    enableDemoSeederData();

    test()->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@agroservicio.com')->first();

    if ($admin === null) {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'admin@agroservicio.com',
        ]);
        $admin->roles()->sync([$role->id]);
    }

    if (! Employee::query()->exists()) {
        Employee::query()->create([
            'name' => 'Empleado Base QA',
            'position' => 'Cajero',
            'salary' => 12000,
            'phone' => null,
            'address' => null,
            'contract_type' => 'full_time',
            'payment_frequency' => 'monthly',
        ]);
    }

    return $admin->fresh();
}

/**
 * @return array<string, mixed>|null
 */
function resolveProductionRouteParameters(array $parameterNames, string $routeName): ?array
{
    if ($parameterNames === []) {
        return [];
    }

    $params = [];

    foreach ($parameterNames as $name) {
        $value = match ($name) {
            'id' => match (true) {
                Str::startsWith($routeName, 'facturacion.') => Sale::query()->value('id'),
                Str::startsWith($routeName, 'inventario.') => Product::query()->value('id'),
                Str::startsWith($routeName, 'clientes.') => Client::query()->value('id'),
                Str::startsWith($routeName, 'compras.') => Purchase::query()->value('id'),
                Str::startsWith($routeName, 'proveedores.') => Supplier::query()->value('id'),
                Str::startsWith($routeName, 'proformas.') => Proforma::query()->value('id'),
                Str::startsWith($routeName, 'reparaciones.') => RepairOrder::query()->value('id'),
                Str::startsWith($routeName, 'ajustes.') => InventoryAdjustment::query()->value('id'),
                default => Sale::query()->value('id')
                    ?? Product::query()->value('id')
                    ?? Client::query()->value('id'),
            },
            'saleId' => Sale::query()->value('id'),
            'clientId' => Client::query()->value('id'),
            'product' => Product::query()->value('id'),
            'employee' => Employee::query()->value('id') ?? Employee::query()->create([
                'name' => 'Empleado Cobertura',
                'position' => 'Operativo',
                'salary' => 10000,
                'phone' => null,
                'address' => null,
                'contract_type' => 'full_time',
                'payment_frequency' => 'monthly',
            ])->id,
            'leave', 'leaveRequest' => LeaveRequest::query()->value('id'),
            'loan' => Loan::query()->value('id'),
            'bonus' => Bonus::query()->value('id'),
            'deduction' => Deduction::query()->value('id'),
            'exchangeRate' => ExchangeRate::query()->value('id'),
            'operationalExpense' => OperationalExpense::query()->value('id'),
            'journalEntry' => JournalEntry::query()->value('id'),
            'account' => Account::query()->value('id'),
            'centro_costo' => CostCenter::query()->value('id'),
            'tax' => Tax::query()->value('id'),
            'user' => User::query()->value('id'),
            'role' => Role::query()->value('id'),
            'paymentId' => CreditPayment::query()->value('id'),
            'warehouse' => Warehouse::query()->value('id'),
            'priceList' => PriceList::query()->value('id'),
            'item' => PriceListItem::query()->value('id'),
            'conversion' => ProductUnitConversion::query()->value('id'),
            'report' => 'estado-resultados',
            'code' => Product::query()->value('code') ?? 'TEST-CODE',
            default => null,
        };

        if ($value === null) {
            return null;
        }

        $params[$name] = $value;
    }

    return $params;
}

test('every authenticated GET route renders without server errors for admin', function () {
    $admin = productionSeededAdmin();

    $skipped = [
        'login',
        'logout',
        'facturacion.pdf',
        'facturacion.print',
        'reportes.export',
        'inventario.export',
        'proveedores.export',
        'contabilidad.diario.export',
        'contabilidad.balance-comprobacion.export',
        'contabilidad.balance-general.export',
        'contabilidad.estado-resultados.export',
        'contabilidad.flujo-caja.export',
        'contabilidad.reportes.pdf',
        'contabilidad.reportes.excel',
        'creditos.export',
        'inventario.lookup',
        'inventario.next-code',
        'facturacion.pos-products',
        'proveedores.credit_info',
    ];

    $failures = [];

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();

        if ($name === null || in_array($name, $skipped, true)) {
            continue;
        }

        $methods = collect($route->methods())->intersect(['GET', 'HEAD']);
        if ($methods->isEmpty()) {
            continue;
        }

        if (Str::startsWith($route->uri(), 'api/') || Str::contains($route->uri(), 'storage')) {
            continue;
        }

        $params = resolveProductionRouteParameters($route->parameterNames(), $name);

        if ($params === null) {
            continue;
        }

        $url = route($name, $params, false);

        $response = test()->actingAs($admin)->get($url);

        if ($response->status() >= 500) {
            $failures[] = "{$name} ({$url}) => HTTP {$response->status()}";
        }
    }

    expect($failures)->toBeEmpty('Server errors on routes: '.implode('; ', $failures));
});

test('guest cannot access protected modules', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'pos' => ['facturacion.pos'],
    'inventario' => ['inventario.index'],
    'settings' => ['settings.index'],
    'contabilidad' => ['contabilidad.dashboard'],
    'planilla' => ['planilla.index'],
]);
