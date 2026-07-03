@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Gestión de Clientes
        </h1>

        <button onclick="document.getElementById('modalCliente').classList.remove('hidden')"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            + Nuevo Cliente
        </button>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-4 gap-6">

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Total Clientes</p>
            <p class="text-2xl font-bold text-blue-700">35</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Activos</p>
            <p class="text-2xl font-bold text-green-700">30</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">Con Crédito</p>
            <p class="text-2xl font-bold text-yellow-600">12</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Morosos</p>
            <p class="text-2xl font-bold text-red-700">3</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Tipo</th>
                    <th class="px-6 py-3">Teléfono</th>
                    <th class="px-6 py-3">Límite Crédito</th>
                    <th class="px-6 py-3">Saldo</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @foreach($clients as $client)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $client->name }}</td>
                        <td class="px-6 py-4">{{ $client->type ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $client->phone }}</td>
                        <td class="px-6 py-4">C$ {{ number_format($client->credit_limit ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-red-600">C$ {{ number_format($client->balance ?? 0, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="{{ $client->is_active ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} px-3 py-1 rounded-full text-xs">
                                {{ $client->is_active ? 'Al Día' : 'Crédito Activo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 space-x-3">
                            <a href="{{ route('clientes.show', $client->id) }}" class="text-blue-600 hover:underline">Ver</a>
                            <a href="{{ route('clientes.edit', $client->id) }}" class="text-yellow-600 hover:underline">Editar</a>
                            <form action="{{ route('clientes.destroy', $client->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('¿Está seguro de eliminar este cliente?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>

</div>

<!-- Modal Nuevo Cliente -->
<div id="modalCliente"
     class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">

    <div class="bg-white rounded-xl shadow-xl w-[600px] p-6 space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Nuevo Cliente</h2>
            <button onclick="cerrarModalCliente()" class="text-gray-500">✕</button>
        </div>

            <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 gap-4">

                <input type="text" name="name" placeholder="Nombre"
                    class="border rounded-lg px-3 py-2 w-full" required value="{{ old('name') }}">

                <select name="type" class="border rounded-lg px-3 py-2 w-full">
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                </select>

                <input type="text" name="phone" placeholder="Teléfono"
                    class="border rounded-lg px-3 py-2 w-full" value="{{ old('phone') }}">

                <input type="number" name="credit_limit" placeholder="Límite de Crédito"
                    class="border rounded-lg px-3 py-2 w-full" value="{{ old('credit_limit') }}">

                <input type="text" name="address" placeholder="Dirección"
                    class="border rounded-lg px-3 py-2 w-full col-span-2" value="{{ old('address') }}">

            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">

                <button type="button" onclick="cerrarModalCliente()"
                    class="px-4 py-2 bg-gray-300 rounded-lg">
                    Cancelar
                </button>

                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg">
                    Guardar
                </button>

            </div>
            </form>

    </div>
</div>

@endsection
