<?php

use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ProformaController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin autenticación)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    Route::get('/facturacion/create', [FacturacionController::class, 'create'])->name('facturacion.create');
    Route::get('/facturacion/pos', [FacturacionController::class, 'pos'])->name('facturacion.pos');
    Route::get('/facturacion/pos/daily-report', [FacturacionController::class, 'posDailyReport'])->name('facturacion.pos-daily-report');
    Route::post('/facturacion/pos-store', [FacturacionController::class, 'posStore'])->name('facturacion.pos-store');
    Route::get('/facturacion/change/{saleId}', [FacturacionController::class, 'change'])->name('facturacion.change');
    Route::get('/facturacion/receipt/{saleId}', [FacturacionController::class, 'receipt'])->name('facturacion.receipt');
    Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
    Route::get('/facturacion/{id}', [FacturacionController::class, 'show'])->name('facturacion.show');
    Route::get('/facturacion/{id}/edit', [FacturacionController::class, 'edit'])->name('facturacion.edit');
    Route::match(['put', 'patch'], '/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');
    Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])->name('facturacion.destroy');
    Route::get('/facturacion/print', [FacturacionController::class, 'print'])->name('facturacion.print');

    // Rutas de Crédito y Abonos
    Route::get('/creditos', [CreditController::class, 'index'])->name('creditos.index');
    Route::get('/creditos/search', [CreditController::class, 'search'])->name('creditos.search');
    Route::get('/creditos/statement/{clientId}', [CreditController::class, 'statement'])->name('creditos.statement');
    Route::get('/creditos/cliente/{clientId}', [CreditController::class, 'show'])->name('creditos.show');
    Route::get('/creditos/abono/nuevo/{clientId}', [CreditController::class, 'create'])->name('creditos.create');
    Route::post('/creditos/abono', [CreditController::class, 'store'])->name('creditos.store');
    Route::get('/creditos/payment/{paymentId}/invoice', [CreditController::class, 'invoice'])->name('creditos.invoice');
    Route::get('/creditos/vencidos', [CreditController::class, 'overdue'])->name('creditos.overdue');
    Route::get('/creditos/reporte', [CreditController::class, 'report'])->name('creditos.report');
    Route::get('/creditos/reporte/export', [CreditController::class, 'export'])->name('creditos.export');

    // Arqueo de caja (cierre diario)
    Route::get('/arqueo', [\App\Http\Controllers\ArqueoController::class, 'index'])->name('arqueo.index');
    Route::post('/arqueo/open', [\App\Http\Controllers\ArqueoController::class, 'open'])->name('arqueo.open');
    Route::post('/arqueo/run', [\App\Http\Controllers\ArqueoController::class, 'run'])->name('arqueo.run');

    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/create', [InventarioController::class, 'create'])->name('inventario.create');
    Route::get('/inventario/rapido', [InventarioController::class, 'quick'])->name('inventario.quick');
    Route::post('/inventario/rapido', [InventarioController::class, 'quickStore'])->name('inventario.quick-store');
    Route::get('/inventario/buscar/{code}', [InventarioController::class, 'lookupCode'])->name('inventario.lookup');
    Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
    Route::get('/inventario/dashboard', [InventarioController::class, 'dashboard'])->name('inventario.dashboard');
    Route::get('/inventario/carga-masiva', [InventarioController::class, 'bulk'])->name('inventario.bulk');
    Route::post('/inventario/carga-masiva', [InventarioController::class, 'bulkStore'])->name('inventario.bulk-store');
    Route::get('/inventario/next-code', [InventarioController::class, 'nextCode'])->name('inventario.next-code');
    Route::post('/inventario/reconciliar', [InventarioController::class, 'reconcile'])->name('inventario.reconcile');
    Route::get('/inventario/export', [InventarioController::class, 'export'])->name('inventario.export');
    Route::get('/inventario/{id}', [InventarioController::class, 'show'])->name('inventario.show')->whereNumber('id');
    Route::get('/inventario/{id}/edit', [InventarioController::class, 'edit'])->name('inventario.edit')->whereNumber('id');
    Route::match(['put', 'patch'], '/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update')->whereNumber('id');
    Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy')->whereNumber('id');

    Route::get('/movimientos', [MovimientosController::class, 'index'])->name('movimientos.index');

    Route::get('/dashboard-general', [DashboardController::class, 'index'])->name('dashboard.general');

    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
    Route::get('/proveedores/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show'])->name('proveedores.show');
    Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::match(['put', 'patch'], '/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    Route::get('/proveedores/{id}/credit-info', [ProveedorController::class, 'getCreditInfo'])->name('proveedores.credit_info');
    Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export');

    Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
    Route::get('/compras/create', [CompraController::class, 'create'])->name('compras.create');
    Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
    Route::get('/compras/{id}/edit', [CompraController::class, 'edit'])->name('compras.edit');
    Route::match(['put', 'patch'], '/compras/{id}', [CompraController::class, 'update'])->name('compras.update');
    Route::delete('/compras/{id}', [CompraController::class, 'destroy'])->name('compras.destroy');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
    Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::match(['put', 'patch'], '/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::post('/clientes/{id}/toggle-credit', [ClienteController::class, 'toggleCredit'])->name('clientes.toggle_credit');
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

    Route::get('/planilla', [PlanillaController::class, 'index'])->name('planilla.index');

    // Proformas / Cotizaciones
    Route::get('/proformas', [ProformaController::class, 'index'])->name('proformas.index');
    Route::get('/proformas/nueva', [ProformaController::class, 'pos'])->name('proformas.pos');
    Route::post('/proformas', [ProformaController::class, 'store'])->name('proformas.store');
    Route::get('/proformas/{id}', [ProformaController::class, 'show'])->name('proformas.show');
    Route::patch('/proformas/{id}/status', [ProformaController::class, 'updateStatus'])->name('proformas.status');
    Route::delete('/proformas/{id}', [ProformaController::class, 'destroy'])->name('proformas.destroy');
    Route::get('/proformas/{id}/pdf', [ProformaController::class, 'pdf'])->name('proformas.pdf');
    Route::get('/proformas/{id}/ticket', [ProformaController::class, 'ticket'])->name('proformas.ticket');
    Route::post('/proformas/{id}/convert', [ProformaController::class, 'convertToSale'])->name('proformas.convert');

    // Reportes solo para admin
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/export', [ReporteController::class, 'exportExcel'])->name('reportes.export');
    });

    Route::get('/facturacion/pdf', [FacturacionController::class, 'pdf'])->name('facturacion.pdf');

    Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');

    // Ajustes de inventario solo para admin
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/ajustes', [AjusteInventarioController::class, 'index'])->name('ajustes.index');
        Route::get('/ajustes/create', [AjusteInventarioController::class, 'create'])->name('ajustes.create');
        Route::post('/ajustes', [AjusteInventarioController::class, 'store'])->name('ajustes.store');
        Route::get('/ajustes/{id}', [AjusteInventarioController::class, 'show'])->name('ajustes.show');
        Route::delete('/ajustes/{id}', [AjusteInventarioController::class, 'destroy'])->name('ajustes.destroy');
        Route::get('/api/products/{id}/info', [AjusteInventarioController::class, 'getProductInfo'])->name('api.products.info');
    });

}); // Cierre del grupo auth middleware
