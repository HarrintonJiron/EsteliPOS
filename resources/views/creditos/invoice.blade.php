
@extends('layouts.app')

@section('title', 'Recibo de Abono - ' . ($payment->client->name ?? 'Cliente'))

@section('content')

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold">Recibo de Abono</h2>
                <p class="text-sm text-gray-600">Fecha: {{ $payment->payment_date->format('d/m/Y H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm">Recibo #: {{ $payment->id }}</p>
                <p class="text-sm">Usuario: {{ $payment->user?->name ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="mb-4">
            <h3 class="font-semibold">Cliente</h3>
            <p>{{ $client->name }} @if($client->phone) - {{ $client->phone }} @endif</p>
            <p class="text-sm text-gray-600">{{ $client->address ?? '' }}</p>
        </div>

        <div class="mb-4">
            <h3 class="font-semibold">Detalle del Abono</h3>
            <table class="w-full text-sm">
                <tr>
                    <td class="py-2">Monto</td>
                    <td class="text-right font-bold">C$ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="py-2">Tipo</td>
                    <td class="text-right">{{ ucfirst($payment->payment_type) }}</td>
                </tr>
                <tr>
                    <td class="py-2">Referencia</td>
                    <td class="text-right">{{ $payment->reference_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="py-2">Notas</td>
                    <td class="text-right">{{ $payment->notes ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="mb-4">
            <h3 class="font-semibold">Ventas Pendientes</h3>
            @if($pendingSales->count() > 0)
                <table class="w-full text-sm border-t border-gray-200">
                    <thead class="text-left text-xs text-gray-600">
                        <tr>
                            <th class="py-2">Factura</th>
                            <th class="py-2">Fecha</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingSales as $s)
                            <tr class="border-b">
                                <td class="py-2">#{{ str_pad($s->invoice_number, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="py-2">{{ $s->date?->format('d/m/Y') }}</td>
                                <td class="py-2 text-right">C$ {{ number_format($s->total, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 pb-3 text-xs text-gray-600">
                                    Productos:
                                    <ul class="list-disc ml-5">
                                        @foreach($s->details as $d)
                                            <li>{{ $d->product?->name ?? 'N/A' }} — {{ $d->quantity }} x C$ {{ number_format($d->price, 2) }} = C$ {{ number_format($d->subtotal, 2) }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-600">No hay ventas pendientes para este cliente.</p>
            @endif
        </div>

        <div class="mt-6 flex justify-between items-center">
            <a href="{{ route('creditos.show', $client->id) }}" class="text-sm text-gray-600">← Volver</a>
            <button onclick="window.print()" class="bg-blue-700 text-white px-4 py-2 rounded">Imprimir</button>
        </div>
    </div>
</div>

@endsection
