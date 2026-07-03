@extends('layouts.app')

@section('title', 'Inventario')

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-700">Gestión de Inventario</h2>
            <p class="text-sm text-gray-500">Control de productos agrícolas y lotes</p>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('inventario.export') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-sm">
                📥 Exportar
            </a>
            <a href="{{ route('inventario.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
                + Nuevo Producto
            </a>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Total Productos</p>
            <p class="text-2xl font-bold text-blue-700">{{ $stats['total_products'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">Bajo Stock</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['low_stock_count'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Vencidos</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['expired_count'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">Por Vencer (30d)</p>
            <p class="text-2xl font-bold text-orange-600">{{ $stats['expiring_soon_count'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Valor Inventario</p>
            <p class="text-2xl font-bold text-purple-700">C$ {{ number_format($stats['total_inventory_value'], 0) }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('inventario.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Búsqueda</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Código, nombre, lote, ingrediente..."
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Estado Stock</label>
                <select name="stock_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="normal" {{ request('stock_status') == 'normal' ? 'selected' : '' }}>Stock Normal</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Bajo Stock</option>
                    <option value="expiring_soon" {{ request('stock_status') == 'expiring_soon' ? 'selected' : '' }}>Por Vencer</option>
                    <option value="expired" {{ request('stock_status') == 'expired' ? 'selected' : '' }}>Vencido</option>
                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Sin Stock</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    <option value="discontinued" {{ request('status') == 'discontinued' ? 'selected' : '' }}>Descontinuado</option>
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('inventario.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="min-w-full text-sm">

            <thead class="bg-green-700 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <a href="{{ route('inventario.index', array_merge(request()->all(), ['sort_by' => 'code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                            Código
                            @if(request('sort_by') == 'code')
                                <span class="ml-1">{{ request('sort_order') == 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <a href="{{ route('inventario.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                            Producto
                            @if(request('sort_by') == 'name')
                                <span class="ml-1">{{ request('sort_order') == 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left">Categoría</th>
                    <th class="px-4 py-3 text-left">Lote</th>
                    <th class="px-4 py-3 text-left">Ubicación</th>
                    <th class="px-4 py-3 text-left">Vencimiento</th>
                    <th class="px-4 py-3 text-left">
                        <a href="{{ route('inventario.index', array_merge(request()->all(), ['sort_by' => 'stock', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                            Stock
                            @if(request('sort_by') == 'stock')
                                <span class="ml-1">{{ request('sort_order') == 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left">Precio Venta</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 {{ $product->isExpired() ? 'bg-red-50' : ($product->expiresSoon(30) ? 'bg-orange-50' : ($product->isLowStock() ? 'bg-yellow-50' : '')) }}">
                        <td class="px-4 py-3 font-semibold">{{ $product->code ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $product->name }}</div>
                            @if($product->active_ingredient)
                                <div class="text-xs text-gray-500">{{ $product->active_ingredient }} {{ $product->concentration ? '(' . $product->concentration . ')' : '' }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $product->lot ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $product->location ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($product->expiry_date)
                                <span class="{{ $product->isExpired() ? 'text-red-600 font-bold' : ($product->expiresSoon(30) ? 'text-orange-600 font-semibold' : 'text-gray-600') }}">
                                    {{ $product->expiry_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold {{ $product->isLowStock() ? 'text-red-600' : ($product->stock == 0 ? 'text-red-600' : 'text-green-600') }}">
                                {{ $product->stock }} {{ $product->unit }}
                            </span>
                            <div class="text-xs text-gray-500">Mín: {{ $product->low_stock_threshold ?? 10 }}</div>
                        </td>
                        <td class="px-4 py-3">C$ {{ number_format($product->sale_price, 2) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = match($product->inventory_status) {
                                    'expired' => 'bg-red-100 text-red-700',
                                    'expiring_soon' => 'bg-orange-100 text-orange-700',
                                    'low_stock' => 'bg-yellow-100 text-yellow-700',
                                    'normal' => 'bg-green-100 text-green-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="{{ $statusClass }} px-2 py-1 rounded-full text-xs">
                                {{ $product->inventory_status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center space-x-1">
                            <a href="{{ route('inventario.show', $product->id) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            @if(auth()->user()?->isAdmin())
                            <a href="{{ route('inventario.edit', $product->id) }}" class="text-gray-600 hover:underline text-xs">Editar</a>
                            <a href="{{ route('ajustes.create', ['product_id' => $product->id]) }}" class="text-green-600 hover:underline text-xs">Ajustar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron productos con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>

        <div class="px-4 py-3 bg-gray-50 border-t">
            {{ $products->links() }}
        </div>

    </div>

</div>

@endsection
