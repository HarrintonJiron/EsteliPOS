<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=302, initial-scale=1">
    <title>Nómina {{ $startDate->translatedFormat('F Y') }}</title>
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
        .info-row { display: grid; grid-template-columns: 24mm minmax(0, 1fr); gap: 2mm; align-items: start; }
        .info-row span:last-child { text-align: right; overflow-wrap: anywhere; }
        .items-header, .item-main, .items-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 21mm;
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
        .item-name { font-weight: 700; overflow-wrap: anywhere; font-size: 8.5pt; }
        .item-meta { margin-top: .5mm; font-size: 8pt; color: #333; }
        .item-amount { text-align: right; white-space: nowrap; }
        .totals { display: grid; gap: 1mm; font-size: 9pt; }
        .total-row { display: flex; justify-content: space-between; gap: 3mm; }
        .status-badge {
            display: inline-block;
            margin-top: 2mm;
            padding: 1mm 2mm;
            border: 1px solid #000;
            font-size: 8.5pt;
            font-weight: 700;
        }
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
            html, body { width: 80mm !important; max-width: 80mm !important; min-width: 80mm !important; height: auto !important; background: #fff !important; }
            .screen-only { display: none !important; }
            .receipt {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 2mm 4mm 4mm !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
@php
    $totals = $ticketData['totals'];
    $receiptLogoUrl = $companyProfile['ticket_logo_url'] ?: $companyProfile['company_logo_url'];
@endphp

<div class="print-hint screen-only">
    Impresora térmica 80 mm: papel <strong>80 mm</strong>, márgenes <strong>ninguno</strong>, escala <strong>100%</strong>.
</div>
<div class="screen-actions screen-only">
    <button type="button" onclick="window.print()">Imprimir ticket 80 mm</button>
    <button type="button" onclick="window.close()">Cerrar</button>
</div>

<main class="receipt" data-paper-width="80mm">
    <header class="center">
        @if($receiptLogoUrl)
            <img src="{{ $receiptLogoUrl }}" alt="Logo de {{ $companyProfile['company_name'] }}" class="ticket-logo">
        @endif
        <div class="company-name">{{ $companyProfile['company_name'] }}</div>
        @if($companyProfile['company_ruc'])<div class="company-line">RUC: {{ $companyProfile['company_ruc'] }}</div>@endif
        @if($companyProfile['company_address'])<div class="company-line">{{ $companyProfile['company_address'] }}</div>@endif
        <div class="status-badge">{{ $ticketData['is_paid'] ? 'NÓMINA PAGADA' : 'CÁLCULO PRELIMINAR' }}</div>
    </header>

    <div class="separator"></div>

    <section class="info" aria-label="Período">
        <div class="info-row"><strong>PERÍODO</strong><span>{{ $startDate->translatedFormat('F Y') }}</span></div>
        <div class="info-row"><strong>DESDE</strong><span>{{ $startDate->format($companyProfile['date_format']) }}</span></div>
        <div class="info-row"><strong>HASTA</strong><span>{{ $endDate->format($companyProfile['date_format']) }}</span></div>
        <div class="info-row"><strong>EMPLEADOS</strong><span>{{ count($ticketData['employees']) }}</span></div>
        @if($ticketData['is_paid'] && $ticketData['paid_at'])
            <div class="info-row"><strong>PAGADO</strong><span>{{ $ticketData['paid_at']->format($companyProfile['date_format'].' H:i') }}</span></div>
        @endif
        @if($ticketData['is_paid'] && $ticketData['paid_by_name'])
            <div class="info-row"><strong>POR</strong><span>{{ $ticketData['paid_by_name'] }}</span></div>
        @endif
    </section>

    <div class="separator"></div>

    <section aria-label="Detalle por empleado">
        <div class="items-header">
            <span>EMPLEADO</span>
            <span class="item-amount">NETO</span>
        </div>
        @foreach($ticketData['employees'] as $employee)
            <article class="item">
                <div class="item-main">
                    <span class="item-name">{{ $employee['employee_name'] }}</span>
                    <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($employee['net_salary'], 2) }}</span>
                </div>
                @if($employee['position'])
                    <div class="item-meta">{{ $employee['position'] }}</div>
                @endif
            </article>
        @endforeach
    </section>

    <div class="separator"></div>

    <section class="totals" aria-label="Totales">
        <div class="total-row"><span>Salario base</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($totals['base_salary'], 2) }}</span></div>
        <div class="total-row"><span>Bonos</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($totals['bonuses'], 2) }}</span></div>
        <div class="total-row"><span>Bruto</span><span>{{ $companyProfile['currency_symbol'] }} {{ number_format($totals['gross_salary'], 2) }}</span></div>
        <div class="total-row"><span>INSS</span><span>- {{ $companyProfile['currency_symbol'] }} {{ number_format($totals['inss_deduction'], 2) }}</span></div>
        <div class="total-row"><span>IR</span><span>- {{ $companyProfile['currency_symbol'] }} {{ number_format($totals['ir_deduction'], 2) }}</span></div>
        @if($totals['other_deductions'] > 0)
            <div class="total-row"><span>Otras ded.</span><span>- {{ $companyProfile['currency_symbol'] }} {{ number_format($totals['other_deductions'], 2) }}</span></div>
        @endif
        @if($totals['loan_payments'] > 0)
            <div class="total-row"><span>Préstamos</span><span>- {{ $companyProfile['currency_symbol'] }} {{ number_format($totals['loan_payments'], 2) }}</span></div>
        @endif
        <div class="items-footer">
            <span>NETO TOTAL</span>
            <span class="item-amount">{{ $companyProfile['currency_symbol'] }}{{ number_format($totals['net_salary'], 2) }}</span>
        </div>
    </section>

    <footer class="footer">
        <p class="strong">Comprobante de nómina</p>
        <p>{{ now()->format($companyProfile['date_format'].' H:i:s') }}</p>
    </footer>
</main>
</body>
</html>
