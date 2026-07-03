@extends('layouts.app')

@section('title', 'Reportes')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Reportes y Análisis</h2>
            <p class="text-sm text-gray-500">Generación de reportes detallados del sistema</p>
        </div>
    </div>

    {{-- Panel de Filtros --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <form method="GET" action="{{ route('reportes.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Tipo de Reporte --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Reporte</label>
                    <select name="report_type" class="w-full border-gray-300 rounded-lg shadow-sm" onchange="this.form.submit()">
                        <option value="sales" {{ $reportType == 'sales' ? 'selected' : '' }}>📊 Ventas</option>
                        <option value="purchases" {{ $reportType == 'purchases' ? 'selected' : '' }}>📦 Compras</option>
                        <option value="inventory" {{ $reportType == 'inventory' ? 'selected' : '' }}>📋 Inventario</option>
                        <option value="kardex" {{ $reportType == 'kardex' ? 'selected' : '' }}>📈 Kardex</option>
                        <option value="profit" {{ $reportType == 'profit' ? 'selected' : '' }}>💰 Rentabilidad</option>
                    </select>
                </div>

                {{-- Fecha Inicio --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>

                {{-- Fecha Fin --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full border-gray-300 rounded-lg shadow-sm">
                </div>

                {{-- Botones --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Generar
                    </button>
                    <a href="{{ route('reportes.export', request()->all()) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2" title="Exportar a Excel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Excel
                    </a>
                </div>
            </div>

            {{-- Filtros adicionales según tipo --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
                @if($reportType == 'sales')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <select name="client_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos los clientes</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Pagada</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición de Pago</label>
                        <select name="payment_type" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todas</option>
                            <option value="cash" {{ request('payment_type') == 'cash' ? 'selected' : '' }}>Contado</option>
                            <option value="credit" {{ request('payment_type') == 'credit' ? 'selected' : '' }}>Crédito</option>
                        </select>
                    </div>
                @elseif($reportType == 'purchases')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                        <select name="supplier_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos los proveedores</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completada</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                @elseif(in_array($reportType, ['inventory', 'kardex']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                        <select name="product_id" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos los productos</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($reportType == 'kardex')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento</label>
                        <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos</option>
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Entrada</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Salida</option>
                        </select>
                    </div>
                    @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado de Stock</label>
                        <select name="stock_status" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">Todos</option>
                            <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Stock Bajo</option>
                            <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Sin Stock</option>
                            <option value="expired" {{ request('stock_status') == 'expired' ? 'selected' : '' }}>Vencido</option>
                            <option value="expiring_soon" {{ request('stock_status') == 'expiring_soon' ? 'selected' : '' }}>Por Vencer</option>
                        </select>
                    </div>
                    @endif
                @endif
            </div>
        </form>
    </div>

    {{-- Resumen Ejecutivo --}}
    @if(isset($summary) && count($summary) > 0)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @if($reportType == 'sales')
            <div class="bg-gradient-to-br from-green-600 to-green-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-green-100 text-xs uppercase">Total Ventas</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['total_sales'] ?? 0, 2) }}</p>
                <p class="text-green-200 text-xs">{{ $summary['total_count'] ?? 0 }} transacciones</p>
            </div>
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-blue-100 text-xs uppercase">Promedio por Venta</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['avg_sale'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-500">
                <p class="text-gray-500 text-xs">Pagadas</p>
                <p class="text-2xl font-bold text-green-600">
                    {{ $summary['by_status']->where('status', 'completed')->first()->count ?? 0 }}
                </p>
                <p class="text-xs text-gray-400">
                    C$ {{ number_format($summary['by_status']->where('status', 'completed')->first()->total ?? 0, 2) }}
                </p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-yellow-500">
                <p class="text-gray-500 text-xs">Pendientes</p>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $summary['by_status']->where('status', 'pending')->first()->count ?? 0 }}
                </p>
                <p class="text-xs text-gray-400">
                    C$ {{ number_format($summary['by_status']->where('status', 'pending')->first()->total ?? 0, 2) }}
                </p>
            </div>
        @elseif($reportType == 'purchases')
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-blue-100 text-xs uppercase">Total Compras</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['total_purchases'] ?? 0, 2) }}</p>
                <p class="text-blue-200 text-xs">{{ $summary['total_count'] ?? 0 }} órdenes</p>
            </div>
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-purple-100 text-xs uppercase">Promedio</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['avg_purchase'] ?? 0, 2) }}</p>
            </div>
        @elseif($reportType == 'inventory')
            <div class="bg-gradient-to-br from-green-600 to-green-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-green-100 text-xs uppercase">Total Productos</p>
                <p class="text-2xl font-bold">{{ $summary['total_products'] ?? 0 }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-600 to-purple-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-purple-100 text-xs uppercase">Valor Inventario</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['total_value'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-yellow-500">
                <p class="text-gray-500 text-xs">Stock Bajo</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $summary['low_stock'] ?? 0 }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-red-500">
                <p class="text-gray-500 text-xs">Vencidos</p>
                <p class="text-2xl font-bold text-red-600">{{ $summary['expired'] ?? 0 }}</p>
            </div>
        @elseif($reportType == 'profit')
            <div class="bg-gradient-to-br from-green-600 to-green-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-green-100 text-xs uppercase">Ventas</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['total_sales'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-5 rounded-xl shadow-lg">
                <p class="text-red-100 text-xs uppercase">Costos</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-gradient-to-br {{ ($summary['gross_profit'] ?? 0) >= 0 ? 'from-green-600 to-green-700' : 'from-red-600 to-red-700' }} text-white p-5 rounded-xl shadow-lg">
                <p class="text-green-100 text-xs uppercase">Ganancia Bruta</p>
                <p class="text-2xl font-bold">C$ {{ number_format($summary['gross_profit'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow border-l-4 {{ ($summary['profit_margin'] ?? 0) >= 0 ? 'border-green-500' : 'border-red-500' }}">
                <p class="text-gray-500 text-xs">Margen de Ganancia</p>
                <p class="text-2xl font-bold {{ ($summary['profit_margin'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($summary['profit_margin'] ?? 0, 1) }}%
                </p>
            </div>
        @endif
    </div>
    @endif

    {{-- Tabla de Resultados --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700">
                @switch($reportType)
                    @case('sales') Detalle de Ventas @break
                    @case('purchases') Detalle de Compras @break
                    @case('inventory') Estado del Inventario @break
                    @case('kardex') Kardex de Movimientos @break
                    @case('profit') Análisis de Rentabilidad @break
                @endswitch
            </h3>
            <span class="text-sm text-gray-500">Total: {{ $data->total() ?? 0 }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                @if($reportType == 'sales')
                    <thead class="bg-green-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Factura</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Condición</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-right">IVA</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    <a href="{{ route('facturacion.show', $sale->id) }}" class="text-blue-600 hover:underline">
                                        {{ $sale->invoice_number ?? '#' . str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $sale->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $sale->billing_name ?? $sale->client?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $sale->payment_type === 'credit' ? 'Crédito' : 'Contado' }}</td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($sale->subtotal, 2) }}</td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($sale->tax_total, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium">C$ {{ number_format($sale->total, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $sale->status === 'completed' ? 'Pagada' : 'Pendiente' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>

                @elseif($reportType == 'purchases')
                    <thead class="bg-blue-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Orden</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Proveedor</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data as $purchase)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">#{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3">{{ $purchase->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right font-medium">C$ {{ number_format($purchase->total, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $purchase->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $purchase->status === 'completed' ? 'Completada' : 'Pendiente' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>

                @elseif($reportType == 'inventory')
                    <thead class="bg-purple-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Categoría</th>
                            <th class="px-4 py-3 text-right">Stock</th>
                            <th class="px-4 py-3 text-right">Precio</th>
                            <th class="px-4 py-3 text-right">Valor Total</th>
                            <th class="px-4 py-3 text-left">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data as $product)
                            <tr class="hover:bg-gray-50 {{ $product->isExpired() ? 'bg-red-50' : ($product->isLowStock() ? 'bg-yellow-50' : '') }}">
                                <td class="px-4 py-3">{{ $product->code ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                                <td class="px-4 py-3">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="{{ $product->isLowStock() ? 'text-red-600 font-bold' : '' }}">
                                        {{ $product->stock }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($product->sale_price, 2) }}</td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($product->stock * $product->purchase_price, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if($product->expiry_date)
                                        <span class="{{ $product->isExpired() ? 'text-red-600 font-bold' : ($product->expiresSoon(30) ? 'text-orange-600' : 'text-gray-600') }}">
                                            {{ $product->expiry_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>

                @elseif($reportType == 'kardex')
                    <thead class="bg-orange-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-center">Tipo</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-right">Stock Antes</th>
                            <th class="px-4 py-3 text-right">Stock Después</th>
                            <th class="px-4 py-3 text-left">Referencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data as $movement)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $movement->product?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $movement->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ $movement->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ $movement->stock_before ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ $movement->stock_after ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $movement->reference ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>

                @elseif($reportType == 'profit')
                    <thead class="bg-teal-700 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Factura</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-right">Total Venta</th>
                            <th class="px-4 py-3 text-right">Costo</th>
                            <th class="px-4 py-3 text-right">Ganancia</th>
                            <th class="px-4 py-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($data as $sale)
                            @php
                                $cost = $sale->details->sum(function($d) {
                                    return $d->quantity * ($d->product?->purchase_price ?? 0);
                                });
                                $profit = $sale->total - $cost;
                                $margin = $sale->total > 0 ? ($profit / $sale->total) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ $sale->invoice_number ?? '#' . $sale->id }}</td>
                                <td class="px-4 py-3">{{ $sale->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($sale->total, 2) }}</td>
                                <td class="px-4 py-3 text-right">C$ {{ number_format($cost, 2) }}</td>
                                <td class="px-4 py-3 text-right font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    C$ {{ number_format($profit, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right {{ $margin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($margin, 1) }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay registros</td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        @if($data->hasPages())
        <div class="px-4 py-3 bg-gray-50 border-t">
            {{ $data->appends(request()->all())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

