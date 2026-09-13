<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Arqueo #{{ $arqueo->id }}</title>
    <style>
        @page { margin: 24px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        h2 { margin: 18px 0 6px; font-size: 12px; }
        .muted { color: #64748b; }
        .summary { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .summary td { width: 25%; padding: 8px; border: 1px solid #cbd5e1; vertical-align: top; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 12px; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; }
        table.detail th, table.detail td { padding: 5px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        table.detail th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; }
        .right { text-align: right !important; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #cbd5e1; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
@php
    $symbol = ($arqueo->currency ?? 'NIO') === 'USD' ? 'US$' : 'C$';
    $difference = (float) $physicalTotal - (float) $expectedCashTotal;
    $openedAt = data_get($arqueo->details, 'session.opened_at');
@endphp
<h1>Arqueo de caja #{{ $arqueo->id }}</h1>
<div class="muted">
    {{ $date->format('d/m/Y') }} · {{ $arqueo->branch?->name ?: data_get($arqueo->details, 'session.branch') ?: 'Sin sucursal' }} · {{ $arqueo->user?->name ?: data_get($arqueo->details, 'session.cashier') ?: 'Cajero' }}<br>
    Apertura {{ $openedAt ? \Illuminate\Support\Carbon::parse($openedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—' }} · Cierre {{ $arqueo->closed_at?->format('d/m/Y H:i:s') ?? '—' }}
</div>

<table class="summary"><tr>
    <td><div class="label">Fondo</div><div class="value">{{ $symbol }} {{ number_format($openingAmount, 2) }}</div></td>
    <td><div class="label">Ventas</div><div class="value">{{ $symbol }} {{ number_format($totalSalesAmount, 2) }}</div></td>
    <td><div class="label">Abonos</div><div class="value">{{ $symbol }} {{ number_format($creditPaymentsTotal, 2) }}</div></td>
    <td><div class="label">Esperado</div><div class="value">{{ $symbol }} {{ number_format($expectedCashTotal, 2) }}</div></td>
</tr><tr>
    <td><div class="label">Gastos de caja</div><div class="value">{{ $symbol }} {{ number_format($operationalExpensesCashTotal, 2) }}</div></td>
    <td><div class="label">Compras de caja</div><div class="value">{{ $symbol }} {{ number_format($cashPurchasesTotal, 2) }}</div></td>
    <td><div class="label">Contado</div><div class="value">{{ $symbol }} {{ number_format($physicalTotal, 2) }}</div></td>
    <td><div class="label">Diferencia</div><div class="value">{{ $symbol }} {{ number_format($difference, 2) }}</div></td>
</tr></table>

<h2>Resumen por método de pago</h2>
<table class="detail"><thead><tr><th>Método</th><th class="right">Cantidad</th><th class="right">Importe</th></tr></thead><tbody>
@forelse($byType as $type => $row)
    <tr><td>{{ ucfirst((string) $type) }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ $symbol }} {{ number_format($row['total'], 2) }}</td></tr>
@empty
    <tr><td colspan="3">Sin ventas</td></tr>
@endforelse
</tbody></table>

<h2>Ventas ({{ $totalSalesCount }})</h2>
<table class="detail"><thead><tr><th>Factura</th><th>Cliente</th><th>Pago</th><th class="right">Total</th></tr></thead><tbody>
@forelse($sales as $sale)
    <tr><td>{{ $sale->invoice_number }}</td><td>{{ is_string($sale->client ?? null) ? $sale->client : 'Consumidor' }}</td><td>{{ $sale->payment_type }}</td><td class="right">{{ $symbol }} {{ number_format($sale->total, 2) }}</td></tr>
@empty
    <tr><td colspan="4">Sin ventas</td></tr>
@endforelse
</tbody></table>

<h2>Gastos de caja</h2>
<table class="detail"><thead><tr><th>Descripción</th><th class="right">Monto</th></tr></thead><tbody>
@forelse($operationalExpenses as $expense)
    <tr><td>{{ $expense->description }}</td><td class="right">{{ $symbol }} {{ number_format($expense->amount, 2) }}</td></tr>
@empty
    <tr><td colspan="2">Sin gastos de caja</td></tr>
@endforelse
</tbody></table>

<div class="footer">Integridad SHA-256: {{ $arqueo->snapshot_hash ?: 'No disponible' }}</div>
</body>
</html>
