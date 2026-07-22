@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-lg font-bold">Arqueo #{{ $arqueo->id ?? '—' }}</h2>
            <div class="text-sm text-slate-500">Fecha: {{ $date->format('d/m/Y') }} · Sesión: {{ optional($arqueo->caja_session_id) ? 'Sí' : 'Manual' }}</div>
        </div>
        <div class="space-x-2">
            <button onclick="window.print()" class="btn-outline">Imprimir</button>
            <a href="{{ route('arqueo.index') }}" class="btn-primary">Nuevo</a>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3 text-sm">
        <div class="p-3 bg-white rounded shadow">
            <div class="text-xs text-slate-500">Ventas</div>
            <div class="text-lg font-semibold">{{ number_format($totalSalesAmount, 2) }}</div>
            <div class="text-xs text-slate-400">{{ $totalSalesCount }} tickets</div>
        </div>
        <div class="p-3 bg-white rounded shadow">
            <div class="text-xs text-slate-500">Abonos</div>
            <div class="text-lg font-semibold">{{ number_format($creditPaymentsTotal, 2) }}</div>
            <div class="text-xs text-slate-400">{{ $creditPayments->count() }} registros</div>
        </div>
        <div class="p-3 bg-white rounded shadow">
            <div class="text-xs text-slate-500">Efectivo (sistema)</div>
            <div class="text-lg font-semibold">{{ number_format(($byType['cash']['total'] ?? 0), 2) }}</div>
            <div class="text-xs text-slate-400">Ventas en efectivo</div>
        </div>
    </div>

    <div class="mb-3">
        <div class="p-3 bg-white rounded shadow text-sm">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs text-slate-500">Conteo físico</div>
                    <div class="text-lg font-semibold">{{ number_format($physicalTotal ?? 0,2) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-500">Diferencia</div>
                    <div class="text-lg font-semibold {{ (($physicalTotal ?? 0) - ($byType['cash']['total'] ?? 0)) < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format(($physicalTotal ?? 0) - ($byType['cash']['total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-3 mb-4 text-sm">
        <h3 class="font-semibold mb-2">Desglose por tipo de pago</h3>
        <table class="w-full">
            <thead>
                <tr class="text-left text-slate-600 text-xs">
                    <th>Tipo</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byType as $type => $row)
                    <tr>
                        <td class="py-1">{{ $type }}</td>
                        <td class="py-1 text-right">{{ $row['count'] }}</td>
                        <td class="py-1 text-right">{{ number_format($row['total'],2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded shadow p-4 mb-6">
        <h3 class="font-semibold mb-2">Ventas del día (detallado)</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Items</th>
                    <th class="text-right">Total</th>
                    <th>Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $s)
                <tr class="border-t">
                    <td class="py-2">{{ $s->invoice_number }}</td>
                    <td class="py-2">{{ optional($s->client)->billing_business_name ?? optional($s->client)->billing_name ?? 'Consumidor' }}</td>
                    <td class="py-2">{{ $s->details->count() }}</td>
                    <td class="py-2 text-right">{{ number_format($s->total,2) }}</td>
                    <td class="py-2">{{ $s->payment_type }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h3 class="font-semibold mb-2">Abonos registrados hoy</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-600">
                    <th>Id</th>
                    <th>Cliente</th>
                    <th class="text-right">Importe</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditPayments as $p)
                <tr class="border-t">
                    <td class="py-2">{{ $p->id }}</td>
                    <td class="py-2">{{ optional($p->client)->billing_business_name ?? optional($p->client)->billing_name ?? 'Cliente' }}</td>
                    <td class="py-2 text-right">{{ number_format($p->amount,2) }}</td>
                    <td class="py-2">{{ optional($p->user)->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
