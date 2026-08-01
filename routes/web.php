<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\AccountingReportExportController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\ArqueoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceBrandController;
use App\Http\Controllers\DiarioController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\IncomeStatementController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RepairServiceController;
use App\Http\Controllers\ReparacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin autenticación)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth'])->group(function () {

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('module:ventas')->group(function () {
        Route::get('/facturacion/create', [FacturacionController::class, 'create'])
            ->middleware('permission:ventas.create')->name('facturacion.create');
        Route::middleware('permission:ventas.view')->group(function () {
            Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
            Route::get('/facturacion/pos', [FacturacionController::class, 'pos'])->name('facturacion.pos');
            Route::get('/facturacion/pos/products', [FacturacionController::class, 'posProducts'])->name('facturacion.pos-products');
            Route::get('/facturacion/pos/daily-report', [FacturacionController::class, 'posDailyReport'])->name('facturacion.pos-daily-report');
            Route::get('/facturacion/change/{saleId}', [FacturacionController::class, 'change'])->name('facturacion.change');
            Route::get('/facturacion/receipt/{saleId}', [FacturacionController::class, 'receipt'])->name('facturacion.receipt');
            Route::get('/facturacion/print', [FacturacionController::class, 'print'])->name('facturacion.print');
            Route::get('/facturacion/pdf', [FacturacionController::class, 'pdf'])->name('facturacion.pdf');
            Route::get('/facturacion/{id}', [FacturacionController::class, 'show'])->name('facturacion.show');
        });
        Route::middleware('permission:ventas.create')->group(function () {
            Route::post('/facturacion/pos-store', [FacturacionController::class, 'posStore'])->name('facturacion.pos-store');
            Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
        });
        Route::middleware('permission:ventas.edit')->group(function () {
            Route::get('/facturacion/{id}/edit', [FacturacionController::class, 'edit'])->name('facturacion.edit');
            Route::match(['put', 'patch'], '/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');
        });
        Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])
            ->middleware('permission:ventas.delete')->name('facturacion.destroy');
    });

    // Rutas de Crédito y Abonos
    Route::middleware('module:creditos')->group(function () {
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
    });

    // Arqueo de caja (cierre diario)
    Route::middleware('module:caja')->group(function () {
        Route::get('/arqueo', [ArqueoController::class, 'index'])->name('arqueo.index');
        Route::post('/arqueo/open', [ArqueoController::class, 'open'])->name('arqueo.open');
        Route::post('/arqueo/run', [ArqueoController::class, 'run'])->name('arqueo.run');
    });

    Route::middleware('module:inventario')->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('/inventario/create', [InventarioController::class, 'create'])->name('inventario.create');
        Route::get('/inventario/rapido', [InventarioController::class, 'quick'])->name('inventario.quick');
        Route::post('/inventario/rapido', [InventarioController::class, 'quickStore'])->name('inventario.quick-store');
        Route::post('/categorias', [InventarioController::class, 'storeCategory'])->name('categorias.store');
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
    });

    Route::get('/dashboard-general', [DashboardController::class, 'index'])->name('dashboard.general');

    Route::middleware('module:proveedores')->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('/proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::get('/proveedores/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::get('/proveedores/{id}', [ProveedorController::class, 'show'])->name('proveedores.show');
        Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::match(['put', 'patch'], '/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
        Route::get('/proveedores/{id}/credit-info', [ProveedorController::class, 'getCreditInfo'])->name('proveedores.credit_info');
        Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export');
    });

    Route::middleware('module:compras')->group(function () {
        Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
        Route::get('/compras/create', [CompraController::class, 'create'])->name('compras.create');
        Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show');
        Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
        Route::get('/compras/{id}/edit', [CompraController::class, 'edit'])->name('compras.edit');
        Route::match(['put', 'patch'], '/compras/{id}', [CompraController::class, 'update'])->name('compras.update');
        Route::delete('/compras/{id}', [CompraController::class, 'destroy'])->name('compras.destroy');
    });

    Route::middleware('module:clientes')->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
        Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::match(['put', 'patch'], '/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::post('/clientes/{id}/toggle-credit', [ClienteController::class, 'toggleCredit'])->name('clientes.toggle_credit');
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    });

    Route::get('/planilla', [PlanillaController::class, 'index'])->middleware('module:planilla')->name('planilla.index');

    // Proformas / Cotizaciones
    Route::middleware('module:proformas')->group(function () {
        Route::get('/proformas', [ProformaController::class, 'index'])->name('proformas.index');
        Route::get('/proformas/nueva', [ProformaController::class, 'pos'])->name('proformas.pos');
        Route::post('/proformas', [ProformaController::class, 'store'])->name('proformas.store');
        Route::get('/proformas/{id}', [ProformaController::class, 'show'])->name('proformas.show');
        Route::patch('/proformas/{id}/status', [ProformaController::class, 'updateStatus'])->name('proformas.status');
        Route::delete('/proformas/{id}', [ProformaController::class, 'destroy'])->name('proformas.destroy');
        Route::get('/proformas/{id}/pdf', [ProformaController::class, 'pdf'])->name('proformas.pdf');
        Route::get('/proformas/{id}/ticket', [ProformaController::class, 'ticket'])->name('proformas.ticket');
        Route::post('/proformas/{id}/convert', [ProformaController::class, 'convertToSale'])->name('proformas.convert');
    });

    // Reparaciones
    Route::middleware('module:reparaciones')->group(function () {
        Route::get('/device-brands', [DeviceBrandController::class, 'index'])->name('device-brands.index');
        Route::post('/device-brands', [DeviceBrandController::class, 'store'])->name('device-brands.store');
        Route::get('/repair-services', [RepairServiceController::class, 'index'])->name('repair-services.index');
        Route::post('/repair-services', [RepairServiceController::class, 'store'])->name('repair-services.store');
        Route::get('/reparaciones', [ReparacionController::class, 'index'])->name('reparaciones.index');
        Route::get('/reparaciones/nueva', [ReparacionController::class, 'create'])->name('reparaciones.create');
        Route::post('/reparaciones', [ReparacionController::class, 'store'])->name('reparaciones.store');
        Route::get('/reparaciones/{id}', [ReparacionController::class, 'show'])->name('reparaciones.show');
        Route::get('/reparaciones/{id}/edit', [ReparacionController::class, 'edit'])->name('reparaciones.edit');
        Route::put('/reparaciones/{id}', [ReparacionController::class, 'update'])->name('reparaciones.update');
        Route::patch('/reparaciones/{id}/status', [ReparacionController::class, 'updateStatus'])->name('reparaciones.status');
        Route::delete('/reparaciones/{id}', [ReparacionController::class, 'destroy'])->name('reparaciones.destroy');
        Route::get('/reparaciones/{id}/ticket', [ReparacionController::class, 'ticket'])->name('reparaciones.ticket');
        Route::get('/reparaciones/{id}/pdf', [ReparacionController::class, 'pdf'])->name('reparaciones.pdf');
    });

    // Reportes solo para admin
    Route::middleware(['module:reportes', 'permission:reportes.view'])->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/export', [ReporteController::class, 'exportExcel'])->name('reportes.export');
    });

    Route::get('/nomina', [NominaController::class, 'index'])->middleware('module:planilla')->name('nomina.index');

    // Ajustes de inventario solo para admin
    Route::middleware(['module:inventario', 'role:admin'])->group(function () {
        Route::get('/ajustes', [AjusteInventarioController::class, 'index'])->name('ajustes.index');
        Route::get('/ajustes/create', [AjusteInventarioController::class, 'create'])->name('ajustes.create');
        Route::post('/ajustes', [AjusteInventarioController::class, 'store'])->name('ajustes.store');
        Route::get('/ajustes/{id}', [AjusteInventarioController::class, 'show'])->name('ajustes.show');
        Route::delete('/ajustes/{id}', [AjusteInventarioController::class, 'destroy'])->name('ajustes.destroy');
        Route::get('/api/products/{id}/info', [AjusteInventarioController::class, 'getProductInfo'])->name('api.products.info');
    });

    // Configuración del sistema solo para admin
    Route::middleware(['module:configuracion', 'permission:configuracion.view'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::middleware('permission:configuracion.manage_users')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
            Route::get('/users/{user}/reset-password', [UserController::class, 'resetPasswordForm'])->name('users.reset-password.form');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        Route::middleware('permission:configuracion.manage_roles')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/compare', [RoleController::class, 'compare'])->name('roles.compare');
            Route::get('/roles/{role}/clone', [RoleController::class, 'cloneForm'])->name('roles.clone.form');
            Route::post('/roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
            Route::get('/roles/{role}/delete', [RoleController::class, 'deleteForm'])->name('roles.delete.form');
            Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });
        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:configuracion.manage_permissions')->name('permissions');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::post('/general', [SettingsController::class, 'updateGeneral'])->middleware('permission:configuracion.edit')->name('general.update');
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::middleware('permission:configuracion.edit')->group(function () {
            Route::get('/taxes/create', [TaxController::class, 'create'])->name('taxes.create');
            Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store');
            Route::get('/taxes/{tax}/edit', [TaxController::class, 'edit'])->name('taxes.edit');
            Route::match(['put', 'patch'], '/taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
            Route::delete('/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');
        });
        Route::middleware('permission:configuracion.manage_modules')->group(function () {
            Route::get('/modules', [ModuleController::class, 'index'])->name('modules');
            Route::put('/modules', [ModuleController::class, 'update'])->name('modules.update');
        });
        Route::match(['get', 'post'], '/security', [SettingsController::class, 'security'])->name('security');
        Route::match(['get', 'post'], '/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        Route::match(['get', 'post'], '/sequences', [SettingsController::class, 'sequences'])->name('sequences');
    });

    // Contabilidad
    Route::middleware(['module:contabilidad', 'permission:contabilidad.view'])->prefix('contabilidad')->name('contabilidad.')->group(function () {
        Route::get('/', AccountingDashboardController::class)->name('dashboard');
        Route::get('/reportes/{report}/pdf', [AccountingReportExportController::class, 'pdf'])
            ->whereIn('report', ['estado-resultados', 'balance-general', 'flujo-caja', 'balance-comprobacion', 'diario-general', 'mayor-general'])
            ->name('reportes.pdf');
        Route::get('/reportes/{report}/excel', [AccountingReportExportController::class, 'excel'])
            ->whereIn('report', ['estado-resultados', 'balance-general', 'flujo-caja', 'balance-comprobacion', 'diario-general', 'mayor-general'])
            ->name('reportes.excel');
        Route::get('/cuentas', [AccountController::class, 'index'])->name('cuentas.index');
        Route::middleware(['permission:contabilidad.create'])->group(function () {
            Route::get('/cuentas/create', [AccountController::class, 'create'])->name('cuentas.create');
            Route::post('/cuentas', [AccountController::class, 'store'])->name('cuentas.store');
        });
        Route::middleware(['permission:contabilidad.edit'])->group(function () {
            Route::get('/cuentas/{account}/edit', [AccountController::class, 'edit'])->name('cuentas.edit');
            Route::match(['put', 'patch'], '/cuentas/{account}', [AccountController::class, 'update'])->name('cuentas.update');
        });
        Route::delete('/cuentas/{account}', [AccountController::class, 'destroy'])
            ->middleware(['permission:contabilidad.delete'])
            ->name('cuentas.destroy');

        // Asientos contables (pólizas)
        Route::get('/asientos', [JournalEntryController::class, 'index'])->name('asientos.index');
        Route::get('/asientos/{journalEntry}', [JournalEntryController::class, 'show'])->name('asientos.show');
        Route::middleware(['permission:contabilidad.create'])->group(function () {
            Route::get('/asientos-nuevo', [JournalEntryController::class, 'create'])->name('asientos.create');
            Route::post('/asientos', [JournalEntryController::class, 'store'])->name('asientos.store');
        });
        Route::middleware(['permission:contabilidad.edit'])->group(function () {
            Route::post('/asientos/{journalEntry}/contabilizar', [JournalEntryController::class, 'post'])->name('asientos.post');
            Route::post('/asientos/{journalEntry}/anular', [JournalEntryController::class, 'void'])->name('asientos.void');
        });
        Route::delete('/asientos/{journalEntry}', [JournalEntryController::class, 'destroy'])
            ->middleware(['permission:contabilidad.delete'])
            ->name('asientos.destroy');

        // Diario General
        Route::get('/diario', [DiarioController::class, 'index'])->name('diario.index');
        Route::get('/diario/export', [DiarioController::class, 'export'])->name('diario.export');

        // Mayor General
        Route::get('/mayor', [LedgerController::class, 'index'])->name('mayor.index');

        // Balance de Comprobación
        Route::get('/balance-comprobacion', [TrialBalanceController::class, 'index'])->name('balance-comprobacion.index');
        Route::get('/balance-comprobacion/export', [TrialBalanceController::class, 'export'])->name('balance-comprobacion.export');

        // Estado de Resultados
        Route::get('/estado-resultados', [IncomeStatementController::class, 'index'])->name('estado-resultados.index');
        Route::get('/estado-resultados/export', [IncomeStatementController::class, 'export'])->name('estado-resultados.export');

        // Balance General
        Route::get('/balance-general', [BalanceSheetController::class, 'index'])->name('balance-general.index');
        Route::get('/balance-general/export', [BalanceSheetController::class, 'export'])->name('balance-general.export');

        // Flujo de Caja
        Route::get('/flujo-caja', [CashFlowController::class, 'index'])->name('flujo-caja.index');
        Route::get('/flujo-caja/export', [CashFlowController::class, 'export'])->name('flujo-caja.export');

        // Centros de Costo
        Route::get('/centros-costo', [CostCenterController::class, 'index'])->name('centros-costo.index');
        Route::middleware(['permission:contabilidad.create'])->group(function () {
            Route::get('/centros-costo/create', [CostCenterController::class, 'create'])->name('centros-costo.create');
            Route::post('/centros-costo', [CostCenterController::class, 'store'])->name('centros-costo.store');
        });
        Route::middleware(['permission:contabilidad.edit'])->group(function () {
            Route::get('/centros-costo/{centro_costo}/edit', [CostCenterController::class, 'edit'])->name('centros-costo.edit');
            Route::match(['put', 'patch'], '/centros-costo/{centro_costo}', [CostCenterController::class, 'update'])->name('centros-costo.update');
        });
        Route::delete('/centros-costo/{centro_costo}', [CostCenterController::class, 'destroy'])
            ->middleware(['permission:contabilidad.delete'])
            ->name('centros-costo.destroy');

        // Períodos Fiscales (cierre mensual/anual)
        Route::get('/periodos', [FiscalPeriodController::class, 'index'])->name('periodos.index');
        Route::middleware(['permission:contabilidad.close_period'])->group(function () {
            Route::post('/periodos/{periodo}/cerrar', [FiscalPeriodController::class, 'closeMonth'])->name('periodos.cerrar');
            Route::post('/periodos/{periodo}/reabrir', [FiscalPeriodController::class, 'reopenMonth'])->name('periodos.reabrir');
            Route::post('/periodos/{periodo}/cerrar-anio', [FiscalPeriodController::class, 'closeYear'])->name('periodos.cerrar-anio');
            Route::post('/periodos/{periodo}/reabrir-anio', [FiscalPeriodController::class, 'reopenYear'])->name('periodos.reabrir-anio');
        });

    });

    Route::get(
        '/proveedores/{supplier}/productos',
        [CompraController::class, 'productosPorProveedor']
    )->middleware('module:compras')->name('proveedores.productos');

    Route::get('/proveedores/{supplier}/productos/buscar',
        [CompraController::class, 'buscarProductos']
    )->middleware('module:compras')->name('proveedores.productos.buscar');

}); // Cierre del grupo auth middleware
