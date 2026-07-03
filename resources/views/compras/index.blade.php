@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Gestión de Compras
        </h1>

        <a href="{{ route('compras.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">+ Nueva Compra</a>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('compras.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                <select name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Pagado</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>
            <div class="md:col-span-4 flex justify-end space-x-2">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('compras.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-4 gap-6">

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Compras del Mes</p>
            <p class="text-2xl font-bold text-blue-700">C$ 72,000</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Pagadas</p>
            <p class="text-2xl font-bold text-green-700">5</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">Pendientes</p>
            <p class="text-2xl font-bold text-yellow-600">2</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Total Invertido</p>
            <p class="text-2xl font-bold text-purple-700">C$ 185,000</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

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



