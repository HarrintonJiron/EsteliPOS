<?php

namespace App\Services;

use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @param  Collection<int, string>  $modules
     * @return array<string, mixed>
     */
    public function build(Collection $modules): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $hasVentas = $modules->contains('ventas');
        $hasInventario = $modules->contains('inventario');
        $hasCompras = $modules->contains('compras');
        $hasClientes = $modules->contains('clientes');

        $salesStats = [
            'today' => $hasVentas ? (float) Sale::whereDate('date', $today)->where('status', 'completed')->sum('total') : 0,
            'month' => $hasVentas ? (float) Sale::whereBetween('date', [$startOfMonth, $endOfMonth])->where('status', 'completed')->sum('total') : 0,
            'pending' => $hasVentas ? Sale::where('status', 'pending')->count() : 0,
            'count_today' => $hasVentas ? Sale::whereDate('date', $today)->where('status', 'completed')->count() : 0,
            'count_month' => $hasVentas ? Sale::whereBetween('date', [$startOfMonth, $endOfMonth])->where('status', 'completed')->count() : 0,
            'average_ticket' => 0,
        ];

        if ($hasVentas && $salesStats['count_month'] > 0) {
            $salesStats['average_ticket'] = round($salesStats['month'] / $salesStats['count_month'], 2);
        }

        $purchaseStats = [
            'month' => $hasCompras ? (float) Purchase::whereBetween('date', [$startOfMonth, $endOfMonth])->where('status', 'completed')->sum('total') : 0,
            'pending' => $hasCompras ? Purchase::where('status', 'pending')->count() : 0,
            'count_month' => $hasCompras ? Purchase::whereBetween('date', [$startOfMonth, $endOfMonth])->where('status', 'completed')->count() : 0,
        ];

        $inventoryStats = [
            'total_products' => $hasInventario ? Product::where('status', 'active')->count() : 0,
            'low_stock' => $hasInventario ? Product::where('status', 'active')->whereRaw('stock <= COALESCE(low_stock_threshold, 10)')->count() : 0,
            'expired' => $hasInventario ? Product::where('status', 'active')->whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count() : 0,
            'expiring_soon' => $hasInventario ? Product::where('status', 'active')->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
                ->count() : 0,
            'inventory_value' => $hasInventario ? (float) (Product::where('status', 'active')->select(DB::raw('SUM(stock * purchase_price) as total'))->value('total') ?? 0) : 0,
            'normal_stock' => 0,
        ];

        if ($hasInventario) {
            $inventoryStats['normal_stock'] = max(0, $inventoryStats['total_products'] - $inventoryStats['low_stock'] - $inventoryStats['expired'] - $inventoryStats['expiring_soon']);
        }

        $customerStats = [
            'total_clients' => $hasClientes ? Client::count() : 0,
            'new_this_month' => $hasClientes ? Client::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count() : 0,
            'top_clients' => $hasClientes ? Client::withSum(['sales as total_sales' => fn ($q) => $q->where('status', 'completed')], 'total')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get() : collect(),
        ];

        $recentMovements = $hasInventario ? InventoryMovement::with('product')
            ->latest()
            ->limit(10)
            ->get() : collect();

        $latestSales = $hasVentas ? Sale::with('client')
            ->latest()
            ->limit(8)
            ->get() : collect();

        $alerts = $this->buildAlerts($inventoryStats, $salesStats, $hasInventario, $hasVentas);

        $charts = [
            'sales_trend' => $hasVentas ? $this->monthlyTrend(12, 'sales') : [],
            'purchases_trend' => $hasCompras ? $this->monthlyTrend(12, 'purchases') : [],
            'combined_trend' => ($hasVentas || $hasCompras) ? $this->combinedTrend(12, $hasVentas, $hasCompras) : [],
            'daily_sales' => $hasVentas ? $this->dailySalesCurrentMonth() : [],
            'payment_methods' => $hasVentas ? $this->salesByPaymentType($startOfMonth, $endOfMonth) : [],
            'top_products' => ($hasVentas && $hasInventario) ? $this->topProducts() : [],
            'top_clients' => $hasClientes ? $this->topClientsChart($customerStats['top_clients']) : [],
            'inventory_health' => $hasInventario ? [
                ['label' => 'Stock normal', 'value' => $inventoryStats['normal_stock']],
                ['label' => 'Stock bajo', 'value' => $inventoryStats['low_stock']],
                ['label' => 'Por vencer', 'value' => $inventoryStats['expiring_soon']],
                ['label' => 'Vencidos', 'value' => $inventoryStats['expired']],
            ] : [],
        ];

        $summary = [
            'profit_estimate' => round($salesStats['month'] - $purchaseStats['month'], 2),
            'period_label' => $startOfMonth->translatedFormat('F Y'),
            'month' => $startOfMonth->format('Y-m'),
        ];

        return compact(
            'salesStats',
            'purchaseStats',
            'inventoryStats',
            'customerStats',
            'recentMovements',
            'latestSales',
            'alerts',
            'charts',
            'summary',
        );
    }

    /**
     * @return list<array{label: string, month: string, total: float}>
     */
    private function monthlyTrend(int $months, string $type): array
    {
        return collect(range($months - 1, 0))->map(function (int $monthsAgo) use ($type) {
            $month = Carbon::now()->subMonths($monthsAgo);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $total = match ($type) {
                'sales' => (float) Sale::whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total'),
                'purchases' => (float) Purchase::whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total'),
                default => 0,
            };

            return [
                'label' => $start->translatedFormat('M Y'),
                'month' => $start->format('Y-m'),
                'total' => round($total, 2),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{label: string, month: string, sales: float, purchases: float, profit: float}>
     */
    private function combinedTrend(int $months, bool $hasVentas, bool $hasCompras): array
    {
        return collect(range($months - 1, 0))->map(function (int $monthsAgo) use ($hasVentas, $hasCompras) {
            $month = Carbon::now()->subMonths($monthsAgo);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $sales = $hasVentas
                ? (float) Sale::whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total')
                : 0;
            $purchases = $hasCompras
                ? (float) Purchase::whereBetween('date', [$start, $end])->where('status', 'completed')->sum('total')
                : 0;

            return [
                'label' => $start->translatedFormat('M Y'),
                'month' => $start->format('Y-m'),
                'sales' => round($sales, 2),
                'purchases' => round($purchases, 2),
                'profit' => round($sales - $purchases, 2),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{label: string, day: string, total: float, count: int}>
     */
    private function dailySalesCurrentMonth(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $salesByDay = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('DATE(date) as day, SUM(total) as total, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('total', 'day');

        $countsByDay = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('DATE(date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        return collect(range(0, $start->diffInDays($end)))
            ->map(function (int $offset) use ($start, $salesByDay, $countsByDay) {
                $day = $start->copy()->addDays($offset);
                $key = $day->format('Y-m-d');

                return [
                    'label' => $day->format('d'),
                    'day' => $key,
                    'total' => round((float) ($salesByDay[$key] ?? 0), 2),
                    'count' => (int) ($countsByDay[$key] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: float, count: int}>
     */
    private function salesByPaymentType(Carbon $start, Carbon $end): array
    {
        $labels = [
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia',
            'credit' => 'Crédito',
        ];

        $rows = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('payment_type, SUM(total) as total, COUNT(*) as count')
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');

        return collect($labels)->map(function (string $label, string $type) use ($rows) {
            $row = $rows->get($type);

            return [
                'label' => $label,
                'value' => round((float) ($row->total ?? 0), 2),
                'count' => (int) ($row->count ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{name: string, qty: int, sales: float}>
     */
    private function topProducts(): array
    {
        return DB::table('sale_details')
            ->select('products.name', DB::raw('SUM(sale_details.quantity) as total_qty'), DB::raw('SUM(sale_details.subtotal) as total_sales'))
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->where('sales.date', '>=', Carbon::now()->subMonths(6))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'qty' => (int) $row->total_qty,
                'sales' => round((float) $row->total_sales, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, total: float}>
     */
    private function topClientsChart(Collection $clients): array
    {
        return $clients->map(fn ($client) => [
            'name' => $client->name,
            'total' => round((float) ($client->total_sales ?? 0), 2),
        ])->values()->all();
    }

    /**
     * @return list<array{type: string, message: string, link: string}>
     */
    private function buildAlerts(array $inventoryStats, array $salesStats, bool $hasInventario, bool $hasVentas): array
    {
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

        return $alerts;
    }
}
