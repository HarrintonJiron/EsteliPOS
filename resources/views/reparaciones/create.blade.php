@extends('layouts.app')

@section('title', 'Nueva Orden de Reparación')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Nueva Orden de Reparación</h1>
            <p class="page-subtitle">Registro de ingreso de equipo al taller</p>
        </div>
        <a href="{{ route('reparaciones.index') }}" class="btn-outline text-sm">← Volver</a>
    </div>

    @if($errors->any())
    <div class="card p-4 bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('reparaciones.store') }}" method="POST" id="repairForm">
        @csrf

        <div class="grid grid-cols-3 gap-5">

            {{-- LEFT: 2/3 --}}
            <div class="col-span-2 space-y-5">

                {{-- CLIENT --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Datos del Cliente</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm text-slate-600 mb-1">Buscar cliente existente</label>
                            <select id="client_selector" onchange="fillClient(this)"
                                class="select-field">
                                <option value="">— Cliente nuevo / sin registro —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-phone="{{ $c->phone }}"
                                        data-email="{{ $c->email ?? '' }}">
                                        {{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="client_id" id="client_id_input">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Nombre *</label>
                            <input type="text" name="client_name" value="{{ old('client_name') }}" required class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
                            <input type="text" name="client_phone" value="{{ old('client_phone') }}" class="input-field">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm text-slate-600 mb-1">Email</label>
                            <input type="email" name="client_email" value="{{ old('client_email') }}" class="input-field">
                        </div>
                    </div>
                </div>

                {{-- DEVICE --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Datos del Equipo</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Marca *</label>
                            <input type="text" name="device_brand" value="{{ old('device_brand') }}" required
                                list="brands_list" class="input-field" placeholder="Samsung, iPhone, Xiaomi...">
                            <datalist id="brands_list">
                                @foreach(['Samsung','Apple','Xiaomi','Huawei','Motorola','LG','Sony','Nokia','OPPO','Realme','OnePlus','Tecno','ZTE'] as $b)
                                    <option value="{{ $b }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Modelo *</label>
                            <input type="text" name="device_model" value="{{ old('device_model') }}" required class="input-field" placeholder="Galaxy A54, iPhone 13...">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Color</label>
                            <input type="text" name="device_color" value="{{ old('device_color') }}" class="input-field" placeholder="Negro, Blanco...">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">IMEI / Serie</label>
                            <input type="text" name="device_imei" value="{{ old('device_imei') }}" class="input-field" placeholder="15 dígitos">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Contraseña / Patrón</label>
                            <input type="text" name="device_password" value="{{ old('device_password') }}" class="input-field" placeholder="PIN, patrón, etc.">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Accesorios entregados</label>
                            <input type="text" name="accessories" value="{{ old('accessories') }}" class="input-field" placeholder="Cargador, funda, caja...">
                        </div>
                    </div>
                </div>

                {{-- DIAGNOSIS --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Diagnóstico</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Falla reportada por cliente *</label>
                        <textarea name="problem_description" rows="3" required class="input-field resize-none"
                            placeholder="Descripción del problema según el cliente...">{{ old('problem_description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Diagnóstico técnico</label>
                        <textarea name="diagnosis" rows="3" class="input-field resize-none"
                            placeholder="Lo que se encontró al revisar el equipo...">{{ old('diagnosis') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Notas internas del técnico</label>
                        <textarea name="repair_notes" rows="2" class="input-field resize-none"
                            placeholder="Observaciones internas, procedimientos, etc.">{{ old('repair_notes') }}</textarea>
                    </div>
                </div>

                {{-- PARTS / REPUESTOS --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="font-semibold text-slate-800">Repuestos / Materiales</h2>
                        <button type="button" onclick="addItem()" class="btn-primary text-xs py-1.5">+ Agregar</button>
                    </div>
                    <div id="itemsContainer" class="space-y-3">
                        {{-- items injected by JS --}}
                    </div>
                    <p id="noItemsMsg" class="text-sm text-slate-400 text-center py-4">No se han agregado repuestos. Haz clic en "+ Agregar".</p>
                </div>

            </div>

            {{-- RIGHT: 1/3 --}}
            <div class="space-y-5">

                {{-- STATUS & PRIORITY --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Estado y Prioridad</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Estado</label>
                        <select name="status" class="select-field">
                            @foreach(['received' => 'Recibido', 'diagnosing' => 'En Diagnóstico', 'waiting_parts' => 'Esperando Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
                                <option value="{{ $val }}" {{ old('status', 'received') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Prioridad</label>
                        <select name="priority" class="select-field">
                            @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                <option value="{{ $val }}" {{ old('priority', 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Técnico asignado</label>
                        <select name="technician_id" class="select-field">
                            <option value="">Sin asignar</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}" {{ old('technician_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- DATES --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Fechas</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Fecha de recepción *</label>
                        <input type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Entrega estimada</label>
                        <input type="date" name="estimated_date" value="{{ old('estimated_date') }}" class="input-field">
                    </div>
                </div>

                {{-- PAYMENT --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Costos y Pago</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Mano de obra (C$)</label>
                        <input type="number" step="0.01" min="0" name="labor_cost"
                            value="{{ old('labor_cost', 0) }}" class="input-field" id="laborCostInput"
                            oninput="updateTotal()">
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 space-y-1 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Repuestos</span>
                            <span id="partsCostDisplay">C$ 0.00</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Mano de obra</span>
                            <span id="laborDisplay">C$ 0.00</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1">
                            <span>Total</span>
                            <span id="totalDisplay" class="text-indigo-700 text-base">C$ 0.00</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Anticipo recibido (C$)</label>
                        <input type="number" step="0.01" min="0" name="advance_payment"
                            value="{{ old('advance_payment', 0) }}" class="input-field" id="advanceInput"
                            oninput="updateTotal()">
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-sm">
                        <div class="flex justify-between font-bold text-amber-800">
                            <span>Saldo pendiente</span>
                            <span id="balanceDisplay">C$ 0.00</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Método de pago</label>
                        <select name="payment_type" class="select-field">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full btn-primary justify-center py-3 text-base">
                    Guardar Orden
                </button>
            </div>
        </div>
    </form>
</div>

@php
    $productsJson = $products->map(function($p) {
        return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'price' => (float)$p->sale_price];
    })->values()->toJson();
@endphp
<script>
// Products data for parts selection
const productsData = {!! $productsJson !!};

let itemIndex = 0;
let partsCost = 0;

function fillClient(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.querySelector('[name=client_name]').value = opt.dataset.name ?? '';
    document.querySelector('[name=client_phone]').value = opt.dataset.phone ?? '';
    document.querySelector('[name=client_email]').value = opt.dataset.email ?? '';
    document.getElementById('client_id_input').value = opt.value || '';
}

function fmt(v) { return 'C$ ' + parseFloat(v || 0).toFixed(2); };

function addItem(desc = '', qty = 1, price = 0, productId = '') {
    document.getElementById('noItemsMsg').classList.add('hidden');
    const idx = itemIndex++;
    const opts = productsData.map(p =>
        `<option value="${p.id}" data-price="${p.price}" ${productId == p.id ? 'selected' : ''}>${p.name} (${p.code})</option>`
    ).join('');

    const html = `
    <div class="item-row flex gap-2 items-start bg-slate-50 rounded-xl p-3" data-idx="${idx}">
        <div class="flex-1 space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <div class="col-span-2">
                    <label class="text-xs text-slate-500">Repuesto / Descripción *</label>
                    <input type="text" name="items[${idx}][description]" value="${desc}" required
                        class="input-field text-sm" placeholder="Pantalla, batería, conector...">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Vinc. a producto</label>
                    <select name="items[${idx}][product_id]" class="select-field text-sm part-product-sel" data-idx="${idx}"
                        onchange="onProductSelect(this, ${idx})">
                        <option value="">— Manual —</option>
                        ${opts}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-500">Cant.</label>
                        <input type="number" name="items[${idx}][quantity]" value="${qty}" min="0.01" step="0.01" required
                            class="input-field text-sm item-qty" data-idx="${idx}" oninput="calcItemSubtotal(${idx})">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Precio</label>
                        <input type="number" name="items[${idx}][price]" value="${price}" min="0" step="0.01" required
                            class="input-field text-sm item-price" data-idx="${idx}" oninput="calcItemSubtotal(${idx})">
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right shrink-0 pt-5">
            <p class="text-xs text-slate-500">Subtotal</p>
            <p class="font-bold text-slate-900 text-sm item-subtotal" data-idx="${idx}">${fmt(qty * price)}</p>
            <button type="button" onclick="removeItem(this)" class="text-xs text-red-500 hover:text-red-700 mt-1">Quitar</button>
        </div>
    </div>`;

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    recalcParts();
}

function onProductSelect(sel, idx) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        const priceInput = document.querySelector(`.item-price[data-idx="${idx}"]`);
        priceInput.value = opt.dataset.price ?? 0;
        calcItemSubtotal(idx);
    }
}

function calcItemSubtotal(idx) {
    const qty   = parseFloat(document.querySelector(`.item-qty[data-idx="${idx}"]`)?.value || 0);
    const price = parseFloat(document.querySelector(`.item-price[data-idx="${idx}"]`)?.value || 0);
    const sub   = qty * price;
    const el    = document.querySelector(`.item-subtotal[data-idx="${idx}"]`);
    if (el) el.textContent = fmt(sub);
    recalcParts();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    if (document.querySelectorAll('.item-row').length === 0) {
        document.getElementById('noItemsMsg').classList.remove('hidden');
    }
    recalcParts();
}

function recalcParts() {
    partsCost = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const idx   = row.dataset.idx;
        const qty   = parseFloat(document.querySelector(`.item-qty[data-idx="${idx}"]`)?.value || 0);
        const price = parseFloat(document.querySelector(`.item-price[data-idx="${idx}"]`)?.value || 0);
        partsCost  += qty * price;
    });
    document.getElementById('partsCostDisplay').textContent = fmt(partsCost);
    updateTotal();
}

function updateTotal() {
    const labor   = parseFloat(document.getElementById('laborCostInput').value || 0);
    const advance = parseFloat(document.getElementById('advanceInput').value || 0);
    const total   = labor + partsCost;
    const balance = Math.max(0, total - advance);
    document.getElementById('laborDisplay').textContent   = fmt(labor);
    document.getElementById('totalDisplay').textContent   = fmt(total);
    document.getElementById('balanceDisplay').textContent = fmt(balance);
}
</script>
@endsection
