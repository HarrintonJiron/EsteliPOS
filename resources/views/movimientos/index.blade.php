@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@php
    $productoActual = $productName ?? null;
@endphp


@section('content')

<div class="space-y-4">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-700">
    Movimientos de Inventario
    @if($productoActual)
        – {{ $productoActual }}
    @endif
</h2>

<p class="text-sm text-gray-500">
    @if($productoActual)
        Historial de entradas y salidas del producto seleccionado
    @else
        Registro general de entradas y salidas
    @endif
</p>


        <div class="text-xs text-gray-500">
            Los movimientos se generan automáticamente desde Compras, Facturación y Ajustes.
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="min-w-full text-sm">

            <thead class="bg-slate-800 text-white">
    <tr>
        <th class="px-4 py-2 text-left">Fecha</th>
        <th class="px-4 py-2 text-left">Producto</th>
        <th class="px-4 py-2 text-left">Tipo</th>
        <th class="px-4 py-2 text-left">Cantidad</th>
        <th class="px-4 py-2 text-left">Stock después</th>
        <th class="px-4 py-2 text-left">Referencia</th>
        <th class="px-4 py-2 text-left">Usuario</th>
        <th class="px-4 py-2 text-left">Nota</th>
    </tr>
</thead>
    

            <tbody class="bg-white">

                @foreach($movements as $m)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 font-semibold">{{ $m->product->name ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="{{ $m->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} px-3 py-1 rounded-full text-xs">{{ $m->type === 'in' ? 'Entrada' : 'Salida' }}</span>
                        </td>
                        <td class="px-4 py-2 font-semibold">{{ $m->type === 'in' ? '+' : '-' }}{{ $m->quantity }}</td>
                        <td class="px-4 py-2 font-bold">{{ $m->stock_after ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600">{{ $m->reference }}</td>
                        <td class="px-4 py-2">{{ $m->user->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $m->note ?? '—' }}</td>
                    </tr>
                @endforeach


        </table>

    </div>

</div>

@endsection
