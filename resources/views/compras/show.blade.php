@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Compra #{{ $purchase->id }}</h1>
            <p class="text-sm text-gray-500">Detalle de compra y entradas a inventario</p>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('compras.edit', $purchase->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">Editar</a>
            <a href="{{ route('compras.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-sm text-gray-500">Proveedor</p>
            <p class="font-semibold">{{ $purchase->supplier->name ?? 'N/A' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Fecha</p>
            <p class="font-semibold">{{ $purchase->date ? $purchase->date->format('d/m/Y') : 'N/A' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Estado</p>
            @php
                $status = $purchase->status;
                $statusLabel = $status === 'completed' ? 'Completada' : ($status === 'pending' ? 'Pendiente' : 'Anulada');
                $statusClass = $status === 'completed' ? 'bg-green-100 text-green-700' : ($status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
            @endphp
            <span class="inline-block px-3 py-1 rounded-full text-xs {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total</p>
            <p class="font-semibold text-blue-700">C$ {{ number_format($purchase->total ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-700">Productos</h2>
            <span class="text-xs text-gray-500">{{ $purchase->details->count() }} ítems</span>
        </div>

        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2">Producto</th>
                    <th class="px-4 py-2">Cantidad</th>
                    <th class="px-4 py-2">Costo</th>
                    <th class="px-4 py-2">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($purchase->details as $detail)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $detail->product->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $detail->quantity }}</td>
                        <td class="px-4 py-2">C$ {{ number_format($detail->price, 2) }}</td>
                        <td class="px-4 py-2">C$ {{ number_format($detail->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50">
                    <td colspan="3" class="px-4 py-3 text-right font-semibold">Total</td>
                    <td class="px-4 py-3 font-semibold">C$ {{ number_format($purchase->total ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
