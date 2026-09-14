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
    @elseif($reportType === 'abc')
        <x-ui.stat-card label="SKUs con venta" :value="number_format($summary['sku_count'] ?? 0)" accent="#2563eb" />
        <x-ui.stat-card label="Clase A" :value="number_format($summary['class_a'] ?? 0)" :meta="number_format($summary['class_a_share'] ?? 0, 1) . '% de las ventas'" accent="#059669" />
        <x-ui.stat-card label="Clase B / C" :value="number_format(($summary['class_b'] ?? 0) + ($summary['class_c'] ?? 0))" meta="Cola larga de inventario" accent="#d97706" />
        <x-ui.stat-card label="Ventas clasificadas" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 0)" accent="#7c3aed" />
    @elseif($reportType === 'aging')
        <x-ui.stat-card label="Cartera total" :value="'C$ ' . number_format($summary['total_due'] ?? 0, 2)" :meta="($summary['count'] ?? 0) . ' facturas abiertas'" accent="#d97706" />
        <x-ui.stat-card label="Al día" :value="'C$ ' . number_format($summary['current'] ?? 0, 2)" accent="#059669" />
        <x-ui.stat-card label="Vencido" :value="'C$ ' . number_format($summary['overdue'] ?? 0, 2)" accent="#dc2626" />
        <x-ui.stat-card label="Más de 90 días" :value="'C$ ' . number_format($summary['over_90'] ?? 0, 2)" accent="#7c3aed" />
    @elseif($reportType === 'slow')
        <x-ui.stat-card label="Sin movimiento" :value="number_format($summary['count'] ?? 0)" meta="Productos con stock y sin venta en el período" accent="#d97706" />
        <x-ui.stat-card label="Capital inmovilizado" :value="'C$ ' . number_format($summary['tied_value'] ?? 0, 0)" accent="#dc2626" />
    @elseif($reportType === 'top_clients')
        <x-ui.stat-card label="Clientes con compra" :value="number_format($summary['clients'] ?? 0)" accent="#2563eb" />
        <x-ui.stat-card label="Ventas del período" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 0)" accent="#059669" />
        <x-ui.stat-card label="Top 5" :value="number_format($summary['top_share'] ?? 0, 1) . '%'" meta="Concentración de la cartera" accent="#7c3aed" />
    @elseif($reportType === 'sellers')
        <x-ui.stat-card label="Cajas / vendedores" :value="number_format($summary['sellers'] ?? 0)" accent="#2563eb" />
        <x-ui.stat-card label="Tickets" :value="number_format($summary['tickets'] ?? 0)" accent="#0d9488" />
        <x-ui.stat-card label="Ventas" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 0)" accent="#059669" />
    @elseif($reportType === 'categories')
        <x-ui.stat-card label="Categorías" :value="number_format($summary['categories'] ?? 0)" accent="#2563eb" />
        <x-ui.stat-card label="Ventas" :value="'C$ ' . number_format($summary['total_sales'] ?? 0, 0)" accent="#059669" />
        <x-ui.stat-card label="Líder" :value="$summary['top_category'] ?? '—'" accent="#7c3aed" />
    @endif
</div>

<p class="mt-2 hidden text-xs text-slate-500 print:block">Período: {{ $periodLabel }} · Generado {{ now()->format('d/m/Y H:i') }}</p>
