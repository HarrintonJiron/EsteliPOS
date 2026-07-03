@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <h1 class="text-2xl font-bold text-gray-800">
        Ficha del Cliente
    </h1>

    <div class="bg-white rounded-xl shadow p-6 grid grid-cols-3 gap-6">

        <div>
            <p class="text-sm text-gray-500">Nombre</p>
            <p class="font-semibold">Juan Pérez</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Tipo</p>
            <p class="font-semibold">Crédito</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Teléfono</p>
            <p class="font-semibold">8888-1111</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Límite de Crédito</p>
            <p class="font-semibold">C$ 20,000</p>
        </div>

        <div>
            <p class="text-sm text-gray-500">Saldo Pendiente</p>
            <p class="font-semibold text-red-600">C$ 5,000</p>
        </div>

    </div>

    <!-- Historial -->
    <div class="bg-white rounded-xl shadow p-6">

        <h2 class="text-lg font-semibold mb-4">
            Historial de Facturas
        </h2>

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Factura</th>
                    <th class="px-4 py-2">Fecha</th>
                    <th class="px-4 py-2">Total</th>
                    <th class="px-4 py-2">Estado</th>
                </tr>
            </thead>

            <tbody>
                <tr class="border-t">
                    <td class="px-4 py-2">FAC-0045</td>
                    <td class="px-4 py-2">16/02/2026</td>
                    <td class="px-4 py-2">C$ 3,500</td>
                    <td class="px-4 py-2 text-red-600">Pendiente</td>
                </tr>

                <tr class="border-t">
                    <td class="px-4 py-2">FAC-0043</td>
                    <td class="px-4 py-2">10/02/2026</td>
                    <td class="px-4 py-2">C$ 1,800</td>
                    <td class="px-4 py-2 text-green-600">Pagado</td>
                </tr>
            </tbody>
        </table>

    </div>

</div>

@endsection
