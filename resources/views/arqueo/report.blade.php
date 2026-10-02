@extends('layouts.app')

@section('content')
@php
    $difference = (float) (($physicalTotal ?? 0) - ($expectedCashTotal ?? 0));
    $currencySymbol = ($arqueo->currency ?? 'NIO') === 'USD' ? 'US$' : 'C$';
@endphp
<div class="p-3 sm:p-4">
    <div class="mx-auto w-full max-w-4xl space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold">Arqueo #{{ $arqueo->id ?? '—' }}</h2>
                <p class="text-xs text-slate-500">{{ $date->format('d/m/Y') }} · {{ $arqueo->branch?->name ?: data_get($arqueo->details, 'session.branch') ?: 'Sin sucursal' }} · {{ $arqueo->user?->name ?: data_get($arqueo->details, 'session.cashier') ?: 'Cajero' }}</p>
                <p class="text-[10px] text-slate-400">Apertura {{ ($openedAt = data_get($arqueo->details, 'session.opened_at')) ? \Illuminate\Support\Carbon::parse($openedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—' }} · Cierre {{ $arqueo->closed_at?->format('d/m/Y H:i:s') ?? (($closedAt = data_get($arqueo->details, 'session.closed_at')) ? \Illuminate\Support\Carbon::parse($closedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—') }} · Integridad {{ substr((string) $arqueo->snapshot_hash, 0, 12) }}</p>
            </div>
            <div class="no-print flex flex-wrap gap-2">
                <a href="{{ route('arqueo.pdf', $arqueo) }}" class="btn-outline text-sm">Imprimir / PDF</a>
                <a href="{{ route('arqueo.history') }}" class="btn-outline text-sm">Historial</a>
                <a href="{{ route('facturacion.pos') }}" class="btn-outline text-sm">POS</a>
                <a href="{{ route('arqueo.index') }}" class="btn-primary text-sm">Caja</a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Fondo</div>
                <div class="mt-1 font-bold tabular-nums">{{ $currencySymbol }} {{ number_format($openingAmount ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Ventas</div>
                <div class="mt-1 font-bold tabular-nums">{{ $currencySymbol }} {{ number_format($totalSalesAmount, 2) }}</div>
                <div class="text-[11px] text-slate-400">{{ $totalSalesCount }} tickets</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Abonos</div>
                <div class="mt-1 font-bold tabular-nums">{{ $currencySymbol }} {{ number_format($creditPaymentsTotal, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Taller</div>
                <div class="mt-1 font-bold tabular-nums">{{ $currencySymbol }} {{ number_format($repairPaymentsTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Gastos</div>
                <div class="mt-1 font-bold tabular-nums text-red-600">{{ $currencySymbol }} {{ number_format($operationalExpensesCashTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-lg bg-red-50 p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Compras en efectivo</div>
                <div class="mt-1 font-bold tabular-nums text-red-600">{{ $currencySymbol }} {{ number_format($cashPurchasesTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Esperado</div>
                <div class="mt-1 font-bold tabular-nums text-indigo-700">{{ $currencySymbol }} {{ number_format($expectedCashTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Diferencia</div>
                <div class="mt-1 font-bold tabular-nums {{ $difference < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $currencySymbol }} {{ number_format($difference, 2) }}</div>
                <div class="text-[11px] text-slate-400">Contado {{ $currencySymbol }} {{ number_format($physicalTotal ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
                <h3 class="mb-2 font-semibold">Pagos</h3>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-slate-500">
                            <th class="pb-1">Tipo</th>
                            <th class="pb-1 text-right">Cant.</th>
                            <th class="pb-1 text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byType as $type => $row)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $type }}</td>
                                <td class="py-1.5 text-right">{{ $row['count'] }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-center text-slate-400">Sin ventas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
                <h3 class="mb-2 font-semibold">Gastos operativos</h3>
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-slate-500">
                            <th class="pb-1">Descripción</th>
                            <th class="pb-1 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operationalExpenses as $expense)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $expense->description }}</td>
                                <td class="py-1.5 text-right tabular-nums text-red-600">{{ number_format($expense->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-3 text-center text-slate-400">Sin gastos en efectivo</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <details class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
            <summary class="cursor-pointer font-semibold">Ventas del día ({{ $totalSalesCount }})</summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[480px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-slate-500">
                            <th class="pb-1">Factura</th>
                            <th class="pb-1">Cliente</th>
                            <th class="pb-1 text-right">Total</th>
                            <th class="pb-1">Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $s)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $s->invoice_number }}</td>
                                <td class="py-1.5">{{ is_string($s->client ?? null) ? $s->client : (optional($s->client)->billing_business_name ?? optional($s->client)->billing_name ?? 'Consumidor') }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ number_format($s->total, 2) }}</td>
                                <td class="py-1.5">{{ $s->payment_type }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        <details class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
            <summary class="cursor-pointer font-semibold">Cobros de taller ({{ $repairPayments->count() }})</summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[420px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-slate-500">
                            <th class="pb-1">Orden</th>
                            <th class="pb-1">Cliente</th>
                            <th class="pb-1 text-right">Importe</th>
                            <th class="pb-1">Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($repairPayments as $repair)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $repair->order_number }}</td>
                                <td class="py-1.5">{{ $repair->client_name }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ number_format($repair->advance_payment, 2) }}</td>
                                <td class="py-1.5">{{ $repair->payment_type }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-slate-400">Sin cobros de taller</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>

        <details class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
            <summary class="cursor-pointer font-semibold">Abonos ({{ $creditPayments->count() }})</summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[420px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase text-slate-500">
                            <th class="pb-1">Id</th>
                            <th class="pb-1">Cliente</th>
                            <th class="pb-1 text-right">Importe</th>
                            <th class="pb-1">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditPayments as $p)
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5">{{ $p->id }}</td>
                                <td class="py-1.5">{{ is_string($p->client ?? null) ? $p->client : (optional($p->client)->billing_business_name ?? optional($p->client)->billing_name ?? 'Cliente') }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ number_format($p->amount, 2) }}</td>
                                <td class="py-1.5">{{ optional($p->user ?? null)->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-slate-400">Sin abonos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</div>
@endsection
