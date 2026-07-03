<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $sale?->invoice_number ?? ($sale ? str_pad((string)$sale->id, 6, '0', STR_PAD_LEFT) : '') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="w-20 h-20 bg-green-800 rounded-lg flex items-center justify-center text-white text-3xl font-bold">
                AS
            </div>
            <div>
                <h1 class="text-2xl font-bold text-green-900">AGROSERVICIO S.A.</h1>
                <p class="text-xs text-gray-600">Suministros Agrícolas y Agroquímicos</p>
                <p class="text-xs text-gray-600">RUC: J10240330417</p>
                <p class="text-xs text-gray-600">Dirección: Carretera Norte Km. 4.5, Managua, Nicaragua</p>
                <p class="text-xs text-gray-600">Tel: +505 2772-0000 | Email: info@agroservicio.com.ni</p>
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
            <p>Fecha: {{ $sale?->date ? $sale->date->format('d/m/Y') : '' }}</p>
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
        <p><strong>RUC:</strong> {{ $sale?->billing_ruc ?? $sale?->client?->ruc ?? 'N/A' }}</p>
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
                    <td class="border px-4 py-2">C$ {{ number_format($detail->price, 2) }}</td>
                    <td class="border px-4 py-2">C$ {{ number_format($detail->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>

    </table>

    {{-- Totales --}}
    <div class="flex justify-end">
        <div class="w-1/3 space-y-2">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>C$ {{ number_format($sale?->subtotal ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between">
                <span>IVA ({{ number_format(($sale?->tax_rate ?? 0.15) * 100, 0) }}%):</span>
                <span>C$ {{ number_format($sale?->tax_total ?? 0, 2) }}</span>
            </div>

            <div class="flex justify-between font-bold border-t pt-2">
                <span>Total:</span>
                <span>C$ {{ number_format($sale?->total ?? 0, 2) }}</span>
            </div>

        </div>
    </div>

    {{-- Observaciones --}}
    <div class="mt-8">
        <p><strong>Observaciones:</strong></p>
        <p>{{ $sale?->notes ?: 'Gracias por su compra.' }}</p>
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

</div>

<script>
    window.print();
</script>

</body>
</html>
