@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Editar Orden ' . $order->order_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="page-title">Editar {{ $order->order_number }}</h1>
            <p class="page-subtitle">{{ $order->device_brand }} {{ $order->device_model }} · {{ $order->client_name }}</p>
        </div>
        <a href="{{ route('reparaciones.show', $order->id) }}" class="btn-outline text-sm">← Volver</a>
    </div>

    @if($errors->any())
    <div class="card p-4 bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('reparaciones.update', $order->id) }}" method="POST" id="repairForm">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- LEFT --}}
            <div class="space-y-5 lg:col-span-2">

                {{-- CLIENT --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Datos del Cliente</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-slate-600 mb-1">Buscar cliente existente</label>
                            <select id="client_selector" onchange="fillClient(this)" class="select-field">
                                <option value="">— Cliente nuevo / sin registro —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" data-email="{{ $c->email ?? '' }}"
                                        {{ old('client_id', $order->client_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="client_id" id="client_id_input" value="{{ old('client_id', $order->client_id) }}">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Nombre *</label>
                            <input type="text" name="client_name" value="{{ old('client_name', $order->client_name) }}" required class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
                            <input type="text" name="client_phone" value="{{ old('client_phone', $order->client_phone) }}" class="input-field">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-slate-600 mb-1">Email</label>
                            <input type="email" name="client_email" value="{{ old('client_email', $order->client_email) }}" class="input-field">
                        </div>
                    </div>
                </div>

                {{-- DEVICE --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Datos del Equipo</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Marca *</label>
                            <div class="flex gap-2">
                                <input type="text" name="device_brand" value="{{ old('device_brand', $order->device_brand) }}" required 
                                    list="brands_list" class="input-field flex-1" placeholder="Selecciona o escribe una marca..." autocomplete="off">
                                <button type="button" onclick="showAddBrandModal()" class="px-3 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 text-sm flex-shrink-0" title="Agregar nueva marca">
                                    +
                                </button>
                            </div>
                            <datalist id="brands_list">
                                @if(isset($brands) && $brands->count() > 0)
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->name }}">
                                    @endforeach
                                @else
                                    <option value="Samsung">
                                    <option value="Apple">
                                    <option value="Xiaomi">
                                    <option value="Huawei">
                                    <option value="Motorola">
                                    <option value="LG">
                                    <option value="Sony">
                                    <option value="Nokia">
                                    <option value="OPPO">
                                    <option value="Realme">
                                    <option value="OnePlus">
                                    <option value="Tecno">
                                    <option value="ZTE">
                                @endif
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Modelo *</label>
                            <input type="text" name="device_model" value="{{ old('device_model', $order->device_model) }}" required class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Color</label>
                            <input type="text" name="device_color" value="{{ old('device_color', $order->device_color) }}" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">IMEI / Serie</label>
                            <input type="text" name="device_imei" value="{{ old('device_imei', $order->device_imei) }}" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Tipo de bloqueo</label>
                            <select id="lockTypeSelect" name="lock_type" class="select-field" onchange="toggleLockFields()">
                                <option value="password" {{ old('lock_type', $order->lock_type ?? 'none') === 'password' ? 'selected' : '' }}>Contraseña/PIN</option>
                                <option value="pattern" {{ old('lock_type', $order->lock_type ?? 'none') === 'pattern' ? 'selected' : '' }}>Patrón de desbloqueo</option>
                                <option value="none" {{ old('lock_type', $order->lock_type ?? 'none') === 'none' ? 'selected' : '' }}>Sin bloqueo</option>
                            </select>
                        </div>
                        <div id="lockPasswordContainer" class="hidden">
                            <label class="block text-sm text-slate-600 mb-1">Contraseña / PIN</label>
                            <input type="text" id="devicePasswordText" value="{{ old('device_password', $order->device_password) }}" class="input-field" oninput="syncLockValue()" placeholder="Ingrese la contraseña o PIN">
                        </div>
                        <div id="lockPatternContainer" class="hidden">
                            <label class="block text-sm text-slate-600 mb-1">Patrón de desbloqueo</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3">
                                <div id="patternPad" class="relative mx-auto grid max-w-[220px] grid-cols-3 gap-3">
                                    <svg id="patternSvg" class="pointer-events-none absolute inset-0 h-full w-full"></svg>
                                    @for($i = 1; $i <= 9; $i++)
                                        <button type="button" class="pattern-dot relative z-10 flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-300 bg-white text-lg font-semibold text-slate-600 shadow-sm transition hover:border-indigo-400 hover:shadow-md" data-point="{{ $i }}">{{ $i }}</button>
                                    @endfor
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs text-slate-500">Dibuja el patrón arrastrando sobre los puntos.</p>
                                    <button type="button" onclick="clearPattern()" class="btn-outline text-xs py-1.5">Limpiar</button>
                                </div>
                                <div class="text-xs text-slate-500">Secuencia actual: <span id="patternPreview" class="font-semibold text-slate-700">Sin patrón</span></div>
                            </div>
                            <div class="mt-4">
                                <x-pattern-viewer :pattern="old('device_password', $order->device_password)" />
                            </div>
                        </div>
                        <input type="hidden" name="device_password" id="devicePasswordHidden" value="{{ old('device_password', $order->device_password) }}">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Accesorios entregados</label>
                            <input type="text" name="accessories" value="{{ old('accessories', $order->accessories) }}" class="input-field">
                        </div>
                    </div>
                </div>

                {{-- DIAGNOSIS --}}
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Diagnóstico</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Falla reportada *</label>
                        <textarea name="problem_description" rows="3" required class="input-field resize-none">{{ old('problem_description', $order->problem_description) }}</textarea>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="text-sm text-slate-600">Diagnóstico técnico</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                                <input type="checkbox" id="diagnosisToggle" onclick="toggleOptionalField('diagnosis')"
                                    {{ old('diagnosis', $order->diagnosis) ? 'checked' : '' }}>
                                <span>Habilitar</span>
                            </label>
                        </div>
                        <textarea id="diagnosisField" name="diagnosis" rows="3" class="input-field resize-none {{ old('diagnosis', $order->diagnosis) ? '' : 'hidden' }}" {{ old('diagnosis', $order->diagnosis) ? '' : 'disabled' }}>{{ old('diagnosis', $order->diagnosis) }}</textarea>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="text-sm text-slate-600">Notas internas</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                                <input type="checkbox" id="repairNotesToggle" onclick="toggleOptionalField('repair_notes')"
                                    {{ old('repair_notes', $order->repair_notes) ? 'checked' : '' }}>
                                <span>Habilitar</span>
                            </label>
                        </div>
                        <textarea id="repair_notesField" name="repair_notes" rows="2" class="input-field resize-none {{ old('repair_notes', $order->repair_notes) ? '' : 'hidden' }}" {{ old('repair_notes', $order->repair_notes) ? '' : 'disabled' }}>{{ old('repair_notes', $order->repair_notes) }}</textarea>
                    </div>
                </div>

                {{-- PARTS --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="font-semibold text-slate-800">Repuestos / Materiales</h2>
                        <button type="button" onclick="addItem()" class="btn-primary text-xs py-1.5">+ Agregar</button>
                    </div>
                    <div id="itemsContainer" class="space-y-3"></div>
                    <p id="noItemsMsg" class="{{ $order->items->count() ? 'hidden' : '' }} text-sm text-slate-400 text-center py-4">No se han agregado repuestos.</p>
                </div>
            </div>

            {{-- RIGHT --}}
            <div class="space-y-5">
                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Estado y Prioridad</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Estado</label>
                        <select name="status" class="select-field">
                            @foreach(['received' => 'Recibido', 'diagnosing' => 'En Diagnóstico', 'waiting_parts' => 'Esperando Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $order->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Prioridad</label>
                        <select name="priority" class="select-field">
                            @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                <option value="{{ $val }}" {{ old('priority', $order->priority) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Técnico asignado</label>
                        <select name="technician_id" class="select-field">
                            <option value="">Sin asignar</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}" {{ old('technician_id', $order->technician_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Fechas y Horas</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Fecha de recepción *</label>
                        <input type="date" name="received_date" value="{{ old('received_date', $order->received_date->format('Y-m-d')) }}" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Hora de recepción *</label>
                        <input type="time" name="received_time" value="{{ old('received_time', $order->received_time ?? date('H:i')) }}" required class="input-field">
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <label class="block text-sm text-slate-600 mb-1">Entrega estimada (fecha opcional)</label>
                        <input type="date" name="estimated_date" value="{{ old('estimated_date', $order->estimated_date?->format('Y-m-d')) }}" class="input-field">
                        <p class="text-xs text-slate-400 mt-1">Si solo quieres indicar la hora, deja este campo en blanco.</p>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Hora estimada de entrega</label>
                        <input type="time" name="estimated_delivery_time" value="{{ old('estimated_delivery_time', $order->estimated_delivery_time) }}" class="input-field">
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <label class="block text-sm text-slate-600 mb-1">Fecha real de entrega</label>
                        <input type="date" name="delivered_date" value="{{ old('delivered_date', $order->delivered_date?->format('Y-m-d')) }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Hora de entrega</label>
                        <input type="time" name="delivered_time" value="{{ old('delivered_time', $order->delivered_time) }}" class="input-field">
                    </div>
                </div>

                <div class="card p-5 space-y-4">
                    <h2 class="font-semibold text-slate-800 border-b border-slate-100 pb-2">Costos y Pago</h2>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Mano de obra (C$)</label>
                        <input type="number" step="0.01" min="0" name="labor_cost" id="laborCostInput"
                            value="{{ old('labor_cost', $order->labor_cost) }}" class="input-field" oninput="updateTotal()">
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Descuento %</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount_percentage" id="discountPercentageInput"
                                value="{{ old('discount_percentage', $order->discount_percentage ?? 0) }}" class="input-field" oninput="updateTotal()">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Descuento Fijo (C$)</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="discountFixedInput"
                                value="{{ old('discount_amount', $order->discount_amount ?? 0) }}" class="input-field" oninput="updateTotal()">
                        </div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 space-y-1 text-sm">
                        <div class="flex justify-between text-slate-600"><span>Repuestos</span><span id="partsCostDisplay">C$ {{ number_format($order->parts_cost, 2) }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>Mano de obra</span><span id="laborDisplay">C$ {{ number_format($order->labor_cost, 2) }}</span></div>
                        <div class="flex justify-between text-red-600 hidden" id="discountRow">
                            <span>Descuento</span><span id="discountDisplay">C$ 0.00</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1">
                            <span>Total</span><span id="totalDisplay" class="text-indigo-700 text-base">C$ {{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Anticipo (C$)</label>
                        <input type="number" step="0.01" min="0" name="advance_payment" id="advanceInput"
                            value="{{ old('advance_payment', $order->advance_payment) }}" class="input-field" oninput="updateTotal()">
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-sm">
                        <div class="flex justify-between font-bold text-amber-800">
                            <span>Saldo</span><span id="balanceDisplay">C$ {{ number_format($order->balance(), 2) }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Método de pago</label>
                        <select name="payment_type" class="select-field">
                            @foreach(['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'] as $val => $label)
                                <option value="{{ $val }}" {{ old('payment_type', $order->payment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- WARRANTY --}}
                <div class="card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="font-semibold text-slate-800">Garantía en Ticket</h2>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="warranty_enabled" value="0">
                            <input type="checkbox" name="warranty_enabled" id="warrantyEnabled" value="1"
                                {{ old('warranty_enabled', $order->warranty_enabled ?? true) ? 'checked' : '' }}
                                onchange="toggleWarrantyText()"
                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-600">Incluir en ticket</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Texto de la garantía</label>
                        <textarea id="warrantyTextField" name="warranty_text" rows="4"
                            class="input-field resize-none text-sm"
                            placeholder="Texto de garantía a mostrar en el ticket del cliente...">{{ old('warranty_text', $order->warranty_text) }}</textarea>
                        <button type="button" onclick="loadDefaultWarranty()" class="text-xs text-indigo-600 hover:text-indigo-800 mt-1">Usar texto predeterminado</button>
                    </div>
                </div>

                <button type="submit" class="w-full btn-primary justify-center py-3 text-base">Guardar Cambios</button>
            </div>
        </div>
    </form>

    {{-- MODAL: Agregar Nueva Marca --}}
    <div id="addBrandModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Agregar Nueva Marca</h2>
                <button type="button" onclick="closeAddBrandModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5">
                <label class="block text-sm text-slate-600 mb-2">Nombre de la marca</label>
                <input type="text" id="newBrandInput" class="input-field" placeholder="Ej: Vivo, Alcatel, HTC...">
            </div>
            <div class="p-4 border-t border-slate-200 flex gap-2">
                <button type="button" onclick="closeAddBrandModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
                <button type="button" onclick="addNewBrand(this)" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-xl">Agregar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Agregar Nuevo Servicio --}}
    <div id="addServiceModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Agregar Nuevo Servicio</h2>
                <button type="button" onclick="closeAddServiceModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Nombre del servicio *</label>
                    <input type="text" id="newServiceName" class="input-field" placeholder="Ej: Cambio de cámara trasera">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Descripción</label>
                    <textarea id="newServiceDescription" class="input-field" rows="2" placeholder="Descripción detallada del servicio..."></textarea>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Precio (C$) *</label>
                    <input type="number" id="newServicePrice" class="input-field" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
            <div class="p-4 border-t border-slate-200 flex gap-2">
                <button type="button" onclick="closeAddServiceModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
                <button type="button" onclick="addNewService(this)" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-xl">Agregar</button>
            </div>
        </div>
    </div>
</div>

@php
    $productsJson = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'price' => (float) $p->effectivePrice(),
            'stock' => (float) $p->stock,
        ];
    })->values()->toJson();
    $defaultRepairWarrantyJson = json_encode($companyProfile['repair_warranty_text'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp
<script>
const productsData = {!! $productsJson !!};
const defaultRepairWarranty = {!! $defaultRepairWarrantyJson !!};
let itemIndex = 0;
let partsCost = {{ $order->parts_cost }};
let patternPoints = [];
let customBrands = [];
let isDrawingPattern = false;

function toggleLockFields() {
    const type = document.getElementById('lockTypeSelect').value;
    const passwordContainer = document.getElementById('lockPasswordContainer');
    const patternContainer = document.getElementById('lockPatternContainer');
    const hiddenInput = document.getElementById('devicePasswordHidden');
    const textInput = document.getElementById('devicePasswordText');

    passwordContainer.classList.toggle('hidden', type !== 'password');
    patternContainer.classList.toggle('hidden', type !== 'pattern');

    if (type === 'password') {
        textInput.value = hiddenInput.value || '';
        syncLockValue();
    } else if (type === 'pattern') {
        if (!patternPoints.length && hiddenInput.value) {
            setPatternFromValue(hiddenInput.value);
        }
        syncLockValue();
    } else {
        hiddenInput.value = '';
        textInput.value = '';
        clearPattern();
    }
}

function syncLockValue() {
    const type = document.getElementById('lockTypeSelect').value;
    const hiddenInput = document.getElementById('devicePasswordHidden');
    const textInput = document.getElementById('devicePasswordText');

    if (type === 'password') {
        hiddenInput.value = (textInput.value || '').trim();
    } else if (type === 'pattern') {
        hiddenInput.value = patternPoints.length ? patternPoints.join('-') : '';
    } else {
        hiddenInput.value = '';
    }
}

function clearPattern() {
    patternPoints = [];
    document.querySelectorAll('.pattern-dot').forEach(dot => dot.classList.remove('selected'));
    document.getElementById('patternPreview').textContent = 'Sin patrón';
    document.getElementById('patternSvg').innerHTML = '';
    syncLockValue();
}

function setPatternFromValue(value) {
    if (!value) {
        clearPattern();
        return;
    }

    const points = String(value).split('-').filter(Boolean);
    patternPoints = points;
    document.querySelectorAll('.pattern-dot').forEach(dot => {
        dot.classList.toggle('selected', points.includes(dot.dataset.point));
    });
    document.getElementById('patternPreview').textContent = points.join(' → ');
    renderPatternLines();
    syncLockValue();
}

function renderPatternLines() {
    const svg = document.getElementById('patternSvg');
    svg.innerHTML = '';

    if (patternPoints.length < 2) {
        return;
    }

    const pad = document.getElementById('patternPad');
    const padRect = pad.getBoundingClientRect();
    const points = patternPoints.map(point => {
        const dot = document.querySelector(`.pattern-dot[data-point="${point}"]`);
        const rect = dot.getBoundingClientRect();
        return {
            x: rect.left - padRect.left + rect.width / 2,
            y: rect.top - padRect.top + rect.height / 2,
        };
    });

    const pathData = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' ');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', pathData);
    path.setAttribute('stroke', '#4f46e5');
    path.setAttribute('stroke-width', '6');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('fill', 'none');
    svg.appendChild(path);
}

function addPatternPoint(point) {
    if (!isDrawingPattern) {
        return;
    }

    if (!patternPoints.includes(point)) {
        patternPoints.push(point);
        const dot = document.querySelector(`.pattern-dot[data-point="${point}"]`);
        if (dot) {
            dot.classList.add('selected');
        }
    }

    document.getElementById('patternPreview').textContent = patternPoints.join(' → ');
    renderPatternLines();
    syncLockValue();
}

function initPatternPad() {
    document.querySelectorAll('.pattern-dot').forEach(dot => {
        dot.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            isDrawingPattern = true;
            patternPoints = [];
            document.querySelectorAll('.pattern-dot').forEach(item => item.classList.remove('selected'));
            addPatternPoint(this.dataset.point);
        });

        dot.addEventListener('pointerenter', function () {
            if (isDrawingPattern) {
                addPatternPoint(this.dataset.point);
            }
        });
    });

    document.addEventListener('pointerup', () => {
        isDrawingPattern = false;
    });

    document.addEventListener('pointercancel', () => {
        isDrawingPattern = false;
    });
}

function fillClient(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.querySelector('[name=client_name]').value = opt.dataset.name ?? '';
    document.querySelector('[name=client_phone]').value = opt.dataset.phone ?? '';
    document.querySelector('[name=client_email]').value = opt.dataset.email ?? '';
    document.getElementById('client_id_input').value = opt.value || '';
}

function toggleOptionalField(field) {
    const checkbox = document.getElementById(field === 'diagnosis' ? 'diagnosisToggle' : 'repairNotesToggle');
    const textarea = document.getElementById(field === 'diagnosis' ? 'diagnosisField' : 'repair_notesField');
    if (!textarea) return;
    textarea.disabled = !checkbox.checked;
    textarea.classList.toggle('hidden', !checkbox.checked);
    if (!checkbox.checked) {
        textarea.value = '';
    }
}

function fmt(v) { return 'C$ ' + parseFloat(v || 0).toFixed(2); }

function addItem(desc = '', qty = 1, price = 0, productId = '', itemType = 'part', deviceBrand = '', serviceId = '') {
    document.getElementById('noItemsMsg').classList.add('hidden');
    const idx = itemIndex++;
    const opts = productsData.map(p =>
        `<option value="${p.id}" data-price="${p.price}" ${productId == p.id ? 'selected' : ''}>${p.name} (${p.code})</option>`
    ).join('');

    const servicesOpts = @json($services->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->price])->values());

    const html = `
    <div class="item-row flex gap-2 items-start bg-slate-50 rounded-xl p-3" data-idx="${idx}">
        <div class="flex-1 space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <div class="col-span-2">
                    <label class="text-xs text-slate-500">Tipo de Item</label>
                    <select name="items[${idx}][item_type]" class="select-field text-sm" onchange="toggleItemTypeFields(${idx})">
                        <option value="part" ${itemType === 'part' ? 'selected' : ''}>Repuesto</option>
                        <option value="service" ${itemType === 'service' ? 'selected' : ''}>Servicio</option>
                    </select>
                </div>
                <div class="col-span-2 service-select-field ${itemType === 'service' ? '' : 'hidden'}">
                    <label class="text-xs text-slate-500">Seleccionar Servicio Predefinido</label>
                    <div class="flex gap-2">
                        <select name="items[${idx}][service_id]" class="select-field text-sm flex-1 item-service-sel" data-idx="${idx}">
                            <option value="">— Manual —</option>
                            ${servicesOpts.map(s => `<option value="${s.id}" data-price="${s.price}" data-description="${s.name}" ${serviceId == s.id ? 'selected' : ''}>${s.name} - C$ ${s.price.toFixed(2)}</option>`).join('')}
                        </select>
                        <button type="button" onclick="showAddServiceModal()" class="px-3 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 text-xs flex-shrink-0" title="Agregar nuevo servicio">
                            +
                        </button>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-slate-500">Descripción *</label>
                    <input type="text" name="items[${idx}][description]" value="${desc}" required class="input-field text-sm" placeholder="Pantalla, batería, cambio de pantalla...">
                </div>
                <div class="col-span-2 service-brand-field ${itemType === 'service' ? '' : 'hidden'}">
                    <label class="text-xs text-slate-500">Marca del Dispositivo (para servicios)</label>
                    <input type="text" name="items[${idx}][device_brand]" value="${deviceBrand}" class="input-field text-sm" placeholder="Samsung, iPhone, Alcatel...">
                </div>
                <div class="part-product-field ${itemType === 'part' ? '' : 'hidden'}">
                    <label class="text-xs text-slate-500">Vincular a producto</label>
                    <select name="items[${idx}][product_id]" class="select-field text-sm part-product-sel" data-idx="${idx}">
                        <option value="">— Manual —</option>${opts}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-500">Cant.</label>
                        <input type="number" name="items[${idx}][quantity]" value="${qty}" min="0.01" step="0.01" required class="input-field text-sm item-qty" data-idx="${idx}">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Precio</label>
                        <input type="number" name="items[${idx}][price]" value="${price}" min="0" step="0.01" required class="input-field text-sm item-price" data-idx="${idx}">
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

    if (productId) {
        const sel = getItemRow(idx)?.querySelector('.part-product-sel');
        if (sel) {
            onProductSelect(sel, idx);
            return;
        }
    }

    syncItemRow(idx);
}

function toggleItemTypeFields(idx) {
    const row = document.querySelector(`.item-row[data-idx="${idx}"]`);
    const itemType = row.querySelector(`[name="items[${idx}][item_type]"]`).value;
    const brandField = row.querySelector('.service-brand-field');
    const serviceSelectField = row.querySelector('.service-select-field');
    const productField = row.querySelector('.part-product-field');
    
    if (itemType === 'service') {
        brandField.classList.remove('hidden');
        serviceSelectField.classList.remove('hidden');
        productField.classList.add('hidden');
    } else {
        brandField.classList.add('hidden');
        brandField.querySelector('input').value = '';
        serviceSelectField.classList.add('hidden');
        serviceSelectField.querySelector('select').value = '';
        productField.classList.remove('hidden');
    }
}

function getItemRow(idx) {
    return document.querySelector(`.item-row[data-idx="${idx}"]`);
}

function findProduct(productId) {
    return productsData.find(product => String(product.id) === String(productId));
}

function syncItemRow(idx) {
    const row = getItemRow(idx);
    if (!row) {
        return;
    }

    const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
    const price = parseFloat(row.querySelector('.item-price')?.value || 0);
    const subtotalEl = row.querySelector('.item-subtotal');

    if (subtotalEl) {
        subtotalEl.textContent = fmt(qty * price);
    }

    recalcParts();
}

function onServiceSelect(sel, idx) {
    const row = getItemRow(idx) || sel.closest('.item-row');
    if (!row) {
        return;
    }

    const opt = sel.options[sel.selectedIndex];
    if (!opt?.value) {
        syncItemRow(idx);
        return;
    }

    const descField = row.querySelector(`[name="items[${idx}][description]"]`);
    const priceField = row.querySelector('.item-price');
    const servicePrice = parseFloat(opt.dataset.price ?? '');

    if (descField) {
        descField.value = opt.dataset.description || opt.text.split(' - ')[0];
    }

    if (priceField) {
        priceField.value = Number.isFinite(servicePrice) ? servicePrice.toFixed(2) : '0.00';
    }

    syncItemRow(idx);
}

function onProductSelect(sel, idx) {
    const row = getItemRow(idx) || sel.closest('.item-row');
    if (!row) {
        return;
    }

    const productId = sel.value;
    if (!productId) {
        syncItemRow(idx);
        return;
    }

    const product = findProduct(productId);
    if (!product) {
        return;
    }

    const descField = row.querySelector(`[name="items[${idx}][description]"]`);
    const priceField = row.querySelector('.item-price');

    if (descField) {
        descField.value = product.code ? `${product.name} (${product.code})` : product.name;
    }

    if (priceField) {
        priceField.value = Number(product.price).toFixed(2);
    }

    syncItemRow(idx);
}

function calcItemSubtotal(idx) {
    syncItemRow(idx);
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    if (!document.querySelectorAll('.item-row').length) document.getElementById('noItemsMsg').classList.remove('hidden');
    recalcParts();
}

function recalcParts() {
    partsCost = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const price = parseFloat(row.querySelector('.item-price')?.value || 0);
        partsCost += qty * price;
    });
    document.getElementById('partsCostDisplay').textContent = fmt(partsCost);
    updateTotal();
}

function updateTotal() {
    const labor = parseFloat(document.getElementById('laborCostInput').value || 0);
    const advance = parseFloat(document.getElementById('advanceInput').value || 0);
    const discountPct = parseFloat(document.getElementById('discountPercentageInput').value || 0);
    const discountFixed = parseFloat(document.getElementById('discountFixedInput').value || 0);
    
    const subtotal = labor + partsCost;
    const percentageDiscount = subtotal * (discountPct / 100);
    const totalDiscount = percentageDiscount + discountFixed;
    const total = subtotal - totalDiscount;
    
    document.getElementById('laborDisplay').textContent = fmt(labor);
    document.getElementById('totalDisplay').textContent = fmt(total);
    document.getElementById('balanceDisplay').textContent = fmt(Math.max(0, total - advance));
    
    const discountRow = document.getElementById('discountRow');
    if (totalDiscount > 0) {
        discountRow.classList.remove('hidden');
        document.getElementById('discountDisplay').textContent = '-' + fmt(totalDiscount);
    } else {
        discountRow.classList.add('hidden');
    }
}

function showAddBrandModal() {
    document.getElementById('addBrandModal').classList.remove('hidden');
    document.getElementById('newBrandInput').value = '';
    document.getElementById('newBrandInput').focus();
}

function closeAddBrandModal() {
    document.getElementById('addBrandModal').classList.add('hidden');
}

async function addNewBrand(buttonEl = null) {
    const newBrand = document.getElementById('newBrandInput').value.trim();
    if (!newBrand) {
        alert('Por favor ingrese un nombre para la marca');
        return;
    }
    
    // Check if brand already exists in datalist
    const brandsList = document.getElementById('brands_list');
    const existingOptions = Array.from(brandsList.options).map(opt => opt.value.toLowerCase());
    if (existingOptions.includes(newBrand.toLowerCase())) {
        alert('Esta marca ya existe en la lista');
        return;
    }
    
    try {
        const button = buttonEl ?? document.querySelector('#addBrandModal button[onclick^="addNewBrand"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Agregando...';
        }

        const response = await fetch('{{ route('device-brands.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ name: newBrand })
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            if (error.errors && error.errors.name) {
                alert(error.errors.name[0]);
            } else if (error.error) {
                alert(error.error);
            } else {
                alert('Error al agregar la marca');
            }
            return;
        }
        
        const brand = await response.json();
        
        // Add to datalist
        const option = document.createElement('option');
        option.value = brand.name;
        brandsList.appendChild(option);
        
        // Set the brand input
        const brandInput = document.querySelector('[name="device_brand"]');
        brandInput.value = brand.name;
        
        // Trigger change event to update any dependent fields
        brandInput.dispatchEvent(new Event('change'));
        
        closeAddBrandModal();
        
        // Show success message
        const successMsg = document.createElement('div');
        successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        successMsg.textContent = 'Marca agregada exitosamente';
        document.body.appendChild(successMsg);
        setTimeout(() => successMsg.remove(), 3000);
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al agregar la marca. Por favor intenta nuevamente.');
    } finally {
        const button = buttonEl ?? document.querySelector('#addBrandModal button[onclick^="addNewBrand"]');
        if (button) {
            button.disabled = false;
            button.textContent = 'Agregar';
        }
    }
}

function initRepairItems() {
    const itemsContainer = document.getElementById('itemsContainer');
    if (!itemsContainer) {
        return;
    }

    itemsContainer.addEventListener('change', (event) => {
        const row = event.target.closest('.item-row');
        if (!row) {
            return;
        }

        const idx = row.dataset.idx;

        if (event.target.classList.contains('part-product-sel')) {
            onProductSelect(event.target, idx);
        } else if (event.target.classList.contains('item-service-sel')) {
            onServiceSelect(event.target, idx);
        }
    });

    itemsContainer.addEventListener('input', (event) => {
        const row = event.target.closest('.item-row');
        if (!row) {
            return;
        }

        if (event.target.matches('.item-qty, .item-price')) {
            syncItemRow(row.dataset.idx);
        }
    });
}

// Pre-load existing items
document.addEventListener('DOMContentLoaded', () => {
    initPatternPad();
    toggleLockFields();
    toggleOptionalField('diagnosis');
    toggleOptionalField('repair_notes');
    toggleWarrantyText();
    initRepairItems();

    // Load brands dynamically
    loadBrands();

    @foreach($order->items as $item)
        addItem(
            @json($item->description),
            {{ $item->quantity }},
            {{ $item->price }},
            '{{ $item->product_id ?? "" }}',
            '{{ $item->item_type ?? "part" }}',
            @json($item->device_brand ?? ""),
            '{{ $item->service_id ?? "" }}'
        );
    @endforeach
    updateTotal();
});

function toggleWarrantyText() {
    const checkbox = document.getElementById('warrantyEnabled');
    const textarea = document.getElementById('warrantyTextField');
    if (!checkbox || !textarea) return;
    textarea.disabled = !checkbox.checked;
    textarea.classList.toggle('opacity-50', !checkbox.checked);
    textarea.classList.toggle('bg-slate-50', !checkbox.checked);
}

function loadDefaultWarranty() {
    const textarea = document.getElementById('warrantyTextField');
    if (!textarea) return;
    textarea.value = defaultRepairWarranty;
}

async function loadBrands() {
    // Check if brands are cached in localStorage
    const cachedBrands = localStorage.getItem('device_brands');
    const cacheTime = localStorage.getItem('device_brands_timestamp');
    const cacheDuration = 30 * 60 * 1000; // 30 minutes
    
    // Use cached brands if available and recent
    if (cachedBrands && cacheTime && (Date.now() - parseInt(cacheTime)) < cacheDuration) {
        const brands = JSON.parse(cachedBrands);
        const brandsList = document.getElementById('brands_list');
        if (brandsList) {
            brandsList.innerHTML = '';
            brands.forEach(brand => {
                const option = document.createElement('option');
                option.value = brand.name;
                brandsList.appendChild(option);
            });
            console.log('Marcas cargadas desde caché:', brands.length);
            return;
        }
    }
    
    // Fetch from API if no cache or expired
    try {
        const response = await fetch('{{ route('device-brands.index') }}');
        console.log('Respuesta recibida:', response.status);
        
        if (response.ok) {
            const brands = await response.json();
            console.log('Marcas desde API:', brands);
            
            const brandsList = document.getElementById('brands_list');
            if (brandsList) {
                brandsList.innerHTML = ''; // Clear existing options
                
                brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.name;
                    brandsList.appendChild(option);
                });
                
                // Cache the brands
                localStorage.setItem('device_brands', JSON.stringify(brands));
                localStorage.setItem('device_brands_timestamp', Date.now().toString());
                
                console.log('Marcas cargadas en datalist:', brands.length);
            } else {
                console.error('Elemento brands_list no encontrado');
            }
        } else {
            console.error('Error en respuesta API:', response.status);
        }
    } catch (error) {
        console.error('Error loading brands:', error);
        // Fallback: ensure at least some brands are available
        const brandsList = document.getElementById('brands_list');
        if (brandsList && brandsList.options.length === 0) {
            console.log('Usando marcas por defecto');
            const defaultBrands = ['Samsung', 'Apple', 'Xiaomi', 'Huawei', 'Motorola', 'LG', 'Sony', 'Nokia', 'OPPO', 'Realme', 'OnePlus', 'Tecno', 'ZTE'];
            defaultBrands.forEach(brand => {
                const option = document.createElement('option');
                option.value = brand;
                brandsList.appendChild(option);
            });
            console.log('Marcas por defecto cargadas:', defaultBrands.length);
        }
    }
}

function showAddServiceModal() {
    document.getElementById('addServiceModal').classList.remove('hidden');
    document.getElementById('newServiceName').value = '';
    document.getElementById('newServiceDescription').value = '';
    document.getElementById('newServicePrice').value = '';
    document.getElementById('newServiceName').focus();
}

function closeAddServiceModal() {
    document.getElementById('addServiceModal').classList.add('hidden');
}

async function addNewService(buttonEl = null) {
    const name = document.getElementById('newServiceName').value.trim();
    const description = document.getElementById('newServiceDescription').value.trim();
    const price = document.getElementById('newServicePrice').value;
    
    if (!name) {
        alert('Por favor ingresa un nombre para el servicio');
        return;
    }
    
    if (!price || price < 0) {
        alert('Por favor ingresa un precio válido');
        return;
    }
    
    const button = buttonEl ?? document.querySelector('#addServiceModal button[onclick^="addNewService"]');
    if (!button) {
        alert('No se pudo inicializar el formulario de servicio. Recarga la página.');
        return;
    }
    button.disabled = true;
    button.textContent = 'Agregando...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        alert('Error: No se encontró el token CSRF. Por favor recarga la página.');
        button.disabled = false;
        button.textContent = 'Agregar';
        return;
    }
    
    try {
        const response = await fetch('{{ route('repair-services.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, description, price })
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await response.json().catch(() => ({}));
        console.log('Response data:', data);
        
        if (!response.ok) {
            if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                alert('Error: ' + firstError);
            } else if (data.error) {
                alert('Error: ' + data.error);
            } else if (data.message) {
                alert('Error: ' + data.message);
            } else {
                alert('Error al agregar el servicio. Código: ' + response.status);
            }
            return;
        }
        
        alert('Servicio agregado exitosamente');
        closeAddServiceModal();
        location.reload();
        
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error de conexión: ' + error.message + '. Por favor intenta nuevamente.');
    } finally {
        button.disabled = false;
        button.textContent = 'Agregar';
    }
}
</script>
@endsection
