@extends('layouts.app')

@section('title', $supplier->name)

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $supplier->name }}</h1>
                <p class="text-sm text-gray-500">
                    {{ $supplier->code ? '[' . $supplier->code . '] ' : '' }}{{ $supplier->business_name ? '- ' . $supplier->business_name : '' }}
                </p>
            </div>
        </div>

        <div class="flex space-x-2">
            <a href="{{ route('proveedores.edit', $supplier->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">Editar</a>
            <a href="{{ route('proveedores.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
        </div>
    </div>

    {{-- Badges de Estado --}}
    <div class="flex gap-2">
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-{{ $supplier->status === 'active' ? 'green' : 'gray' }}-100 text-{{ $supplier->status === 'active' ? 'green' : 'gray' }}-700">
            {{ $supplier->status_label }}
        </span>
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
            {{ $supplier->payment_condition_label }}
        </span>
    </div>

    {{-- Estadísticas de Crédito y Compras --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Total Compras</p>
            <p class="text-2xl font-bold text-green-700">C$ {{ number_format($supplierStats['total_purchases'], 2) }}</p>
            <p class="text-xs text-gray-400">{{ $supplierStats['completed_orders'] }} órdenes pagadas</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">Compras Pendientes</p>
            <p class="text-2xl font-bold text-yellow-600">C$ {{ number_format($supplierStats['pending_purchases'], 2) }}</p>
            <p class="text-xs text-gray-400">{{ $supplierStats['pending_orders'] }} órdenes por pagar</p>
        </div>

        @if($supplier->credit_limit > 0)
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-600">
                <p class="text-sm text-gray-500">Límite de Crédito</p>
                <p class="text-2xl font-bold text-blue-700">C$ {{ number_format($supplier->credit_limit, 2) }}</p>
                <p class="text-xs {{ $supplierStats['credit_available'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    C$ {{ number_format($supplierStats['credit_available'], 2) }} disponibles
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 {{ $supplierStats['credit_used'] > $supplier->credit_limit ? 'border-red-600' : 'border-purple-600' }}">
                <p class="text-sm text-gray-500">Crédito Utilizado</p>
                <p class="text-2xl font-bold {{ $supplierStats['credit_used'] > $supplier->credit_limit ? 'text-red-700' : 'text-purple-700' }}">
                    C$ {{ number_format($supplierStats['credit_used'], 2) }}
                </p>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                    @php
                        $percentage = min(100, ($supplierStats['credit_used'] / $supplier->credit_limit) * 100);
                        $color = $percentage > 90 ? 'bg-red-600' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-green-600');
                    @endphp
                    <div class="{{ $color }} h-2 rounded-full progress-bar" data-width="{{ $percentage }}"></div>
                </div>
            </div>
        @else
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Condición de Pago</p>
                <p class="text-2xl font-bold text-gray-700">{{ $supplier->payment_condition_label }}</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Promedio por Compra</p>
                <p class="text-2xl font-bold text-gray-700">C$ {{ number_format($supplierStats['average_purchase'], 2) }}</p>
            </div>
        @endif
    </div>

    {{-- Información de Contacto --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Información de Contacto</h2>

            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Contacto Principal</span>
                    <span class="font-medium">{{ $supplier->contact_name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Teléfono</span>
                    <span class="font-medium">{{ $supplier->phone ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Email</span>
                    <span class="font-medium">{{ $supplier->email ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Ciudad</span>
                    <span class="font-medium">{{ $supplier->city ?? '—' }}</span>
                </div>
                <div class="py-2">
                    <span class="text-sm text-gray-500">Dirección</span>
                    <p class="font-medium mt-1">{{ $supplier->address ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Información Fiscal</h2>

            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">RUC</span>
                    <span class="font-medium">{{ $supplier->ruc ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Razón Social</span>
                    <span class="font-medium">{{ $supplier->business_name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Tipo</span>
                    <span class="font-medium">{{ $supplier->type ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Última Compra</span>
                    <span class="font-medium">
                        {{ $supplierStats['last_purchase'] ? $supplierStats['last_purchase']->date->format('d/m/Y') : 'Sin compras' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de Compras --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 flex items-center justify-between border-b">
            <h2 class="text-lg font-semibold text-gray-700">Historial de Compras</h2>
            <div class="flex gap-2">
                <span class="text-sm text-gray-500">{{ $supplierStats['total_orders'] }} total</span>
                <a href="{{ route('compras.create', ['supplier_id' => $supplier->id]) }}" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">+ Nueva Compra</a>
            </div>
        </div>

        @if($supplier->purchases->count() > 0)
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Compra</th>
                        <th class="px-4 py-2 text-left">Productos</th>
                        <th class="px-4 py-2 text-right">Total</th>
                        <th class="px-4 py-2 text-center">Estado</th>
                        <th class="px-4 py-2 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($supplier->purchases as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $p->date ? $p->date->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-2">
                                <a class="text-blue-600 hover:underline font-medium" href="{{ route('compras.show', $p->id) }}">
                                    #{{ str_pad($p->id, 4, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $p->details->count() }} items</td>
                            <td class="px-4 py-2 text-right font-medium">C$ {{ number_format($p->total ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-1 rounded-full text-xs {{ $p->status === 'completed' ? 'bg-green-100 text-green-700' : ($p->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $p->status === 'completed' ? 'Pagado' : ($p->status === 'pending' ? 'Pendiente' : 'Cancelado') }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('compras.show', $p->id) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center text-gray-500">
                <p class="mb-4">No hay compras registradas con este proveedor.</p>
                <a href="{{ route('compras.create', ['supplier_id' => $supplier->id]) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    Registrar primera compra
                </a>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.progress-bar').forEach(function(bar) {
        const width = bar.getAttribute('data-width');
        if (width) {
            bar.style.width = width + '%';
        }
    });
});
</script>

@endsection
