@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="max-w-6xl mx-auto space-y-4">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $product->name }}</h1>
                <p class="text-sm text-gray-500">
                    Código: <span class="font-mono">{{ $product->code ?? '—' }}</span>
                    @if($product->category)
                        | Categoría: {{ $product->category->name }}
                    @endif
                </p>
            </div>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('inventario.edit', $product->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">Editar</a>
            <a href="{{ route('inventario.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
        </div>
    </div>

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

        {{-- Estadísticas --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Estadísticas</h2>

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

                @if($productStats['last_movement'])
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Último Movimiento</p>
                        <p class="text-sm font-medium">{{ $productStats['last_movement']->created_at->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-gray-500">{{ $productStats['last_movement']->note ?? 'Sin nota' }}</p>
                    </div>
                @endif
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

    {{-- Historial de Movimientos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 flex items-center justify-between border-b">
            <h2 class="text-lg font-semibold text-gray-700">Historial de Movimientos</h2>
            <a href="{{ route('movimientos.index', ['producto' => $product->id]) }}" class="text-blue-600 hover:underline text-sm">Ver todos</a>
        </div>

        @if($movements->count() > 0)
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Tipo</th>
                        <th class="px-4 py-2 text-left">Cantidad</th>
                        <th class="px-4 py-2 text-left">Stock Después</th>
                        <th class="px-4 py-2 text-left">Referencia</th>
                        <th class="px-4 py-2 text-left">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($movements as $movement)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $movement->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 font-medium {{ $movement->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                            </td>
                            <td class="px-4 py-2">{{ $movement->stock_after ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $movement->reference ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $movement->user->name ?? '—' }}</td>
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

    {{-- Acciones --}}
    <div class="flex justify-end gap-3">
        <a href="{{ route('ajustes.create', ['product_id' => $product->id]) }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Hacer Ajuste
        </a>
        <form action="{{ route('inventario.destroy', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este producto?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                Eliminar Producto
            </button>
        </form>
    </div>

</div>

@endsection
