<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=300">
    <title>Estado de Cuenta - {{ $client->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; width: 280px; margin: 0; padding: 8px; }
        .center { text-align: center; }
        .small { font-size: 12px; }
        .xs { font-size: 11px; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .products { font-size: 11px; }
        @media print { body { width: 80mm; } }
    </style>
    <x-mobile-ticket-paper selector="body" desktop-content-width="72mm" mobile-content-width="46mm" />
</head>
<body>
    <div class="center">
        <h3 style="margin:0">{{ config('app.name', 'Mi Tienda') }}</h3>
        <p class="small" style="margin:4px 0">Estado de Cuenta</p>
    </div>

    <p class="xs"><strong>Cliente:</strong> {{ $client->legal_name }}</p>
    <p class="xs"><strong>Tipo:</strong> {{ $client->isCompany() ? 'Empresa' : 'Persona Natural' }}</p>
    <p class="xs"><strong>Tel:</strong> {{ $client->phone ?? '—' }} &nbsp; <strong>{{ $client->document_label }}:</strong> {{ $client->document_number ?? '—' }}</p>

    <div class="line"></div>

    <p class="xs"><strong>Resumen</strong></p>
    <table class="small">
        <tr>
            <td>Deuda total</td>
            <td class="right">@money($creditSummary['balance'], 2)</td>
        </tr>
        <tr>
            <td>Disponible</td>
            <td class="right">{{ $creditSummary['available_credit'] === null ? 'Ilimitado' : app(\App\Services\MoneyDisplayService::class)->format($creditSummary['available_credit'], 2) }}</td>
        </tr>
        <tr>
            <td>Plazo</td>
            <td class="right">{{ $creditSummary['credit_days'] }} días</td>
        </tr>
    </table>

    <div class="line"></div>

    <p class="xs bold">Créditos pendientes</p>
    @if($pendingSales->count() > 0)
        @foreach($pendingSales as $sale)
            <div class="small">
                <p style="margin:4px 0"><strong>#{{ str_pad($sale->invoice_number ?? $sale->id, 6, '0', STR_PAD_LEFT) }}</strong> · {{ $sale->date?->format('d/m/Y') }} · Vence: {{ $sale->due_date?->format('d/m/Y') ?? '—' }}</p>
                <table class="products xs">
                    @foreach($sale->details as $d)
                        <tr>
                            <td style="width:65%">{{ Str::limit($d->product?->name ?? 'N/A', 28) }}</td>
                            <td class="right">{{ $d->quantity }} x {{ number_format($d->price,2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="bold">Subtotal</td>
                        <td class="right bold">@money($sale->total, 2)</td>
                    </tr>
                </table>
                <div class="line"></div>
            </div>
        @endforeach
    @elseif($repairRows->isEmpty())
        <p class="xs">No hay créditos pendientes.</p>
        <div class="line"></div>
    @endif

    @if($repairRows->isNotEmpty())
        <p class="xs bold">Reparaciones a crédito</p>
        <table class="small xs">
            @foreach($repairRows as $row)
                <tr>
                    <td><strong>{{ $row['order']->order_number }}</strong> · {{ Str::limit(trim($row['order']->device_brand.' '.$row['order']->device_model), 22) }}</td>
                    <td class="right">@money($row['balance'], 2)</td>
                </tr>
                <tr>
                    <td colspan="2" class="xs">Vence: {{ $row['order']->due_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
        <div class="line"></div>
    @endif

    <p class="xs bold">Abonos recientes</p>
    @php
        $allPayments = collect($payments)->map(fn ($p) => ['date' => $p->payment_date, 'amount' => (float) $p->amount, 'ref' => null])
            ->concat(collect($repairPayments)->map(fn ($p) => ['date' => $p->payment_date, 'amount' => (float) $p->amount, 'ref' => $p->repairOrder?->order_number]))
            ->sortByDesc('date')->values();
    @endphp
    @if($allPayments->count() > 0)
        <table class="small xs">
            @foreach($allPayments->take(6) as $p)
                <tr>
                    <td>{{ $p['date']?->format('d/m/Y') }}@if($p['ref']) · {{ $p['ref'] }}@endif</td>
                    <td class="right">@money($p['amount'], 2)</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="xs">Sin abonos registrados.</p>
    @endif

    <div class="line"></div>

    <table class="small">
        <tr>
            <td class="bold">Total deuda</td>
            <td class="right bold">@money($creditSummary['balance'], 2)</td>
        </tr>
        <tr>
            <td>Pagado</td>
            <td class="right">{{ $currencySymbol }} {{ number_format(collect($payments)->sum('amount') + collect($repairPayments)->sum('amount'), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Saldo</td>
            <td class="right bold">@money($creditSummary['balance'], 2)</td>
        </tr>
    </table>

    <div class="line"></div>

    <p class="xs center">Gracias por su preferencia</p>
    <div class="screen-only" style="text-align:center; margin-top:8px">
        <button onclick="window.print()" style="padding:8px 12px; font-size:13px">Imprimir</button>
    </div>

</body>
</html>
