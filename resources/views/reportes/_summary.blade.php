@php
    $periodLabel = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' — ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
@endphp

<div class="kpi-grid">
    @if($reportType === 'sales')
        <x-ui.stat-card label="Total ventas" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 2)" :meta="($summary['total_count'] ?? 0) . ' transacciones'" accent="#059669" />
        <x-ui.stat-card label="Promedio por venta" :value="'C$ ' . number_format($summary['avg_sale'] ?? 0, 2)" accent="#0d9488" />
        <x-ui.stat-card label="Facturas pagadas" :value="$summary['by_status']->where('status', 'completed')->first()->count ?? 0" :meta="'C$ ' . number_format($summary['by_status']->where('status', 'completed')->first()->total ?? 0, 2)" accent="#2563eb" />
        <x-ui.stat-card label="Pendientes" :value="$summary['by_status']->where('status', 'pending')->first()->count ?? 0" :meta="'C$ ' . number_format($summary['by_status']->where('status', 'pending')->first()->total ?? 0, 2)" accent="#d97706" />
    @elseif($reportType === 'purchases')
        <x-ui.stat-card label="Total compras" :value="'C$ ' . number_format($summary['total_purchases'] ?? 0, 2)" :meta="($summary['total_count'] ?? 0) . ' órdenes'" accent="#2563eb" />
        <x-ui.stat-card label="Promedio por orden" :value="'C$ ' . number_format($summary['avg_purchase'] ?? 0, 2)" accent="#0d9488" />
    @elseif($reportType === 'inventory')
        <x-ui.stat-card label="Productos" :value="number_format($summary['total_products'] ?? 0)" accent="#0d9488" />
        <x-ui.stat-card label="Valor inventario" :value="'C$ ' . number_format($summary['total_value'] ?? 0, 0)" accent="#059669" />
        <x-ui.stat-card label="Stock bajo" :value="number_format($summary['low_stock'] ?? 0)" accent="#d97706" />
        <x-ui.stat-card label="Vencidos" :value="number_format($summary['expired'] ?? 0)" accent="#dc2626" />
    @elseif($reportType === 'profit')
        <x-ui.stat-card label="Ventas" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 2)" accent="#059669" />
        <x-ui.stat-card label="Costos" :value="'C$ ' . number_format($summary['total_cost'] ?? 0, 2)" accent="#dc2626" />
        <x-ui.stat-card label="Ganancia bruta" :value="'C$ ' . number_format($summary['gross_profit'] ?? 0, 2)" :accent="($summary['gross_profit'] ?? 0) >= 0 ? '#059669' : '#dc2626'" />
        <x-ui.stat-card label="Margen" :value="number_format($summary['profit_margin'] ?? 0, 1) . '%'" :accent="($summary['profit_margin'] ?? 0) >= 0 ? '#0d9488' : '#dc2626'" />
    @endif
</div>

<p class="mt-2 hidden text-xs text-slate-500 print:block">Período: {{ $periodLabel }} · Generado {{ now()->format('d/m/Y H:i') }}</p>
