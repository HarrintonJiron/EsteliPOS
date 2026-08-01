<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Reparación {{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white p-6 text-sm text-slate-800">
<div class="max-w-3xl mx-auto">
    <div class="border-4 border-slate-800 p-1">
    <div class="border-2 border-slate-400 p-6">

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-xl font-black text-slate-900">AGROSERVICIO S.A.</h1>
            <p class="text-xs text-slate-600">SUMINISTROS AGRÍCOLAS · TALLER TÉCNICO</p>
            <div class="text-xs text-slate-600 mt-1 space-y-0.5">
                <p>RUC: J10240330417 · Tel: +505 2772-0000</p>
                <p>Carretera Norte Km 4.5, Managua, NI</p>
            </div>
        </div>
        <div class="text-center bg-slate-800 text-white px-6 py-4 rounded-xl">
            <p class="text-xs font-medium uppercase tracking-widest opacity-70 mb-1">ORDEN DE REPARACIÓN</p>
            <p class="text-2xl font-black">{{ $order->order_number }}</p>
            <div class="text-xs mt-1 space-y-0.5 opacity-90">
                <p>Recibido: {{ $order->received_date->format('d/m/Y') }}</p>
                @if($order->estimated_date)
                <p>Entrega est.: {{ $order->estimated_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Status bar --}}
    <div class="flex gap-2 mb-5">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $order->priorityColor() }}">Prioridad: {{ $order->priorityLabel() }}</span>
        @if($order->technician)
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Técnico: {{ $order->technician->name }}</span>
        @endif
    </div>

    {{-- Client + Device --}}
    <div class="grid grid-cols-2 gap-4 mb-5">
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente</p>
            <p class="font-bold text-slate-900">{{ $order->client_name }}</p>
            @if($order->client_phone)<p class="text-xs text-slate-600">📞 {{ $order->client_phone }}</p>@endif
            @if($order->client_email)<p class="text-xs text-slate-600">✉ {{ $order->client_email }}</p>@endif
        </div>
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Equipo</p>
            <p class="font-bold text-slate-900">{{ $order->device_brand }} {{ $order->device_model }}</p>
            @if($order->device_color)<p class="text-xs text-slate-600">Color: {{ $order->device_color }}</p>@endif
            @if($order->device_imei)<p class="text-xs text-slate-600">IMEI: <span class="font-mono">{{ $order->device_imei }}</span></p>@endif
            @if($order->accessories)<p class="text-xs text-slate-600">Accesorios: {{ $order->accessories }}</p>@endif
        </div>
    </div>
    @php
        $pdfLockType = $order->lock_type ?? ($order->device_password ? (preg_match('/^[1-9](?:-[1-9])*$/', $order->device_password) ? 'pattern' : 'password') : 'none');
    @endphp
    @if($order->device_password && $pdfLockType === 'pattern')
    <div class="mb-5">
        <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Patrón de desbloqueo</p>
        <x-pattern-viewer :pattern="$order->device_password" />
    </div>
    @endif
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Falla reportada por cliente</p>
            <p class="text-xs text-slate-700 bg-slate-50 rounded-xl p-3 whitespace-pre-line">{{ $order->problem_description }}</p>
        </div>
        @if($order->diagnosis)
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Diagnóstico técnico</p>
            <p class="text-xs text-slate-700 bg-blue-50 rounded-xl p-3 whitespace-pre-line">{{ $order->diagnosis }}</p>
        </div>
        @endif
    </div>

    {{-- Parts table --}}
    @if($order->items->count())
    <table class="w-full text-xs mb-4">
        <thead>
            <tr class="bg-slate-800 text-white">
                <th class="text-left px-3 py-2 rounded-tl-lg">Repuesto / Servicio</th>
                <th class="text-center px-3 py-2">Cant.</th>
                <th class="text-right px-3 py-2">P. Unit.</th>
                <th class="text-right px-3 py-2 rounded-tr-lg">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50' }}">
                <td class="px-3 py-2 font-medium">{{ $item->description }}</td>
                <td class="px-3 py-2 text-center">{{ number_format($item->quantity,2) }}</td>
                <td class="px-3 py-2 text-right">C$ {{ number_format($item->price,2) }}</td>
                <td class="px-3 py-2 text-right font-semibold">C$ {{ number_format($item->subtotal,2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Totals + Signature --}}
    <div class="flex justify-between items-end mb-5">
        <div class="text-xs text-slate-500 max-w-xs">
            @if($order->repair_notes)
            <p class="font-semibold text-slate-700 mb-1">Notas técnicas:</p>
            <p>{{ $order->repair_notes }}</p>
            @endif
        </div>
        <div class="w-52 space-y-1">
            @if($order->parts_cost > 0)
            <div class="flex justify-between text-xs text-slate-600"><span>Repuestos</span><span>C$ {{ number_format($order->parts_cost,2) }}</span></div>
            @endif
            <div class="flex justify-between text-xs text-slate-600"><span>Mano de obra</span><span>C$ {{ number_format($order->labor_cost,2) }}</span></div>
            <div class="flex justify-between font-bold text-sm border-t border-slate-300 pt-1 text-slate-900">
                <span>TOTAL</span><span>C$ {{ number_format($order->total,2) }}</span>
            </div>
            @if($order->advance_payment > 0)
            <div class="flex justify-between text-xs text-slate-600"><span>Anticipo</span><span>-C$ {{ number_format($order->advance_payment,2) }}</span></div>
            <div class="flex justify-between font-bold text-sm text-red-700">
                <span>SALDO</span><span>C$ {{ number_format($order->balance(),2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Signatures --}}
    <div class="grid grid-cols-2 gap-8 pt-4">
        <div class="text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="text-xs text-slate-500">Firma del Técnico</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $order->technician?->name ?? '' }}</p>
            </div>
        </div>
        <div class="text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="text-xs text-slate-500">Firma de Recepción / Conformidad del Cliente</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $order->client_name }}</p>
            </div>
        </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4 border-t border-slate-200 pt-3">
        Conserve este documento para retirar su equipo. Agroservicio S.A. no se hace responsable de equipos no retirados después de 30 días.
    </p>

    </div>
    </div>

    <div class="mt-4 flex justify-center gap-3 no-print">
        <button onclick="window.print()" class="px-6 py-2.5 bg-slate-800 text-white rounded-xl font-semibold text-sm hover:bg-slate-700">
            Imprimir / Guardar PDF
        </button>
        <a href="{{ route('reparaciones.show', $order->id) }}" class="px-6 py-2.5 bg-slate-200 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-300">
            Volver
        </a>
    </div>
</div>
</body>
</html>
