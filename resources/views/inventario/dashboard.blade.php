@extends('layouts.app')

@section('title', 'Análisis de Inventario')

@section('content')

<div class="space-y-4">

    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Dashboard de inventario</h2>
            <p class="text-xs text-slate-500">Análisis · últimos {{ $periodDays }} días</p>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('inventario.index') }}" class="btn-outline text-xs py-1.5">Catálogo</a>
            @if(auth()->user()?->isAdmin() && count($discrepancies) > 0)
            <form action="{{ route('inventario.reconcile') }}" method="POST" onsubmit="return confirm('¿Corregir {{ count($discrepancies) }} discrepancias?')">
                @csrf
                <button type="submit" class="btn-primary text-xs py-1.5">Reconciliar</button>
            </form>
            @endif
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
        <div class="rounded-lg bg-indigo-600 px-3 py-2.5 text-white">
            <p class="text-[10px] uppercase opacity-80">Valor inventario</p>
            <p class="text-lg font-bold">C$ {{ number_format($stats['total_inventory_value'], 0) }}</p>
        </div>
        <div class="rounded-lg bg-emerald-600 px-3 py-2.5 text-white">
            <p class="text-[10px] uppercase opacity-80">Entradas</p>
            <p class="text-lg font-bold">+{{ number_format($movementStats['entries']) }}</p>
        </div>
        <div class="rounded-lg bg-red-500 px-3 py-2.5 text-white">
            <p class="text-[10px] uppercase opacity-80">Salidas</p>
            <p class="text-lg font-bold">−{{ number_format($movementStats['exits']) }}</p>
        </div>
        <div class="rounded-lg bg-slate-700 px-3 py-2.5 text-white">
            <p class="text-[10px] uppercase opacity-80">Balance neto</p>
            @php $net = $movementStats['entries'] - $movementStats['exits']; @endphp
            <p class="text-lg font-bold">{{ $net >= 0 ? '+' : '' }}{{ number_format($net) }}</p>
        </div>
    </div>

    @if(count($discrepancies) > 0)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
        <strong>{{ count($discrepancies) }} discrepancias</strong> de stock detectadas — revisa antes de reconciliar.
    </div>
    @endif

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
        <div class="card p-3 xl:col-span-2">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">Movimientos diarios</h3>
            <div class="h-56"><canvas id="movementTrendChart"></canvas></div>
        </div>
        <div class="card p-3">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">Salud del stock</h3>
            <div class="h-56"><canvas id="stockHealthChart"></canvas></div>
        </div>
        <div class="card p-3 xl:col-span-2">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">Top ventas (unidades)</h3>
            <div class="h-52"><canvas id="topSellersChart"></canvas></div>
        </div>
        <div class="card p-3">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">Valor por categoría</h3>
            <div class="h-52"><canvas id="categoryValueChart"></canvas></div>
        </div>
    </div>

    {{-- Resúmenes --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                <h3 class="text-sm font-semibold text-slate-800">Stock por bodega</h3>
                <a href="{{ route('inventario.warehouses.index') }}" class="text-xs text-indigo-600">Ver</a>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse($warehouseSummary ?? [] as $row)
                <div class="flex items-center justify-between px-3 py-2">
                    <div>
                        <p class="font-medium text-slate-800">{{ $row['warehouse']->name }}</p>
                        <p class="text-[10px] text-slate-500">{{ $row['products'] }} productos</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-indigo-700">{{ number_format($row['quantity'], 1) }}</p>
                        <p class="text-[10px] text-slate-400">C$ {{ number_format($row['value'], 0) }}</p>
                    </div>
                </div>
                @empty
                <p class="p-4 text-center text-slate-500">Sin bodegas</p>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                <h3 class="text-sm font-semibold text-slate-800">Listas de precios</h3>
                <a href="{{ route('inventario.price-lists.index') }}" class="text-xs text-indigo-600">Ver</a>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse($priceLists ?? [] as $list)
                <a href="{{ route('inventario.price-lists.show', $list) }}" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50">
                    <span class="font-medium text-slate-800">{{ $list->name }}</span>
                    <span class="font-semibold text-emerald-600">{{ $list->items_count }} precios</span>
                </a>
                @empty
                <p class="p-4 text-center text-slate-500">Sin listas</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-semibold text-slate-800">Top vendidos</div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse($topSellers->take(8) as $i => $p)
                <div class="flex items-center gap-2 px-3 py-1.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold">{{ $i + 1 }}</span>
                    <span class="min-w-0 flex-1 truncate text-slate-800">{{ $p->name }}</span>
                    <span class="font-bold text-emerald-600">{{ (int)$p->sold_qty }}</span>
                </div>
                @empty
                <p class="p-4 text-center text-slate-500">Sin ventas</p>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-semibold text-slate-800">Baja rotación / stock muerto</div>
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($lowRotation->take(4) as $p)
                <div class="flex justify-between px-3 py-1.5">
                    <span class="truncate text-slate-800">{{ $p->name }}</span>
                    <span class="badge-warning">{{ number_format($p->rotation_index ?? 0, 1) }}x</span>
                </div>
                @endforeach
                @foreach($deadStock->take(4) as $p)
                <div class="flex justify-between px-3 py-1.5 text-red-700">
                    <span class="truncate">{{ $p->name }}</span>
                    <span class="font-bold">{{ $p->stock }} u.</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const movementTrend = @json($movementTrend);
const stockHealth = @json($stockHealth);
const topSellersChart = @json($topSellersChart);
const categoryValues = @json($valueByCategory->take(8)->map(fn ($c) => ['label' => $c->name, 'value' => round($c->inventory_value, 2)])->values());

new Chart(document.getElementById('movementTrendChart'), {
    type: 'line',
    data: {
        labels: movementTrend.labels,
        datasets: [
            { label: 'Entradas', data: movementTrend.entries, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
            { label: 'Salidas', data: movementTrend.exits, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.08)', fill: true, tension: 0.3 },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
        scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { maxTicksLimit: 10, font: { size: 10 } } } },
    },
});

new Chart(document.getElementById('stockHealthChart'), {
    type: 'doughnut',
    data: {
        labels: stockHealth.labels,
        datasets: [{ data: stockHealth.values, backgroundColor: stockHealth.colors, borderWidth: 0 }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
    },
});

new Chart(document.getElementById('topSellersChart'), {
    type: 'bar',
    data: {
        labels: topSellersChart.map(i => i.label),
        datasets: [{ label: 'Unidades', data: topSellersChart.map(i => i.value), backgroundColor: '#6366f1', borderRadius: 4 }],
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { font: { size: 10 } } }, y: { ticks: { font: { size: 10 } } } },
    },
});

new Chart(document.getElementById('categoryValueChart'), {
    type: 'polarArea',
    data: {
        labels: categoryValues.map(c => c.label),
        datasets: [{ data: categoryValues.map(c => c.value), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'] }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } },
        scales: { r: { ticks: { display: false } } },
    },
});
</script>
@endpush
@endsection
