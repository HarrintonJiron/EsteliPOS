<?php

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function presentationAdmin(): User
{
    $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador', 'is_system' => true]);
    $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $user->roles()->sync([$role->id]);

    return $user;
}

test('administrator can open executive presentation screens', function (string $routeName) {
    $this->actingAs(presentationAdmin())
        ->get(route($routeName))
        ->assertOk();
})->with([
    'analitica' => ['analitica.index'],
    'sucursales' => ['sucursales.index'],
    'rrhh hub' => ['rrhh.hub'],
    'directorio' => ['rrhh.directory'],
    'organigrama' => ['rrhh.organigram'],
    'asistencia' => ['rrhh.attendance'],
    'turnos' => ['rrhh.shifts'],
    'aguinaldo' => ['rrhh.thirteenth'],
    'inss' => ['rrhh.inss'],
    'evaluaciones' => ['rrhh.evaluations'],
    'centros de costo' => ['contabilidad.centros-costo.analytics'],
]);

test('executive analytics renders the command center', function () {
    $this->actingAs(presentationAdmin())
        ->get(route('analitica.index'))
        ->assertOk()
        ->assertSee('Sala de control gerencial')
        ->assertSee('Red de sucursales')
        ->assertSee('Clientes ancla')
        ->assertSee('Tendencia comercial');
});

test('module hubs share the command center pattern', function (string $routeName, string $heading) {
    $this->actingAs(presentationAdmin())
        ->get(route($routeName))
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('ex-hero', false);
})->with([
    'dashboard' => ['dashboard.general', 'Sala de control'],
    'facturacion' => ['facturacion.index', 'Facturación'],
    'compras' => ['compras.index', 'Compras'],
    'inventario' => ['inventario.index', 'Catálogo de productos'],
    'clientes' => ['clientes.index', 'Clientes'],
    'creditos' => ['creditos.index', 'Créditos'],
    'proveedores' => ['proveedores.index', 'Proveedores'],
    'proformas' => ['proformas.index', 'Proformas'],
    'reparaciones' => ['reparaciones.index', 'Reparaciones'],
    'planilla' => ['planilla.index', 'Dashboard de planilla'],
    'contabilidad' => ['contabilidad.dashboard', 'Dashboard contable'],
    'reportes' => ['reportes.index', 'Reportes y análisis'],
    'rrhh' => ['rrhh.hub', 'Recursos humanos'],
    'settings' => ['settings.index', 'Configura el negocio desde un solo lugar'],
    'ayuda' => ['help.index', '¿Cómo podemos ayudarte?'],
]);

test('administrator can open a branch profile', function () {
    $branch = Branch::query()->create([
        'code' => 'SUC-01',
        'name' => 'Casa matriz Centro',
        'type' => 'matriz',
        'city' => 'Estelí',
        'address' => 'Costado norte del mercado, Estelí',
        'manager_name' => 'Roberto Cano',
        'share_percent' => 48,
        'is_active' => true,
    ]);

    Employee::query()->create([
        'name' => 'Ana Cajero',
        'position' => 'Cajera',
        'salary' => 12000,
        'contract_type' => 'full_time',
        'payment_frequency' => 'monthly',
        'is_active' => true,
        'branch_id' => $branch->id,
        'hire_date' => now()->subYears(2),
    ]);

    $this->actingAs(presentationAdmin())
        ->get(route('sucursales.show', $branch))
        ->assertOk()
        ->assertSee('Casa matriz Centro')
        ->assertSee('Ana Cajero');
});

test('administrator can configure branches without demonstration data', function () {
    $admin = presentationAdmin();

    $this->actingAs($admin)
        ->get(route('sucursales.index'))
        ->assertOk()
        ->assertSee('Crear primera sucursal')
        ->assertDontSee('Pueblo Nuevo');

    $this->actingAs($admin)
        ->post(route('sucursales.store'), [
            'code' => 'NORTE-01',
            'name' => 'Punto Norte',
            'type' => 'sucursal',
            'city' => 'Jinotega',
            'address' => 'Avenida central',
            'phone' => '2222-3333',
            'manager_name' => 'María López',
            'is_active' => '1',
        ])
        ->assertRedirect();

    $branch = Branch::query()->where('code', 'NORTE-01')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('sucursales.update', $branch), [
            'code' => 'NORTE-01',
            'name' => 'Punto Norte actualizado',
            'type' => 'matriz',
            'city' => 'Jinotega',
            'is_active' => '1',
        ])
        ->assertRedirect(route('sucursales.show', $branch));

    expect($branch->fresh())
        ->name->toBe('Punto Norte actualizado')
        ->type->toBe('matriz');
});

test('branch analytics never invents values for a branch without a warehouse', function () {
    Branch::query()->create([
        'code' => 'REAL-01',
        'name' => 'Sucursal sin bodega',
        'type' => 'sucursal',
        'share_percent' => 90,
        'is_active' => true,
    ]);

    $this->actingAs(presentationAdmin())
        ->get(route('sucursales.index'))
        ->assertOk()
        ->assertSee('Sucursal sin bodega')
        ->assertSee('C$ 0');
});

test('administrator can open the new managerial reports', function (string $reportType, string $heading) {
    $this->actingAs(presentationAdmin())
        ->get(route('reportes.index', ['report_type' => $reportType]))
        ->assertOk()
        ->assertSee($heading);
})->with([
    'abc' => ['abc', 'Clasificación ABC'],
    'aging' => ['aging', 'Antigüedad de cartera'],
    'slow' => ['slow', 'lenta rotación'],
    'top clients' => ['top_clients', 'Clientes con mayor compra'],
    'sellers' => ['sellers', 'Desempeño de vendedores'],
    'categories' => ['categories', 'Ventas por categoría'],
]);
