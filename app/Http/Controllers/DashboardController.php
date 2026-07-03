<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\InventoryMovement;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear();

        // Estadísticas de Ventas
        $salesStats = [
            'today' => Sale::whereDate('date', $today)->sum('total') ?? 0,
            'month' => Sale::whereBetween('date', [$startOfMonth, Carbon::now()])->sum('total') ?? 0,
            'pending' => Sale::where('status', 'pending')->count(),
            'count_today' => Sale::whereDate('date', $today)->count(),
        ];

        // Estadísticas de Compras
        $purchaseStats = [
            'month' => Purchase::whereBetween('date', [$startOfMonth, Carbon::now()])->sum('total') ?? 0,
            'pending' => Purchase::where('status', 'pending')->count(),
            'count_month' => Purchase::whereBetween('date', [$startOfMonth, Carbon::now()])->count(),
        ];

        // Inventario
        $inventoryStats = [
            'total_products' => Product::count(),
            'low_stock' => Product::whereColumn('stock', '<=', 'low_stock_threshold')->orWhere('stock', '<=', 10)->count(),
            'expired' => Product::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count(),
            'expiring_soon' => Product::whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                ->count(),
            'inventory_value' => Product::select(DB::raw('SUM(stock * purchase_price) as total'))->value('total') ?? 0,
        ];

        // Clientes y Proveedores
        $customerStats = [
            'total_clients' => Client::count(),
            'new_this_month' => Client::whereBetween('created_at', [$startOfMonth, Carbon::now()])->count(),
            'top_clients' => Client::withSum('sales as total_sales', 'total')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get(),
        ];

        // Movimientos recientes
        $recentMovements = InventoryMovement::with('product')
            ->latest()
            ->limit(10)
            ->get();

        // Datos para gráfico de ventas mensuales (últimos 6 meses)
        $salesChart = [];
        for ($i = 5; $i >= 0; $i--) {
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
        $topProducts = DB::table('sale_details')
            ->select('products.name', DB::raw('SUM(sale_details.quantity) as total_qty'), DB::raw('SUM(sale_details.subtotal) as total_sales'))
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Alertas del sistema
        $alerts = [];

        if ($inventoryStats['low_stock'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inventoryStats['low_stock']} productos con stock bajo",
                'link' => route('inventario.index', ['stock_status' => 'low']),
            ];
        }

        if ($inventoryStats['expired'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$inventoryStats['expired']} productos vencidos",
                'link' => route('inventario.index', ['stock_status' => 'expired']),
            ];
        }

        if ($inventoryStats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inventoryStats['expiring_soon']} productos por vencer (30 días)",
                'link' => route('inventario.index', ['stock_status' => 'expiring_soon']),
            ];
        }

        if ($salesStats['pending'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$salesStats['pending']} facturas pendientes de pago",
                'link' => route('facturacion.index', ['status' => 'pending']),
            ];
        }

        // Últimas facturas
        $latestSales = Sale::with('client')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard-general', compact(
            'salesStats',
            'purchaseStats',
            'inventoryStats',
            'customerStats',
            'recentMovements',
            'salesChart',
            'topProducts',
            'alerts',
            'latestSales'
        ));
    }

    public function facturacion()
    {
        return view('dashboard');
    }
}
