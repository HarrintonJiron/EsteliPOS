<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Proforma {{ $proforma->proforma_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 0;
            font-size: 11px;
            background: #fff;
        }
        .receipt { width: 100%; padding: 0.4cm; line-height: 1.3; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 0.25cm 0; }
        .company-name { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 2px; }
        .ticket-logo { display: block; width: auto; max-width: 68mm; max-height: 44mm; object-fit: contain; margin: 0 auto 2.5mm; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .proforma-badge {
            background: #000; color: #fff;
            font-size: 10px; font-weight: bold;
            text-align: center; padding: 2px 0; margin: 4px 0;
            letter-spacing: 2px;
        }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 10px; }
        .items-header,
        .item-main,
        .items-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 9mm 21mm;
            gap: 1.5mm;
            align-items: start;
        }
        .items-header { padding-bottom: 1mm; border-bottom: 1px solid #000; font-size: 8.5pt; font-weight: 700; margin-bottom: 2px; }
        .item { padding: 1.5mm 0; border-bottom: 1px dotted #777; }
        .item-main { margin-bottom: 4px; }
        .item-name { font-weight: bold; overflow-wrap: anywhere; }
        .item-qty { text-align: center; }
        .item-amount { text-align: right; white-space: nowrap; font-weight: bold; }
        .item-meta { grid-column: 1 / -1; font-size: 9px; margin-top: 1px; }
        .items-footer {
            margin-top: 1.5mm;
            padding-top: 1.5mm;
            border-top: 1.5px solid #000;
            font-size: 12px;
            font-weight: bold;
        }
        .footer { font-size: 9px; text-align: center; margin-top: 0.3cm; line-height: 1.4; }
        @media print {
            .no-print { display: none !important; }
            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                min-width: 80mm !important;
            }
            .receipt {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 2mm 4mm !important;
            }
        }
    </style>
</head>
<body>
@php($receiptLogoUrl = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'])
@php($totalQuantity = $proforma->details->sum(fn ($detail) => (float) $detail->quantity))
@php($totalQuantityFormatted = fmod($totalQuantity, 1.0) === 0.0 ? number_format($totalQuantity, 0) : number_format($totalQuantity, 2))
<div class="receipt">
    
    @if($receiptLogoUrl)
        <img src="{{ $receiptLogoUrl }}" alt="Logo de {{ $companyProfile['company_name'] }}" class="ticket-logo">
    @endif
    
    <div class="company-name">{{ $companyProfile['company_name'] }}</div>
    @if($companyProfile['company_legal_name'])<p class="center" style="font-size:9px;">{{ $companyProfile['company_legal_name'] }}</p>@endif
    @if($companyProfile['company_phone'])<p class="center" style="font-size:9px;">Tel: {{ $companyProfile['company_phone'] }}</p>@endif

    <div class="proforma-badge">COTIZACIÓN / PROFORMA</div>

    <div class="divider"></div>

    <div class="row">
        <span>No.:</span>
        <span class="bold">{{ $proforma->proforma_number }}</span>
    </div>
    <div class="row">
        <span>Fecha:</span>
        <span>{{ $proforma->date->format($companyProfile['date_format']) }}</span>
    </div>
    @if($proforma->expiry_date)
    <div class="row">
        <span>Válida hasta:</span>
        <span class="bold">{{ $proforma->expiry_date->format($companyProfile['date_format']) }}</span>
    </div>
    @endif
    <div class="row">
        <span>Estado:</span>
        <span class="bold">{{ $proforma->statusLabel() }}</span>
    </div>

    <div class="divider"></div>

    <div style="font-size:10px; margin-bottom:0.2cm;">
        <div class="bold">Cliente:</div>
        <div>{{ $proforma->client_name ?? 'Cliente General' }}</div>
        @if($proforma->client_phone)<div>Tel: {{ $proforma->client_phone }}</div>@endif
    </div>

    <div class="divider"></div>

    <div class="items-header">
        <span>DESCRIPCIÓN</span>
        <span class="item-qty">CANT.</span>
        <span class="item-amount">IMPORTE</span>
    </div>
    @foreach($proforma->details as $detail)
    <div class="item-main">
        <span class="item-name">{{ $detail->product_name }}</span>
        <span class="item-qty">{{ number_format($detail->quantity, 0) }}</span>
        <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($detail->subtotal, 2) }}</span>
        <span class="item-meta">
            {{ number_format($detail->quantity, 0) }} x {{ $companyProfile['currency_symbol'] }} {{ number_format($detail->price, 2) }}
            @if($detail->discount > 0) (-{{ $detail->discount }}%)@endif
        </span>
    </div>
    @endforeach
    @if($invoiceTaxDisplay->showsTaxInTotals((float) $proforma->tax_total))
        <div class="items-footer" style="margin-top: 1mm; padding-top: 1mm; border-top: 1px dashed #777; font-size: 10px; font-weight: 400;">
            <span>SUBTOTAL</span>
            <span class="item-qty"></span>
            <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($proforma->subtotal, 2) }}</span>
        </div>
        <div class="items-footer" style="margin-top: 0; padding-top: 0.5mm; border-top: 0; font-size: 10px; font-weight: 400;">
            <span>{{ strtoupper($invoiceTaxDisplay->taxLabel((float) $proforma->tax_rate)) }}</span>
            <span class="item-qty"></span>
            <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount((float) $proforma->tax_total), 2) }}</span>
        </div>
    @endif
    <div class="items-footer">
        <span>TOTAL</span>
        <span class="item-qty">{{ $totalQuantityFormatted }}</span>
        <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($proforma->total, 2) }}</span>
    </div>

    @if($proforma->notes)
    <div class="divider"></div>
    <div style="font-size:9px;">
        <div class="bold">Notas:</div>
        <div>{{ $proforma->notes }}</div>
    </div>
    @endif

    <div class="divider"></div>
    <div class="footer">
        Este documento es una cotización.<br>
        No es una factura de venta.<br>
        Elaborado por: {{ $proforma->user?->name ?? 'Sistema' }}<br>
        {{ now()->format($companyProfile['date_format'].' H:i') }}
    </div>

</div>

<div class="no-print" style="padding:8px; text-align:center; margin-top:8px;">
    <p style="font-size:11px; color:#9a3412; margin-bottom:8px;">Impresora 80 mm: papel 80 mm, márgenes ninguno, escala 100%. Imprimir desde Chrome/Edge, no Word.</p>
    <button onclick="window.print()" style="padding:6px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px;">
        Imprimir Ticket 80 mm
    </button>
    <a href="{{ route('proformas.show', $proforma->id) }}" style="margin-left:8px; font-size:12px; color:#64748b;">Volver</a>
</div>
</body>
</html>
