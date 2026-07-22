<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma {{ $proforma->proforma_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .watermark {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px; color: rgba(22,101,52,0.03);
            font-weight: bold; pointer-events: none; z-index: -1;
        }
    </style>
</head>
<body class="bg-white p-6 text-sm">

<div class="watermark">PROFORMA</div>

<div class="max-w-3xl mx-auto">
    <div class="border-4 border-indigo-800 p-1">
    <div class="border-2 border-indigo-500 p-6 relative">

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6">
        <div class="flex items-center gap-4">
            <div class="w-20 h-20 bg-gradient-to-br from-indigo-700 to-indigo-900 rounded-xl flex items-center justify-center text-white shadow-lg">
                <div class="text-center">
                    <div class="text-2xl font-bold">AS</div>
                    <div class="text-xs">AGRO</div>
                </div>
            </div>
            <div>
                <h1 class="text-xl font-black text-indigo-900 tracking-tight">AGROSERVICIO S.A.</h1>
                <p class="text-xs text-gray-600">SUMINISTROS AGRÍCOLAS Y AGROQUÍMICOS</p>
                <div class="mt-1 space-y-0.5 text-xs text-gray-600">
                    <p><span class="font-semibold">RUC:</span> J10240330417</p>
                    <p><span class="font-semibold">Dir:</span> Carretera Norte Km. 4.5, Managua, NI</p>
                    <p><span class="font-semibold">Tel:</span> +505 2772-0000</p>
                </div>
            </div>
        </div>

        <div class="text-center bg-indigo-700 text-white px-6 py-4 rounded-xl shadow-lg">
            <p class="text-xs font-medium uppercase tracking-widest mb-1 opacity-80">PROFORMA</p>
            <p class="text-2xl font-black">{{ $proforma->proforma_number }}</p>
            <div class="mt-2 text-xs space-y-0.5 opacity-90">
                <p>Fecha: {{ $proforma->date->format('d/m/Y') }}</p>
                @if($proforma->expiry_date)
                <p>Válida hasta: {{ $proforma->expiry_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Client --}}
    <div class="bg-slate-50 rounded-xl p-4 mb-5">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Cliente</p>
                <p class="font-bold text-slate-900">{{ $proforma->client_name ?? 'Cliente General' }}</p>
                @if($proforma->client_phone)<p class="text-xs text-slate-600">{{ $proforma->client_phone }}</p>@endif
                @if($proforma->client_email)<p class="text-xs text-slate-600">{{ $proforma->client_email }}</p>@endif
                @if($proforma->client_address)<p class="text-xs text-slate-600">{{ $proforma->client_address }}</p>@endif
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Elaborado por</p>
                <p class="font-semibold text-slate-800">{{ $proforma->user?->name ?? 'Sistema' }}</p>
                <p class="text-xs text-slate-500 mt-1">Estado: <span class="font-semibold text-indigo-700">{{ $proforma->statusLabel() }}</span></p>
            </div>
        </div>
    </div>

    {{-- Items --}}
    <table class="w-full text-xs mb-5">
        <thead>
            <tr class="bg-indigo-700 text-white">
                <th class="text-left px-3 py-2 rounded-tl-lg">Descripción</th>
                <th class="text-center px-3 py-2">Cant.</th>
                <th class="text-right px-3 py-2">P. Unit.</th>
                <th class="text-right px-3 py-2">Dto.</th>
                <th class="text-right px-3 py-2 rounded-tr-lg">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proforma->details as $i => $detail)
            <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' }}">
                <td class="px-3 py-2 font-medium text-slate-800">{{ $detail->product_name }}</td>
                <td class="px-3 py-2 text-center text-slate-600">{{ number_format($detail->quantity, 2) }}</td>
                <td class="px-3 py-2 text-right text-slate-600">C$ {{ number_format($detail->price, 2) }}</td>
                <td class="px-3 py-2 text-right text-slate-600">{{ $detail->discount > 0 ? $detail->discount.'%' : '—' }}</td>
                <td class="px-3 py-2 text-right font-semibold text-slate-900">C$ {{ number_format($detail->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-5">
        <div class="w-56 space-y-1.5">
            <div class="flex justify-between text-xs text-slate-600">
                <span>Subtotal</span>
                <span>C$ {{ number_format($proforma->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-600">
                <span>IVA (15%)</span>
                <span>C$ {{ number_format($proforma->tax_total, 2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm border-t border-indigo-300 pt-1.5 mt-1 text-indigo-900">
                <span>TOTAL</span>
                <span>C$ {{ number_format($proforma->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Notes & Signature --}}
    @if($proforma->notes)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 text-xs">
        <p class="font-semibold text-amber-800 mb-1">Notas y Condiciones</p>
        <p class="text-slate-700 whitespace-pre-line">{{ $proforma->notes }}</p>
    </div>
    @endif

    <div class="grid grid-cols-2 gap-8 mt-6 pt-4">
        <div class="text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="text-xs text-slate-500">Firma del Vendedor</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $proforma->user?->name ?? '' }}</p>
            </div>
        </div>
        <div class="text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="text-xs text-slate-500">Aceptación del Cliente</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $proforma->client_name ?? '' }}</p>
            </div>
        </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4 border-t border-slate-200 pt-3">
        Este documento es una cotización y no constituye una factura de venta.
        @if($proforma->expiry_date)
            Válida hasta el {{ $proforma->expiry_date->format('d \d\e F \d\e Y') }}.
        @endif
    </p>

    </div>
    </div>

    <div class="mt-4 flex justify-center gap-3 no-print">
        <button onclick="window.print()" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold text-sm hover:bg-indigo-700">
            Imprimir / Guardar PDF
        </button>
        <a href="{{ route('proformas.show', $proforma->id) }}" class="px-6 py-2.5 bg-slate-200 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-300">
            Volver
        </a>
    </div>
</div>
</body>
</html>
