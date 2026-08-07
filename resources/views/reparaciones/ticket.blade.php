<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Orden {{ $order->order_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            font-size: 11px;
            background: #fff;
        }
        .receipt { padding: 0.4cm; line-height: 1.4; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 0.25cm 0; }
        .shop-name { font-size: 15px; font-weight: bold; text-align: center; }
        .ticket-logo { display: block; width: auto; max-width: 68mm; max-height: 44mm; object-fit: contain; margin: 0 auto 2.5mm; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .badge { background: #000; color: #fff; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; margin: 4px 0; letter-spacing: 2px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 10px; }
        .items-header,
        .item-main,
        .items-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 9mm 21mm;
            gap: 1.5mm;
            align-items: start;
        }
        .items-header { padding-bottom: 1mm; border-bottom: 1px solid #000; font-size: 9px; font-weight: bold; margin-bottom: 2px; }
        .item-main { font-size: 10px; margin-bottom: 4px; }
        .item-name { font-weight: bold; overflow-wrap: anywhere; }
        .item-qty { text-align: center; }
        .item-amount { text-align: right; font-weight: bold; white-space: nowrap; }
        .item-meta { grid-column: 1 / -1; font-size: 9px; margin-top: 1px; color: #444; }
        .items-footer {
            margin-top: 1.5mm;
            padding-top: 1.5mm;
            border-top: 1.5px solid #000;
            font-size: 12px;
            font-weight: bold;
        }
        .status-box { border: 1px solid #000; padding: 4px 8px; display: inline-block; font-size: 11px; font-weight: bold; margin: 4px auto; }
        .footer { font-size: 9px; text-align: center; margin-top: 0.3cm; line-height: 1.5; }
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
@php($totalItemQuantity = $order->items->sum(fn ($item) => (float) $item->quantity))
@php($totalItemQuantityFormatted = fmod($totalItemQuantity, 1.0) === 0.0 ? number_format($totalItemQuantity, 0) : number_format($totalItemQuantity, 2))
<div class="receipt">

    @php($receiptLogoUrl = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'])
    
    @if($receiptLogoUrl)
        <img src="{{ $receiptLogoUrl }}" alt="Logo de {{ $companyProfile['company_name'] }}" class="ticket-logo">
    @endif
    
    <div class="shop-name">{{ $companyProfile['company_name'] }}</div>
    @if($companyProfile['company_legal_name'])<div class="center" style="font-size:9px;">{{ $companyProfile['company_legal_name'] }}</div>@endif
    @if($companyProfile['company_phone'])<div class="center" style="font-size:9px;">Tel: {{ $companyProfile['company_phone'] }}</div>@endif

    <div class="badge">ORDEN DE SERVICIO</div>

    <div class="divider"></div>

    <div class="row"><span>Orden:</span><span class="bold">{{ $order->order_number }}</span></div>
    <div class="row"><span>Recepción:</span><span>{{ $order->received_date->format($companyProfile['date_format']) }} @if($order->formattedReceivedTime())<span class="bold">· {{ $order->formattedReceivedTime() }}</span>@endif</span></div>
    @if($order->estimated_date || $order->estimated_delivery_time)
    <div class="row">
        <span>Entrega est.:</span>
        <span class="bold">
            @if($order->estimated_date){{ $order->estimated_date->format($companyProfile['date_format']) }}@endif
            @if($order->estimated_date && $order->formattedEstimatedDeliveryTime()) · @endif
            @if($order->formattedEstimatedDeliveryTime()){{ $order->formattedEstimatedDeliveryTime() }}@endif
        </span>
    </div>
    @endif
    @if($order->delivered_date || $order->delivered_time)
    <div class="row">
        <span>Entregado:</span>
        <span class="bold">
            @if($order->delivered_date){{ $order->delivered_date->format($companyProfile['date_format']) }}@endif
            @if($order->delivered_date && $order->formattedDeliveredTime()) · @endif
            @if($order->formattedDeliveredTime()){{ $order->formattedDeliveredTime() }}@endif
        </span>
    </div>
    @endif

    <div class="divider"></div>

    <div style="font-size:10px; margin-bottom:0.2cm;">
        <div class="bold">CLIENTE</div>
        <div>{{ $order->client_name }}</div>
        @if($order->client_phone)<div>Tel: {{ $order->client_phone }}</div>@endif
    </div>

    <div class="divider"></div>

    <div style="font-size:10px; margin-bottom:0.2cm;">
        <div class="bold">EQUIPO</div>
        <div>{{ $order->device_brand }} {{ $order->device_model }}</div>
        @if($order->device_color)<div>Color: {{ $order->device_color }}</div>@endif
        @if($order->device_imei)<div>IMEI: {{ $order->device_imei }}</div>@endif
        @if($order->accessories)<div>Accesorios: {{ $order->accessories }}</div>@endif
    </div>

    <div class="divider"></div>

    <div style="font-size:10px; margin-bottom:0.2cm;">
        <div class="bold">FALLA REPORTADA</div>
        <div>{{ $order->problem_description }}</div>
    </div>

    @if($order->diagnosis)
    <div style="font-size:10px; margin-bottom:0.2cm;">
        <div class="bold">DIAGNÓSTICO</div>
        <div>{{ $order->diagnosis }}</div>
    </div>
    @endif

    @if($order->items->count())
    <div class="divider"></div>
    <div class="bold" style="font-size:10px; margin-bottom:3px;">REPUESTOS Y SERVICIOS</div>
    <div class="items-header">
        <span>DESCRIPCIÓN</span>
        <span class="item-qty">CANT.</span>
        <span class="item-amount">IMPORTE</span>
    </div>
    @foreach($order->items as $item)
    <div class="item-main">
        <span class="item-name">
            @if(isset($item->item_type) && $item->item_type === 'service')
                [SERVICIO] {{ $item->description }}
            @else
                {{ $item->description }}
            @endif
        </span>
        <span class="item-qty">{{ number_format($item->quantity, 0) }}</span>
        <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($item->subtotal, 2) }}</span>
        @if(isset($item->item_type) && $item->item_type === 'service' && $item->device_brand)
        <span class="item-meta">Marca: {{ $item->device_brand }}</span>
        @else
        <span class="item-meta">{{ number_format($item->quantity, 0) }} x {{ $companyProfile['currency_symbol'] }} {{ number_format($item->price, 2) }}</span>
        @endif
    </div>
    @endforeach
    @endif

    <div class="divider"></div>

    @if($order->parts_cost > 0)
    <div class="row"><span>Repuestos</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($order->parts_cost,2) }}</span></div>
    @endif
    <div class="row"><span>Mano de obra</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($order->labor_cost,2) }}</span></div>
    <div class="items-footer">
        <span>TOTAL</span>
        <span class="item-qty">{{ $order->items->count() ? $totalItemQuantityFormatted : '' }}</span>
        <span class="item-amount">{{ $companyProfile['currency_symbol'] }} {{ number_format($order->total,2) }}</span>
    </div>
    @if($order->advance_payment > 0)
    <div class="row" style="margin-top:3px;"><span>Anticipo</span><span>-{{ $companyProfile['currency_symbol'] }} {{ number_format($order->advance_payment,2) }}</span></div>
    <div class="row"><span class="bold">SALDO</span><span class="bold">{{ $companyProfile['currency_symbol'] }} {{ number_format($order->balance(),2) }}</span></div>
    @endif

    <div class="divider"></div>

    <div class="center">
        <div class="status-box">{{ strtoupper($order->statusLabel()) }}</div>
    </div>

    @if($order->technician)
    <div class="row" style="margin-top:4px;"><span>Técnico:</span><span>{{ $order->technician->name }}</span></div>
    @endif

    @if($order->warranty_enabled)
    <div class="divider"></div>
    <div style="font-size:9px; line-height:1.45; margin-top:2px;">
        <div class="bold" style="font-size:10px; margin-bottom:2px;">✓ GARANTÍA</div>
        <div>{{ $order->effectiveWarrantyText() }}</div>
    </div>
    @endif

    <div class="footer">
        Gracias por su confianza.<br>
        Conserve este comprobante para retirar su equipo.<br>
        {{ now()->format($companyProfile['date_format'].' H:i') }}
    </div>
</div>

<div class="no-print" style="padding:8px; text-align:center; margin-top:8px;">
    <p style="font-size:11px; color:#9a3412; margin-bottom:8px;">Impresora 80 mm: papel 80 mm, márgenes ninguno, escala 100%. Imprimir desde Chrome/Edge, no Word.</p>
    <button onclick="window.print()" style="padding:6px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px;">Imprimir Ticket 80 mm</button>
    <a href="{{ route('reparaciones.show', $order->id) }}" style="margin-left:8px; font-size:12px; color:#64748b;">Volver</a>
</div>
</body>
</html>
