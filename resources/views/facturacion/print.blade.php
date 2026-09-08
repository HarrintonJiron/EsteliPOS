<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $sale?->invoice_number ?? ($sale ? str_pad((string)$sale->id, 6, '0', STR_PAD_LEFT) : '') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        .bg-pattern {
            background-image: radial-gradient(circle at 1px 1px, #e5e7eb 1px, transparent 0);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-white p-8 text-sm">

<div class="max-w-3xl mx-auto bg-white">

    {{-- Marco decorativo --}}
    <div class="border-4 border-green-800 p-1">
    <div class="border border-green-600 p-6 bg-pattern">

    {{-- Encabezado Profesional --}}
    <div class="flex justify-between items-start mb-6 border-b-2 border-green-800 pb-4">
        <div class="flex items-center gap-4">
            @if($companyProfile['company_logo_url'])
                <img src="{{ $companyProfile['company_logo_url'] }}" alt="Logo" class="h-20 w-20 object-contain">
            @else
                <div class="w-20 h-20 bg-green-800 rounded-lg flex items-center justify-center text-white text-3xl font-bold">{{ mb_strtoupper(mb_substr($companyProfile['company_name'], 0, 2)) }}</div>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-green-900">{{ $companyProfile['company_name'] }}</h1>
                @if($companyProfile['company_legal_name'])<p class="text-xs text-gray-600">{{ $companyProfile['company_legal_name'] }}</p>@endif
                @if($companyProfile['company_ruc'])<p class="text-xs text-gray-600">RUC: {{ $companyProfile['company_ruc'] }}</p>@endif
                @if($companyProfile['company_address'])<p class="text-xs text-gray-600">Dirección: {{ $companyProfile['company_address'] }}, {{ $companyProfile['company_city'] }}, {{ $companyProfile['company_country'] }}</p>@endif
                <p class="text-xs text-gray-600">@if($companyProfile['company_phone'])Tel: {{ $companyProfile['company_phone'] }}@endif @if($companyProfile['company_email']) · {{ $companyProfile['company_email'] }}@endif</p>
            </div>
        </div>

        <div class="text-right">
            <div class="bg-green-800 text-white px-4 py-2 rounded-t-lg text-center">
                <p class="text-xs font-bold">FACTURA</p>
            </div>
            <div class="border-2 border-green-800 px-4 py-2 text-center">
                <p class="text-xs text-gray-600">No.</p>
                <p class="text-xl font-bold text-green-900">{{ $sale?->invoice_number ?? str_pad((string)($sale?->id ?? '000000'), 6, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
    </div>

        <div class="text-right">
            <h2 class="text-xl font-bold">FACTURA</h2>
            <p>No: <span class="font-semibold">{{ $sale?->invoice_number ?? ($sale ? str_pad((string)$sale->id, 6, '0', STR_PAD_LEFT) : '') }}</span></p>
            <p>Fecha: {{ $sale?->date ? $sale->date->format($companyProfile['date_format']) : '' }}</p>
            <p>Condición: {{ $sale?->payment_type === 'credit' ? 'Crédito' : 'Contado' }}</p>
            @if($sale?->due_date)
                <p>Vence: {{ $sale->due_date->format('d/m/Y') }}</p>
            @endif
        </div>

    </div>

    {{-- Cliente --}}
    <div class="mb-6 border p-4">
        <p><strong>Cliente:</strong> {{ $sale?->billing_name ?? $sale?->client?->name }}</p>
        @if(($sale?->billing_business_name) || ($sale?->client?->business_name))
            <p><strong>Razón social:</strong> {{ $sale?->billing_business_name ?? $sale?->client?->business_name }}</p>
        @endif
        <p><strong>{{ $sale?->billing_document_label ?? 'Documento' }}:</strong> {{ $sale?->billing_document_number ?? 'N/A' }}</p>
        <p><strong>Dirección:</strong> {{ $sale?->billing_address ?? $sale?->client?->address ?? 'N/A' }}</p>
        <p><strong>Teléfono:</strong> {{ $sale?->billing_phone ?? $sale?->client?->phone ?? 'N/A' }}</p>
    </div>

    {{-- Tabla --}}
    <table class="min-w-full border text-sm mb-6">

        <thead class="bg-gray-200">
            <tr>
                <th class="border px-4 py-2 text-left">Producto</th>
                <th class="border px-4 py-2 text-left">Cantidad</th>
                <th class="border px-4 py-2 text-left">Precio</th>
                <th class="border px-4 py-2 text-left">Subtotal</th>
            </tr>
        </thead>

        <tbody>
            @foreach(($sale?->details ?? []) as $detail)
                <tr>
                    <td class="border px-4 py-2">{{ $detail->product->name ?? 'N/A' }}</td>
                    <td class="border px-4 py-2">{{ $detail->quantity }}</td>
                    <td class="border px-4 py-2">{{ $companyProfile['currency_symbol'] }} {{ number_format($detail->price, 2) }}</td>
                    <td class="border px-4 py-2">{{ $companyProfile['currency_symbol'] }} {{ number_format($detail->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>

    </table>

    {{-- Totales --}}
    <div class="flex justify-end">
        <div class="w-1/3 space-y-2">
            @if($invoiceTaxDisplay->showsTaxBreakdown())
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale?->subtotal ?? 0, 2) }}</span>
                </div>

                <div class="flex justify-between">
                    <span>{{ $invoiceTaxDisplay->taxLabel((float) ($sale?->tax_rate ?? 0)) }}:</span>
                    <span>{{ $companyProfile['currency_symbol'] }} {{ number_format($invoiceTaxDisplay->displayTaxAmount((float) ($sale?->tax_total ?? 0)), 2) }}</span>
                </div>
            @endif

            <div class="flex justify-between font-bold border-t pt-2">
                <span>Total:</span>
                <span>{{ $companyProfile['currency_symbol'] }} {{ number_format($sale?->total ?? 0, 2) }}</span>
            </div>

        </div>
    </div>

    {{-- Observaciones --}}
    <div class="mt-8">
        <p><strong>Observaciones:</strong></p>
        <p>{{ $sale?->notes ?: $companyProfile['receipt_message'] }}</p>
    </div>

    {{-- Firma --}}
    <div class="mt-16 flex justify-between text-center">
        <div>
            <p>_________________________</p>
            <p>Firma del Cliente</p>
        </div>

        <div>
            <p>_________________________</p>
            <p>Vendedor</p>
        </div>
    </div>

    @if($companyProfile['invoice_footer'])
        <p class="mt-8 border-t pt-3 text-center text-xs text-gray-500">{{ $companyProfile['invoice_footer'] }}</p>
    @endif

</div>

<script>
    window.print();
</script>

</body>
</html>
