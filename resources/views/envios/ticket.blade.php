<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Envío {{ $shipment->number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 80mm; max-width: 80mm; margin: 0 auto; background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; }
        .label-ticket { width: 100%; padding: 3mm 4mm 4mm; }
        .logo { display: block; width: auto; max-width: 50mm; max-height: 18mm; margin: 0 auto 1mm; object-fit: contain; }
        .company { text-align: center; font-size: 13px; font-weight: 900; line-height: 1.15; }
        .company-detail { margin-top: 1mm; text-align: center; font-size: 9px; line-height: 1.25; }
        .title { margin: 2.5mm 0 2mm; border-block: 2px solid #000; padding: 1.5mm 0; text-align: center; font-size: 15px; font-weight: 900; letter-spacing: .7px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5mm 3mm; margin-bottom: 2mm; font-size: 10px; }
        .meta strong { display: block; margin-top: .5mm; font-size: 12px; }
        .field { margin-top: 2mm; }
        .field-label { display: block; margin-bottom: .6mm; font-size: 9px; font-weight: 900; letter-spacing: .4px; text-transform: uppercase; }
        .field-value { display: block; border: 2px solid #000; padding: 1.6mm 2mm; text-align: center; font-size: 19px; font-weight: 900; line-height: 1.05; overflow-wrap: anywhere; }
        .field-value.name { font-size: 20px; }
        .field-value.destination { font-size: 21px; }
        .field-value.phone { font-size: 22px; letter-spacing: 1px; }
        .field-value.handling { border-width: 3px; font-size: 23px; text-transform: uppercase; }
        .reference { margin-top: 2mm; border-top: 1px dashed #000; padding-top: 1.5mm; font-size: 10px; }
        .footer { margin-top: 2.5mm; text-align: center; font-size: 9px; font-weight: 700; }
        .no-print { width: 80mm; margin: 4mm auto; text-align: center; }
        button, a { display: inline-block; border: 0; border-radius: 6px; padding: 8px 14px; background: #0369a1; color: #fff; text-decoration: none; cursor: pointer; }
        @media print {
            .no-print { display: none !important; }
            html, body { width: 80mm !important; max-width: 80mm !important; }
            .label-ticket { width: 76mm; margin: 0 auto; padding: 2mm; }
        }
    </style>
</head>
<body>
@php($logo = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'])
<main class="label-ticket">
    @if($logo)<img src="{{ $logo }}" alt="Logo" class="logo">@endif
    <div class="company">{{ $companyProfile['company_name'] }}</div>
    <div class="company-detail">
        @if($companyProfile['company_phone'])Tel: {{ $companyProfile['company_phone'] }}@endif
        @if($companyProfile['company_ruc']) · RUC: {{ $companyProfile['company_ruc'] }}@endif
    </div>

    <div class="title">DATOS PARA ENVÍO</div>

    <div class="meta">
        <div>ENVÍO<strong>{{ $shipment->number }}</strong></div>
        <div>FECHA<strong>{{ $shipment->created_at->format('d/m/Y h:i A') }}</strong></div>
        @if($shipment->sale)<div>FACTURA<strong>{{ $shipment->sale->invoice_number }}</strong></div>@endif
        @if($shipment->tracking_number)<div>GUÍA<strong>{{ $shipment->tracking_number }}</strong></div>@endif
    </div>

    <div class="field"><span class="field-label">Nombre</span><span class="field-value name">{{ $shipment->recipient_name }}</span></div>
    <div class="field"><span class="field-label">Departamento</span><span class="field-value destination">{{ $shipment->department }}</span></div>
    @if($shipment->recipient_phone)<div class="field"><span class="field-label">Teléfono</span><span class="field-value phone">{{ $shipment->recipient_phone }}</span></div>@endif
    <div class="field"><span class="field-label">Descripción / manejo</span><span class="field-value handling">{{ $shipment->address }}</span></div>
    @if($shipment->municipality)<div class="field"><span class="field-label">Ciudad / municipio</span><span class="field-value destination">{{ $shipment->municipality }}</span></div>@endif

    @if($shipment->reference)<div class="reference"><strong>REFERENCIA:</strong> {{ $shipment->reference }}</div>@endif
    @if($shipment->carrier)<div class="reference"><strong>TRANSPORTE:</strong> {{ $shipment->carrier }}</div>@endif
    <div class="footer">PEGAR VISIBLEMENTE EN EL PAQUETE</div>
</main>
<div class="no-print"><button onclick="window.print()">Imprimir etiqueta 80 mm</button> <a href="{{ route('envios.show',$shipment) }}">Volver</a></div>
</body>
</html>
