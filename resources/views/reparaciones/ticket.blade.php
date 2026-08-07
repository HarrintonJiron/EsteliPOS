<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orden {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; width: 80mm; font-size: 11px; }
        .receipt { padding: 0.4cm; line-height: 1.4; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 0.25cm 0; }
        .shop-name { font-size: 15px; font-weight: bold; text-align: center; }
        .ticket-logo { display: block; width: auto; max-width: 68mm; max-height: 44mm; object-fit: contain; margin: 0 auto 2.5mm; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .badge { background: #000; color: #fff; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; margin: 4px 0; letter-spacing: 2px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 10px; }
        .status-box { border: 1px solid #000; padding: 4px 8px; display: inline-block; font-size: 11px; font-weight: bold; margin: 4px auto; }
        .total-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin-top: 4px; }
        .footer { font-size: 9px; text-align: center; margin-top: 0.3cm; line-height: 1.5; }
        @media print {
            .no-print { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
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
    @foreach($order->items as $item)
    <div style="font-size:10px; margin-bottom:3px;">
        <div class="bold">
            @if(isset($item->item_type) && $item->item_type === 'service')
                [SERVICIO] {{ $item->description }}
            @else
                {{ $item->description }}
            @endif
        </div>
        @if(isset($item->item_type) && $item->item_type === 'service' && $item->device_brand)
        <div style="font-size:9px; color:#666;">Marca: {{ $item->device_brand }}</div>
        @endif
        <div style="display:flex; justify-content:space-between;">
            <span>{{ number_format($item->quantity,0) }} x {{ $companyProfile['currency_symbol'] }} {{ number_format($item->price,2) }}</span>
            <span class="bold">{{ $companyProfile['currency_symbol'] }} {{ number_format($item->subtotal,2) }}</span>
        </div>
    </div>
    @endforeach
    @endif

    <div class="divider"></div>

    @if($order->items->count())
    <div class="row"><span>TOTAL ÍTEMS</span><span class="bold">{{ $order->items->sum('quantity') }}</span></div>
    @endif
    @if($order->parts_cost > 0)
    <div class="row"><span>Repuestos</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($order->parts_cost,2) }}</span></div>
    @endif
    <div class="row"><span>Mano de obra</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($order->labor_cost,2) }}</span></div>
    <div class="divider"></div>
    <div class="total-row"><span>TOTAL</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($order->total,2) }}</span></div>
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
    <button onclick="window.print()" style="padding:6px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px;">Imprimir</button>
    <a href="{{ route('reparaciones.show', $order->id) }}" style="margin-left:8px; font-size:12px; color:#64748b;">Volver</a>
</div>
</body>
</html>
