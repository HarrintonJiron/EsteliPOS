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
</head>
<body>
    <div class="center">
        <h3 style="margin:0">{{ config('app.name', 'Mi Tienda') }}</h3>
        <p class="small" style="margin:4px 0">Estado de Cuenta</p>
    </div>

    <p class="xs"><strong>Cliente:</strong> {{ $client->name }}</p>
    <p class="xs"><strong>Tel:</strong> {{ $client->phone ?? '—' }} &nbsp; <strong>RUC:</strong> {{ $client->ruc ?? '—' }}</p>

    <div class="line"></div>

    <p class="xs"><strong>Resumen</strong></p>
    <table class="small">
        <tr>
            <td>Deuda total</td>
            <td class="right">C$ {{ number_format($creditSummary['balance'], 2) }}</td>
        </tr>
        <tr>
            <td>Disponible</td>
            <td class="right">{{ $creditSummary['available_credit'] === null ? 'Ilimitado' : 'C$ '.number_format($creditSummary['available_credit'], 2) }}</td>
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
                        <td class="right bold">C$ {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </table>
                <div class="line"></div>
            </div>
        @endforeach
    @else
        <p class="xs">No hay créditos pendientes.</p>
        <div class="line"></div>
    @endif

    <p class="xs bold">Abonos recientes</p>
    @if($payments->count() > 0)
        <table class="small xs">
            @foreach($payments->take(5) as $p)
                <tr>
                    <td>{{ $p->payment_date?->format('d/m/Y') }}</td>
                    <td class="right">C$ {{ number_format($p->amount, 2) }}</td>
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
            <td class="right bold">C$ {{ number_format($creditSummary['balance'], 2) }}</td>
        </tr>
        <tr>
            <td>Pagado</td>
            <td class="right">C$ {{ number_format(collect($payments)->sum('amount'), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Saldo</td>
            <td class="right bold">C$ {{ number_format($creditSummary['balance'], 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <p class="xs center">Gracias por su preferencia</p>
    <div style="text-align:center; margin-top:8px">
        <button onclick="window.print()" style="padding:8px 12px; font-size:13px">Imprimir</button>
    </div>

</body>
</html>
