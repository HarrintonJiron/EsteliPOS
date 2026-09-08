
@extends('layouts.app')

@section('title', 'Dashboard Facturación')

@section('content')

<div class="space-y-6">

    {{-- Título --}}
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-700">Dashboard de Facturación</h1>
        <span class="text-sm text-gray-500">Fecha: {{ date('d/m/Y') }}</span>
    </div>

    {{-- Tarjetas Resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <div class="bg-indigo-600 text-white p-5 rounded-xl shadow">
            <h4 class="text-sm">Ventas del Día</h4>
            <p class="text-2xl font-bold mt-2">C$ 12,450</p>
        </div>

        <div class="bg-indigo-600 text-white p-5 rounded-xl shadow">
            <h4 class="text-sm">Ventas del Mes</h4>
            <p class="text-2xl font-bold mt-2">C$ 285,300</p>
        </div>

        <div class="bg-yellow-500 text-white p-5 rounded-xl shadow">
            <h4 class="text-sm">Facturas Pendientes</h4>
            <p class="text-2xl font-bold mt-2">8</p>
        </div>

        <div class="bg-gray-700 text-white p-5 rounded-xl shadow">
            <h4 class="text-sm">Clientes Activos</h4>
            <p class="text-2xl font-bold mt-2">124</p>
        </div>

    </div>

    {{-- Gráfico (Visual simulado) --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Ventas Mensuales</h3>

        <div class="h-64 flex items-end space-x-4">

            <div class="bg-green-500 w-10 h-32 rounded"></div>
            <div class="bg-green-500 w-10 h-40 rounded"></div>
            <div class="bg-green-500 w-10 h-52 rounded"></div>
            <div class="bg-green-500 w-10 h-36 rounded"></div>
            <div class="bg-green-500 w-10 h-60 rounded"></div>
            <div class="bg-green-500 w-10 h-48 rounded"></div>

        </div>

        <div class="flex justify-between text-xs text-gray-500 mt-2">
            <span>Ene</span>
            <span>Feb</span>
            <span>Mar</span>
            <span>Abr</span>
            <span>May</span>
            <span>Jun</span>
        </div>
    </div>

    {{-- Últimas Facturas --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Últimas Facturas</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left"># Factura</th>
                        <th class="px-4 py-2 text-left">Cliente</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Total</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <tr class="border-t">
                        <td class="px-4 py-2">000145</td>
                        <td class="px-4 py-2">Cooperativa San José</td>
                        <td class="px-4 py-2">15/02/2026</td>
                        <td class="px-4 py-2">C$ 3,450</td>
                        <td class="px-4 py-2">
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                                Pagada
                            </span>
                        </td>
                    </tr>

                    <tr class="border-t">
                        <td class="px-4 py-2">000146</td>
                        <td class="px-4 py-2">Productor Carlos Méndez</td>
                        <td class="px-4 py-2">16/02/2026</td>
                        <td class="px-4 py-2">C$ 5,780</td>
                        <td class="px-4 py-2">
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
                                Pendiente
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
