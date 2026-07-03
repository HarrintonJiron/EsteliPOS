@extends('layouts.app')

@section('title', 'Facturación')

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-700">Gestión de Facturas</h2>
            <p class="text-sm text-gray-500">Administra las ventas realizadas</p>
        </div>

        <a href="{{ route('facturacion.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
            + Nueva Factura
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('facturacion.index') }}" class="flex flex-wrap gap-4 items-center">
            <input type="text" name="search" placeholder="Buscar por cliente o factura..." value="{{ request('search') }}"
                   class="border rounded-lg px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-green-500">

            <input type="date" name="date" value="{{ request('date') }}"
                   class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">

            <select name="status" class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">Todos los estados</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Pagada</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
            </select>

            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Filtrar</button>
            <a href="{{ route('facturacion.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Limpiar</a>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="min-w-full text-sm">

            <thead class="bg-green-700 text-white">
                <tr>
                    <th class="px-6 py-3 text-left"># Factura</th>
                    <th class="px-6 py-3 text-left">Cliente</th>
                    <th class="px-6 py-3 text-left">Fecha</th>
                    <th class="px-6 py-3 text-left">Método</th>
                    <th class="px-6 py-3 text-left">Total</th>
                    <th class="px-6 py-3 text-left">Estado</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="bg-white">
                @foreach($sales as $sale)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-3 font-semibold">{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-6 py-3">{{ $sale->client->name ?? 'N/A' }}</td>
                    <td class="px-6 py-3">{{ $sale->date ? $sale->date->format('d/m/Y') : 'N/A' }}</td>
                    <td class="px-6 py-3">{{ ucfirst($sale->payment_type) }}</td>
                    <td class="px-6 py-3 font-semibold text-green-700">C$ {{ number_format($sale->total, 2) }}</td>
                    <td class="px-6 py-3">
                        <span class="{{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} px-3 py-1 rounded-full text-xs">
                            {{ $sale->status === 'completed' ? 'Pagada' : 'Pendiente' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-center space-x-2">
                        <a href="{{ route('facturacion.show', $sale->id) }}" class="text-blue-600 hover:underline">Ver</a>
                        <a href="{{ route('facturacion.edit', $sale->id) }}" class="text-yellow-600 hover:underline">Editar</a>
                        <a href="{{ route('facturacion.print', ['sale_id' => $sale->id]) }}" target="_blank" class="text-gray-600 hover:underline">PDF</a>
                        @if(auth()->user()?->isAdmin())
                        <form action="{{ route('facturacion.destroy', $sale->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta factura?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>

    </div>

    {{ $sales->links() }}

</div>

@endsection
