<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiqueta envío #{{ $shipment->number }}</title>
    <style>
        @page {
            size: 10cm 15cm;
            margin: 0;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .label {
            width: 10cm;
            max-width: 10cm;
            min-height: 15cm;
            margin: 12px auto;
            padding: 6mm;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .15);
        }

        .center { text-align: center; }
        .separator { border-top: 2px dashed #000; margin: 3mm 0; }
        .company-name { font-size: 13pt; font-weight: 900; line-height: 1.15; overflow-wrap: anywhere; }
        .company-line { margin-top: .8mm; font-size: 8.5pt; font-weight: 700; overflow-wrap: anywhere; }

        .label-title { margin-top: 3mm; font-size: 11pt; font-weight: 900; letter-spacing: .5px; }

        .meta { display: grid; gap: 1.2mm; margin-top: 2mm; font-size: 8.5pt; }
        .meta-row { display: grid; grid-template-columns: 22mm minmax(0, 1fr); gap: 2mm; }
        .meta-row span:first-child { font-weight: 700; }
        .meta-row span:last-child { overflow-wrap: anywhere; }

        .field { margin-top: 3mm; }
        .field-label { font-size: 8pt; font-weight: 700; letter-spacing: .5px; color: #334155; }
        .field-value {
            margin-top: 1mm;
            padding: 2mm 2.5mm;
            font-size: 13pt;
            font-weight: 900;
            background: #f1f5f9;
            border-radius: 2mm;
            overflow-wrap: anywhere;
        }
        .field-value.fragile {
            color: #991b1b;
            background: #fee2e2;
            border: 1.5px solid #991b1b;
            text-align: center;
            font-size: 15pt;
            letter-spacing: 1px;
        }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; }

        .footer { margin-top: 5mm; text-align: center; font-size: 8pt; font-weight: 700; color: #475569; }

        .screen-actions {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: rgba(15, 23, 42, .94);
        }
        .screen-actions button {
            border: 0;
            border-radius: 8px;
            padding: 9px 16px;
            color: #fff;
            background: #4f46e5;
            font: 600 14px system-ui, sans-serif;
            cursor: pointer;
        }
        .screen-actions button:last-child { background: #475569; }
        .print-hint {
            max-width: 10cm;
            margin: 0 auto 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
            font: 12px/1.4 system-ui, sans-serif;
            text-align: center;
        }

        @media print {
            *, *::before, *::after {
                color: #000 !important;
                opacity: 1 !important;
                text-shadow: none !important;
                filter: none !important;
                -webkit-font-smoothing: none;
            }
            html, body {
                background: #fff !important;
            }
            .screen-only { display: none !important; }
            .label {
                width: 10cm !important;
                max-width: 10cm !important;
                min-height: 15cm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .field-value.fragile {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="print-hint screen-only">
        Etiqueta de envío 10&times;15&nbsp;cm: si su impresora no tiene ese tamaño de papel,
        imprima en papel carta y recorte por el borde de la caja.
    </div>
    <div class="screen-actions screen-only">
        <button type="button" onclick="window.print()">Imprimir etiqueta</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    <main class="label">
        <header class="center">
            <div class="company-name">{{ $companyProfile['company_name'] }}</div>
            @if($companyProfile['company_address'])<div class="company-line">{{ $companyProfile['company_address'] }}</div>@endif
            @if($companyProfile['company_phone'])<div class="company-line">Tel: {{ $companyProfile['company_phone'] }}</div>@endif
        </header>

        <div class="separator"></div>

        <div class="label-title center">DATOS PARA ENVÍO</div>

        <div class="meta">
            <div class="meta-row"><span>Factura</span><span>{{ $shipment->sale?->invoice_number ? '#'.str_pad((string) $shipment->sale->invoice_number, 6, '0', STR_PAD_LEFT) : $shipment->number }}</span></div>
            <div class="meta-row"><span>Fecha</span><span>{{ now()->format('d/m/Y h:i A') }}</span></div>
        </div>

        <div class="field">
            <div class="field-label">NOMBRE</div>
            <div class="field-value">{{ $shipment->recipient_name }}</div>
        </div>

        <div class="two-col">
            <div class="field">
                <div class="field-label">DEPARTAMENTO</div>
                <div class="field-value">{{ $shipment->department }}</div>
            </div>
            <div class="field">
                <div class="field-label">MUNICIPIO</div>
                <div class="field-value">{{ $shipment->municipality ?: '—' }}</div>
            </div>
        </div>

        <div class="field">
            <div class="field-label">TELÉFONO</div>
            <div class="field-value">{{ $shipment->recipient_phone ?: '—' }}</div>
        </div>

        @if($shipment->is_fragile)
            <div class="field">
                <div class="field-value fragile">FRÁGIL</div>
            </div>
        @endif

        <footer class="footer">
            <div class="separator"></div>
            Etiqueta generada por el sistema · {{ $shipment->number }}
        </footer>
    </main>
</body>
</html>
