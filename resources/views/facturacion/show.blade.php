@extends('layouts.app')

@section('title', 'Factura #' . $sale->id)

@section('content')

<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-700">Factura #{{ $sale->id }}</h2>
            <p class="text-sm text-gray-500">Detalles de la venta</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('facturacion.edit', $sale->id) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar
            </a>
            <a href="{{ route('facturacion.pdf', ['sale_id' => $sale->id]) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                PDF
            </a>
            <a href="{{ route('facturacion.print', ['sale_id' => $sale->id]) }}" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir
            </a>
            @if(auth()->user()?->isAdmin())
            <form action="{{ route('facturacion.destroy', $sale->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta factura?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Eliminar
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Datos principales --}}
    <div class="bg-white p-6 rounded-xl shadow space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div>
                <label class="text-sm text-gray-600">Cliente</label>
                <p class="mt-1 text-gray-900">
                    {{ $sale->billing_name ?? $sale->client->name ?? 'N/A' }}
                    @if($sale->billing_business_name)
                        <span class="block text-xs text-gray-500">{{ $sale->billing_business_name }}</span>
                    @endif
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-600">Fecha</label>
                <p class="mt-1 text-gray-900">{{ $sale->date ? $sale->date->format('d/m/Y') : 'N/A' }}</p>
            </div>

            <div>
                <label class="text-sm text-gray-600">Método de Pago</label>
                <p class="mt-1 text-gray-900">{{ ucfirst($sale->payment_type) }}</p>
            </div>

            <div>
                <label class="text-sm text-gray-600">Estado</label>
                <span class="mt-1 inline-block px-3 py-1 rounded-full text-xs {{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $sale->status === 'completed' ? 'Pagada' : ($sale->status === 'pending' ? 'Pendiente' : 'Anulada') }}
                </span>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="text-sm text-gray-600">No. Factura</label>
                <p class="mt-1 text-gray-900">{{ $sale->invoice_number ?: ('#' . $sale->id) }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">RUC</label>
                <p class="mt-1 text-gray-900">{{ $sale->billing_ruc ?? $sale->client->ruc ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Vence</label>
                <p class="mt-1 text-gray-900">{{ $sale->due_date ? $sale->due_date->format('d/m/Y') : 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Contacto</label>
                <p class="mt-1 text-gray-900">
                    {{ $sale->billing_phone ?? $sale->client->phone ?? 'N/A' }}
                    @if($sale->billing_email || $sale->client->email)
                        <span class="block text-xs text-gray-500">{{ $sale->billing_email ?? $sale->client->email }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-600">Dirección</label>
            <p class="mt-1 text-gray-900">{{ $sale->billing_address ?? $sale->client->address ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Productos --}}
    <div class="bg-white p-6 rounded-xl shadow space-y-4">

        <h3 class="text-lg font-semibold text-gray-700">Detalle de Productos</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Producto</th>
                        <th class="px-6 py-3">Cantidad</th>
                        <th class="px-6 py-3">Precio</th>
                        <th class="px-6 py-3">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($sale->details as $detail)
                        <tr>
                            <td class="px-6 py-4">{{ $detail->product->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $detail->quantity }}</td>
                            <td class="px-6 py-4">C$ {{ number_format($detail->price, 2) }}</td>
                            <td class="px-6 py-4">C$ {{ number_format($detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="px-6 py-3 text-right text-sm">Subtotal</td>
                        <td class="px-6 py-3 font-semibold">C$ {{ number_format($sale->subtotal ?? 0, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="px-6 py-3 text-right text-sm">IVA ({{ number_format(($sale->tax_rate ?? 0.15) * 100, 0) }}%)</td>
                        <td class="px-6 py-3 font-semibold">C$ {{ number_format($sale->tax_total ?? 0, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="px-6 py-4 text-right font-semibold">Total</td>
                        <td class="px-6 py-4 font-semibold">C$ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($sale->notes)
        <div class="bg-white p-6 rounded-xl shadow space-y-2">
            <h3 class="text-lg font-semibold text-gray-700">Observaciones</h3>
            <p class="text-gray-800 whitespace-pre-line">{{ $sale->notes }}</p>
        </div>
    @endif

</div>

@endsection