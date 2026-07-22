<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proforma {{ $proforma->proforma_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            padding: 0;
            font-size: 11px;
        }
        .receipt { width: 100%; padding: 0.4cm; line-height: 1.3; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 0.25cm 0; }
        .company-name { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 2px; }
        .proforma-badge {
            background: #000; color: #fff;
            font-size: 10px; font-weight: bold;
            text-align: center; padding: 2px 0; margin: 4px 0;
            letter-spacing: 2px;
        }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 10px; }
        .item-row { font-size: 10px; margin-bottom: 4px; }
        .item-name { flex: 1; }
        .item-total { text-align: right; font-weight: bold; }
        .total-grand { font-size: 14px; font-weight: bold; display: flex; justify-content: space-between; margin-top: 4px; }
        .footer { font-size: 9px; text-align: center; margin-top: 0.3cm; line-height: 1.4; }
        @media print {
            .no-print { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
<div class="receipt">

    <div class="company-name">AGROSERVICIO S.A.</div>
    <p class="center" style="font-size:9px;">SUMINISTROS AGRÍCOLAS Y AGROQUÍMICOS</p>
    <p class="center" style="font-size:9px;">Tel: +505 2772-0000</p>

    <div class="proforma-badge">COTIZACIÓN / PROFORMA</div>

    <div class="divider"></div>

    <div class="row">
        <span>No.:</span>
        <span class="bold">{{ $proforma->proforma_number }}</span>
    </div>
    <div class="row">
        <span>Fecha:</span>
        <span>{{ $proforma->date->format('d/m/Y') }}</span>
    </div>
    @if($proforma->expiry_date)
    <div class="row">
        <span>Válida hasta:</span>
        <span class="bold">{{ $proforma->expiry_date->format('d/m/Y') }}</span>
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

    @foreach($proforma->details as $detail)
    <div class="item-row">
        <div class="bold">{{ $detail->product_name }}</div>
        <div style="display:flex; justify-content:space-between;">
            <span>{{ number_format($detail->quantity, 2) }} x C$ {{ number_format($detail->price, 2) }}
                @if($detail->discount > 0) (-{{ $detail->discount }}%)@endif
            </span>
            <span class="bold">C$ {{ number_format($detail->subtotal, 2) }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="row">
        <span>Subtotal</span>
        <span>C$ {{ number_format($proforma->subtotal, 2) }}</span>
    </div>
    <div class="row">
        <span>IVA (15%)</span>
        <span>C$ {{ number_format($proforma->tax_total, 2) }}</span>
    </div>
    <div class="divider"></div>
    <div class="total-grand">
        <span>TOTAL</span>
        <span>C$ {{ number_format($proforma->total, 2) }}</span>
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
        {{ now()->format('d/m/Y H:i') }}
    </div>

</div>

<div class="no-print" style="padding:8px; text-align:center; margin-top:8px;">
    <button onclick="window.print()" style="padding:6px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px;">
        Imprimir Ticket
    </button>
    <a href="{{ route('proformas.show', $proforma->id) }}" style="margin-left:8px; font-size:12px; color:#64748b;">Volver</a>
</div>
</body>
</html>
