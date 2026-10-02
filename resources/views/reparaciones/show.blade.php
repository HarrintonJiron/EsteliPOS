@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Orden ' . $order->order_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <a href="{{ route('reparaciones.index') }}" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="break-all text-2xl font-bold text-slate-900">{{ $order->order_number }}</h1>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->priorityColor() }}">{{ $order->priorityLabel() }}</span>
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->paymentStateColor() }}">{{ $order->paymentStateLabel() }}</span>
                </div>
                @if($order->isDeliveredWithBalance())
                    <p class="mt-2 inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700">
                        Entregada sin pagar completo · debe {{ $currencySymbol }} {{ number_format($order->balance(), 2) }}
                    </p>
                @endif
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
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reparaciones.ticket', $order->id) }}" target="print-window" class="btn-outline text-sm">Ticket</a>
            <a href="{{ route('reparaciones.pdf', $order->id) }}" target="print-window" class="btn-outline text-sm">PDF</a>
            <a href="{{ route('reparaciones.edit', $order->id) }}" class="btn-secondary text-sm">Editar</a>
        </div>
    </div>

    @if(session('success'))
        <div class="card p-3 bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-5 lg:col-span-2">

            {{-- Client & Device --}}
            <div class="card grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">
                @php
                    $gallery = collect();
                    if ($order->device_photo_url) {
                        $gallery->push($order->device_photo_url);
                    }
                    foreach ($order->photos as $orderPhoto) {
                        $gallery->push($orderPhoto->url);
                    }
                @endphp
                @if($gallery->isNotEmpty())
                <div class="sm:col-span-2" id="repairGallery">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase text-slate-500">Fotos del equipo</p>
                        <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800">{{ $gallery->count() }} de {{ \App\Models\RepairOrder::MAX_PHOTOS }}</span>
                    </div>
                    <a id="repairGalleryMainLink" href="{{ $gallery->first() }}" data-lightbox data-lightbox-list='@json($gallery->values())' class="flex h-80 items-center justify-center overflow-hidden rounded-2xl border-2 border-sky-200 bg-sky-50">
                        <img id="repairGalleryMain" src="{{ $gallery->first() }}" alt="Foto del equipo {{ $order->device_brand }} {{ $order->device_model }}" class="h-full w-full object-contain">
                    </a>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($gallery as $url)
                            <button type="button" data-repair-thumb data-url="{{ $url }}" aria-label="Ver foto {{ $loop->iteration }}"
                                    class="relative h-16 w-16 overflow-hidden rounded-xl border-2 bg-sky-50 transition {{ $loop->first ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-sky-200 hover:border-sky-400' }}">
                                <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                                <span class="absolute bottom-0 right-0 rounded-tl-md bg-white/90 px-1 text-[10px] font-bold text-slate-600">{{ $loop->iteration }}</span>
                            </button>
                        @endforeach
                        @for($slot = $gallery->count() + 1; $slot <= \App\Models\RepairOrder::MAX_PHOTOS; $slot++)
                            <div aria-hidden="true" class="flex h-16 w-16 flex-col items-center justify-center rounded-xl border-2 border-dashed border-sky-200 bg-sky-50/60 text-sky-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h1.5l1-1.5h9l1 1.5H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3.2" stroke-width="1.8"/></svg>
                                <span class="text-[10px] font-semibold">{{ $slot }}</span>
                            </div>
                        @endfor
                    </div>
                    <script>
                        document.querySelectorAll('#repairGallery [data-repair-thumb]').forEach(function (thumb) {
                            thumb.addEventListener('click', function () {
                                document.getElementById('repairGalleryMain').src = thumb.dataset.url;
                                document.getElementById('repairGalleryMainLink').href = thumb.dataset.url;
                                document.querySelectorAll('#repairGallery [data-repair-thumb]').forEach(function (other) {
                                    const active = other === thumb;
                                    ['border-indigo-500', 'ring-2', 'ring-indigo-200'].forEach(function (c) { other.classList.toggle(c, active); });
                                    ['border-sky-200', 'hover:border-sky-400'].forEach(function (c) { other.classList.toggle(c, !active); });
                                });
                            });
                        });
                    </script>
                </div>
                @endif
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
                    @if($order->device_battery !== null)<p class="text-sm text-slate-600">Batería: {{ $order->device_battery }}%</p>@endif
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
                            <td class="px-4 py-3 text-right text-slate-600">@money($item->price, 2)</td>
                            <td class="px-5 py-3 text-right font-semibold">@money($item->subtotal, 2)</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="px-5 py-2 text-right text-slate-600 text-sm">Repuestos</td>
                            <td class="px-5 py-2 text-right font-medium">@money($order->parts_cost, 2)</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-5 py-2 text-right text-slate-600 text-sm">Mano de obra</td>
                            <td class="px-5 py-2 text-right font-medium">@money($order->labor_cost, 2)</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-bold text-slate-900">TOTAL</td>
                            <td class="px-5 py-3 text-right font-bold text-xl text-indigo-700">@money($order->total, 2)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="card p-5">
                <h2 class="font-semibold text-slate-700 mb-2">Resumen de Costos</h2>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between text-slate-600"><span>Mano de obra</span><span>@money($order->labor_cost, 2)</span></div>
                    <div class="flex justify-between font-bold text-slate-900 border-t pt-1 mt-1"><span>Total</span><span class="text-indigo-700">@money($order->total, 2)</span></div>
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
                        @foreach(['received' => 'Recibido', 'diagnosing' => 'En Diagnóstico', 'waiting_parts' => 'Esperando Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo para Entregar', 'delivered' => 'Entregado', 'not_repaired' => 'No reparado', 'cancelled' => 'Cancelado'] as $val => $label)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="deliveryNotesBox" class="{{ $order->status === 'delivered' ? '' : 'hidden' }}">
                        <label for="delivery_notes_status" class="block text-xs font-semibold text-slate-600 mb-1">Detalles del equipo al entregar <span class="font-normal text-slate-400">(opcional)</span></label>
                        <textarea id="delivery_notes_status" name="delivery_notes" rows="3" maxlength="2000"
                                  placeholder="Ej.: Se entrega con pantalla nueva, sin rayones, con cargador. Cliente revisó y recibe conforme."
                                  class="input-field text-sm">{{ old('delivery_notes', $order->delivery_notes) }}</textarea>
                        <p class="mt-1 text-[11px] text-slate-400">Puedes dejarlo vacío. Si escribes algo, saldrá en el ticket y el PDF como constancia de entrega.</p>
                    </div>
                    <button type="submit" class="w-full btn-secondary text-sm py-2">Actualizar</button>
                </form>
                <script>
                    (function () {
                        const select = document.querySelector('form[action="{{ route('reparaciones.status', $order->id) }}"] select[name="status"]');
                        const box = document.getElementById('deliveryNotesBox');
                        if (!select || !box) return;
                        select.addEventListener('change', function () { box.classList.toggle('hidden', select.value !== 'delivered'); });
                    })();
                </script>
            </div>

            @if($order->canCollect())
            <div class="card p-4 {{ $order->isDeliveredWithBalance() ? 'border border-red-200 bg-red-50' : 'border border-emerald-200 bg-emerald-50' }}" id="collectCard">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase {{ $order->isDeliveredWithBalance() ? 'text-red-700' : 'text-emerald-700' }}">
                            {{ $order->status === 'ready' ? 'Listo para entregar' : 'Entregada con saldo' }}
                        </p>
                        <p class="text-lg font-black text-slate-900">Saldo {{ $currencySymbol }} {{ number_format($order->balance(), 2) }}</p>
                    </div>
                    <button type="button" id="openCollect" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-emerald-700">Cobrar</button>
                </div>
            </div>

            <div id="collectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="collectTitle">
                <form method="POST" action="{{ route('reparaciones.collect', $order->id) }}" id="collectForm"
                      class="w-full max-w-sm space-y-3 rounded-2xl bg-white p-4 shadow-2xl"
                      data-balance="{{ number_format($order->balance(), 2, '.', '') }}">
                    @csrf
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 id="collectTitle" class="font-bold text-slate-900">Cobrar {{ $order->order_number }}</h3>
                            <p class="text-xs text-slate-500">{{ $order->client_name }}</p>
                        </div>
                        <button type="button" data-close-collect class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Cerrar">&times;</button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-2 text-center text-xs">
                        <div><p class="text-slate-500">Total</p><p class="font-bold">{{ $currencySymbol }} {{ number_format($order->total, 2) }}</p></div>
                        <div><p class="text-slate-500">Pagado</p><p class="font-bold text-emerald-700">{{ $currencySymbol }} {{ number_format($order->paidAmount(), 2) }}</p></div>
                        <div><p class="text-slate-500">Saldo</p><p class="font-bold text-red-600">{{ $currencySymbol }} {{ number_format($order->balance(), 2) }}</p></div>
                    </div>

                    <input type="hidden" name="method" id="collectMethod" value="cash">
                    <div class="grid {{ $order->payment_type === 'credit' ? 'grid-cols-3' : 'grid-cols-4' }} gap-1.5" id="collectMethods">
                        <button type="button" data-method="cash" class="collect-method rounded-lg border-2 px-1 py-2 text-xs font-bold">Efectivo</button>
                        <button type="button" data-method="card" class="collect-method rounded-lg border-2 px-1 py-2 text-xs font-bold">Tarjeta</button>
                        <button type="button" data-method="transfer" class="collect-method rounded-lg border-2 px-1 py-2 text-xs font-bold">Transf.</button>
                        @if($order->payment_type !== 'credit')
                        <button type="button" data-method="credit" class="collect-method rounded-lg border-2 px-1 py-2 text-xs font-bold">Crédito</button>
                        @endif
                    </div>

                    <div id="collectPayBox" class="space-y-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600" for="collectAmount">Monto a cobrar</label>
                            <input type="number" step="0.01" min="0.01" max="{{ number_format($order->balance(), 2, '.', '') }}" name="amount" id="collectAmount"
                                   value="{{ number_format($order->balance(), 2, '.', '') }}" class="input-field text-sm">
                            <p class="mt-0.5 text-[11px] text-slate-400">Si cobras menos que el saldo, queda como abono parcial.</p>
                        </div>
                        <div id="collectCashBox" class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600" for="collectReceived">Recibido</label>
                                <input type="number" step="0.01" min="0" name="received" id="collectReceived" class="input-field text-sm" placeholder="0.00">
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-600">Cambio</p>
                                <p id="collectChange" class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-800">{{ $currencySymbol }} 0.00</p>
                            </div>
                        </div>
                        <div id="collectRefBox" class="hidden">
                            <label class="block text-xs font-semibold text-slate-600" for="collectRef">Referencia (opcional)</label>
                            <input type="text" name="reference_number" id="collectRef" maxlength="100" class="input-field text-sm" placeholder="N.º de autorización o transferencia">
                        </div>
                    </div>

                    @if($order->payment_type !== 'credit')
                    <div id="collectCreditBox" class="hidden space-y-2 rounded-xl border border-violet-200 bg-violet-50 p-3">
                        <p class="text-xs text-violet-800">Se entrega el equipo y el saldo de <strong>{{ $currencySymbol }} {{ number_format($order->balance(), 2) }}</strong> queda como deuda del cliente.</p>
                        @if($order->client)
                            <p class="text-xs font-semibold text-slate-700">Cliente: {{ $order->client->name }}
                                @if(! $order->client->credit_enabled)<span class="text-red-600"> · sin crédito habilitado</span>@endif
                            </p>
                        @else
                            <div>
                                <label class="block text-xs font-semibold text-slate-600" for="collectClient">Cliente con crédito</label>
                                <select name="client_id" id="collectClient" class="select-field text-sm">
                                    <option value="">— Elige un cliente —</option>
                                    @foreach($creditClients as $creditClient)
                                        <option value="{{ $creditClient->id }}">{{ $creditClient->name }}{{ $creditClient->phone ? ' · '.$creditClient->phone : '' }}</option>
                                    @endforeach
                                </select>
                                @if($creditClients->isEmpty())<p class="mt-1 text-[11px] text-red-600">No hay clientes con crédito habilitado. Actívalo en el módulo de clientes.</p>@endif
                            </div>
                        @endif
                        <div>
                            <label class="block text-xs font-semibold text-slate-600" for="collectDue">Fecha límite de pago</label>
                            <input type="date" name="due_date" id="collectDue" value="{{ $defaultDueDate }}" min="{{ now()->toDateString() }}" class="input-field text-sm">
                        </div>
                    </div>
                    @endif

                    @if($order->status === 'ready')
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="mark_delivered" value="1" checked class="rounded border-slate-300 text-emerald-600">
                        Marcar como entregada
                    </label>
                    @endif

                    <div class="flex gap-2 pt-1">
                        <button type="button" data-close-collect class="flex-1 rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancelar</button>
                        <button type="submit" id="collectSubmit" class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700">Cobrar</button>
                    </div>
                </form>
            </div>
            <script>
                (function () {
                    const modal = document.getElementById('collectModal');
                    const form = document.getElementById('collectForm');
                    if (!modal || !form) return;

                    const symbol = @json($currencySymbol);
                    const balance = parseFloat(form.dataset.balance);
                    const method = document.getElementById('collectMethod');
                    const amount = document.getElementById('collectAmount');
                    const received = document.getElementById('collectReceived');
                    const change = document.getElementById('collectChange');
                    const payBox = document.getElementById('collectPayBox');
                    const cashBox = document.getElementById('collectCashBox');
                    const refBox = document.getElementById('collectRefBox');
                    const creditBox = document.getElementById('collectCreditBox');
                    const submit = document.getElementById('collectSubmit');
                    const buttons = form.querySelectorAll('.collect-method');

                    function paintMethods() {
                        buttons.forEach(function (btn) {
                            const active = btn.dataset.method === method.value;
                            btn.classList.toggle('border-emerald-600', active);
                            btn.classList.toggle('bg-emerald-50', active);
                            btn.classList.toggle('text-emerald-700', active);
                            btn.classList.toggle('border-slate-200', !active);
                            btn.classList.toggle('text-slate-600', !active);
                        });
                    }

                    function refresh() {
                        const isCredit = method.value === 'credit';
                        payBox.classList.toggle('hidden', isCredit);
                        if (creditBox) creditBox.classList.toggle('hidden', !isCredit);
                        cashBox.classList.toggle('hidden', method.value !== 'cash');
                        refBox.classList.toggle('hidden', !(method.value === 'card' || method.value === 'transfer'));
                        amount.disabled = isCredit;
                        received.disabled = method.value !== 'cash';
                        submit.textContent = isCredit ? 'Dar a crédito' : 'Cobrar ' + symbol + ' ' + (parseFloat(amount.value) || 0).toFixed(2);

                        const due = (parseFloat(amount.value) || 0);
                        const got = parseFloat(received.value);
                        change.textContent = symbol + ' ' + (isNaN(got) ? 0 : Math.max(0, got - due)).toFixed(2);
                        paintMethods();
                    }

                    buttons.forEach(function (btn) {
                        btn.addEventListener('click', function () { method.value = btn.dataset.method; refresh(); });
                    });
                    amount.addEventListener('input', refresh);
                    received.addEventListener('input', refresh);

                    function open() { modal.classList.remove('hidden'); modal.classList.add('flex'); refresh(); amount.focus(); amount.select(); }
                    function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

                    document.getElementById('openCollect').addEventListener('click', open);
                    modal.querySelectorAll('[data-close-collect]').forEach(function (el) { el.addEventListener('click', close); });
                    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
                    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });

                    form.addEventListener('submit', function () { submit.disabled = true; });
                    refresh();
                })();
            </script>
            @endif

            @if($order->delivery_notes)
            <div class="card p-5 bg-sky-50 border border-sky-200">
                <h2 class="font-semibold text-sky-900 mb-2">Detalles del equipo al entregar</h2>
                <p class="text-sm text-sky-950 whitespace-pre-line">{{ $order->delivery_notes }}</p>
                @if($order->delivered_date)
                    <p class="mt-2 text-xs text-sky-700">Entregado el {{ $order->delivered_date->format($companyProfile['date_format']) }}@if($order->formattedDeliveredTime()) · {{ $order->formattedDeliveredTime() }}@endif</p>
                @endif
            </div>
            @endif

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
                        <span class="font-bold">@money($order->total, 2)</span>
                    </div>
                    <div class="flex justify-between text-slate-700">
                        <span>Anticipo</span>
                        <span class="text-emerald-700 font-medium">-@money($order->advance_payment, 2)</span>
                    </div>
                    @foreach($order->creditPayments as $payment)
                    <div class="flex justify-between text-slate-700">
                        <span>{{ $payment->payment_date->format($companyProfile['date_format']) }} · {{ ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'check' => 'Cheque'][$payment->payment_type] ?? 'Otro' }}</span>
                        <span class="text-emerald-700 font-medium">-@money($payment->amount, 2)</span>
                    </div>
                    @endforeach                    <div class="flex justify-between font-bold text-slate-900 border-t border-indigo-200 pt-2">
                        <span>Saldo</span>
                        <span class="{{ $order->balance() > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $currencySymbol }} {{ number_format($order->balance(), 2) }}</span>
                    </div>
                    <div class="mt-2">
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $order->paymentStateColor() }}">{{ $order->paymentStateLabel() }}</span>
                        @if($order->payment_type === 'credit' && $order->due_date && $order->balance() > 0)
                            <span class="ml-1 text-xs text-slate-500">vence {{ $order->due_date->format($companyProfile['date_format']) }}</span>
                        @endif
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
                @if($order->device_password)
                <details class="rounded-lg border border-amber-200 bg-amber-50 p-2">
                    <summary class="cursor-pointer text-xs font-semibold text-amber-800">Mostrar acceso del dispositivo</summary>
                    @if($lockType === 'pattern')
                        <div class="mt-2"><x-pattern-viewer :pattern="$order->device_password" /></div>
                    @else
                        <p class="mt-2 font-mono text-xs text-slate-800">{{ $order->device_password }}</p>
                    @endif
                    <p class="mt-2 text-[11px] text-amber-700">Información confidencial. No compartir ni imprimir.</p>
                </details>
                @endif
            </div>

            {{-- Delete --}}
            @if($order->payment_type === 'credit')
            <div class="card p-5 space-y-3"><h2 class="font-semibold text-slate-700">Crédito de reparación</h2><div class="flex justify-between"><span>Vence</span><strong>{{ $order->due_date?->format('d/m/Y') ?? '—' }}</strong></div><div class="flex justify-between"><span>Saldo actual</span><strong>{{ $currencySymbol }} {{ number_format($order->balance(),2) }}</strong></div>
            @if($order->client_id)
            <div class="flex flex-wrap gap-x-4 gap-y-1 rounded-lg bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800">
                <a class="hover:underline" href="{{ route('creditos.show', $order->client_id) }}">Ver cuenta de crédito del cliente</a>
                @if($order->balance() > 0)<a class="hover:underline" href="{{ route('creditos.create', ['clientId' => $order->client_id, 'apply_to' => 'repair:'.$order->id]) }}">Abonar desde Créditos</a>@endif
            </div>
            @endif
            @if($order->balance() > 0)
            <form method="POST" action="{{ route('reparaciones.credit-payments.store',$order) }}" class="space-y-2">@csrf<input class="input-field" type="number" name="amount" min="0.01" max="{{ $order->balance() }}" step="0.01" required placeholder="Monto del abono"><select name="payment_type" class="select-field"><option value="cash">Efectivo</option><option value="transfer">Transferencia</option><option value="check">Cheque</option><option value="other">Otro</option></select><input class="input-field" name="reference_number" placeholder="Referencia (opcional)"><button class="btn-primary w-full">Registrar abono</button></form>
            @endif
            @if($order->creditPayments->isNotEmpty())<div class="border-t pt-2 text-xs space-y-1">@foreach($order->creditPayments as $payment)<div class="flex justify-between"><span>{{ $payment->payment_date->format('d/m/Y') }}</span><span><strong>@money($payment->amount,2)</strong> <a class="ml-1 text-slate-400 hover:text-slate-700" target="print-window" href="{{ route('creditos.repair-receipt', $payment->id) }}">Recibo</a></span></div>@endforeach</div>@endif</div>
            @endif

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
