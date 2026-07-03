@extends('layouts.app')

@section('title', 'Dashboard General')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">
                Dashboard General
            </h2>
            <p class="text-sm text-slate-500">
                Resumen ejecutivo del sistema | {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reportes.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Reportes
            </a>
        </div>
    </div>

    {{-- Alertas del Sistema --}}
    @if(count($alerts) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($alerts as $alert)
            <a href="{{ $alert['link'] }}" class="flex items-center gap-3 p-3 rounded-lg border {{ 
                $alert['type'] === 'danger' ? 'bg-red-50 border-red-200 hover:bg-red-100' : 
                ($alert['type'] === 'warning' ? 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100' : 'bg-blue-50 border-blue-200 hover:bg-blue-100') 
            }}">
                <span class="text-xl">{{ $alert['type'] === 'danger' ? '🚨' : ($alert['type'] === 'warning' ? '⚠️' : 'ℹ️') }}</span>
                <p class="text-sm font-medium {{ $alert['type'] === 'danger' ? 'text-red-800' : ($alert['type'] === 'warning' ? 'text-yellow-800' : 'text-blue-800') }}">
                    {{ $alert['message'] }}
                </p>
            </a>
        @endforeach
    </div>
    @endif

    {{-- KPIs Principales --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Ventas Hoy --}}
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 text-white p-5 rounded-xl shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-indigo-100 text-xs uppercase tracking-wide">Ventas Hoy</p>
                    <p class="text-2xl font-bold mt-1">C$ {{ number_format($salesStats['today'], 2) }}</p>
                    <p class="text-indigo-200 text-xs mt-1">{{ $salesStats['count_today'] }} facturas</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Ventas Mes --}}
        <div class="bg-gradient-to-br from-slate-600 to-slate-700 text-white p-5 rounded-xl shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-100 text-xs uppercase tracking-wide">Ventas del Mes</p>
                    <p class="text-2xl font-bold mt-1">C$ {{ number_format($salesStats['month'], 2) }}</p>
                    <p class="text-slate-200 text-xs mt-1">{{ now()->format('F Y') }}</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Valor Inventario --}}
        <div class="bg-gradient-to-br from-violet-600 to-violet-700 text-white p-5 rounded-xl shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-violet-100 text-xs uppercase tracking-wide">Valor Inventario</p>
                    <p class="text-2xl font-bold mt-1">C$ {{ number_format($inventoryStats['inventory_value'], 0) }}</p>
                    <p class="text-violet-200 text-xs mt-1">{{ $inventoryStats['total_products'] }} productos</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
        </div>

        {{-- Clientes --}}
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-5 rounded-xl shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-amber-100 text-xs uppercase tracking-wide">Clientes Activos</p>
                    <p class="text-2xl font-bold mt-1">{{ $customerStats['total_clients'] }}</p>
                    <p class="text-amber-200 text-xs mt-1">+{{ $customerStats['new_this_month'] }} este mes</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Segunda fila de estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl shadow border-l-4 {{ $inventoryStats['low_stock'] > 0 ? 'border-yellow-500' : 'border-green-500' }}">
            <div class="flex justify-between">
                <div>
                    <p class="text-gray-500 text-xs">Stock Bajo</p>
                    <p class="text-2xl font-bold {{ $inventoryStats['low_stock'] > 0 ? 'text-yellow-600' : 'text-green-600' }}">{{ $inventoryStats['low_stock'] }}</p>
                </div>
                <a href="{{ route('inventario.index', ['stock_status' => 'low']) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 {{ $inventoryStats['expired'] > 0 ? 'border-red-500' : 'border-green-500' }}">
            <div class="flex justify-between">
                <div>
                    <p class="text-gray-500 text-xs">Vencidos</p>
                    <p class="text-2xl font-bold {{ $inventoryStats['expired'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $inventoryStats['expired'] }}</p>
                </div>
                <a href="{{ route('inventario.index', ['stock_status' => 'expired']) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
            <div class="flex justify-between">
                <div>
                    <p class="text-gray-500 text-xs">Facturas Pendientes</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $salesStats['pending'] }}</p>
                </div>
                <a href="{{ route('facturacion.index', ['status' => 'pending']) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            </div>
        </div>
    </div>

    {{-- Gráficos y Tablas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Gráfico de Ventas --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Ventas Mensuales (Últimos 6 meses)
            </h3>

            @php
                $maxSales = collect($salesChart)->max('total') ?: 1;
            @endphp

            <div class="flex items-end justify-around h-48 gap-2">
                @foreach($salesChart as $data)
                    @php
                        $height = $maxSales > 0 ? ($data['total'] / $maxSales) * 100 : 0;
                        $heightPx = max(20, $height * 1.5);
                    @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="text-xs text-gray-600 mb-1">C$ {{ number_format($data['total'] / 1000, 0) }}k</div>
                        <div class="w-full bg-gradient-to-t from-green-600 to-green-400 rounded-t-lg transition-all hover:from-green-700 hover:to-green-500 relative group" style="height: {{ $heightPx }}px;">
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                C$ {{ number_format($data['total'], 2) }}
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2">{{ $data['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Productos Más Vendidos --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                Top 5 Productos Más Vendidos
            </h3>

            @if($topProducts->count() > 0)
                <div class="space-y-3">
                    @foreach($topProducts as $index => $product)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $index < 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $product->name }}</p>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>{{ $product->total_qty }} unidades</span>
                                    <span>C$ {{ number_format($product->total_sales, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">No hay datos de ventas disponibles</p>
            @endif
        </div>
    </div>

    {{-- Últimas Facturas y Movimientos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Últimas Facturas --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Últimas Facturas</h3>
                <a href="{{ route('facturacion.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Factura</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($latestSales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    <a href="{{ route('facturacion.show', $sale->id) }}" class="text-indigo-600 hover:underline">
                                        #{{ $sale->invoice_number ?? str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $sale->billing_name ?? $sale->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right font-medium">C$ {{ number_format($sale->total, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : ($sale->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $sale->status === 'completed' ? 'Pagada' : ($sale->status === 'pending' ? 'Pendiente' : 'Cancelada') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay facturas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Movimientos Recientes --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Movimientos Recientes</h3>
                <a href="{{ route('movimientos.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recentMovements as $movement)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-xs">{{ $movement->product?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $movement->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ $movement->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $movement->stock_after ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay movimientos recientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
