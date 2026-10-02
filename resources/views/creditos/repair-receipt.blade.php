<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de abono {{ $order->order_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { width: 80mm; margin: 0 auto; padding: 4mm; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .big { font-size: 16px; font-weight: 800; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="center">
        <h1>{{ $companyProfile['company_name'] }}</h1>
        @if($companyProfile['company_ruc'])<div>RUC: {{ $companyProfile['company_ruc'] }}</div>@endif
        @if($companyProfile['company_phone'])<div>Tel: {{ $companyProfile['company_phone'] }}</div>@endif
        <div class="line"></div>
        <div class="bold">RECIBO DE ABONO · REPARACIÓN</div>
    </div>

    <table>
        <tr><td>Recibo</td><td class="right">R-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td>Fecha</td><td class="right">{{ $payment->payment_date->format($companyProfile['date_format'].' H:i') }}</td></tr>
        <tr><td>Cliente</td><td class="right">{{ $client?->legal_name ?? $order->client_name }}</td></tr>
        <tr><td>Orden</td><td class="right">{{ $order->order_number }}</td></tr>
        <tr><td>Equipo</td><td class="right">{{ $order->device_brand }} {{ $order->device_model }}</td></tr>
        @if($payment->user)<tr><td>Cajero</td><td class="right">{{ $payment->user->name }}</td></tr>@endif
    </table>

    <div class="line"></div>

    <table>
        <tr><td>Forma de pago</td><td class="right">{{ ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'check' => 'Cheque'][$payment->payment_type] ?? 'Otro' }}</td></tr>
        @if($payment->reference_number)<tr><td>Referencia</td><td class="right">{{ $payment->reference_number }}</td></tr>@endif
        <tr><td class="bold">ABONO</td><td class="right big">@money($payment->amount, 2)</td></tr>
    </table>

    <div class="line"></div>

    <table>
        <tr><td>Total de la reparación</td><td class="right">@money($order->total, 2)</td></tr>
        <tr><td>Pagado a la fecha</td><td class="right">@money($order->paidAmount(), 2)</td></tr>
        <tr><td class="bold">Saldo pendiente</td><td class="right bold">@money($balance, 2)</td></tr>
        @if($balance > 0 && $order->due_date)<tr><td>Vence</td><td class="right">{{ $order->due_date->format($companyProfile['date_format']) }}</td></tr>@endif
    </table>

    <div class="line"></div>
    <p class="center">Gracias por su pago</p>

    <div class="no-print center" style="margin-top:8px">
        <button onclick="window.print()" style="padding:6px 14px">Imprimir</button>
        <a href="{{ route('creditos.show', $order->client_id) }}" style="margin-left:8px">Volver</a>
    </div>
</body>
</html>
