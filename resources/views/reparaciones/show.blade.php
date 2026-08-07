@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Orden ' . $order->order_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('reparaciones.index') }}" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $order->order_number }}</h1>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->priorityColor() }}">{{ $order->priorityLabel() }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-0.5">Recibido: {{ $order->received_date->format('d/m/Y') }}
                    @if($order->formattedReceivedTime()) <span class="font-medium">· {{ $order->formattedReceivedTime() }}</span>@endif
                    @if($order->estimated_date || $order->estimated_delivery_time) · Entrega est.:
                        <span class="{{ $order->estimated_date && $order->estimated_date->isPast() && $order->status !== 'delivered' ? 'text-red-600 font-semibold' : '' }}">
                            @if($order->estimated_date){{ $order->estimated_date->format('d/m/Y') }}@endif
                            @if($order->estimated_date && $order->formattedEstimatedDeliveryTime()) · @endif
                            @if($order->formattedEstimatedDeliveryTime()){{ $order->formattedEstimatedDeliveryTime() }}@endif
                        </span>
                    @endif
                    @if($order->delivered_date || $order->delivered_time) · Entregado:
                        <span class="text-emerald-700 font-medium">
                            @if($order->delivered_date){{ $order->delivered_date->format('d/m/Y') }}@endif
                            @if($order->delivered_date && $order->formattedDeliveredTime()) · @endif
                            @if($order->formattedDeliveredTime()){{ $order->formattedDeliveredTime() }}@endif
                        </span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reparaciones.ticket', $order->id) }}" target="_blank" class="btn-outline text-sm">Ticket</a>
            <a href="{{ route('reparaciones.pdf', $order->id) }}" target="_blank" class="btn-outline text-sm">PDF</a>
            <a href="{{ route('reparaciones.edit', $order->id) }}" class="btn-secondary text-sm">Editar</a>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-3 gap-5">

        {{-- LEFT --}}
        <div class="col-span-2 space-y-5">

            {{-- Client & Device --}}
            <div class="card p-5 grid grid-cols-2 gap-5">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente</p>
                    <p class="font-bold text-slate-900 text-lg">{{ $order->client_name }}</p>
                    @if($order->client_phone)<p class="text-sm text-slate-600">📞 {{ $order->client_phone }}</p>@endif
                    @if($order->client_email)<p class="text-sm text-slate-600">✉ {{ $order->client_email }}</p>@endif
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Equipo</p>
                    <p class="font-bold text-slate-900 text-lg">{{ $order->device_brand }} {{ $order->device_model }}</p>
                    @if($order->device_color)<p class="text-sm text-slate-600">Color: {{ $order->device_color }}</p>@endif
                    @if($order->device_imei)<p class="text-sm text-slate-600">IMEI: <span class="font-mono">{{ $order->device_imei }}</span></p>@endif
                    @if($order->accessories)<p class="text-sm text-slate-600">Accesorios: {{ $order->accessories }}</p>@endif
                </div>
            </div>

            {{-- Diagnosis --}}
            <div class="card p-5 space-y-4">
                <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Diagnóstico y Reparación</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Falla reportada</p>
                        <p class="text-sm text-slate-700 bg-slate-50 rounded-xl p-3 whitespace-pre-line">{{ $order->problem_description }}</p>
                    </div>
                    @if($order->diagnosis)
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Diagnóstico técnico</p>
                        <p class="text-sm text-slate-700 bg-blue-50 rounded-xl p-3 whitespace-pre-line">{{ $order->diagnosis }}</p>
                    </div>
                    @endif
                    @if($order->repair_notes)
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Notas internas</p>
                        <p class="text-sm text-slate-700 bg-amber-50 rounded-xl p-3 whitespace-pre-line">{{ $order->repair_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Parts --}}
            @if($order->items->count())
            <div class="card overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-700">Repuestos Utilizados</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-5 py-2.5 text-slate-500 font-medium">Descripción</th>
                            <th class="text-right px-4 py-2.5 text-slate-500 font-medium">Cant.</th>
                            <th class="text-right px-4 py-2.5 text-slate-500 font-medium">P. Unit.</th>
                            <th class="text-right px-5 py-2.5 text-slate-500 font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">C$ {{ number_format($item->price, 2) }}</td>
                            <td class="px-5 py-3 text-right font-semibold">C$ {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="px-5 py-2 text-right text-slate-600 text-sm">Repuestos</td>
                            <td class="px-5 py-2 text-right font-medium">C$ {{ number_format($order->parts_cost, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-5 py-2 text-right text-slate-600 text-sm">Mano de obra</td>
                            <td class="px-5 py-2 text-right font-medium">C$ {{ number_format($order->labor_cost, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-bold text-slate-900">TOTAL</td>
                            <td class="px-5 py-3 text-right font-bold text-xl text-indigo-700">C$ {{ number_format($order->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="card p-5">
                <h2 class="font-semibold text-slate-700 mb-2">Resumen de Costos</h2>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between text-slate-600"><span>Mano de obra</span><span>C$ {{ number_format($order->labor_cost, 2) }}</span></div>
                    <div class="flex justify-between font-bold text-slate-900 border-t pt-1 mt-1"><span>Total</span><span class="text-indigo-700">C$ {{ number_format($order->total, 2) }}</span></div>
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div class="space-y-5">

            {{-- Quick status change --}}
            <div class="card p-5">
                <h2 class="font-semibold text-slate-700 mb-3">Actualizar Estado</h2>
                <form action="{{ route('reparaciones.status', $order->id) }}" method="POST" class="space-y-3">
                    @csrf @method('PATCH')
                    <select name="status" class="select-field text-sm">
                        @foreach(['received' => 'Recibido', 'diagnosing' => 'En Diagnóstico', 'waiting_parts' => 'Esperando Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo para Entregar', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full btn-secondary text-sm py-2">Actualizar</button>
                </form>
            </div>

            {{-- Warranty --}}
            <div class="card p-5 {{ $order->warranty_enabled ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 border border-slate-200' }}">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold {{ $order->warranty_enabled ? 'text-emerald-800' : 'text-slate-700' }}">Garantía</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $order->warranty_enabled ? 'bg-emerald-200 text-emerald-800 font-semibold' : 'bg-slate-200 text-slate-600' }}">
                        {{ $order->warranty_enabled ? 'INCLUIDA' : 'NO INCLUIDA' }}
                    </span>
                </div>
                @if($order->warranty_enabled)
                <p class="text-xs leading-relaxed {{ $order->warranty_enabled ? 'text-emerald-900' : 'text-slate-700' }} whitespace-pre-line">{{ $order->effectiveWarrantyText() }}</p>
                @else
                <p class="text-xs text-slate-500 italic">La garantía no fue incluida en el ticket para esta orden.</p>
                @endif
            </div>

            {{-- Payment summary --}}
            <div class="card p-5 bg-indigo-50 border border-indigo-100">
                <h2 class="font-semibold text-indigo-800 mb-3">Pagos</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-slate-700">
                        <span>Total</span>
                        <span class="font-bold">C$ {{ number_format($order->total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-700">
                        <span>Anticipo</span>
                        <span class="text-emerald-700 font-medium">-C$ {{ number_format($order->advance_payment, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-900 border-t border-indigo-200 pt-2">
                        <span>Saldo</span>
                        <span class="{{ $order->balance() > 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($order->balance(), 2) }}</span>
                    </div>
                    <div class="mt-2">
                        @php
                            $payColors = ['pending' => 'badge-warning', 'partial' => 'badge-info', 'paid' => 'badge-success'];
                            $payLabels = ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'];
                        @endphp
                        <span class="{{ $payColors[$order->payment_status] ?? 'badge-info' }}">{{ $payLabels[$order->payment_status] ?? $order->payment_status }}</span>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="card p-5 space-y-3 text-sm">
                <h2 class="font-semibold text-slate-700 border-b border-slate-100 pb-2">Información</h2>
                @if($order->technician)
                <div class="flex justify-between"><span class="text-slate-500">Técnico</span><span class="font-medium">{{ $order->technician->name }}</span></div>
                @endif
                @if($order->user)
                <div class="flex justify-between"><span class="text-slate-500">Atendido por</span><span class="font-medium">{{ $order->user->name }}</span></div>
                @endif
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Recepción</span>
                    <span class="text-right">
                        <span>{{ $order->received_date->format('d/m/Y') }}</span>
                        @if($order->formattedReceivedTime())<br><span class="font-medium">{{ $order->formattedReceivedTime() }}</span>@endif
                    </span>
                </div>
                @if($order->estimated_date || $order->estimated_delivery_time)
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Est. entrega</span>
                    <span class="text-right">
                        @if($order->estimated_date)<span>{{ $order->estimated_date->format('d/m/Y') }}</span>@endif
                        @if($order->estimated_date && $order->formattedEstimatedDeliveryTime())<br>@endif
                        @if($order->formattedEstimatedDeliveryTime())<span class="font-medium">{{ $order->formattedEstimatedDeliveryTime() }}</span>@endif
                    </span>
                </div>
                @endif
                @if($order->delivered_date || $order->delivered_time)
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Entregado</span>
                    <span class="text-right text-emerald-700">
                        @if($order->delivered_date)<span>{{ $order->delivered_date->format('d/m/Y') }}</span>@endif
                        @if($order->delivered_date && $order->formattedDeliveredTime())<br>@endif
                        @if($order->formattedDeliveredTime())<span class="font-medium">{{ $order->formattedDeliveredTime() }}</span>@endif
                    </span>
                </div>
                @endif
                @php
                    $lockType = $order->lock_type ?? ($order->device_password ? (preg_match('/^[1-9](?:-[1-9])*$/', $order->device_password) ? 'pattern' : 'password') : 'none');
                @endphp
                @if($order->device_password && $lockType === 'pattern')
                <div class="space-y-2">
                    <span class="text-slate-500">Patrón</span>
                    <x-pattern-viewer :pattern="$order->device_password" />
                </div>
                @elseif($order->device_password)
                <div class="flex justify-between"><span class="text-slate-500">Contraseña</span><span class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $order->device_password }}</span></div>
                @endif
            </div>

            {{-- Delete --}}
            <form action="{{ route('reparaciones.destroy', $order->id) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar esta orden de reparación?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium py-2.5 rounded-xl border border-red-200">
                    Eliminar Orden
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
