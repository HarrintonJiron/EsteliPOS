<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Ticket proforma de compra #{{ $purchase->id }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 80mm; max-width: 80mm; margin: 0 auto; background: #fff; color: #000; font: 11px/1.35 "Courier New", monospace; }
        .ticket { width: 100%; padding: 4mm; }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .logo { display: block; width: auto; max-width: 68mm; max-height: 38mm; margin: 0 auto 2mm; object-fit: contain; }
        .company { font-size: 14px; font-weight: 700; text-align: center; }
        .badge { margin: 3mm 0; padding: 1.5mm; background: #000; color: #fff; font-weight: 700; letter-spacing: 1px; text-align: center; }
        .divider { margin: 2.5mm 0; border-top: 1px dashed #000; }
        .row { display: flex; justify-content: space-between; gap: 3mm; margin-bottom: 1mm; }
        .row span:last-child { text-align: right; }
        .header, .item { display: grid; grid-template-columns: minmax(0, 1fr) 11mm 21mm; gap: 1mm; }
        .header { padding-bottom: 1mm; border-bottom: 1px solid #000; font-size: 9px; font-weight: 700; }
        .item { padding: 2mm 0; border-bottom: 1px dotted #777; }
        .name { font-weight: 700; overflow-wrap: anywhere; }
        .qty { text-align: center; }
        .amount { text-align: right; white-space: nowrap; }
        .meta { grid-column: 1 / -1; font-size: 9px; }
        .totals { margin-top: 2mm; }
        .total { margin-top: 1.5mm; padding-top: 1.5mm; border-top: 2px solid #000; font-size: 13px; font-weight: 700; }
        .notice { margin-top: 3mm; padding: 2mm; border: 1px solid #000; font-size: 9px; text-align: center; }
        .footer { margin-top: 3mm; font-size: 9px; text-align: center; }
        .actions { margin-top: 8px; padding: 8px; text-align: center; }
        .actions button { padding: 7px 14px; border: 0; border-radius: 7px; background: #4f46e5; color: #fff; cursor: pointer; }
        .actions a { margin-left: 8px; color: #64748b; }
        @media print {
            .no-print { display: none !important; }
            html, body { width: 80mm !important; max-width: 80mm !important; }
            .ticket { width: 72mm; margin: 0 auto; padding: 2mm 4mm; }
        }
    </style>
</head>
<body>
@php
    $logo = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'];
    $documentSubtotal = (float) ($purchase->foreign_subtotal ?? $purchase->subtotal);
    $documentTax = (float) ($purchase->foreign_tax_total ?? $purchase->tax_total);
    $totalQuantity = $purchase->details->sum(fn ($detail) => (float) $detail->quantity);
@endphp
<div class="ticket">
    @if($logo)
        <img src="{{ $logo }}" alt="Logo de {{ $companyProfile['company_name'] }}" class="logo">
    @endif
    <div class="company">{{ $companyProfile['company_name'] }}</div>
    @if($companyProfile['company_phone'])
        <p class="center">Tel: {{ $companyProfile['company_phone'] }}</p>
    @endif

    <div class="badge">PROFORMA DE COMPRA</div>

    <div class="row"><span>Número:</span><span class="bold">PC-{{ str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT) }}</span></div>
    <div class="row"><span>Fecha:</span><span>{{ $purchase->date?->format($companyProfile['date_format']) }}</span></div>
    <div class="row"><span>Estado:</span><span class="bold">{{ $purchase->statusLabel() }}</span></div>
    <div class="row"><span>Bodega:</span><span>{{ $purchase->warehouse->name ?? '—' }}</span></div>

    <div class="divider"></div>
    <p class="bold">Proveedor</p>
    <p>{{ $purchase->supplier->name ?? 'Sin proveedor' }}</p>

    <div class="divider"></div>
    <div class="header"><span>PRODUCTO</span><span class="qty">CANT.</span><span class="amount">IMPORTE</span></div>
    @foreach($purchase->details as $detail)
        <div class="item">
            <span class="name">{{ $detail->product->name ?? 'N/A' }}</span>
            <span class="qty">{{ rtrim(rtrim(number_format((float) $detail->quantity, 2, '.', ''), '0'), '.') }}</span>
            <span class="amount">{{ $purchaseTotals['document_symbol'] }} {{ number_format($detail->subtotal, 2) }}</span>
            <span class="meta">{{ $detail->product->code ?? '' }} · {{ $purchaseTotals['document_symbol'] }} {{ number_format($detail->price, 2) }} c/u</span>
        </div>
    @endforeach

    <div class="totals">
        @if($invoiceTaxDisplay->showsTaxInTotals($documentTax))
            <div class="row"><span>Subtotal</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($documentSubtotal, 2) }}</span></div>
            <div class="row"><span>Impuesto</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount($documentTax), 2) }}</span></div>
        @endif
        <div class="row total"><span>TOTAL ({{ $purchaseTotals['document_currency'] }})</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($purchaseTotals['document_total'], 2) }}</span></div>
        <div class="row"><span>Unidades</span><span>{{ rtrim(rtrim(number_format($totalQuantity, 2, '.', ''), '0'), '.') }}</span></div>
    </div>

    <div class="notice">Este pedido no modifica inventario ni contabilidad hasta confirmar la recepción.</div>
    <div class="footer">Elaborado por: {{ $purchase->user?->name ?? 'Sistema' }}<br>{{ now()->format($companyProfile['date_format'].' H:i') }}</div>
</div>
<div class="actions no-print">
    <p style="margin-bottom:8px;color:#9a3412;font-size:10px;">Papel 80 mm · márgenes ninguno · escala 100%</p>
    <button type="button" onclick="window.print()">Imprimir ticket 80 mm</button>
    <a href="{{ route('compras.show', $purchase->id) }}">Volver</a>
</div>
</body>
</html>
