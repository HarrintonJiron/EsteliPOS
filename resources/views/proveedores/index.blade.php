@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Proveedores</h1>
            <p class="text-sm text-gray-500">Catálogo y control de compras</p>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('proveedores.export') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                📥 Exportar
            </a>
            <a href="{{ route('proveedores.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                + Nuevo Proveedor
            </a>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Total Proveedores</p>
            <p class="text-2xl font-bold text-blue-700">{{ $stats['total_suppliers'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Activos</p>
            <p class="text-2xl font-bold text-green-700">{{ $stats['active_suppliers'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-500">
            <p class="text-sm text-gray-500">Inactivos</p>
            <p class="text-2xl font-bold text-gray-600">{{ $stats['inactive_suppliers'] }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Con Crédito</p>
            <p class="text-2xl font-bold text-purple-700">{{ $stats['suppliers_with_credit'] }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('proveedores.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Búsqueda</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, RUC, código, contacto..."
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Condición de Pago</label>
                <select name="payment_condition" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Todas</option>
                    <option value="contado" {{ request('payment_condition') == 'contado' ? 'selected' : '' }}>Contado</option>
                    <option value="credito_15" {{ request('payment_condition') == 'credito_15' ? 'selected' : '' }}>Crédito 15 días</option>
                    <option value="credito_30" {{ request('payment_condition') == 'credito_30' ? 'selected' : '' }}>Crédito 30 días</option>
                    <option value="credito_60" {{ request('payment_condition') == 'credito_60' ? 'selected' : '' }}>Crédito 60 días</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                <input type="text" name="city" value="{{ request('city') }}" placeholder="Filtrar por ciudad"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('proveedores.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-green-700 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <a href="{{ route('proveedores.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                            Proveedor
                            @if(request('sort_by') == 'name')
                                <span class="ml-1">{{ request('sort_order') == 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left">RUC</th>
                    <th class="px-4 py-3 text-left">Contacto</th>
                    <th class="px-4 py-3 text-left">Ciudad</th>
                    <th class="px-4 py-3 text-left">Condición</th>
                    <th class="px-4 py-3 text-center">Compras</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ $supplier->code ? '[' . $supplier->code . '] ' : '' }}{{ $supplier->name }}
                            </div>
                            @if($supplier->business_name)
                                <div class="text-xs text-gray-500">{{ $supplier->business_name }}</div>
                            @endif
                            @if($supplier->type)
                                <div class="text-xs text-gray-400">{{ $supplier->type }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $supplier->ruc ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $supplier->contact_name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $supplier->phone ?? '—' }}</div>
                            <div class="text-xs text-gray-400">{{ $supplier->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $supplier->city ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm">{{ $supplier->payment_condition_label }}</div>
                            @if(auth()->user()?->isAdmin() && $supplier->credit_limit > 0)
                                <div class="text-xs text-gray-500">
                                    Límite: C$ {{ number_format($supplier->credit_limit, 0) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                {{ $supplier->purchases_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $supplier->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $supplier->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center space-x-1">
                            <a href="{{ route('proveedores.show', $supplier->id) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            @if(auth()->user()?->isAdmin())
                            <a href="{{ route('proveedores.edit', $supplier->id) }}" class="text-gray-600 hover:underline text-xs">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron proveedores con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 bg-gray-50 border-t">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>

@endsection
