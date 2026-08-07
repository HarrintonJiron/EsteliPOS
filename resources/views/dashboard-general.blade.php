@extends('layouts.app')

@section('title', 'Dashboard General')

@section('content')
@php
    $hasVentas = $dashboardModules->contains('ventas');
    $hasInventario = $dashboardModules->contains('inventario');
    $hasCompras = $dashboardModules->contains('compras');
    $hasClientes = $dashboardModules->contains('clientes');
    $hasReportes = $dashboardModules->contains('reportes');
@endphp

<div class="page-shell">

    <x-ui.page-header
        title="Dashboard General"
        :subtitle="'Resumen ejecutivo · ' . $summary['period_label'] . ' · ' . now()->format('d/m/Y H:i')"
    >
        <x-slot:actions>
            @if($hasVentas)
                <a href="{{ route('facturacion.pos') }}" class="btn-primary btn-sm">Punto de Venta</a>
            @endif
            @if($hasReportes)
                <a href="{{ route('reportes.index') }}" class="btn-outline btn-sm">Ver reportes</a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Alertas --}}
    @if(count($alerts) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach($alerts as $alert)
            <a href="{{ $alert['link'] }}"
               class="card flex items-center gap-3 p-4 border-l-4 transition hover:shadow-md
                {{ $alert['type'] === 'danger' ? 'border-red-500 bg-red-50/50' : ($alert['type'] === 'warning' ? 'border-amber-500 bg-amber-50/50' : 'border-blue-500 bg-blue-50/50') }}">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                    {{ $alert['type'] === 'danger' ? 'bg-red-100 text-red-600' : ($alert['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-700">{{ $alert['message'] }}</p>
            </a>
        @endforeach
    </div>
    @endif

    {{-- KPIs principales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @if($hasVentas)
        <div class="card p-5 relative overflow-hidden border-t-4 border-indigo-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-indigo-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Ventas hoy</p>
            <p class="mt-2 text-3xl font-bold text-indigo-700">C$ {{ number_format($salesStats['today'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $salesStats['count_today'] }} facturas</p>
        </div>
        <div class="card p-5 relative overflow-hidden border-t-4 border-emerald-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Ventas del mes</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">C$ {{ number_format($salesStats['month'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">Ticket prom. C$ {{ number_format($salesStats['average_ticket'], 2) }}</p>
        </div>
        @endif

        @if($hasCompras)
        <div class="card p-5 relative overflow-hidden border-t-4 border-rose-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-rose-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Compras del mes</p>
            <p class="mt-2 text-3xl font-bold text-rose-700">C$ {{ number_format($purchaseStats['month'], 2) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $purchaseStats['count_month'] }} órdenes</p>
        </div>
        @endif

        @if($hasVentas && $hasCompras)
        <div class="card p-5 relative overflow-hidden border-t-4 border-violet-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Margen estimado</p>
            <p class="mt-2 text-3xl font-bold {{ $summary['profit_estimate'] >= 0 ? 'text-violet-700' : 'text-red-600' }}">
                C$ {{ number_format($summary['profit_estimate'], 2) }}
            </p>
            <p class="mt-1 text-sm text-slate-500">Ventas − compras del mes</p>
        </div>
        @elseif($hasInventario)
        <div class="card p-5 relative overflow-hidden border-t-4 border-violet-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Valor inventario</p>
            <p class="mt-2 text-3xl font-bold text-violet-700">C$ {{ number_format($inventoryStats['inventory_value'], 0) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $inventoryStats['total_products'] }} productos</p>
        </div>
        @elseif($hasClientes)
        <div class="card p-5 relative overflow-hidden border-t-4 border-amber-500">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10"></div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Clientes</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $customerStats['total_clients'] }}</p>
            <p class="mt-1 text-sm text-slate-500">+{{ $customerStats['new_this_month'] }} este mes</p>
        </div>
        @endif
    </div>

    {{-- KPIs secundarios --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @if($hasInventario)
        <div class="card p-4">
            <p class="text-xs text-slate-500">Stock bajo</p>
            <p class="text-xl font-bold {{ $inventoryStats['low_stock'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $inventoryStats['low_stock'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Vencidos</p>
            <p class="text-xl font-bold {{ $inventoryStats['expired'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $inventoryStats['expired'] }}</p>
        </div>
        @endif
        @if($hasVentas)
        <div class="card p-4">
            <p class="text-xs text-slate-500">Facturas pendientes</p>
            <p class="text-xl font-bold text-blue-600">{{ $salesStats['pending'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500">Facturas del mes</p>
            <p class="text-xl font-bold text-slate-800">{{ $salesStats['count_month'] }}</p>
        </div>
        @endif
    </div>

    {{-- Gráficos principales --}}
    @if($hasVentas || $hasCompras)
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card p-5 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800">Ventas vs compras</h2>
                    <p class="text-xs text-slate-500">Tendencia de los últimos 12 meses</p>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="text-indigo-600">● Ventas</span>
                    <span class="text-rose-600">● Compras</span>
                    <span class="text-emerald-600">● Margen</span>
                </div>
            </div>
            <div class="h-80">
                <canvas id="combinedTrendChart"></canvas>
            </div>
        </div>

        @if($hasVentas)
        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Formas de pago</h2>
                <p class="text-xs text-slate-500">{{ $summary['period_label'] }}</p>
            </div>
            <div class="h-80">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if($hasVentas)
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Ventas diarias del mes</h2>
                <p class="text-xs text-slate-500">Facturación por día · {{ $summary['period_label'] }}</p>
            </div>
            <div class="h-72">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>

        @if($hasInventario && count($charts['top_products']) > 0)
        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Productos más vendidos</h2>
                <p class="text-xs text-slate-500">Últimos 6 meses · por unidades</p>
            </div>
            <div class="h-72">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Inventario y clientes --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @if($hasInventario)
        <div class="card p-5">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Salud del inventario</h2>
                <p class="text-xs text-slate-500">Estado actual del catálogo</p>
            </div>
            <div class="h-64">
                <canvas id="inventoryHealthChart"></canvas>
            </div>
        </div>
        @endif

        @if($hasClientes && count($charts['top_clients']) > 0)
        <div class="card p-5 xl:col-span-2">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-800">Top clientes</h2>
                <p class="text-xs text-slate-500">Por volumen de ventas acumulado</p>
            </div>
            <div class="h-64">
                <canvas id="topClientsChart"></canvas>
            </div>
        </div>
        @endif
    </div>

    {{-- Accesos a informes --}}
    @if($hasReportes)
    <div class="card p-5">
        <h2 class="font-semibold text-slate-800 mb-1">Informes rápidos</h2>
        <p class="text-xs text-slate-500 mb-4">Accede a reportes detallados del sistema</p>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            <a href="{{ route('reportes.index', ['report_type' => 'sales']) }}"
               class="rounded-xl border border-slate-200 p-4 hover:border-indigo-400 hover:bg-indigo-50/40 transition">
                <p class="font-semibold text-slate-800">Ventas</p>
                <p class="text-xs text-slate-500 mt-1">Facturación y cobros</p>
            </a>
            <a href="{{ route('reportes.index', ['report_type' => 'purchases']) }}"
               class="rounded-xl border border-slate-200 p-4 hover:border-rose-400 hover:bg-rose-50/40 transition">
                <p class="font-semibold text-slate-800">Compras</p>
                <p class="text-xs text-slate-500 mt-1">Órdenes y proveedores</p>
            </a>
            <a href="{{ route('reportes.index', ['report_type' => 'inventory']) }}"
               class="rounded-xl border border-slate-200 p-4 hover:border-violet-400 hover:bg-violet-50/40 transition">
                <p class="font-semibold text-slate-800">Inventario</p>
                <p class="text-xs text-slate-500 mt-1">Stock y valorización</p>
            </a>
            <a href="{{ route('reportes.index', ['report_type' => 'kardex']) }}"
               class="rounded-xl border border-slate-200 p-4 hover:border-amber-400 hover:bg-amber-50/40 transition">
                <p class="font-semibold text-slate-800">Kardex</p>
                <p class="text-xs text-slate-500 mt-1">Movimientos por producto</p>
            </a>
            <a href="{{ route('reportes.index', ['report_type' => 'profit']) }}"
               class="rounded-xl border border-slate-200 p-4 hover:border-emerald-400 hover:bg-emerald-50/40 transition">
                <p class="font-semibold text-slate-800">Rentabilidad</p>
                <p class="text-xs text-slate-500 mt-1">Margen y utilidad</p>
            </a>
        </div>
    </div>
    @endif

    {{-- Tablas --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @if($hasVentas)
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div>
                    <h2 class="font-semibold text-slate-800">Últimas facturas</h2>
                    <p class="text-xs text-slate-500">Actividad reciente de ventas</p>
                </div>
                <a href="{{ route('facturacion.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-agro w-full">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestSales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('facturacion.show', $sale->id) }}" class="font-medium text-indigo-600 hover:underline">
                                    #{{ $sale->invoice_number ?? str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>{{ $sale->billing_name ?? $sale->client?->name ?? 'N/A' }}</td>
                            <td class="font-medium">C$ {{ number_format($sale->total, 2) }}</td>
                            <td>
                                <span class="{{ $sale->status === 'completed' ? 'badge-success' : ($sale->status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $sale->status === 'completed' ? 'Pagada' : ($sale->status === 'pending' ? 'Pendiente' : 'Cancelada') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="!py-8 text-center text-slate-500">No hay facturas registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($hasInventario)
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div>
                    <h2 class="font-semibold text-slate-800">Movimientos recientes</h2>
                    <p class="text-xs text-slate-500">Entradas y salidas de inventario</p>
                </div>
                <a href="{{ route('movimientos.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-agro w-full">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cant.</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $movement)
                        <tr>
                            <td class="max-w-[180px] truncate">{{ $movement->product?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="{{ $movement->type === 'in' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                </span>
                            </td>
                            <td class="font-medium {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                            </td>
                            <td class="text-slate-500">{{ $movement->stock_after ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="!py-8 text-center text-slate-500">No hay movimientos recientes</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

</div>

@if($hasVentas || $hasCompras || $hasInventario || $hasClientes)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#64748b';

const money = (v) => 'C$ ' + Number(v).toLocaleString('es-NI', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
const charts = @json($charts);

@if($hasVentas || $hasCompras)
const combined = charts.combined_trend || [];
if (document.getElementById('combinedTrendChart') && combined.length) {
    new Chart(document.getElementById('combinedTrendChart'), {
        type: 'line',
        data: {
            labels: combined.map(i => i.label),
            datasets: [
                { label: 'Ventas', data: combined.map(i => i.sales), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.1)', fill: true, tension: 0.35 },
                { label: 'Compras', data: combined.map(i => i.purchases), borderColor: '#e11d48', tension: 0.35 },
                { label: 'Margen', data: combined.map(i => i.profit), borderColor: '#059669', borderDash: [4, 4], tension: 0.35 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } }, x: { grid: { display: false } } },
        },
    });
}
@endif

@if($hasVentas)
const payments = charts.payment_methods || [];
if (document.getElementById('paymentMethodsChart') && payments.some(p => p.value > 0)) {
    new Chart(document.getElementById('paymentMethodsChart'), {
        type: 'doughnut',
        data: {
            labels: payments.map(i => i.label),
            datasets: [{ data: payments.map(i => i.value), backgroundColor: ['#4f46e5', '#0ea5e9', '#f59e0b'], borderWidth: 0 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '62%',
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${money(ctx.raw)}` } } },
        },
    });
}

const daily = charts.daily_sales || [];
if (document.getElementById('dailySalesChart')) {
    new Chart(document.getElementById('dailySalesChart'), {
        type: 'bar',
        data: {
            labels: daily.map(i => i.label),
            datasets: [{ label: 'Ventas', data: daily.map(i => i.total), backgroundColor: '#6366f1', borderRadius: 4, maxBarThickness: 14 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => money(ctx.raw) } } },
            scales: { y: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } }, x: { grid: { display: false } } },
        },
    });
}

const topProducts = charts.top_products || [];
if (document.getElementById('topProductsChart') && topProducts.length) {
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: topProducts.map(i => i.name),
            datasets: [{ label: 'Unidades', data: topProducts.map(i => i.qty), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 28 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { color: 'rgba(148,163,184,0.2)' } }, y: { grid: { display: false } } },
        },
    });
}
@endif

@if($hasInventario)
const inventory = charts.inventory_health || [];
if (document.getElementById('inventoryHealthChart') && inventory.some(i => i.value > 0)) {
    new Chart(document.getElementById('inventoryHealthChart'), {
        type: 'doughnut',
        data: {
            labels: inventory.map(i => i.label),
            datasets: [{ data: inventory.map(i => i.value), backgroundColor: ['#10b981', '#f59e0b', '#fb923c', '#ef4444'], borderWidth: 0 }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
    });
}
@endif

@if($hasClientes)
const topClients = charts.top_clients || [];
if (document.getElementById('topClientsChart') && topClients.length) {
    new Chart(document.getElementById('topClientsChart'), {
        type: 'bar',
        data: {
            labels: topClients.map(i => i.name),
            datasets: [{ label: 'Ventas', data: topClients.map(i => i.total), backgroundColor: '#8b5cf6', borderRadius: 6, maxBarThickness: 32 }],
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => money(ctx.raw) } } },
            scales: { x: { ticks: { callback: money }, grid: { color: 'rgba(148,163,184,0.2)' } }, y: { grid: { display: false } } },
        },
    });
}
@endif
</script>
@endif
@endsection
