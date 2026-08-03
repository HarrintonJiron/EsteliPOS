@extends('layouts.app')

@section('title', 'Cliente - ' . $client->legal_name)
@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-start gap-4">
            <h1 class="page-title">{{ $client->legal_name }}</h1>
            <p class="page-subtitle">Ficha tributaria y crediticia del cliente</p>
        </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="card p-4"><p class="text-xs text-slate-500">Tipo</p><p class="text-lg font-bold text-slate-700">{{ $client->isCompany() ? 'Empresa' : 'Persona Natural' }}</p></div>
        <div class="card p-4"><p class="text-xs text-slate-500">{{ $client->document_label }}</p><p class="text-lg font-bold text-slate-700">{{ $client->document_number ?? '—' }}</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500">Saldo pendiente</p><p class="text-lg font-bold {{ ($creditSummary['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($creditSummary['balance'] ?? 0, 2) }}</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500">Límite</p><p class="text-lg font-bold text-indigo-600">{{ $client->credit_enabled ? ((float) $client->credit_limit > 0 ? 'C$ '.number_format($client->credit_limit, 2) : 'Ilimitado') : 'Contado' }}</p></div>
    <div class="card p-4"><p class="text-xs text-slate-500">Estado</p><p class="text-lg font-bold {{ ($client->status ?? 'active') === 'active' ? 'text-emerald-600' : 'text-slate-500' }}">{{ ($client->status ?? 'active') === 'active' ? 'Activo' : 'Inactivo' }}</p></div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card p-6 space-y-4">
            <h2 class="font-semibold text-slate-800">Datos generales</h2>
                <div><p class="text-slate-500">Nombre / Razón social</p><p class="font-semibold text-slate-800">{{ $client->legal_name }}</p></div>
                <div><p class="text-slate-500">Tipo de contribuyente</p><p class="font-semibold text-slate-800">{{ $client->taxpayer_type ?? '—' }}</p></div>
                <div><p class="text-slate-500">Teléfono</p><p class="font-semibold text-slate-800">{{ $client->phone ?? '—' }}</p></div>
                <div><p class="text-slate-500">Correo</p><p class="font-semibold text-slate-800">{{ $client->email ?? '—' }}</p></div>
                <div><p class="text-slate-500">Departamento / Municipio</p><p class="font-semibold text-slate-800">{{ $client->formatted_location ?: '—' }}</p></div>
                <div><p class="text-slate-500">Código</p><p class="font-semibold text-slate-800">{{ $client->code ?? '—' }}</p></div>
                <div class="md:col-span-2"><p class="text-slate-500">Dirección</p><p class="font-semibold text-slate-800">{{ $client->address ?? '—' }}</p></div>
            </div>
        </div>
        <div class="card p-6 space-y-4">
            <h2 class="font-semibold text-slate-800">Crédito</h2>
            <div class="text-sm space-y-2">
                <p><span class="text-slate-500">Crédito habilitado:</span> <strong>{{ $client->credit_enabled ? 'Sí' : 'No' }}</strong></p>
                <p><span class="text-slate-500">Plazo:</span> <strong>{{ $creditSummary['credit_days'] ?? 30 }} días</strong></p>
                <p><span class="text-slate-500">Disponible:</span> <strong>@if(($creditSummary['available_credit'] ?? 0) === null) Ilimitado @else C$ {{ number_format($creditSummary['available_credit'] ?? 0, 2) }} @endif</strong></p>
                <p><span class="text-slate-500">Mora acumulada:</span> <strong>C$ {{ number_format($creditSummary['mora'] ?? 0, 2) }}</strong></p>
            </div>
        </div>
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="font-semibold text-slate-800">Historial reciente de facturas</h2>
            @if($client->credit_enabled)
                <a href="{{ route('creditos.show', $client->id) }}" class="btn-outline text-sm">Ver módulo de créditos</a>
            @endif
        <table class="w-full table-agro">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th class="text-right">Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($client->sales as $sale)
                    <tr>
                        <td><a href="{{ route('facturacion.show', $sale->id) }}" class="text-indigo-600 hover:underline">{{ $sale->invoice_number ?: ('#' . $sale->id) }}</a></td>
                        <td>{{ $sale->date?->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($sale->payment_type) }}</td>
                        <td class="text-right font-semibold">C$ {{ number_format($sale->total, 2) }}</td>
                        <td>{{ $sale->status === 'completed' ? 'Pagada' : 'Pendiente' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-8 text-slate-500">Sin facturas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
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
