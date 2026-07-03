@extends('layouts.app')

@section('title', 'Inventario')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="page-title">Centro de Inventario</h2>
            <p class="page-subtitle">Stock sincronizado con ventas POS, compras y ajustes · Kardex en tiempo real</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventario.dashboard') }}" class="btn-outline text-sm">Análisis Pro</a>
            <a href="{{ route('movimientos.index') }}" class="btn-outline text-sm">Kardex Global</a>
            <a href="{{ route('inventario.export') }}" class="btn-outline text-sm">Exportar</a>
            <a href="{{ route('inventario.bulk') }}" class="btn-secondary text-sm">Carga Masiva</a>
            <a href="{{ route('inventario.quick') }}" class="btn-primary">+ Producto Rápido</a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500 uppercase">Productos</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['total_products'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500 uppercase">Bajo Stock</p>
            <p class="text-2xl font-bold text-amber-600">{{ $stats['low_stock_count'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
            <p class="text-xs text-slate-500 uppercase">Sin Stock</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['out_of_stock_count'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-orange-500">
            <p class="text-xs text-slate-500 uppercase">Por Vencer</p>
            <p class="text-2xl font-bold text-orange-600">{{ $stats['expiring_soon_count'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500 uppercase">Valor Costo</p>
            <p class="text-lg font-bold text-violet-700">C$ {{ number_format($stats['total_inventory_value'], 0) }}</p>
        </div>
        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500 uppercase">Valor Venta</p>
            <p class="text-lg font-bold text-emerald-700">C$ {{ number_format($stats['total_sale_value'], 0) }}</p>
        </div>
    </div>

    {{-- Movimientos del período --}}
    <div class="card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-6 text-sm">
                <span class="text-slate-600">Últimos <strong>{{ $periodDays }}</strong> días:</span>
                <span class="flex items-center gap-1 text-emerald-700 font-semibold">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    Entradas: +{{ number_format($movementStats['entries']) }} ({{ $movementStats['entry_count'] }} mov.)
                </span>
                <span class="flex items-center gap-1 text-red-600 font-semibold">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    Salidas: -{{ number_format($movementStats['exits']) }} ({{ $movementStats['exit_count'] }} mov.)
                </span>
            </div>
            @if($discrepancyCount > 0)
            <a href="{{ route('inventario.index', ['stock_status' => 'discrepancy']) }}" class="badge-danger">
                {{ $discrepancyCount }} discrepancia(s) de stock
            </a>
            @else
            <span class="badge-success">Stock conciliado</span>
            @endif
        </div>
    </div>

    {{-- Tabs de vista analítica --}}
    <div class="flex flex-wrap gap-1 border-b border-slate-200">
        @php
            $tabs = [
                'list' => 'Catálogo',
                'top_sellers' => 'Más Vendidos',
                'high_rotation' => 'Alta Rotación',
                'low_rotation' => 'Baja Rotación',
                'dead_stock' => 'Stock Muerto',
            ];
        @endphp
        @foreach($tabs as $key => $label)
        <a href="{{ route('inventario.index', array_merge(request()->except('page'), ['view' => $key])) }}"
           class="tab-link {{ $viewMode === $key ? 'tab-link-active' : 'tab-link-inactive' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Búsqueda</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Código, nombre, lote..." class="input-field">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Categoría</label>
                <select name="category_id" class="select-field">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado Stock</label>
                <select name="stock_status" class="select-field">
                    <option value="">Todos</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Bajo Stock</option>
                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Sin Stock</option>
                    <option value="expiring_soon" {{ request('stock_status') == 'expiring_soon' ? 'selected' : '' }}>Por Vencer</option>
                    <option value="expired" {{ request('stock_status') == 'expired' ? 'selected' : '' }}>Vencido</option>
                    <option value="discrepancy" {{ request('stock_status') == 'discrepancy' ? 'selected' : '' }}>Discrepancia</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período análisis</label>
                <select name="period" class="select-field">
                    @foreach([7, 30, 60, 90] as $d)
                    <option value="{{ $d }}" {{ $periodDays == $d ? 'selected' : '' }}>{{ $d }} días</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary text-sm">Filtrar</button>
                <a href="{{ route('inventario.index') }}" class="btn-outline text-sm">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    @if($viewMode !== 'list')
                    <th class="text-right">Vendido ({{ $periodDays }}d)</th>
                    <th class="text-right">Rotación</th>
                    @endif
                    <th>P. Venta</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr class="{{ $product->isExpired() ? 'bg-red-50' : ($product->expiresSoon(30) ? 'bg-orange-50/50' : ($product->isLowStock() ? 'bg-amber-50/50' : '')) }}">
                    <td class="font-mono font-semibold text-indigo-600">{{ $product->code }}</td>
                    <td>
                        <p class="font-medium text-slate-800">{{ $product->name }}</p>
                        @if($product->location)<p class="text-xs text-slate-400">{{ $product->location }}</p>@endif
                    </td>
                    <td>{{ $product->category->name ?? '—' }}</td>
                    <td>
                        <span class="font-bold {{ $product->stock <= 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ $product->stock }} {{ $product->unit }}
                        </span>
                        <p class="text-xs text-slate-400">mín. {{ $product->low_stock_threshold ?? 10 }}</p>
                    </td>
                    @if($viewMode !== 'list')
                    <td class="text-right font-semibold">{{ (int)($product->sold_qty ?? 0) }}</td>
                    <td class="text-right">
                        @php $rot = (float)($product->rotation_index ?? 0); @endphp
                        <span class="{{ $rot >= 1 ? 'text-emerald-600 font-bold' : ($rot > 0 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ number_format($rot, 2) }}x
                        </span>
                    </td>
                    @endif
                    <td>C$ {{ number_format($product->sale_price, 2) }}</td>
                    <td><span class="badge-{{ match($product->inventory_status) { 'expired' => 'danger', 'expiring_soon', 'low_stock' => 'warning', default => 'success' } }}">{{ $product->inventory_status_label }}</span></td>
                    <td class="text-center space-x-2">
                        <a href="{{ route('inventario.show', $product->id) }}" class="text-indigo-600 text-sm font-medium">Kardex</a>
                        @if(auth()->user()?->isAdmin())
                        <a href="{{ route('inventario.edit', $product->id) }}" class="text-slate-500 text-sm">Editar</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $viewMode !== 'list' ? 9 : 7 }}" class="text-center py-10 text-slate-500">No se encontraron productos</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $products->links() }}</div>
    </div>

</div>
@endsection
