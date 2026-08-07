<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Ticket #{{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

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
            max-width: 80mm;
            margin: 12px auto;
            padding: 4mm;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .15);
        }

        .center { text-align: center; }
        .strong { font-weight: 700; }
        .separator { border-top: 1px dashed #000; margin: 2.5mm 0; }
        .company-name { font-size: 13pt; font-weight: 700; line-height: 1.1; overflow-wrap: anywhere; }
        .ticket-logo { display: block; width: auto; max-width: 68mm; max-height: 44mm; object-fit: contain; margin: 0 auto 2.5mm; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .company-line { margin-top: .8mm; font-size: 8.5pt; overflow-wrap: anywhere; }

        .info { display: grid; gap: 1mm; }
        .info-row { display: grid; grid-template-columns: 20mm minmax(0, 1fr); gap: 2mm; align-items: start; }
        .info-row span:last-child { text-align: right; overflow-wrap: anywhere; }

        .items-header,
        .item-main,
        .items-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 9mm 21mm;
            gap: 1.5mm;
            align-items: start;
        }
        .items-header { padding-bottom: 1mm; border-bottom: 1px solid #000; font-size: 8.5pt; font-weight: 700; }
        .items-footer {
            margin-top: 1.5mm;
            padding-top: 1.5mm;
            border-top: 1.5px solid #000;
            font-size: 11pt;
            font-weight: 700;
        }
        .item { padding: 1.5mm 0; border-bottom: 1px dotted #777; break-inside: avoid; page-break-inside: avoid; }
        .item:last-child { border-bottom: 0; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .item-qty { text-align: center; }
        .item-amount { text-align: right; white-space: nowrap; }
        .item-meta { margin-top: .7mm; font-size: 8pt; }

        .totals { display: grid; gap: 1mm; }
        .total-row { display: flex; justify-content: space-between; gap: 3mm; }
        .total-row span:last-child { white-space: nowrap; }
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
        .print-hint {
            max-width: 80mm;
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
            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                min-width: 80mm !important;
                height: auto !important;
                background: #fff !important;
            }
            .screen-only { display: none !important; }
            .receipt {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 2mm 4mm 4mm !important;
                box-shadow: none !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }

        @media print and (width: 21cm), print and (width: 8.5in) {
            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-hint screen-only">
        Impresora térmica 80 mm: papel <strong>80 mm</strong>, márgenes <strong>ninguno</strong>, escala <strong>100%</strong>.
        No uses Word; imprime directo desde el navegador (Chrome o Edge).
    </div>
    <div class="screen-actions screen-only">
        <button type="button" onclick="window.print()">Imprimir ticket 80 mm</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    @php
        $receiptLogoUrl = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'];
        $totalQuantity = $sale->details->sum(fn ($detail) => (float) $detail->quantity);
        $totalQuantityFormatted = fmod($totalQuantity, 1.0) === 0.0
            ? number_format($totalQuantity, 0)
            : number_format($totalQuantity, 2);
        $grossSubtotal = $sale->details->sum(fn ($detail) => (float) $detail->price * (float) $detail->quantity);
        $discountAmount = (float) ($sale->discount_amount ?? 0);
        if ($discountAmount <= 0) {
            $discountAmount = max(0, $grossSubtotal - (float) $sale->subtotal);
        }
        $hasDiscount = $discountAmount > 0.009;
    @endphp

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
                        <span class="item-name">{{ $detail->product?->name ?? 'Producto' }}</span>
                        <span class="item-qty">{{ number_format($detail->quantity, 0) }}</span>
                        <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($detail->subtotal, 2) }}</span>
                    </div>
                    <div class="item-meta">
                        {{ number_format($detail->quantity, 0) }} × {{ $companyProfile['currency_symbol'] }}{{ number_format($detail->price, 2) }}
                        @if((float) ($detail->discount_percentage ?? 0) > 0)
                            · Dto. {{ number_format((float) $detail->discount_percentage, 2) }}%
                        @endif
                        @if((float) ($detail->discount_amount ?? 0) > 0.009)
                            · Ahorro {{ $companyProfile['currency_symbol'] }}{{ number_format((float) $detail->discount_amount, 2) }}
                        @endif
                        @if($invoiceTaxDisplay->showsLineTax((float) $detail->tax_rate)) · IVA {{ number_format($detail->tax_rate * 100, 2) }}%@endif
                    </div>
                </article>
            @endforeach

            <div class="items-footer" style="margin-top: 1mm; padding-top: 1mm; border-top: 1px dashed #777; font-size: 9pt; font-weight: 400;">
                <span>SUBTOTAL</span>
                <span class="item-qty"></span>
                <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($hasDiscount ? $grossSubtotal : (float) $sale->subtotal, 2) }}</span>
            </div>
            @if($hasDiscount)
                <div class="items-footer" style="margin-top: 0; padding-top: 0.5mm; border-top: 0; font-size: 9pt; font-weight: 400;">
                    <span>
                        DESCUENTO
                        @if((float) $sale->discount_percentage > 0)
                            ({{ number_format((float) $sale->discount_percentage, 2) }}% factura)
                        @endif
                    </span>
                    <span class="item-qty"></span>
                    <span class="item-amount">-{{ $companyProfile['currency_symbol'] }}{{ number_format($discountAmount, 2) }}</span>
                </div>
            @endif
            @if($invoiceTaxDisplay->showsTaxInTotals((float) $sale->tax_total))
                <div class="items-footer" style="margin-top: 0; padding-top: 0.5mm; border-top: 0; font-size: 9pt; font-weight: 400;">
                    <span>{{ strtoupper($invoiceTaxDisplay->taxLabel((float) $sale->tax_rate)) }}</span>
                    <span class="item-qty"></span>
                    <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($invoiceTaxDisplay->displayTaxAmount((float) $sale->tax_total), 2) }}</span>
                </div>
            @endif
            <div class="items-footer">
                <span>TOTAL</span>
                <span class="item-qty">{{ $totalQuantityFormatted }}</span>
                <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($sale->total, 2) }}</span>
            </div>
            @if($hasDiscount)
                <div class="center" style="margin-top: 2mm; font-size: 9pt; font-weight: 700;">
                    ¡Ahorraste {{ $companyProfile['currency_symbol'] }}{{ number_format($discountAmount, 2) }}!
                </div>
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
            @if($sale->payment_type === 'cash')
                @if($sale->amount_paid > 0)
                    <div class="total-row" style="margin-top: 2mm;"><strong>PAGADO</strong><strong>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->amount_paid, 2) }}</strong></div>
                @endif
                @if($sale->change_amount > 0)
                    <div class="total-row" style="margin-top: 1mm;"><strong>CAMBIO</strong><strong>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale->change_amount, 2) }}</strong></div>
                @endif
            @endif
        </section>

        <footer class="footer">
            <p class="strong">{{ $companyProfile['receipt_message'] }}</p>
            <p>{{ now()->format($companyProfile['date_format'].' H:i:s') }}</p>
            <p>Recibo válido sin firma</p>
        </footer>
    </main>
</body>
</html>
