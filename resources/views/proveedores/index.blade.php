@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Proveedores</h1>
            <p class="page-subtitle">Catálogo y control de compras</p>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('proveedores.export') }}" class="btn-outline text-sm">
                📥 Exportar
            </a>
            <a href="{{ route('proveedores.create') }}" class="btn-primary">
                + Nuevo Proveedor
            </a>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Total Proveedores</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['total_suppliers'] }}</p>
        </div>

        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Activos</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['active_suppliers'] }}</p>
        </div>

        <div class="card p-4 border-l-4 border-slate-500">
            <p class="text-xs text-slate-500">Inactivos</p>
            <p class="text-2xl font-bold text-slate-600">{{ $stats['inactive_suppliers'] }}</p>
        </div>

        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Con Crédito</p>
            <p class="text-2xl font-bold text-violet-600">{{ $stats['suppliers_with_credit'] }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('proveedores.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Búsqueda</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, RUC, código, contacto..."
                       class="input-field">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Estado</label>
                <select name="status" class="select-field">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Condición de Pago</label>
                <select name="payment_condition" class="select-field">
                    <option value="">Todas</option>
                    <option value="contado" {{ request('payment_condition') == 'contado' ? 'selected' : '' }}>Contado</option>
                    <option value="credito_15" {{ request('payment_condition') == 'credito_15' ? 'selected' : '' }}>Crédito 15 días</option>
                    <option value="credito_30" {{ request('payment_condition') == 'credito_30' ? 'selected' : '' }}>Crédito 30 días</option>
                    <option value="credito_60" {{ request('payment_condition') == 'credito_60' ? 'selected' : '' }}>Crédito 60 días</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Ciudad</label>
                <input type="text" name="city" value="{{ request('city') }}" placeholder="Filtrar por ciudad"
                       class="input-field">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="btn-primary">Filtrar</button>
                <a href="{{ route('proveedores.index') }}" class="btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>
                        <a href="{{ route('proveedores.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                            Proveedor
                            @if(request('sort_by') == 'name')
                                <span class="ml-1">{{ request('sort_order') == 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Ciudad</th>
                    <th>Condición</th>
                    <th class="text-center">Compras</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-slate-50">
                        <td>
                            <div class="font-medium">
                                {{ $supplier->code ? '[' . $supplier->code . '] ' : '' }}{{ $supplier->name }}
                            </div>
                            @if($supplier->business_name)
                                <div class="text-xs text-slate-500">{{ $supplier->business_name }}</div>
                            @endif
                            @if($supplier->type)
                                <div class="text-xs text-slate-400">{{ $supplier->type }}</div>
                            @endif
                        </td>
                        <td>{{ $supplier->ruc ?? '—' }}</td>
                        <td>
                            <div class="font-medium">{{ $supplier->contact_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $supplier->phone ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $supplier->email ?? '' }}</div>
                        </td>
                        <td>{{ $supplier->city ?? '—' }}</td>
                        <td>
                            <div class="text-sm">{{ $supplier->payment_condition_label }}</div>
                            @if(auth()->user()?->isAdmin() && $supplier->credit_limit > 0)
                                <div class="text-xs text-slate-500">
                                    Límite: C$ {{ number_format($supplier->credit_limit, 0) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge-info">{{ $supplier->purchases_count }}</span>
                        </td>
                        <td>
                            <span class="badge-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $supplier->status_label }}
                            </span>
                        </td>
                        <td class="text-center space-x-1">
                            <a href="{{ route('proveedores.show', $supplier->id) }}" class="text-indigo-600 text-sm font-medium">Ver</a>
                            @if(auth()->user()?->isAdmin())
                            <a href="{{ route('proveedores.edit', $supplier->id) }}" class="text-slate-500 text-sm">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            No se encontraron proveedores con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-slate-100">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>

@endsection
