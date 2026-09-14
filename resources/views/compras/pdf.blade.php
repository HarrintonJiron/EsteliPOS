<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PDF proforma de compra #{{ $purchase->id }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2ff; color: #1e293b; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 18px auto; padding: 16mm; overflow: hidden; background: #fff; box-shadow: 0 10px 35px rgba(15, 23, 42, .14); }
        .watermark { position: absolute; top: 45%; left: 13%; z-index: 0; transform: rotate(-35deg); color: rgba(79, 70, 229, .045); font-size: 72px; font-weight: 800; letter-spacing: 8px; }
        .content { position: relative; z-index: 1; }
        .header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 18px; border-bottom: 3px solid #4338ca; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { width: 70px; max-height: 70px; object-fit: contain; }
        h1, p { margin: 0; }
        .company { color: #312e81; font-size: 22px; font-weight: 800; }
        .muted { color: #64748b; }
        .document { min-width: 215px; padding: 16px 18px; border-radius: 14px; background: #4338ca; color: #fff; text-align: right; }
        .document-label { font-size: 10px; font-weight: 700; letter-spacing: 2px; opacity: .8; }
        .document-number { margin-top: 4px; font-size: 24px; font-weight: 800; }
        .document-meta { margin-top: 7px; font-size: 11px; opacity: .9; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin: 22px 0; }
        .info-card { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        .label { margin-bottom: 6px; color: #64748b; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .value { color: #0f172a; font-size: 14px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 10px 9px; background: #4338ca; color: #fff; font-size: 10px; text-align: left; }
        th:first-child { border-radius: 8px 0 0 0; }
        th:last-child { border-radius: 0 8px 0 0; }
        td { padding: 10px 9px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .center { text-align: center; }
        .right { text-align: right; }
        .product { color: #0f172a; font-weight: 700; }
        .code { margin-top: 2px; color: #94a3b8; font-size: 10px; }
        .summary { display: flex; justify-content: flex-end; margin-top: 18px; }
        .summary-box { width: 290px; }
        .summary-row { display: flex; justify-content: space-between; padding: 5px 0; }
        .grand-total { margin-top: 5px; padding-top: 11px; border-top: 2px solid #4338ca; color: #312e81; font-size: 17px; font-weight: 800; }
        .notice { margin-top: 26px; padding: 13px; border: 1px solid #bfdbfe; border-radius: 10px; background: #eff6ff; color: #1e40af; text-align: center; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 55px; margin-top: 55px; }
        .signature { padding-top: 7px; border-top: 1px solid #94a3b8; color: #64748b; text-align: center; }
        .actions { display: flex; justify-content: center; gap: 10px; margin: 0 auto 20px; }
        .button { display: inline-block; padding: 11px 18px; border: 0; border-radius: 9px; background: #4f46e5; color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #cbd5e1; color: #334155; }
        @media print {
            body { background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    $logo = $companyProfile['company_logo_url'] ?: $companyProfile['ticket_logo_url'];
    $documentSubtotal = (float) ($purchase->foreign_subtotal ?? $purchase->subtotal);
    $documentTax = (float) ($purchase->foreign_tax_total ?? $purchase->tax_total);
@endphp
<main class="sheet">
    <div class="watermark">PROFORMA</div>
    <div class="content">
        <header class="header">
            <div class="brand">
                @if($logo)<img src="{{ $logo }}" alt="Logo" class="logo">@endif
                <div>
                    <h1 class="company">{{ $companyProfile['company_name'] }}</h1>
                    @if($companyProfile['company_legal_name'])<p class="muted">{{ $companyProfile['company_legal_name'] }}</p>@endif
                    @if($companyProfile['company_phone'])<p class="muted">Tel: {{ $companyProfile['company_phone'] }}</p>@endif
                </div>
            </div>
            <div class="document">
                <p class="document-label">PROFORMA DE COMPRA</p>
                <p class="document-number">PC-{{ str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p class="document-meta">{{ $purchase->date?->format($companyProfile['date_format']) }} · {{ $purchase->statusLabel() }}</p>
            </div>
        </header>

        <section class="info">
            <div class="info-card">
                <p class="label">Proveedor</p>
                <p class="value">{{ $purchase->supplier->name ?? 'Sin proveedor' }}</p>
            </div>
            <div class="info-card">
                <p class="label">Destino y responsable</p>
                <p class="value">{{ $purchase->warehouse->name ?? 'Sin bodega' }}</p>
                <p class="muted" style="margin-top:4px;">Elaborado por {{ $purchase->user?->name ?? 'Sistema' }}</p>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="center">Cantidad</th>
                    <th class="right">Costo unitario</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->details as $detail)
                    <tr>
                        <td><p class="product">{{ $detail->product->name ?? 'N/A' }}</p><p class="code">{{ $detail->product->code ?? 'Sin código' }}</p></td>
                        <td class="center">{{ rtrim(rtrim(number_format((float) $detail->quantity, 4, '.', ''), '0'), '.') }} {{ $detail->unit?->abbreviation ?? $detail->product?->baseUnit?->abbreviation ?? '' }}</td>
                        <td class="right">{{ $purchaseTotals['document_symbol'] }} {{ number_format($detail->price, 2) }}</td>
                        <td class="right"><strong>{{ $purchaseTotals['document_symbol'] }} {{ number_format($detail->subtotal, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-box">
                @if($invoiceTaxDisplay->showsTaxInTotals($documentTax))
                    <div class="summary-row"><span class="muted">Subtotal</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($documentSubtotal, 2) }}</span></div>
                    <div class="summary-row"><span class="muted">Impuesto</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount($documentTax), 2) }}</span></div>
                @endif
                <div class="summary-row grand-total"><span>TOTAL {{ $purchaseTotals['document_currency'] }}</span><span>{{ $purchaseTotals['document_symbol'] }} {{ number_format($purchaseTotals['document_total'], 2) }}</span></div>
                @if($purchaseTotals['equivalence_total'] !== null && $purchaseTotals['equivalence_currency'] !== $purchaseTotals['document_currency'])
                    <div class="summary-row muted"><span>Equivalencia {{ $purchaseTotals['equivalence_currency'] }}</span><span>{{ $purchaseTotals['equivalence_symbol'] }} {{ number_format($purchaseTotals['equivalence_total'], 2) }}</span></div>
                @endif
            </div>
        </div>

        <p class="notice">Documento de pedido en proceso. El inventario y la contabilidad se actualizan únicamente cuando se confirma la recepción.</p>
        <div class="signatures"><p class="signature">Autorizado por</p><p class="signature">Recibido por</p></div>
    </div>
</main>
<div class="actions no-print">
    <button type="button" class="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a href="{{ route('compras.show', $purchase->id) }}" class="button secondary">Volver</a>
</div>
</body>
</html>
