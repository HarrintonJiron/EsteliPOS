<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $sale?->invoice_number ?? str_pad((string)($sale?->id ?? '0'), 6, '0', STR_PAD_LEFT) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .bg-pattern {
            background-image: radial-gradient(circle at 1px 1px, #e5e7eb 1px, transparent 0);
            background-size: 20px 20px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(22, 101, 52, 0.03);
            font-weight: bold;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-white p-6 text-sm">

<div class="watermark">AGROSERVICIO</div>

<div class="max-w-3xl mx-auto">
    {{-- Marco decorativo con doble borde --}}
    <div class="border-4 border-green-800 p-1">
    <div class="border-2 border-green-600 p-6 bg-pattern relative">

    {{-- Logo y Encabezado --}}
    <div class="flex justify-between items-start mb-6">
        <div class="flex items-center gap-4">
            <div class="w-24 h-24 bg-gradient-to-br from-green-700 to-green-900 rounded-xl flex items-center justify-center text-white shadow-lg">
                <div class="text-center">
                    <div class="text-3xl font-bold">AS</div>
                    <div class="text-xs">AGRO</div>
                </div>
            </div>
            <div>
                <h1 class="text-2xl font-black text-green-900 tracking-tight">AGROSERVICIO S.A.</h1>
                <p class="text-xs text-gray-600 font-medium">SUMINISTROS AGRÍCOLAS Y AGROQUÍMICOS</p>
                <div class="mt-2 space-y-0.5 text-xs text-gray-600">
                    <p><span class="font-semibold">RUC:</span> J10240330417</p>
                    <p><span class="font-semibold">Dir:</span> Carretera Norte Km. 4.5, Managua, NI</p>
                    <p><span class="font-semibold">Tel:</span> +505 2772-0000 | info@agroservicio.com.ni</p>
                </div>
            </div>
        </div>

        {{-- Caja de Factura --}}
        <div class="text-center">
            <div class="bg-green-800 text-white px-6 py-2 rounded-t-lg">
                <p class="text-xs font-bold tracking-widest">FACTURA</p>
            </div>
            <div class="border-2 border-green-800 bg-white px-6 py-3 rounded-b-lg">
                <p class="text-xs text-gray-500 uppercase">Número</p>
                <p class="text-2xl font-black text-green-900">{{ $sale?->invoice_number ?? str_pad((string)($sale?->id ?? '0'), 6, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
    </div>

    {{-- Información de Factura y Cliente --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        {{-- Datos de Factura --}}
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="text-xs font-bold text-green-800 uppercase mb-3 border-b border-green-200 pb-1">Datos de Factura</h3>
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-600">Fecha de Emisión:</span>
                    <span class="font-semibold">{{ $sale?->date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Condición de Pago:</span>
                    <span class="font-semibold">{{ $sale?->payment_type === 'credit' ? 'CRÉDITO' : 'CONTADO' }}</span>
                </div>
                @if($sale?->due_date)
                <div class="flex justify-between">
                    <span class="text-gray-600">Fecha Vencimiento:</span>
                    <span class="font-semibold">{{ $sale->due_date->format('d/m/Y') }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600">Estado:</span>
                    <span class="font-semibold {{ $sale?->status === 'completed' ? 'text-green-700' : 'text-yellow-700' }}">
                        {{ $sale?->status === 'completed' ? 'PAGADA' : 'PENDIENTE' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Datos del Cliente --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-xs font-bold text-gray-700 uppercase mb-3 border-b border-gray-200 pb-1">Datos del Cliente</h3>
            <div class="space-y-1.5 text-xs">
                <p class="font-semibold text-sm">{{ $sale?->billing_name ?? $sale?->client?->name ?? 'CLIENTE GENERAL' }}</p>
                @if(($sale?->billing_business_name) || ($sale?->client?->business_name))
                    <p class="text-gray-600">{{ $sale?->billing_business_name ?? $sale?->client?->business_name }}</p>
                @endif
                <p><span class="text-gray-600">RUC:</span> {{ $sale?->billing_ruc ?? $sale?->client?->ruc ?? 'N/A' }}</p>
                <p><span class="text-gray-600">Tel:</span> {{ $sale?->billing_phone ?? $sale?->client?->phone ?? 'N/A' }}</p>
                <p class="text-gray-600 text-xs">{{ $sale?->billing_address ?? $sale?->client?->address ?? '' }}</p>
            </div>
        </div>
    </div>

    {{-- Tabla de Productos --}}
    <div class="mb-6">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-green-800 text-white">
                    <th class="px-3 py-2 text-left font-semibold rounded-tl-lg">#</th>
                    <th class="px-3 py-2 text-left font-semibold">DESCRIPCIÓN DEL PRODUCTO</th>
                    <th class="px-3 py-2 text-center font-semibold">CANT.</th>
                    <th class="px-3 py-2 text-right font-semibold">P. UNIT.</th>
                    <th class="px-3 py-2 text-right font-semibold rounded-tr-lg">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody class="border border-gray-200">
                @php $totalQty = 0; @endphp
                @foreach(($sale?->details ?? []) as $index => $detail)
                    @php $totalQty += $detail->quantity; @endphp
                    <tr class="border-b border-gray-100 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="px-3 py-2.5 text-gray-600">{{ $index + 1 }}</td>
                        <td class="px-3 py-2.5">
                            <p class="font-medium text-gray-800">{{ $detail->product?->name ?? 'N/A' }}</p>
                            @if($detail->product?->code)
                                <p class="text-xs text-gray-500">Código: {{ $detail->product->code }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center font-medium">{{ $detail->quantity }}</td>
                        <td class="px-3 py-2.5 text-right">C$ {{ number_format($detail->price, 2) }}</td>
                        <td class="px-3 py-2.5 text-right font-medium">C$ {{ number_format($detail->subtotal, 2) }}</td>
                    </tr>
                @endforeach
                @if(($sale?->details ?? [])->count() < 5)
                    @for($i = ($sale?->details ?? [])->count(); $i < 5; $i++)
                        <tr class="border-b border-gray-100 {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="px-3 py-2.5">&nbsp;</td>
                            <td class="px-3 py-2.5">&nbsp;</td>
                            <td class="px-3 py-2.5">&nbsp;</td>
                            <td class="px-3 py-2.5">&nbsp;</td>
                            <td class="px-3 py-2.5">&nbsp;</td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>
    </div>

    {{-- Totales --}}
    <div class="flex justify-end mb-6">
        <div class="w-80 space-y-1">
            <div class="flex justify-between text-xs py-1">
                <span class="text-gray-600">Total Artículos:</span>
                <span class="font-semibold">{{ $totalQty ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-xs py-1 border-t border-gray-200">
                <span class="text-gray-600">Subtotal:</span>
                <span>C$ {{ number_format($sale?->subtotal ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between text-xs py-1">
                <span class="text-gray-600">IVA ({{ number_format(($sale?->tax_rate ?? 0.15) * 100, 0) }}%):</span>
                <span>C$ {{ number_format($sale?->tax_total ?? 0, 2) }}</span>
            </div>
            @if($sale?->discount > 0)
            <div class="flex justify-between text-xs py-1 text-red-600">
                <span>Descuento:</span>
                <span>- C$ {{ number_format($sale->discount, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm py-2 border-t-2 border-green-800 font-black text-green-900 bg-green-50 px-3 rounded">
                <span>TOTAL A PAGAR:</span>
                <span>C$ {{ number_format($sale?->total ?? 0, 2) }}</span>
            </div>
            <div class="text-xs text-gray-500 text-center italic mt-1">
                {{ $sale?->total ? ucfirst(\App\Helpers\NumberToWords::convert($sale->total)) . ' CÓRDOBAS EXACTOS' : '' }}
            </div>
        </div>
    </div>

    {{-- Notas y Pie --}}
    @if($sale?->notes)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
        <p class="text-xs font-semibold text-yellow-800 mb-1">OBSERVACIONES:</p>
        <p class="text-xs text-gray-700">{{ $sale->notes }}</p>
    </div>
    @endif

    {{-- Términos y Firma --}}
    <div class="grid grid-cols-2 gap-6 mt-8 pt-6 border-t-2 border-green-800">
        <div class="text-xs text-gray-500">
            <p class="font-semibold text-gray-700 mb-2">TÉRMINOS Y CONDICIONES:</p>
            <ul class="space-y-1 text-xs">
                <li>• Precios en Córdobas Nicaragüenses (NIO)</li>
                <li>• Garantía según política del fabricante</li>
                <li>• No se aceptan devoluciones sin factura</li>
                <li>• Productos agroquímicos: verificar fecha de vencimiento</li>
            </ul>
        </div>

        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-16">
                <p class="text-xs font-semibold">FIRMA Y SELLO AUTORIZADO</p>
                <p class="text-xs text-gray-500">Agroservicio S.A.</p>
            </div>
        </div>
    </div>

    {{-- Pie de página --}}
    <div class="mt-6 pt-4 border-t border-green-200 text-center">
        <p class="text-xs text-gray-500">Documento generado el {{ now()->format('d/m/Y H:i:s') }} | Agroservicio S.A. - {{ config('app.url', 'www.agroservicio.com.ni') }}</p>
        <p class="text-xs text-gray-400 mt-1">"Su aliado en el campo"</p>
    </div>

    </div>{{-- cierre border-green-600 --}}
    </div>{{-- cierre border-4 --}}

    {{-- Botones de acción --}}
    <div class="no-print mt-6 flex justify-center gap-3">
        <button onclick="window.print()" class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Factura
        </button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 shadow">
            Cerrar
        </button>
    </div>

</div>

<script>
    // Auto-print después de cargar
    window.addEventListener('load', function() {
        setTimeout(function() {
            // window.print(); // Descomentar para impresión automática
        }, 500);
    });
</script>

</body>
</html>
