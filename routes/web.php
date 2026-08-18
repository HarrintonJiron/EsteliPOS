<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\AccountingReportExportController;
use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\ArqueoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\DeviceBrandController;
use App\Http\Controllers\DiarioController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncomeStatementController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\OperationalExpenseController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\PublicImageController;
use App\Http\Controllers\RepairServiceController;
use App\Http\Controllers\ReparacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseTransferController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin autenticación)
Route::get('/media/{directory}/{filename}', PublicImageController::class)
    ->whereIn('directory', ['company', 'products'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.show');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth'])->group(function () {

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/acceso-limitado', [HomeController::class, 'unavailable'])->name('access.unavailable');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/cambiar-usuario', [AuthController::class, 'switchUser'])->name('auth.switch-user');

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
            Route::post('/facturacion/pos/products/{product}/image', [FacturacionController::class, 'updateProductImage'])
                ->middleware('permission:inventario.edit')->name('facturacion.pos-product-image')->whereNumber('product');
            Route::post('/facturacion/pos-store', [FacturacionController::class, 'posStore'])->name('facturacion.pos-store');
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
        Route::middleware('permission:creditos.view')->group(function () {
            Route::get('/creditos', [CreditController::class, 'index'])->name('creditos.index');
            Route::get('/creditos/search', [CreditController::class, 'search'])->name('creditos.search');
            Route::get('/creditos/statement/{clientId}', [CreditController::class, 'statement'])->name('creditos.statement');
            Route::get('/creditos/cliente/{clientId}', [CreditController::class, 'show'])->name('creditos.show');
            Route::get('/creditos/payment/{paymentId}/invoice', [CreditController::class, 'invoice'])->name('creditos.invoice');
            Route::get('/creditos/vencidos', [CreditController::class, 'overdue'])->name('creditos.overdue');
            Route::get('/creditos/reporte', [CreditController::class, 'report'])->name('creditos.report');
        });
        Route::middleware('permission:creditos.create')->group(function () {
            Route::get('/creditos/abono/nuevo/{clientId}', [CreditController::class, 'create'])->name('creditos.create');
            Route::post('/creditos/abono', [CreditController::class, 'store'])->name('creditos.store');
        });
        Route::get('/creditos/reporte/export', [CreditController::class, 'export'])
            ->middleware('permission:creditos.export')->name('creditos.export');
    });

    // Arqueo de caja (cierre diario)
    Route::middleware('module:caja')->group(function () {
        Route::get('/arqueo', [ArqueoController::class, 'index'])
            ->middleware('permission:caja.view')->name('arqueo.index');
        Route::post('/arqueo/open', [ArqueoController::class, 'open'])
            ->middleware('permission:caja.open')->name('arqueo.open');
        Route::post('/arqueo/run', [ArqueoController::class, 'run'])
            ->middleware('permission:caja.close')->name('arqueo.run');
    });

    Route::middleware('module:inventario')->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index'])->middleware('permission:inventario.view')->name('inventario.index');
        Route::get('/inventario/create', [InventarioController::class, 'create'])->middleware('permission:inventario.create')->name('inventario.create');
        Route::get('/inventario/rapido', [InventarioController::class, 'quick'])->middleware('permission:inventario.create')->name('inventario.quick');
        Route::post('/inventario/rapido', [InventarioController::class, 'quickStore'])->middleware('permission:inventario.create')->name('inventario.quick-store');
        Route::get('/inventario/buscar/{code}', [InventarioController::class, 'lookupCode'])->middleware('permission:inventario.view')->name('inventario.lookup');
        Route::post('/inventario', [InventarioController::class, 'store'])->middleware('permission:inventario.create')->name('inventario.store');
        Route::get('/inventario/dashboard', [InventarioController::class, 'dashboard'])->middleware('permission:inventario.view')->name('inventario.dashboard');
        Route::get('/inventario/unidades', [InventarioController::class, 'units'])->middleware('permission:inventario.view')->name('inventario.units.index');
        Route::post('/inventario/unidades', [InventarioController::class, 'storeUnit'])->middleware('permission:inventario.create')->name('inventario.units.store');
        Route::put('/inventario/unidades/{unit}', [InventarioController::class, 'updateUnit'])->middleware('permission:inventario.edit')->name('inventario.units.update');
        Route::post('/inventario/convertir', [InventarioController::class, 'convertUnits'])->middleware('permission:inventario.adjust')->name('inventario.convert');
        Route::get('/inventario/bodegas', [WarehouseController::class, 'index'])->middleware('permission:inventario.view')->name('inventario.warehouses.index');
        Route::get('/inventario/bodegas/nueva', [WarehouseController::class, 'create'])->middleware('permission:inventario.create')->name('inventario.warehouses.create');
        Route::post('/inventario/bodegas', [WarehouseController::class, 'store'])->middleware('permission:inventario.create')->name('inventario.warehouses.store');
        Route::get('/inventario/bodegas/{warehouse}', [WarehouseController::class, 'show'])->middleware('permission:inventario.view')->name('inventario.warehouses.show');
        Route::get('/inventario/bodegas/{warehouse}/edit', [WarehouseController::class, 'edit'])->middleware('permission:inventario.edit')->name('inventario.warehouses.edit');
        Route::put('/inventario/bodegas/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:inventario.edit')->name('inventario.warehouses.update');
        Route::post('/inventario/bodegas/{warehouse}/transferir', [WarehouseController::class, 'transfer'])->middleware('permission:inventario.adjust')->name('inventario.warehouses.transfer');
        Route::post('/inventario/bodegas/{warehouse}/estantes', [WarehouseController::class, 'storeShelf'])->middleware('permission:inventario.edit')->name('inventario.warehouses.shelves.store');
        Route::delete('/inventario/bodegas/{warehouse}/estantes/{shelf}', [WarehouseController::class, 'destroyShelf'])->middleware('permission:inventario.edit')->name('inventario.warehouses.shelves.destroy');
        Route::delete('/inventario/bodegas/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('permission:inventario.delete')->name('inventario.warehouses.destroy');
        Route::get('/inventario/transferencias', [WarehouseTransferController::class, 'index'])->middleware('permission:inventario.view')->name('inventario.transfers.index');
        Route::post('/inventario/transferencias', [WarehouseTransferController::class, 'store'])->middleware('permission:inventario.adjust')->name('inventario.transfers.store');
        Route::get('/inventario/transferencias/stock', [WarehouseTransferController::class, 'stockAvailability'])->middleware('permission:inventario.view')->name('inventario.transfers.stock');
        Route::get('/inventario/listas-precios', [PriceListController::class, 'index'])->middleware('permission:inventario.view')->name('inventario.price-lists.index');
        Route::get('/inventario/listas-precios/nueva', [PriceListController::class, 'create'])->middleware('permission:inventario.create')->name('inventario.price-lists.create');
        Route::post('/inventario/listas-precios', [PriceListController::class, 'store'])->middleware('permission:inventario.create')->name('inventario.price-lists.store');
        Route::get('/inventario/listas-precios/{priceList}', [PriceListController::class, 'show'])->middleware('permission:inventario.view')->name('inventario.price-lists.show');
        Route::get('/inventario/listas-precios/{priceList}/edit', [PriceListController::class, 'edit'])->middleware('permission:inventario.edit')->name('inventario.price-lists.edit');
        Route::put('/inventario/listas-precios/{priceList}', [PriceListController::class, 'update'])->middleware('permission:inventario.edit')->name('inventario.price-lists.update');
        Route::post('/inventario/listas-precios/{priceList}/items', [PriceListController::class, 'storeItem'])->middleware('permission:inventario.edit')->name('inventario.price-lists.items.store');
        Route::delete('/inventario/listas-precios/{priceList}/items/{item}', [PriceListController::class, 'destroyItem'])->middleware('permission:inventario.delete')->name('inventario.price-lists.items.destroy');
        Route::get('/inventario/carga-masiva', [InventarioController::class, 'bulk'])->middleware('permission:inventario.create')->name('inventario.bulk');
        Route::post('/inventario/carga-masiva', [InventarioController::class, 'bulkStore'])->middleware('permission:inventario.create')->name('inventario.bulk-store');
        Route::get('/inventario/next-code', [InventarioController::class, 'nextCode'])->middleware('permission:inventario.create')->name('inventario.next-code');
        Route::post('/inventario/reconciliar', [InventarioController::class, 'reconcile'])->middleware('permission:inventario.adjust')->name('inventario.reconcile');
        Route::post('/categorias', [InventarioController::class, 'storeCategory'])->middleware('permission:inventario.create')->name('categorias.store');
        Route::get('/inventario/export', [InventarioController::class, 'export'])->middleware('permission:inventario.export')->name('inventario.export');
        Route::post('/inventario/{id}/conversiones', [InventarioController::class, 'storeUnitConversion'])->middleware('permission:inventario.edit')->name('inventario.conversions.store')->whereNumber('id');
        Route::delete('/inventario/{id}/conversiones/{conversion}', [InventarioController::class, 'destroyUnitConversion'])->middleware('permission:inventario.delete')->name('inventario.conversions.destroy')->whereNumber('id');
        Route::get('/inventario/{id}', [InventarioController::class, 'show'])->middleware('permission:inventario.view')->name('inventario.show')->whereNumber('id');
        Route::get('/inventario/{id}/edit', [InventarioController::class, 'edit'])->middleware('permission:inventario.edit')->name('inventario.edit')->whereNumber('id');
        Route::match(['put', 'patch'], '/inventario/{id}', [InventarioController::class, 'update'])->middleware('permission:inventario.edit')->name('inventario.update')->whereNumber('id');
        Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->middleware('permission:inventario.delete')->name('inventario.destroy')->whereNumber('id');

        Route::get('/movimientos', [MovimientosController::class, 'index'])->middleware('permission:inventario.view')->name('movimientos.index');
    });

    Route::get('/dashboard-general', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard.general');

    Route::middleware('module:proveedores')->group(function () {
        Route::middleware('permission:proveedores.view')->group(function () {
            Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
            Route::get('/proveedores/{id}', [ProveedorController::class, 'show'])->whereNumber('id')->name('proveedores.show');
            Route::get('/proveedores/{id}/credit-info', [ProveedorController::class, 'getCreditInfo'])->whereNumber('id')->name('proveedores.credit_info');
        });
        Route::middleware('permission:proveedores.create')->group(function () {
            Route::get('/proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
            Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        });
        Route::middleware('permission:proveedores.edit')->group(function () {
            Route::get('/proveedores/{id}/edit', [ProveedorController::class, 'edit'])->whereNumber('id')->name('proveedores.edit');
            Route::match(['put', 'patch'], '/proveedores/{id}', [ProveedorController::class, 'update'])->whereNumber('id')->name('proveedores.update');
        });
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->whereNumber('id')->middleware('permission:proveedores.delete')->name('proveedores.destroy');
        Route::get('/proveedores/export', [ProveedorController::class, 'export'])->middleware('permission:proveedores.export')->name('proveedores.export');
    });

    Route::middleware('module:compras')->group(function () {
        Route::get('/compras/productos/buscar', [CompraController::class, 'searchProducts'])->middleware('permission:compras.view')->name('compras.products.search');
        Route::get('/compras/productos/siguiente-codigo', [CompraController::class, 'nextProductCode'])->middleware('permission:compras.create')->name('compras.products.next-code');
        Route::post('/compras/productos/rapido', [CompraController::class, 'quickStoreProduct'])->middleware('permission:compras.create')->name('compras.products.quick-store');
        Route::post('/compras/proveedores/rapido', [ProveedorController::class, 'quickStore'])->middleware('permission:compras.create')->name('compras.suppliers.quick-store');
        Route::get('/compras', [CompraController::class, 'index'])->middleware('permission:compras.view')->name('compras.index');
        Route::get('/compras/create', [CompraController::class, 'create'])->middleware('permission:compras.create')->name('compras.create');
        Route::get('/compras/{id}', [CompraController::class, 'show'])->middleware('permission:compras.view')->name('compras.show');
        Route::post('/compras', [CompraController::class, 'store'])->middleware('permission:compras.create')->name('compras.store');
        Route::get('/compras/{id}/edit', [CompraController::class, 'edit'])->middleware('permission:compras.edit')->name('compras.edit');
        Route::match(['put', 'patch'], '/compras/{id}', [CompraController::class, 'update'])->middleware('permission:compras.edit')->name('compras.update');
        Route::delete('/compras/{id}', [CompraController::class, 'destroy'])->middleware('permission:compras.delete')->name('compras.destroy');
    });

    Route::middleware('module:clientes')->group(function () {
        Route::middleware('permission:clientes.view')->group(function () {
            Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
            Route::get('/clientes/{id}', [ClienteController::class, 'show'])->whereNumber('id')->name('clientes.show');
        });
        Route::middleware('permission:clientes.create')->group(function () {
            Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
            Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
            Route::post('/clientes/quick-store', [ClienteController::class, 'quickStore'])->name('clientes.quick-store');
        });
        Route::middleware('permission:clientes.edit')->group(function () {
            Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->whereNumber('id')->name('clientes.edit');
            Route::match(['put', 'patch'], '/clientes/{id}', [ClienteController::class, 'update'])->whereNumber('id')->name('clientes.update');
            Route::post('/clientes/{id}/toggle-credit', [ClienteController::class, 'toggleCredit'])->whereNumber('id')->name('clientes.toggle_credit');
        });
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->whereNumber('id')->middleware('permission:clientes.delete')->name('clientes.destroy');
    });

    Route::get('/planilla', [PlanillaController::class, 'index'])->middleware(['module:planilla', 'permission:planilla.view'])->name('planilla.index');
    Route::get('/planilla/charts', [PlanillaController::class, 'charts'])->middleware(['module:planilla', 'permission:planilla.view'])->name('planilla.charts');

    // Gestión de Empleados
    Route::middleware('module:planilla')->group(function () {
        Route::resource('employees', EmployeeController::class)->names([
            'index' => 'employees.index',
            'create' => 'employees.create',
            'store' => 'employees.store',
            'show' => 'employees.show',
            'edit' => 'employees.edit',
            'update' => 'employees.update',
            'destroy' => 'employees.destroy',
        ])->middlewareFor(['index', 'show'], 'permission:planilla.view')
            ->middlewareFor(['create', 'store'], 'permission:planilla.create')
            ->middlewareFor(['edit', 'update'], 'permission:planilla.edit')
            ->middlewareFor('destroy', 'permission:planilla.delete');
    });

    // Gestión de Permisos
    Route::middleware('module:planilla')->group(function () {
        Route::resource('leave', LeaveRequestController::class)->names([
            'index' => 'leave.index',
            'create' => 'leave.create',
            'store' => 'leave.store',
            'show' => 'leave.show',
            'edit' => 'leave.edit',
            'update' => 'leave.update',
            'destroy' => 'leave.destroy',
        ])->middlewareFor(['index', 'show'], 'permission:planilla.view')
            ->middlewareFor(['create', 'store'], 'permission:planilla.create')
            ->middlewareFor(['edit', 'update'], 'permission:planilla.edit')
            ->middlewareFor('destroy', 'permission:planilla.delete');
        Route::post('/leave/{leave}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:planilla.approve')->name('leave.approve');
        Route::post('/leave/{leave}/reject', [LeaveRequestController::class, 'reject'])->middleware('permission:planilla.approve')->name('leave.reject');
    });

    // Gestión de Préstamos
    Route::middleware('module:planilla')->group(function () {
        Route::resource('loans', LoanController::class)->names([
            'index' => 'loans.index',
            'create' => 'loans.create',
            'store' => 'loans.store',
            'show' => 'loans.show',
            'edit' => 'loans.edit',
            'update' => 'loans.update',
            'destroy' => 'loans.destroy',
        ])->middlewareFor(['index', 'show'], 'permission:planilla.view')
            ->middlewareFor(['create', 'store'], 'permission:planilla.create')
            ->middlewareFor(['edit', 'update'], 'permission:planilla.edit')
            ->middlewareFor('destroy', 'permission:planilla.delete');
        Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])->middleware('permission:planilla.approve')->name('loans.approve');
        Route::post('/loans/{loan}/reject', [LoanController::class, 'reject'])->middleware('permission:planilla.approve')->name('loans.reject');
    });

    // Gestión de Bonos
    Route::middleware('module:planilla')->group(function () {
        Route::resource('bonuses', BonusController::class)->names([
            'index' => 'bonuses.index',
            'create' => 'bonuses.create',
            'store' => 'bonuses.store',
            'show' => 'bonuses.show',
            'edit' => 'bonuses.edit',
            'update' => 'bonuses.update',
            'destroy' => 'bonuses.destroy',
        ])->middlewareFor(['index', 'show'], 'permission:planilla.view')
            ->middlewareFor(['create', 'store'], 'permission:planilla.create')
            ->middlewareFor(['edit', 'update'], 'permission:planilla.edit')
            ->middlewareFor('destroy', 'permission:planilla.delete');
        Route::post('/bonuses/{bonus}/approve', [BonusController::class, 'approve'])->middleware('permission:planilla.approve')->name('bonuses.approve');
        Route::post('/bonuses/{bonus}/mark-paid', [BonusController::class, 'markAsPaid'])->middleware('permission:planilla.pay')->name('bonuses.mark-paid');
    });

    // Gestión de Deducciones
    Route::middleware('module:planilla')->group(function () {
        Route::resource('deductions', DeductionController::class)->names([
            'index' => 'deductions.index',
            'create' => 'deductions.create',
            'store' => 'deductions.store',
            'show' => 'deductions.show',
            'edit' => 'deductions.edit',
            'update' => 'deductions.update',
            'destroy' => 'deductions.destroy',
        ])->middlewareFor(['index', 'show'], 'permission:planilla.view')
            ->middlewareFor(['create', 'store'], 'permission:planilla.create')
            ->middlewareFor(['edit', 'update'], 'permission:planilla.edit')
            ->middlewareFor('destroy', 'permission:planilla.delete');
        Route::post('/deductions/{deduction}/approve', [DeductionController::class, 'approve'])->middleware('permission:planilla.approve')->name('deductions.approve');
        Route::post('/deductions/{deduction}/mark-deducted', [DeductionController::class, 'markAsDeducted'])->middleware('permission:planilla.pay')->name('deductions.mark-deducted');
    });

    // Proformas / Cotizaciones
    Route::middleware('module:proformas')->group(function () {
        Route::middleware('permission:proformas.view')->group(function () {
            Route::get('/proformas', [ProformaController::class, 'index'])->name('proformas.index');
            Route::get('/proformas/{id}', [ProformaController::class, 'show'])->whereNumber('id')->name('proformas.show');
            Route::get('/proformas/{id}/pdf', [ProformaController::class, 'pdf'])->whereNumber('id')->name('proformas.pdf');
            Route::get('/proformas/{id}/ticket', [ProformaController::class, 'ticket'])->whereNumber('id')->name('proformas.ticket');
        });
        Route::middleware('permission:proformas.create')->group(function () {
            Route::get('/proformas/nueva', [ProformaController::class, 'pos'])->name('proformas.pos');
            Route::post('/proformas', [ProformaController::class, 'store'])->name('proformas.store');
        });
        Route::patch('/proformas/{id}/status', [ProformaController::class, 'updateStatus'])->whereNumber('id')->middleware('permission:proformas.edit')->name('proformas.status');
        Route::delete('/proformas/{id}', [ProformaController::class, 'destroy'])->whereNumber('id')->middleware('permission:proformas.delete')->name('proformas.destroy');
        Route::post('/proformas/{id}/convert', [ProformaController::class, 'convertToSale'])->whereNumber('id')->middleware('permission:proformas.convert')->name('proformas.convert');
    });

    // Reparaciones
    Route::middleware('module:reparaciones')->group(function () {
        Route::get('/device-brands', [DeviceBrandController::class, 'index'])->middleware('permission:reparaciones.view')->name('device-brands.index');
        Route::post('/device-brands', [DeviceBrandController::class, 'store'])->middleware('permission:reparaciones.create')->name('device-brands.store');
        Route::post('/repair-services', [RepairServiceController::class, 'store'])->middleware('permission:reparaciones.create')->name('repair-services.store');
        Route::middleware('permission:reparaciones.create_expenses')->group(function () {
            Route::get('/reparaciones/gastos-operativos/nuevo', [OperationalExpenseController::class, 'create'])->name('reparaciones.gastos.create');
            Route::post('/reparaciones/gastos-operativos', [OperationalExpenseController::class, 'store'])->name('reparaciones.gastos.store');
        });
        Route::middleware('permission:reparaciones.edit_expenses')->group(function () {
            Route::get('/reparaciones/gastos-operativos/{operationalExpense}/edit', [OperationalExpenseController::class, 'edit'])->name('reparaciones.gastos.edit');
            Route::match(['put', 'patch'], '/reparaciones/gastos-operativos/{operationalExpense}', [OperationalExpenseController::class, 'update'])->name('reparaciones.gastos.update');
        });
        Route::middleware('permission:reparaciones.view_expenses')->group(function () {
            Route::get('/reparaciones/gastos-operativos', [OperationalExpenseController::class, 'index'])->name('reparaciones.gastos.index');
            Route::get('/reparaciones/gastos-operativos/{operationalExpense}', [OperationalExpenseController::class, 'show'])->name('reparaciones.gastos.show');
        });
        Route::delete('/reparaciones/gastos-operativos/{operationalExpense}', [OperationalExpenseController::class, 'destroy'])
            ->middleware('permission:reparaciones.delete_expenses')->name('reparaciones.gastos.destroy');
        Route::middleware('permission:reparaciones.view')->group(function () {
            Route::get('/reparaciones', [ReparacionController::class, 'index'])->name('reparaciones.index');
            Route::get('/reparaciones/{id}', [ReparacionController::class, 'show'])->whereNumber('id')->name('reparaciones.show');
            Route::get('/reparaciones/{id}/ticket', [ReparacionController::class, 'ticket'])->whereNumber('id')->name('reparaciones.ticket');
            Route::get('/reparaciones/{id}/pdf', [ReparacionController::class, 'pdf'])->whereNumber('id')->name('reparaciones.pdf');
        });
        Route::middleware('permission:reparaciones.create')->group(function () {
            Route::get('/reparaciones/nueva', [ReparacionController::class, 'create'])->name('reparaciones.create');
            Route::post('/reparaciones', [ReparacionController::class, 'store'])->name('reparaciones.store');
        });
        Route::middleware('permission:reparaciones.edit')->group(function () {
            Route::get('/reparaciones/{id}/edit', [ReparacionController::class, 'edit'])->whereNumber('id')->name('reparaciones.edit');
            Route::put('/reparaciones/{id}', [ReparacionController::class, 'update'])->whereNumber('id')->name('reparaciones.update');
            Route::patch('/reparaciones/{id}/status', [ReparacionController::class, 'updateStatus'])->whereNumber('id')->name('reparaciones.status');
        });
        Route::delete('/reparaciones/{id}', [ReparacionController::class, 'destroy'])->whereNumber('id')->middleware('permission:reparaciones.delete')->name('reparaciones.destroy');
    });

    // Reportes solo para admin
    Route::middleware(['module:reportes', 'permission:reportes.view'])->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/export', [ReporteController::class, 'exportExcel'])->middleware('permission:reportes.export')->name('reportes.export');
    });

    Route::get('/nomina', [NominaController::class, 'index'])->middleware(['module:planilla', 'permission:planilla.view'])->name('nomina.index');
    Route::get('/nomina/charts', [NominaController::class, 'charts'])->middleware(['module:planilla', 'permission:planilla.view'])->name('nomina.charts');
    Route::post('/nomina/pagar', [NominaController::class, 'pay'])->middleware(['module:planilla', 'permission:planilla.pay'])->name('nomina.pay');
    Route::get('/nomina/ticket', [NominaController::class, 'ticket'])->middleware(['module:planilla', 'permission:planilla.view'])->name('nomina.ticket');

    // Ajustes de inventario solo para admin
    Route::middleware(['module:inventario', 'permission:inventario.adjust'])->group(function () {
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
            Route::post('/taxes/display-mode', [TaxController::class, 'updateDisplayMode'])->name('taxes.display-mode.update');
            Route::get('/taxes/create', [TaxController::class, 'create'])->name('taxes.create');
            Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store');
            Route::get('/taxes/{tax}/edit', [TaxController::class, 'edit'])->name('taxes.edit');
            Route::match(['put', 'patch'], '/taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
            Route::delete('/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');
        });
        Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
        Route::middleware('permission:configuracion.edit')->group(function () {
            Route::get('/exchange-rates/create', [ExchangeRateController::class, 'create'])->name('exchange-rates.create');
            Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
            Route::get('/exchange-rates/{exchangeRate}/edit', [ExchangeRateController::class, 'edit'])->name('exchange-rates.edit');
            Route::match(['put', 'patch'], '/exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
            Route::delete('/exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy');
        });
        Route::middleware('permission:configuracion.manage_modules')->group(function () {
            Route::get('/modules', [ModuleController::class, 'index'])->name('modules');
            Route::put('/modules', [ModuleController::class, 'update'])->name('modules.update');
        });
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::post('/security', [SettingsController::class, 'security'])->middleware('permission:configuracion.edit')->name('security.update');
        Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        Route::post('/appearance', [SettingsController::class, 'appearance'])->middleware('permission:configuracion.edit')->name('appearance.update');
        Route::get('/sequences', [SettingsController::class, 'sequences'])->name('sequences');
        Route::post('/sequences', [SettingsController::class, 'sequences'])->middleware('permission:configuracion.edit')->name('sequences.update');
    });

    // Contabilidad
    Route::middleware(['module:contabilidad', 'permission:contabilidad.view'])->prefix('contabilidad')->name('contabilidad.')->group(function () {
        Route::get('/', AccountingDashboardController::class)->name('dashboard');
        Route::get('/reportes/{report}/pdf', [AccountingReportExportController::class, 'pdf'])
            ->middleware('permission:contabilidad.export')
            ->whereIn('report', ['estado-resultados', 'balance-general', 'flujo-caja', 'balance-comprobacion', 'diario-general', 'mayor-general'])
            ->name('reportes.pdf');
        Route::get('/reportes/{report}/excel', [AccountingReportExportController::class, 'excel'])
            ->middleware('permission:contabilidad.export')
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
        Route::get('/diario/export', [DiarioController::class, 'export'])->middleware('permission:contabilidad.export')->name('diario.export');

        // Mayor General
        Route::get('/mayor', [LedgerController::class, 'index'])->name('mayor.index');

        // Balance de Comprobación
        Route::get('/balance-comprobacion', [TrialBalanceController::class, 'index'])->name('balance-comprobacion.index');
        Route::get('/balance-comprobacion/export', [TrialBalanceController::class, 'export'])->middleware('permission:contabilidad.export')->name('balance-comprobacion.export');

        // Estado de Resultados
        Route::get('/estado-resultados', [IncomeStatementController::class, 'index'])->name('estado-resultados.index');
        Route::get('/estado-resultados/export', [IncomeStatementController::class, 'export'])->middleware('permission:contabilidad.export')->name('estado-resultados.export');

        // Balance General
        Route::get('/balance-general', [BalanceSheetController::class, 'index'])->name('balance-general.index');
        Route::get('/balance-general/export', [BalanceSheetController::class, 'export'])->middleware('permission:contabilidad.export')->name('balance-general.export');

        // Flujo de Caja
        Route::get('/flujo-caja', [CashFlowController::class, 'index'])->name('flujo-caja.index');
        Route::get('/flujo-caja/export', [CashFlowController::class, 'export'])->middleware('permission:contabilidad.export')->name('flujo-caja.export');

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
    )->middleware(['module:compras', 'permission:compras.view'])->name('proveedores.productos');

    Route::get('/proveedores/{supplier}/productos/buscar',
        [CompraController::class, 'buscarProductos']
    )->middleware(['module:compras', 'permission:compras.view'])->name('proveedores.productos.buscar');

}); // Cierre del grupo auth middleware
