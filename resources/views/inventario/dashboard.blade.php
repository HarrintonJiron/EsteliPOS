@extends('layouts.app')

@section('title', 'Análisis de Inventario')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="page-title">Análisis Pro de Inventario</h2>
            <p class="page-subtitle">Rotación, más vendidos, stock muerto y conciliación · Últimos {{ $periodDays }} días</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventario.index') }}" class="btn-outline text-sm">Catálogo</a>
            <a href="{{ route('inventario.bulk') }}" class="btn-secondary text-sm">Carga Masiva</a>
            @if(auth()->user()?->isAdmin() && count($discrepancies) > 0)
            <form action="{{ route('inventario.reconcile') }}" method="POST" onsubmit="return confirm('¿Corregir {{ count($discrepancies) }} discrepancias de stock según el kardex?')">
                @csrf
                <button type="submit" class="btn-primary text-sm">Reconciliar Stock</button>
            </form>
            @endif
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-5 bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
            <p class="text-indigo-200 text-xs uppercase">Valor Inventario</p>
            <p class="text-2xl font-bold mt-1">C$ {{ number_format($stats['total_inventory_value'], 0) }}</p>
        </div>
        <div class="card p-5 bg-gradient-to-br from-emerald-600 to-emerald-700 text-white">
            <p class="text-emerald-200 text-xs uppercase">Entradas ({{ $periodDays }}d)</p>
            <p class="text-2xl font-bold mt-1">+{{ number_format($movementStats['entries']) }}</p>
        </div>
        <div class="card p-5 bg-gradient-to-br from-red-500 to-red-600 text-white">
            <p class="text-red-200 text-xs uppercase">Salidas ({{ $periodDays }}d)</p>
            <p class="text-2xl font-bold mt-1">-{{ number_format($movementStats['exits']) }}</p>
        </div>
        <div class="card p-5 bg-gradient-to-br from-slate-600 to-slate-700 text-white">
            <p class="text-slate-300 text-xs uppercase">Balance Neto</p>
            <p class="text-2xl font-bold mt-1">{{ $movementStats['entries'] - $movementStats['exits'] >= 0 ? '+' : '' }}{{ number_format($movementStats['entries'] - $movementStats['exits']) }}</p>
        </div>
    </div>

    @if(count($discrepancies) > 0)
    <div class="card p-4 border border-amber-200 bg-amber-50">
        <h3 class="font-semibold text-amber-800 mb-2">Discrepancias de stock detectadas ({{ count($discrepancies) }})</h3>
        <p class="text-sm text-amber-700 mb-3">El stock registrado no coincide con la suma del kardex (entradas − salidas).</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-amber-800"><th class="pr-4 py-1">Producto</th><th class="pr-4">Registrado</th><th>Calculado</th><th>Diferencia</th></tr></thead>
                <tbody>
                    @foreach(array_slice($discrepancies, 0, 5) as $d)
                    <tr class="border-t border-amber-200">
                        <td class="py-2 pr-4">{{ $d['product']->name }}</td>
                        <td class="pr-4">{{ $d['recorded'] }}</td>
                        <td class="pr-4">{{ $d['calculated'] }}</td>
                        <td class="font-bold text-red-600">{{ $d['calculated'] - $d['recorded'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Más vendidos --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Top 10 Más Vendidos</h3>
                <a href="{{ route('inventario.index', ['view' => 'top_sellers']) }}" class="text-indigo-600 text-sm">Ver todos</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($topSellers as $i => $p)
                <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                    <span class="w-7 h-7 rounded-full {{ $i < 3 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 truncate">{{ $p->name }}</p>
                        <p class="text-xs text-slate-500">Stock: {{ $p->stock }} · {{ $p->category->name ?? '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-emerald-600">{{ (int)$p->sold_qty }} uds</p>
                        <p class="text-xs text-slate-500">C$ {{ number_format($p->sold_revenue ?? 0, 0) }}</p>
                    </div>
                </div>
                @empty
                <p class="p-6 text-center text-slate-500">Sin ventas en el período</p>
                @endforelse
            </div>
        </div>

        {{-- Baja rotación --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Menor Rotación</h3>
                <a href="{{ route('inventario.index', ['view' => 'low_rotation']) }}" class="text-indigo-600 text-sm">Ver todos</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($lowRotation as $p)
                <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-800 truncate">{{ $p->name }}</p>
                        <p class="text-xs text-slate-500">Stock: {{ $p->stock }} · Vendido: {{ (int)($p->sold_qty ?? 0) }}</p>
                    </div>
                    <span class="badge-warning">{{ number_format($p->rotation_index ?? 0, 2) }}x</span>
                </div>
                @empty
                <p class="p-6 text-center text-slate-500">Sin datos</p>
                @endforelse
            </div>
        </div>

        {{-- Stock muerto --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800">Stock Muerto (sin ventas)</h3>
                <a href="{{ route('inventario.index', ['view' => 'dead_stock']) }}" class="text-indigo-600 text-sm">Ver todos</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($deadStock as $p)
                <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-800">{{ $p->name }}</p>
                        <p class="text-xs text-slate-500">{{ $p->category->name ?? '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-red-600">{{ $p->stock }} {{ $p->unit }}</p>
                        <p class="text-xs text-slate-400">C$ {{ number_format($p->stock * $p->purchase_price, 0) }} inmovilizado</p>
                    </div>
                </div>
                @empty
                <p class="p-6 text-center text-slate-500">No hay stock muerto</p>
                @endforelse
            </div>
        </div>

        {{-- Valor por categoría --}}
        <div class="card p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Valor por Categoría</h3>
            @php $maxVal = $valueByCategory->max('inventory_value') ?: 1; @endphp
            <div class="space-y-3">
                @foreach($valueByCategory as $cat)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700">{{ $cat->name }} <span class="text-slate-400">({{ $cat->product_count }})</span></span>
                        <span class="font-semibold">C$ {{ number_format($cat->inventory_value, 0) }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full" style="width: {{ ($cat->inventory_value / $maxVal) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Flujo de inventario --}}
    <div class="card p-6">
        <h3 class="font-semibold text-slate-800 mb-4">Flujo de Inventario (cómo se sincroniza)</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                <p class="font-semibold text-emerald-800">Entradas (+)</p>
                <ul class="mt-2 text-emerald-700 space-y-1 text-xs">
                    <li>• Compras a proveedores</li>
                    <li>• Carga masiva inicial</li>
                    <li>• Ajustes de inventario</li>
                    <li>• Reversos de ventas</li>
                </ul>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border border-red-200">
                <p class="font-semibold text-red-800">Salidas (−)</p>
                <ul class="mt-2 text-red-700 space-y-1 text-xs">
                    <li>• Ventas POS / Facturación</li>
                    <li>• Ajustes por merma</li>
                    <li>• Reversos de compras</li>
                </ul>
            </div>
            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                <p class="font-semibold text-indigo-800">Kardex</p>
                <p class="mt-2 text-indigo-700 text-xs">Cada movimiento registra cantidad, stock resultante, referencia y usuario. El stock siempre es trazable.</p>
            </div>
            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                <p class="font-semibold text-amber-800">Reconciliación</p>
                <p class="mt-2 text-amber-700 text-xs">Compara stock actual vs. suma del kardex. Corrige automáticamente discrepancias.</p>
            </div>
        </div>
    </div>

</div>
@endsection
