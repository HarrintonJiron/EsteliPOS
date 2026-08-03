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
        .badge { background: #000; color: #fff; font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; margin: 4px 0; letter-spacing: 2px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 10px; }
        .status-box { border: 1px solid #000; padding: 4px 8px; display: inline-block; font-size: 11px; font-weight: bold; margin: 4px auto; }
        .total-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin-top: 4px; }
        .footer { font-size: 9px; text-align: center; margin-top: 0.3cm; line-height: 1.5; }
        .warranty { font-size: 8px; line-height: 1.35; text-align: left; }
        @media print {
            .no-print { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
<div class="receipt">

    <div class="shop-name">AGROSERVICIO S.A.</div>
    <div class="center" style="font-size:9px;">Taller de Reparaciones · Tel: +505 2772-0000</div>

    <div class="badge">ORDEN DE SERVICIO</div>

    <div class="divider"></div>

    <div class="row"><span>Orden:</span><span class="bold">{{ $order->order_number }}</span></div>
    <div class="row"><span>Recepción:</span><span>{{ $order->received_date->format('d/m/Y') }}{{ $order->received_time ? ' '.substr($order->received_time, 0, 5) : '' }}</span></div>
    @if($order->estimated_date)
    <div class="row"><span>Fecha estimada:</span><span class="bold">{{ $order->estimated_date->format('d/m/Y') }}</span></div>
    <div class="row"><span>Hora estimada:</span><span class="bold">{{ $order->estimated_time ? substr($order->estimated_time, 0, 5) : 'Pendiente' }}</span></div>
    @endif
    @if($order->delivered_date)
    <div class="row"><span>Entregado:</span><span>{{ $order->delivered_date->format('d/m/Y') }}{{ $order->delivered_time ? ' '.substr($order->delivered_time, 0, 5) : '' }}</span></div>
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
            <span>{{ number_format($item->quantity,2) }} x C$ {{ number_format($item->price,2) }}</span>
            <span class="bold">C$ {{ number_format($item->subtotal,2) }}</span>
        </div>
    </div>
    @endforeach
    @endif

    <div class="divider"></div>

    @if($order->parts_cost > 0)
    <div class="row"><span>Repuestos</span><span>C$ {{ number_format($order->parts_cost,2) }}</span></div>
    @endif
    <div class="row"><span>Mano de obra</span><span>C$ {{ number_format($order->labor_cost,2) }}</span></div>
    <div class="divider"></div>
    <div class="total-row"><span>TOTAL</span><span>C$ {{ number_format($order->total,2) }}</span></div>
    @if($order->advance_payment > 0)
    <div class="row" style="margin-top:3px;"><span>Anticipo</span><span>-C$ {{ number_format($order->advance_payment,2) }}</span></div>
    <div class="row"><span class="bold">SALDO</span><span class="bold">C$ {{ number_format($order->balance(),2) }}</span></div>
    @endif

    <div class="divider"></div>

    <div class="center">
        <div class="status-box">{{ strtoupper($order->statusLabel()) }}</div>
    </div>

    @if($order->technician)
    <div class="row" style="margin-top:4px;"><span>Técnico:</span><span>{{ $order->technician->name }}</span></div>
    @endif

    @if($order->include_warranty_policy && $order->warranty_policy)
    <div class="divider"></div>
    <div class="warranty">
        <div class="bold center" style="font-size:9px; margin-bottom:3px;">GARANTÍA: {{ $order->warranty_days }} DÍAS</div>
        <div>{{ $order->warranty_policy }}</div>
    </div>
    @endif

    <div class="footer">
        Gracias por su confianza.<br>
        Conserve este comprobante para retirar su equipo.<br>
        {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<div class="no-print" style="padding:8px; text-align:center; margin-top:8px;">
    <button onclick="window.print()" style="padding:6px 16px; background:#4f46e5; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:12px;">Imprimir</button>
    <a href="{{ route('reparaciones.show', $order->id) }}" style="margin-left:8px; font-size:12px; color:#64748b;">Volver</a>
</div>
</body>
</html>
