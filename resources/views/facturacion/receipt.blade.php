<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket #{{ $sale->invoice_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
            color: #000;
            font-family: "Courier New", Courier, monospace;
            font-size: 9.5pt;
            line-height: 1.25;
        }

        .receipt {
            width: 80mm;
            margin: 12px auto;
            padding: 4mm;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .15);
        }

        .center { text-align: center; }
        .strong { font-weight: 700; }
        .separator { border-top: 1px dashed #000; margin: 2.5mm 0; }
        .company-name { font-size: 13pt; font-weight: 700; line-height: 1.1; overflow-wrap: anywhere; }
        .ticket-logo { display: block; width: auto; max-width: 68mm; max-height: 44mm; object-fit: contain; margin: 0 auto 2.5mm; }
        .company-line { margin-top: .8mm; font-size: 8.5pt; overflow-wrap: anywhere; }

        .info { display: grid; gap: 1mm; }
        .info-row { display: grid; grid-template-columns: 20mm minmax(0, 1fr); gap: 2mm; align-items: start; }
        .info-row span:last-child { text-align: right; overflow-wrap: anywhere; }

        .items-header,
        .item-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 9mm 21mm;
            gap: 1.5mm;
            align-items: start;
        }
        .items-header { padding-bottom: 1mm; border-bottom: 1px solid #000; font-size: 8.5pt; font-weight: 700; }
        .item { padding: 1.5mm 0; border-bottom: 1px dotted #777; break-inside: avoid; page-break-inside: avoid; }
        .item:last-child { border-bottom: 0; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .item-qty { text-align: center; }
        .item-amount { text-align: right; white-space: nowrap; }
        .item-meta { margin-top: .7mm; font-size: 8pt; }

        .totals { display: grid; gap: 1mm; }
        .total-row { display: flex; justify-content: space-between; gap: 3mm; }
        .total-row span:last-child { white-space: nowrap; }
        .grand-total { margin-top: 1.5mm; padding-top: 1.5mm; border-top: 1.5px solid #000; font-size: 12pt; font-weight: 700; }
        .payment { font-size: 9pt; }
        .payment-method { margin-top: 1mm; font-size: 10pt; font-weight: 700; }
        .footer { margin-top: 3mm; text-align: center; font-size: 8.5pt; }
        .footer p { margin: 1mm 0; overflow-wrap: anywhere; }

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

        @media print {
            html, body { width: 80mm; background: #fff; }
            .screen-only { display: none !important; }
            .receipt {
                width: 80mm;
                margin: 0;
                padding: 3mm 4mm 5mm;
                box-shadow: none;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="screen-actions screen-only">
        <button type="button" onclick="window.print()">Imprimir ticket 80 mm</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    @php($receiptLogoUrl = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'])

    <main class="receipt" data-paper-width="80mm">
        <header class="center">
            @if($receiptLogoUrl)
                <img src="{{ $receiptLogoUrl }}" alt="Logo de {{ $companyProfile['company_name'] }}" class="ticket-logo">
            @endif
            <div class="company-name">{{ $companyProfile['company_name'] }}</div>
            @if($companyProfile['company_legal_name'])<div class="company-line">{{ $companyProfile['company_legal_name'] }}</div>@endif
            @if($companyProfile['company_ruc'])<div class="company-line">RUC: {{ $companyProfile['company_ruc'] }}</div>@endif
            @if($companyProfile['company_address'])<div class="company-line">{{ $companyProfile['company_address'] }}</div>@endif
            @if($companyProfile['company_phone'])<div class="company-line">Tel: {{ $companyProfile['company_phone'] }}</div>@endif
        </header>

        <div class="separator"></div>

        <section class="info" aria-label="Datos de la venta">
            <div class="info-row"><strong>FACTURA</strong><span>#{{ str_pad($sale->invoice_number, 6, '0', STR_PAD_LEFT) }}</span></div>
            <div class="info-row"><strong>FECHA</strong><span>{{ $sale->date->format($companyProfile['date_format'].' H:i') }}</span></div>
            <div class="info-row"><strong>CAJERO</strong><span>{{ $sale->user?->name ?? 'Sistema' }}</span></div>
        </section>

        <div class="separator"></div>

        <section class="info" aria-label="Datos del cliente">
            <div class="info-row"><strong>CLIENTE</strong><span>{{ $sale->billing_name }}</span></div>
            @if($sale->billing_document_number)<div class="info-row"><strong>{{ strtoupper($sale->billing_document_label) }}</strong><span>{{ $sale->billing_document_number }}</span></div>@endif
            @if($sale->billing_phone)<div class="info-row"><strong>TEL.</strong><span>{{ $sale->billing_phone }}</span></div>@endif
        </section>

        <div class="separator"></div>

        <section aria-label="Productos">
            <div class="items-header">
                <span>DESCRIPCIÓN</span>
                <span class="item-qty">CANT.</span>
                <span class="item-amount">IMPORTE</span>
            </div>
            @foreach($sale->details as $detail)
                <article class="item">
                    <div class="item-main">
                        <span class="item-name">{{ $detail->description ?? $detail->product?->name ?? 'Servicio' }}</span>
                        <span class="item-qty">{{ rtrim(rtrim(number_format($detail->quantity, 2), '0'), '.') }}</span>
                        <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($detail->subtotal, 2) }}</span>
                    </div>
                    <div class="item-meta">
                        {{ rtrim(rtrim(number_format($detail->quantity, 2), '0'), '.') }} × {{ $companyProfile['currency_symbol'] }}{{ number_format($detail->price, 2) }}
                        @if((float) $detail->tax_rate > 0) · IVA {{ number_format($detail->tax_rate * 100, 2) }}%@endif
                    </div>
                </article>
            @endforeach
        </section>

        <div class="separator"></div>

        <section class="totals" aria-label="Totales">
            <div class="total-row"><span>SUBTOTAL</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->subtotal, 2) }}</span></div>
            <div class="total-row"><span>IVA ({{ number_format($sale->tax_rate * 100, 2) }}%)</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->tax_total, 2) }}</span></div>
            <div class="total-row grand-total"><span>TOTAL</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->total, 2) }}</span></div>
            @if($sale->repairOrder && (float) $sale->repairOrder->advance_payment > 0)
                <div class="total-row"><span>ANTICIPO</span><span>-{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->repairOrder->advance_payment, 2) }}</span></div>
                <div class="total-row strong"><span>PAGO FINAL</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format(max(0, $sale->total - $sale->repairOrder->advance_payment), 2) }}</span></div>
            @endif
        </section>

        <div class="separator"></div>

        <section class="payment center" aria-label="Pago">
            <div>MÉTODO DE PAGO</div>
            <div class="payment-method">
                @if($sale->payment_type === 'cash') EFECTIVO
                @elseif($sale->payment_type === 'card') TARJETA
                @elseif($sale->payment_type === 'transfer') TRANSFERENCIA / TARJETA
                @elseif($sale->payment_type === 'credit') CRÉDITO
                @else {{ mb_strtoupper($sale->payment_type) }}
                @endif
            </div>
            @if($sale->payment_type === 'cash' && ($changeAmount ?? 0) > 0)
                <div class="total-row" style="margin-top: 2mm;"><strong>CAMBIO</strong><strong>{{ $companyProfile['currency_symbol'] }} {{ number_format($changeAmount, 2) }}</strong></div>
            @endif
        </section>

        <footer class="footer">
            <p class="strong">{{ $companyProfile['receipt_message'] }}</p>
            <p>{{ now()->format($companyProfile['date_format'].' H:i:s') }}</p>
            <p>Recibo válido sin firma</p>
        </footer>
    </main>

    @if(request()->boolean('autoprint'))
        <script>
            window.addEventListener('afterprint', () => window.close());
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
