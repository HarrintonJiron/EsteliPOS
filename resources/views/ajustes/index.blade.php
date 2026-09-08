@extends('layouts.app')

@section('title', 'Ajustes de Inventario')

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-700">Ajustes de Inventario</h2>
            <p class="text-sm text-gray-500">Control de ajustes, conteos físicos y correcciones de stock</p>
        </div>

        <a href="{{ route('ajustes.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg shadow">
            + Nuevo Ajuste
        </a>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Total Ajustes</p>
            <p class="text-2xl font-bold text-blue-700">{{ $stats['total_adjustments'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Aumentos Totales</p>
            <p class="text-2xl font-bold text-green-700">{{ $stats['total_increases'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Disminuciones Totales</p>
            <p class="text-2xl font-bold text-red-700">{{ $stats['total_decreases'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Ajustes por Conteo</p>
            <p class="text-2xl font-bold text-purple-700">{{ $stats['total_count_adjustments'] }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('ajustes.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Producto</label>
                <select name="product_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="increase" {{ request('type') == 'increase' ? 'selected' : '' }}>Aumento</option>
                    <option value="decrease" {{ request('type') == 'decrease' ? 'selected' : '' }}>Disminución</option>
                    <option value="count" {{ request('type') == 'count' ? 'selected' : '' }}>Ajuste por Conteo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('ajustes.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla de Ajustes --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="min-w-full text-sm">
            <thead class="bg-slate-800 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <th class="px-4 py-3 text-left">Stock Antes</th>
                    <th class="px-4 py-3 text-left">Cantidad</th>
                    <th class="px-4 py-3 text-left">Stock Después</th>
                    <th class="px-4 py-3 text-left">Motivo</th>
                    <th class="px-4 py-3 text-left">Usuario</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($adjustments as $adjustment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            {{ $adjustment->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ $adjustment->product->name ?? '—' }}
                            <div class="text-xs text-gray-500">{{ $adjustment->product->code ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $adjustment->type === 'increase' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $adjustment->type === 'decrease' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $adjustment->type === 'count' ? 'bg-blue-100 text-blue-700' : '' }}">
                                {{ $adjustment->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $adjustment->stock_before }}</td>
                        <td class="px-4 py-3 font-bold {{ $adjustment->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $adjustment->quantity >= 0 ? '+' : '' }}{{ $adjustment->quantity }}
                        </td>
                        <td class="px-4 py-3 font-bold">{{ $adjustment->stock_after }}</td>
                        <td class="px-4 py-3 max-w-xs truncate" title="{{ $adjustment->reason }}">
                            {{ $adjustment->reason }}
                        </td>
                        <td class="px-4 py-3">{{ $adjustment->user->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="{{ route('ajustes.show', $adjustment->id) }}" class="text-indigo-600 hover:underline">Ver</a>
                            <form action="{{ route('ajustes.destroy', $adjustment->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este ajuste? El stock se restaurará.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            No hay ajustes registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 bg-gray-50 border-t">
            {{ $adjustments->links() }}
        </div>

    </div>

</div>

@endsection
