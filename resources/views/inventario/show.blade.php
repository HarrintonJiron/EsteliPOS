@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="max-w-6xl mx-auto space-y-4">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">{{ $product->name }}</h1>
            <p class="page-subtitle">
                Código: <span class="font-mono">{{ $product->code }}</span>
                @if($product->category) · {{ $product->category->name }} @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventario.edit', $product->id) }}" class="btn-outline text-sm">Editar</a>
            <a href="{{ route('inventario.index') }}" class="btn-outline text-sm">Volver</a>
        </div>
    </div>

    @if($productStats['has_discrepancy'])
    <div class="card p-4 border border-amber-300 bg-amber-50 flex items-center justify-between">
        <div>
            <p class="font-semibold text-amber-800">Discrepancia de stock detectada</p>
            <p class="text-sm text-amber-700">Registrado: <strong>{{ $product->stock }}</strong> · Según kardex: <strong>{{ $productStats['calculated_stock'] }}</strong></p>
        </div>
        @if(auth()->user()?->isAdmin())
        <form action="{{ route('inventario.reconcile') }}" method="POST">
            @csrf
            <button type="submit" class="btn-primary text-sm">Reconciliar</button>
        </form>
        @endif
    </div>
    @endif

    {{-- Badge de Estado --}}
    <div class="flex gap-2">
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-{{ $product->status_color }}-100 text-{{ $product->status_color }}-700">
            {{ $product->status_label }}
        </span>
        <span class="px-3 py-1 rounded-full text-sm font-medium {{ match($product->inventory_status) { 'expired' => 'bg-red-100 text-red-700', 'expiring_soon' => 'bg-orange-100 text-orange-700', 'low_stock' => 'bg-yellow-100 text-yellow-700', 'normal' => 'bg-green-100 text-green-700', default => 'bg-gray-100 text-gray-700' } }}">
            {{ $product->inventory_status_label }}
        </span>
    </div>

    {{-- Información Principal --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Stock y Precios --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Stock y Precios</h2>

            <div class="space-y-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-500">Stock Actual</p>
                    <p class="text-4xl font-bold {{ $product->isLowStock() ? 'text-red-600' : 'text-green-600' }}">
                        {{ $product->stock }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $product->unit }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Precio Compra</p>
                        <p class="text-lg font-semibold">C$ {{ number_format($product->purchase_price, 2) }}</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Precio Venta</p>
                        <p class="text-lg font-semibold">C$ {{ number_format($product->sale_price, 2) }}</p>
                    </div>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Valor en Inventario</p>
                    <p class="text-lg font-semibold">C$ {{ number_format($product->stock * $product->purchase_price, 2) }}</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Stock Mínimo</p>
                    <p class="text-lg font-semibold">{{ $product->low_stock_threshold ?? 10 }} {{ $product->unit }}</p>
                </div>
            </div>
        </div>

        {{-- Trazabilidad --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Trazabilidad</h2>

            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Lote</span>
                    <span class="font-medium">{{ $product->lot ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Vencimiento</span>
                    <span class="font-medium {{ $product->isExpired() ? 'text-red-600' : ($product->expiresSoon(30) ? 'text-orange-600' : '') }}">
                        {{ $product->expiry_date?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Ubicación</span>
                    <span class="font-medium">{{ $product->location ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Reg. Sanitario</span>
                    <span class="font-medium">{{ $product->registration_number ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Ingrediente Activo</span>
                    <span class="font-medium">{{ $product->active_ingredient ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Concentración</span>
                    <span class="font-medium">{{ $product->concentration ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Rotación y ventas --}}
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Rotación ({{ $periodDays }} días)</h2>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-indigo-50 rounded-xl text-center">
                        <p class="text-xs text-indigo-600">Vendido</p>
                        <p class="text-2xl font-bold text-indigo-700">{{ $productStats['sold_qty'] }}</p>
                    </div>
                    <div class="p-3 bg-violet-50 rounded-xl text-center">
                        <p class="text-xs text-violet-600">Índice Rotación</p>
                        <p class="text-2xl font-bold text-violet-700">{{ number_format($productStats['rotation_index'], 2) }}x</p>
                    </div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500">Ingresos por ventas</p>
                    <p class="text-lg font-semibold">C$ {{ number_format($productStats['sold_revenue'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $productStats['sale_count'] }} facturas</p>
                </div>
            </div>
        </div>

        {{-- Estadísticas kardex --}}
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Kardex</h2>

            <div class="space-y-3">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Total Movimientos</p>
                    <p class="text-2xl font-bold">{{ $productStats['total_movements'] }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-xs text-green-600">Entradas</p>
                        <p class="text-xl font-bold text-green-700">{{ $productStats['total_in'] }}</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <p class="text-xs text-red-600">Salidas</p>
                        <p class="text-xl font-bold text-red-700">{{ $productStats['total_out'] }}</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500">Stock según kardex</p>
                    <p class="text-xl font-bold {{ $productStats['has_discrepancy'] ? 'text-amber-600' : 'text-emerald-600' }}">{{ $productStats['calculated_stock'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Descripción y Observaciones --}}
    @if($product->description || $product->observations)
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Información Adicional</h2>

            @if($product->description)
                <div class="mb-4">
                    <p class="text-sm text-gray-500">Descripción</p>
                    <p class="text-gray-800">{{ $product->description }}</p>
                </div>
            @endif

            @if($product->observations)
                <div>
                    <p class="text-sm text-gray-500">Observaciones</p>
                    <p class="text-gray-800">{{ $product->observations }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Historial de Movimientos (Kardex) --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-slate-800">Historial de Movimientos</h2>
            <a href="{{ route('movimientos.index', ['producto' => $product->id]) }}" class="text-indigo-600 text-sm font-medium">Ver todos</a>
        </div>

        @if($movements->count() > 0)
            <table class="min-w-full table-agro">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock Después</th>
                        <th>Referencia</th>
                        <th>Nota</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="{{ $movement->type === 'in' ? 'badge-success' : 'badge-danger' }}">{{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}</span></td>
                            <td class="font-bold {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-red-600' }}">{{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}</td>
                            <td class="font-semibold">{{ $movement->stock_after ?? '—' }}</td>
                            <td class="text-xs font-mono text-slate-500">{{ $movement->reference ?? '—' }}</td>
                            <td class="text-xs text-slate-500 max-w-[150px] truncate">{{ $movement->note ?? '—' }}</td>
                            <td>{{ $movement->user->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-4 py-2 bg-gray-50 border-t">
                {{ $movements->links() }}
            </div>
        @else
            <div class="p-8 text-center text-gray-500">
                No hay movimientos registrados para este producto.
            </div>
        @endif
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('ajustes.create', ['product_id' => $product->id]) }}" class="btn-primary text-sm">Ajustar Stock</a>
        @if(auth()->user()?->isAdmin())
        <form action="{{ route('inventario.destroy', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este producto?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger text-sm">Eliminar</button>
        </form>
        @endif
    </div>

</div>

@endsection
