<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de Venta</title>
    <style>
        * { margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            padding: 0;
        }
        .receipt {
            width: 100%;
            text-align: center;
            padding: 0.5cm;
            line-height: 1.2;
        }
        .header {
            border-bottom: 1px dashed #000;
            padding-bottom: 0.3cm;
            margin-bottom: 0.3cm;
            font-weight: bold;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .invoice-info {
            font-size: 11px;
            margin-bottom: 0.3cm;
            text-align: left;
            border-bottom: 1px dashed #000;
            padding-bottom: 0.3cm;
        }
        .client-info {
            font-size: 10px;
            text-align: left;
            margin-bottom: 0.3cm;
            padding-bottom: 0.2cm;
            border-bottom: 1px dashed #000;
        }
        .items {
            font-size: 10px;
            text-align: left;
            margin: 0.3cm 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 0.3cm;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }
        .item-desc {
            flex: 1;
        }
        .item-qty {
            width: 20px;
            text-align: center;
        }
        .item-price {
            width: 40px;
            text-align: right;
        }
        .totals {
            font-size: 10px;
            text-align: right;
            margin: 0.3cm 0;
            border-top: 1px dashed #000;
            padding-top: 0.2cm;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .total-label {
            flex: 1;
        }
        .total-amount {
            width: 50px;
            text-align: right;
        }
        .grand-total {
            font-weight: bold;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }
        .payment-info {
            font-size: 10px;
            margin: 0.3cm 0;
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 0.3cm;
        }
        .footer {
            font-size: 9px;
            text-align: center;
            margin-top: 0.3cm;
            color: #666;
        }
        .footer-text {
            margin: 2px 0;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .receipt { margin: 0; padding: 0.5cm; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="company-name">{{ config('app.name', 'Agroservicio') }}</div>
            <div style="font-size: 9px;">Punto de Venta</div>
        </div>

        <div class="invoice-info">
            <strong>Factura #:</strong> {{ str_pad($sale->invoice_number, 6, '0', STR_PAD_LEFT) }}<br>
            <strong>Fecha:</strong> {{ $sale->date->format('d/m/Y H:i') }}<br>
            <strong>Cajero:</strong> {{ $sale->user?->name ?? 'Sistema' }}
        </div>

        <div class="client-info">
            <strong>Cliente:</strong> {{ $sale->billing_name }}<br>
            @if($sale->billing_ruc)
                <strong>RUC:</strong> {{ $sale->billing_ruc }}<br>
            @endif
        </div>

        <div class="items">
            <div style="display: flex; justify-content: space-between; margin-bottom: 3px; border-bottom: 1px dashed #000; padding-bottom: 2px; font-weight: bold; font-size: 9px;">
                <div style="flex: 1;">Descripción</div>
                <div style="width: 20px; text-align: center;">Cant</div>
                <div style="width: 40px; text-align: right;">Total</div>
            </div>
            @foreach($sale->details as $detail)
                <div class="item-row">
                    <div class="item-desc">{{ $detail->product->name }}</div>
                    <div class="item-qty">{{ $detail->quantity }}</div>
                    <div class="item-price">C$ {{ number_format($detail->subtotal, 2) }}</div>
                </div>
                <div style="font-size: 8px; margin-left: 3px; margin-bottom: 2px;">
                    @ C$ {{ number_format($detail->price, 2) }}
                </div>
            @endforeach
        </div>

        <div class="totals">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-amount">C$ {{ number_format($sale->subtotal, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">IVA (15%):</div>
                <div class="total-amount">C$ {{ number_format($sale->tax_total, 2) }}</div>
            </div>
        </div>

        <div class="grand-total">
            <div>TOTAL</div>
            <div>C$ {{ number_format($sale->total, 2) }}</div>
        </div>

        <div class="payment-info">
            <strong>Método de Pago:</strong><br>
            @if($sale->payment_type == 'cash')
                💰 EFECTIVO
                @if($changeAmount ?? false)
                    <br><strong>Cambio:</strong> C$ {{ number_format($changeAmount, 2) }}
                @endif
            @elseif($sale->payment_type == 'card')
                💳 TARJETA
            @elseif($sale->payment_type == 'transfer')
                🏦 TRANSFERENCIA / TARJETA
            @elseif($sale->payment_type == 'credit')
                📋 CRÉDITO
            @endif
        </div>

        <div class="footer">
            <div class="footer-text">¡Gracias por su compra!</div>
            <div class="footer-text">{{ now()->format('d/m/Y H:i:s') }}</div>
            <div class="footer-text" style="margin-top: 5px;">Recibo válido sin firma</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
