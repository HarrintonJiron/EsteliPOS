@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="page-title">Gestión de Compras</h1>
        <a href="{{ route('compras.create') }}" class="btn-primary">+ Nueva Compra</a>
    </div>

    <!-- Filtros -->
    <div class="card p-4">
        <form method="GET" action="{{ route('compras.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Proveedor</label>
                <select name="supplier_id" class="select-field">
                    <option value="">Todos</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Fecha Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Fecha Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Estado</label>
                <select name="status" class="select-field">
                    <option value="">Todos</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Pagado</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>
            <div class="md:col-span-4 flex justify-end space-x-2">
                <button type="submit" class="btn-primary">Filtrar</button>
                <a href="{{ route('compras.index') }}" class="btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-4 gap-6">
        <div class="card p-5 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Compras del Mes</p>
            <p class="text-2xl font-bold text-indigo-600">C$ 72,000</p>
        </div>
        <div class="card p-5 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Pagadas</p>
            <p class="text-2xl font-bold text-emerald-600">5</p>
        </div>
        <div class="card p-5 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Pendientes</p>
            <p class="text-2xl font-bold text-amber-600">2</p>
        </div>
        <div class="card p-5 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Total Invertido</p>
            <p class="text-2xl font-bold text-violet-600">C$ 185,000</p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Documento</th>
                    <th class="px-6 py-3">Proveedor</th>
                    <th class="px-6 py-3">Fecha</th>
                    <th class="px-6 py-3">Total</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @foreach($purchases as $purchase)
                    <tr>
                        <td class="px-6 py-4">{{ $purchase->document_number ?? 'COMP-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-6 py-4">{{ $purchase->supplier->name ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $purchase->date ? $purchase->date->format('d/m/Y') : '' }}</td>
                        <td class="px-6 py-4">C$ {{ number_format($purchase->total, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="{{ $purchase->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} px-3 py-1 rounded-full text-xs">
                                {{ $purchase->status === 'completed' ? 'Pagado' : 'Pendiente' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('compras.show', $purchase->id) }}" class="text-indigo-600 hover:underline mr-2">Ver</a>
                            <a href="{{ route('compras.edit', $purchase->id) }}" class="text-yellow-600 hover:underline mr-2">Editar</a>
                            <form action="{{ route('compras.destroy', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta compra?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach

            </tbody>
        
        
        
        
    </div>

</div>

@endsection
    
</script>



