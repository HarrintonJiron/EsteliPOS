<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Services\ModuleAccessService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(ModuleAccessService $moduleAccess)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $dashboardModules = $moduleAccess->accessibleSlugs(auth()->user());
        $hasVentas = $dashboardModules->contains('ventas');
        $hasInventario = $dashboardModules->contains('inventario');
        $hasCompras = $dashboardModules->contains('compras');
        $hasClientes = $dashboardModules->contains('clientes');

        // Estadísticas de Ventas
        $salesStats = [
            'today' => $hasVentas ? Sale::whereDate('date', $today)->sum('total') ?? 0 : 0,
            'month' => $hasVentas ? Sale::whereBetween('date', [$startOfMonth, Carbon::now()])->sum('total') ?? 0 : 0,
            'pending' => $hasVentas ? Sale::where('status', 'pending')->count() : 0,
            'count_today' => $hasVentas ? Sale::whereDate('date', $today)->count() : 0,
        ];

        // Estadísticas de Compras
        $purchaseStats = [
            'month' => $hasCompras ? Purchase::whereBetween('date', [$startOfMonth, Carbon::now()])->sum('total') ?? 0 : 0,
            'pending' => $hasCompras ? Purchase::where('status', 'pending')->count() : 0,
            'count_month' => $hasCompras ? Purchase::whereBetween('date', [$startOfMonth, Carbon::now()])->count() : 0,
        ];

        // Inventario
        $inventoryStats = [
            'total_products' => $hasInventario ? Product::count() : 0,
            'low_stock' => $hasInventario ? Product::whereColumn('stock', '<=', 'low_stock_threshold')->orWhere('stock', '<=', 10)->count() : 0,
            'expired' => $hasInventario ? Product::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count() : 0,
            'expiring_soon' => $hasInventario ? Product::whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                ->count() : 0,
            'inventory_value' => $hasInventario ? Product::select(DB::raw('SUM(stock * purchase_price) as total'))->value('total') ?? 0 : 0,
        ];

        // Clientes y Proveedores
        $customerStats = [
            'total_clients' => $hasClientes ? Client::count() : 0,
            'new_this_month' => $hasClientes ? Client::whereBetween('created_at', [$startOfMonth, Carbon::now()])->count() : 0,
            'top_clients' => $hasClientes ? Client::withSum('sales as total_sales', 'total')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get() : collect(),
        ];

        // Movimientos recientes
        $recentMovements = $hasInventario ? InventoryMovement::with('product')
            ->latest()
            ->limit(10)
            ->get() : collect();

        // Datos para gráfico de ventas mensuales (últimos 6 meses)
        $salesChart = [];
        for ($i = 5; $hasVentas && $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $total = Sale::whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->sum('total') ?? 0;
            $salesChart[] = [
                'month' => $month->format('M Y'),
                'total' => $total,
            ];
        }

        // Productos más vendidos
        $topProducts = $hasVentas && $hasInventario ? DB::table('sale_details')
            ->select('products.name', DB::raw('SUM(sale_details.quantity) as total_qty'), DB::raw('SUM(sale_details.subtotal) as total_sales'))
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get() : collect();

        // Alertas del sistema
        $alerts = [];

        if ($hasInventario && $inventoryStats['low_stock'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inventoryStats['low_stock']} productos con stock bajo",
                'link' => route('inventario.index', ['stock_status' => 'low']),
            ];
        }

        if ($hasInventario && $inventoryStats['expired'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$inventoryStats['expired']} productos vencidos",
                'link' => route('inventario.index', ['stock_status' => 'expired']),
            ];
        }

        if ($hasInventario && $inventoryStats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inventoryStats['expiring_soon']} productos por vencer (30 días)",
                'link' => route('inventario.index', ['stock_status' => 'expiring_soon']),
            ];
        }

        if ($hasVentas && $salesStats['pending'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$salesStats['pending']} facturas pendientes de pago",
                'link' => route('facturacion.index', ['status' => 'pending']),
            ];
        }

        // Últimas facturas
        $latestSales = $hasVentas ? Sale::with('client')
            ->latest()
            ->limit(5)
            ->get() : collect();

        return view('dashboard-general', compact(
            'salesStats',
            'purchaseStats',
            'inventoryStats',
            'customerStats',
            'recentMovements',
            'salesChart',
            'topProducts',
            'alerts',
            'latestSales',
            'dashboardModules'
        ));
    }

    public function facturacion()
    {
        return view('dashboard');
    }
}
