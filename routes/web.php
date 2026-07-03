<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AuthController;

// Rutas públicas (sin autenticación)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth'])->group(function () {

Route::get('/', [DashboardController::class, 'index']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
Route::get('/facturacion/create', [FacturacionController::class, 'create'])->name('facturacion.create');
Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
Route::get('/facturacion/{id}', [FacturacionController::class, 'show'])->name('facturacion.show');
Route::get('/facturacion/{id}/edit', [FacturacionController::class, 'edit'])->name('facturacion.edit');
Route::match(['put','patch'],'/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');
Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])->name('facturacion.destroy');
Route::get('/facturacion/print', [FacturacionController::class, 'print'])->name('facturacion.print');

Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
Route::get('/inventario/create', [InventarioController::class, 'create'])->name('inventario.create');
Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
Route::get('/inventario/{id}', [InventarioController::class, 'show'])->name('inventario.show');
Route::get('/inventario/{id}/edit', [InventarioController::class, 'edit'])->name('inventario.edit');
Route::match(['put','patch'],'/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy');
Route::get('/inventario-dashboard', [InventarioController::class, 'dashboard'])->name('inventario.dashboard');
Route::get('/inventario/export', [InventarioController::class, 'export'])->name('inventario.export');

Route::get('/movimientos', [MovimientosController::class, 'index'])->name('movimientos.index');

Route::get('/dashboard-general', [DashboardController::class, 'index'])->name('dashboard.general');

Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
Route::get('/proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
Route::get('/proveedores/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
Route::get('/proveedores/{id}', [ProveedorController::class, 'show'])->name('proveedores.show');
Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
Route::match(['put','patch'],'/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
Route::get('/proveedores/{id}/credit-info', [ProveedorController::class, 'getCreditInfo'])->name('proveedores.credit_info');
Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export');

Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
Route::get('/compras/create', [CompraController::class, 'create'])->name('compras.create');
Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show');
Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
Route::get('/compras/{id}/edit', [CompraController::class, 'edit'])->name('compras.edit');
Route::match(['put','patch'],'/compras/{id}', [CompraController::class, 'update'])->name('compras.update');
Route::delete('/compras/{id}', [CompraController::class, 'destroy'])->name('compras.destroy');

Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
Route::match(['put','patch'],'/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

Route::get('/planilla', [PlanillaController::class, 'index'])->name('planilla.index');

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
