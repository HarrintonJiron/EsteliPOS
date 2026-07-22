@extends('layouts.app')

@section('title', 'Proforma ' . $proforma->proforma_number)

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('proformas.index') }}" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900">{{ $proforma->proforma_number }}</h1>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $proforma->statusColor() }}">
                    {{ $proforma->statusLabel() }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1 ml-8">Creada el {{ $proforma->date->format('d/m/Y') }}
                @if($proforma->expiry_date)
                    · Vence: <span class="{{ $proforma->expiry_date->isPast() ? 'text-red-600 font-semibold' : 'text-slate-600' }}">{{ $proforma->expiry_date->format('d/m/Y') }}</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('proformas.ticket', $proforma->id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Ticket
            </a>
            <a href="{{ route('proformas.pdf', $proforma->id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- Main content --}}
        <div class="col-span-2 space-y-4">

            {{-- Client info --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-500 uppercase mb-3">Cliente</h2>
                <p class="font-bold text-slate-900">{{ $proforma->client_name ?? 'Cliente General' }}</p>
                @if($proforma->client_phone)
                    <p class="text-sm text-slate-600">{{ $proforma->client_phone }}</p>
                @endif
                @if($proforma->client_email)
                    <p class="text-sm text-slate-600">{{ $proforma->client_email }}</p>
                @endif
                @if($proforma->client_address)
                    <p class="text-sm text-slate-600">{{ $proforma->client_address }}</p>
                @endif
            </div>

            {{-- Items table --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-700">Productos / Servicios</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-5 py-2.5 text-slate-500 font-medium">Descripción</th>
                            <th class="text-right px-4 py-2.5 text-slate-500 font-medium">Cant.</th>
                            <th class="text-right px-4 py-2.5 text-slate-500 font-medium">P. Unit.</th>
                            <th class="text-right px-4 py-2.5 text-slate-500 font-medium">Dto.</th>
                            <th class="text-right px-5 py-2.5 text-slate-500 font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($proforma->details as $detail)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $detail->product_name }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($detail->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">C$ {{ number_format($detail->price, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $detail->discount > 0 ? $detail->discount . '%' : '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">C$ {{ number_format($detail->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="4" class="px-5 py-2 text-right text-slate-600 text-sm">Subtotal</td>
                            <td class="px-5 py-2 text-right font-medium text-slate-800">C$ {{ number_format($proforma->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-5 py-2 text-right text-slate-600 text-sm">IVA (15%)</td>
                            <td class="px-5 py-2 text-right font-medium text-slate-800">C$ {{ number_format($proforma->tax_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-5 py-3 text-right font-bold text-slate-900">TOTAL</td>
                            <td class="px-5 py-3 text-right font-bold text-xl text-indigo-700">C$ {{ number_format($proforma->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($proforma->notes)
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-500 uppercase mb-2">Notas y Condiciones</h2>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ $proforma->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">

            {{-- Status update --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Actualizar Estado</h2>
                <form action="{{ route('proformas.status', $proforma->id) }}" method="POST" class="space-y-3">
                    @csrf @method('PATCH')
                    <select name="status" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                        @foreach(['draft' => 'Borrador', 'sent' => 'Enviada', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada', 'expired' => 'Expirada'] as $val => $label)
                            <option value="{{ $val }}" {{ $proforma->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold py-2 rounded-xl">
                        Guardar Estado
                    </button>
                </form>
            </div>

            {{-- Convert to sale --}}
            @if(in_array($proforma->status, ['draft', 'sent', 'accepted']))
            <div class="bg-white rounded-xl border border-emerald-200 p-5">
                <h2 class="text-sm font-semibold text-emerald-800 mb-1">Convertir a Factura</h2>
                <p class="text-xs text-slate-500 mb-3">Genera una venta real a partir de esta proforma y descuenta el stock.</p>
                <form action="{{ route('proformas.convert', $proforma->id) }}" method="POST"
                      onsubmit="return confirm('¿Convertir esta proforma en una factura de venta?')">
                    @csrf
                    <select name="payment_type" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 mb-3">
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="transfer">Transferencia</option>
                        <option value="credit">Crédito</option>
                    </select>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold py-2.5 rounded-xl">
                        Generar Factura
                    </button>
                </form>
            </div>
            @endif

            {{-- Summary card --}}
            <div class="bg-indigo-50 rounded-xl border border-indigo-100 p-5">
                <h2 class="text-sm font-semibold text-indigo-800 mb-3">Resumen</h2>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span>C$ {{ number_format($proforma->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>IVA (15%)</span>
                        <span>C$ {{ number_format($proforma->tax_total, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-900 border-t border-indigo-200 pt-2 mt-1">
                        <span>Total</span>
                        <span class="text-indigo-700 text-lg">C$ {{ number_format($proforma->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Delete --}}
            <form action="{{ route('proformas.destroy', $proforma->id) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar esta proforma de forma permanente?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium py-2.5 rounded-xl border border-red-200">
                    Eliminar Proforma
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
