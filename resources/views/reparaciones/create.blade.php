@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Nueva Orden de Reparación')

@section('content')
<style>
    .repair-compact .repair-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        overflow: hidden;
    }
    .repair-compact .repair-section-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 0.875rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
    }
    .repair-compact .repair-section-head svg { width: 1rem; height: 1rem; color: #6366f1; flex-shrink: 0; }
    .repair-compact .repair-section-body { padding: 0.75rem 0.875rem; }
    .repair-compact .repair-label {
        display: block;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .repair-compact .repair-input,
    .repair-compact .input-field,
    .repair-compact .select-field {
        padding-top: 0.4375rem;
        padding-bottom: 0.4375rem;
        font-size: 0.8125rem;
        border-radius: 0.625rem;
    }
    .repair-compact textarea.repair-input,
    .repair-compact textarea.input-field {
        min-height: 2.5rem;
    }
    .repair-compact .repair-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .repair-compact .repair-chip:hover { border-color: #a5b4fc; color: #4338ca; }
    .repair-compact .repair-chip.active {
        background: #eef2ff;
        border-color: #818cf8;
        color: #4338ca;
    }
    .repair-compact .pattern-dot {
        height: 2.25rem;
        width: 2.25rem;
        font-size: 0.75rem;
        border-radius: 0.75rem;
    }
    .repair-compact #patternPad { max-width: 11rem; gap: 0.5rem; }
    .repair-compact .pattern-dot.selected {
        background: #eef2ff;
        border-color: #6366f1;
        color: #4338ca;
    }
    .repair-compact .item-row {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fafafa;
        padding: 0.5rem 0.625rem;
    }
    .repair-compact .repair-summary {
        position: sticky;
        top: 0.75rem;
    }
    .repair-compact .repair-total-box {
        background: linear-gradient(135deg, #312e81 0%, #4338ca 100%);
        color: #fff;
        border-radius: 0.75rem;
        padding: 0.75rem;
    }
    .repair-compact .repair-toggle-btn {
        font-size: 0.6875rem;
        font-weight: 600;
        color: #6366f1;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        background: #eef2ff;
        border: none;
        cursor: pointer;
    }
    .repair-compact .repair-toggle-btn:hover { background: #e0e7ff; }
    @media (max-width: 1023px) {
        .repair-compact .repair-summary { position: static; }
    }
</style>

<div class="repair-compact max-w-7xl mx-auto space-y-3 pb-6">

    {{-- Header compacto --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3 min-w-0">
            <div class="hidden sm:flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-slate-900 truncate">Nueva Orden de Reparación</h1>
                <p class="text-xs text-slate-500">Ingreso rápido de equipo al taller</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reparaciones.index') }}" class="btn-outline text-xs py-1.5 px-3">← Volver</a>
            <button type="submit" form="repairForm" class="btn-primary text-xs py-1.5 px-4 lg:hidden">Guardar</button>
        </div>
    </div>

    @if($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-800 text-xs">
        <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('reparaciones.store') }}" method="POST" id="repairForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18.5rem] xl:grid-cols-[minmax(0,1fr)_20rem] gap-3">

            {{-- Columna principal --}}
            <div class="space-y-3 min-w-0">

                {{-- Cliente + Equipo en una sola tarjeta --}}
                <div class="repair-section">
                    <div class="repair-section-head">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Cliente y Equipo
                    </div>
                    <div class="repair-section-body space-y-3">
                        <div>
                            <label class="repair-label" for="client_selector">Cliente registrado</label>
                            <select id="client_selector" onchange="fillClient(this)" class="select-field w-full">
                                <option value="">— Cliente nuevo / sin registro —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-phone="{{ $c->phone }}"
                                        data-email="{{ $c->email ?? '' }}">
                                        {{ $c->name }}{{ $c->phone ? ' · '.$c->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="client_id" id="client_id_input">
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="repair-label">Nombre *</label>
                                <input type="text" name="client_name" value="{{ old('client_name') }}" required class="input-field w-full">
                            </div>
                            <div>
                                <label class="repair-label">Teléfono</label>
                                <input type="text" name="client_phone" value="{{ old('client_phone') }}" class="input-field w-full" placeholder="8888-8888">
                            </div>
                            <div>
                                <label class="repair-label">Email</label>
                                <input type="email" name="client_email" value="{{ old('client_email') }}" class="input-field w-full">
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div>
                                    <label class="repair-label">Marca *</label>
                                    <div class="flex gap-1">
                                        <input type="text" name="device_brand" value="{{ old('device_brand') }}" required
                                            list="brands_list" class="input-field flex-1 min-w-0" placeholder="Samsung..." autocomplete="off">
                                        <button type="button" onclick="showAddBrandModal()" class="shrink-0 h-[34px] w-[34px] rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-bold" title="Nueva marca">+</button>
                                    </div>
                                    <datalist id="brands_list">
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->name }}">
                                        @endforeach
                                    </datalist>
                                </div>
                                <div>
                                    <label class="repair-label">Modelo *</label>
                                    <input type="text" name="device_model" value="{{ old('device_model') }}" required class="input-field w-full" placeholder="Galaxy A54">
                                </div>
                                <div>
                                    <label class="repair-label">Color</label>
                                    <input type="text" name="device_color" value="{{ old('device_color') }}" class="input-field w-full" placeholder="Negro">
                                </div>
                                <div>
                                    <label class="repair-label">IMEI / Serie</label>
                                    <input type="text" name="device_imei" value="{{ old('device_imei') }}" class="input-field w-full" placeholder="15 dígitos">
                                </div>
                                <div>
                                    <label class="repair-label">Bloqueo</label>
                                    <select id="lockTypeSelect" name="lock_type" class="select-field w-full" onchange="toggleLockFields()">
                                        <option value="password" {{ old('lock_type') === 'password' ? 'selected' : '' }}>PIN / Clave</option>
                                        <option value="pattern" {{ old('lock_type') === 'pattern' ? 'selected' : '' }}>Patrón</option>
                                        <option value="none" {{ old('lock_type', 'none') === 'none' ? 'selected' : '' }}>Sin bloqueo</option>
                                    </select>
                                </div>
                                <div class="col-span-2 sm:col-span-3">
                                    <label class="repair-label">Accesorios</label>
                                    <input type="text" name="accessories" value="{{ old('accessories') }}" class="input-field w-full" placeholder="Cargador, funda, caja...">
                                </div>
                            </div>

                            <div id="lockPasswordContainer" class="hidden mt-2 max-w-xs">
                                <label class="repair-label">Contraseña / PIN</label>
                                <input type="text" id="devicePasswordText" value="{{ old('device_password') }}" class="input-field w-full" oninput="syncLockValue()" placeholder="Clave del dispositivo">
                            </div>
                            <div id="lockPatternContainer" class="hidden mt-2">
                                <label class="repair-label">Patrón de desbloqueo</label>
                                <div class="inline-block rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    <div id="patternPad" class="relative mx-auto grid grid-cols-3 gap-2">
                                        <svg id="patternSvg" class="pointer-events-none absolute inset-0 h-full w-full"></svg>
                                        @for($i = 1; $i <= 9; $i++)
                                            <button type="button" class="pattern-dot relative z-10 flex items-center justify-center border border-slate-300 bg-white font-semibold text-slate-600 shadow-sm transition hover:border-indigo-400" data-point="{{ $i }}">{{ $i }}</button>
                                        @endfor
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                                        <span>Secuencia: <strong id="patternPreview" class="text-slate-700">—</strong></span>
                                        <button type="button" onclick="clearPattern()" class="repair-toggle-btn">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="device_password" id="devicePasswordHidden" value="{{ old('device_password') }}">
                        </div>
                    </div>
                </div>

                {{-- Diagnóstico --}}
                <div class="repair-section">
                    <div class="repair-section-head justify-between">
                        <span class="flex items-center gap-2">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Diagnóstico
                        </span>
                        <span class="flex gap-1">
                            <button type="button" class="repair-toggle-btn" onclick="toggleOptionalField('diagnosis')">+ Técnico</button>
                            <button type="button" class="repair-toggle-btn" onclick="toggleOptionalField('repair_notes')">+ Notas</button>
                        </span>
                    </div>
                    <div class="repair-section-body space-y-2">
                        <div>
                            <label class="repair-label">Falla reportada *</label>
                            <textarea name="problem_description" rows="2" required class="input-field w-full resize-none"
                                placeholder="Ej: Pantalla rota, no enciende, no carga...">{{ old('problem_description') }}</textarea>
                        </div>
                        <div class="hidden" id="diagnosisWrapper">
                            <input type="checkbox" id="diagnosisToggle" class="sr-only" {{ old('diagnosis') ? 'checked' : '' }}>
                            <label class="repair-label">Diagnóstico técnico</label>
                            <textarea id="diagnosisField" name="diagnosis" rows="2" class="input-field w-full resize-none {{ old('diagnosis') ? '' : 'hidden' }}"
                                placeholder="Hallazgos al revisar el equipo..." {{ old('diagnosis') ? '' : 'disabled' }}>{{ old('diagnosis') }}</textarea>
                        </div>
                        <div class="hidden" id="repairNotesWrapper">
                            <input type="checkbox" id="repairNotesToggle" class="sr-only" {{ old('repair_notes') ? 'checked' : '' }}>
                            <label class="repair-label">Notas internas</label>
                            <textarea id="repair_notesField" name="repair_notes" rows="2" class="input-field w-full resize-none {{ old('repair_notes') ? '' : 'hidden' }}"
                                placeholder="Observaciones del técnico..." {{ old('repair_notes') ? '' : 'disabled' }}>{{ old('repair_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Repuestos --}}
                <div class="repair-section">
                    <div class="repair-section-head justify-between">
                        <span class="flex items-center gap-2">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Repuestos / Servicios
                        </span>
                        <button type="button" onclick="addItem()" class="repair-toggle-btn bg-indigo-600 text-white hover:bg-indigo-700">+ Agregar</button>
                    </div>
                    <div class="repair-section-body">
                        <div id="itemsContainer" class="space-y-2"></div>
                        <p id="noItemsMsg" class="text-xs text-slate-400 text-center py-3">Sin repuestos. Usa «+ Agregar» si aplica.</p>
                    </div>
                </div>
            </div>

            {{-- Sidebar compacto --}}
            <aside class="repair-summary space-y-3">
                <div class="repair-section">
                    <div class="repair-section-head">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Operación
                    </div>
                    <div class="repair-section-body space-y-2">
                        <div>
                            <label class="repair-label">Estado</label>
                            <select name="status" class="select-field w-full">
                                @foreach(['received' => 'Recibido', 'diagnosing' => 'En Diagnóstico', 'waiting_parts' => 'Esperando Repuestos', 'in_repair' => 'En Reparación', 'ready' => 'Listo', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('status', 'received') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="repair-label">Prioridad</label>
                            <div class="flex flex-wrap gap-1 mb-1" id="priorityChips">
                                @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                    <button type="button" class="repair-chip priority-chip" data-value="{{ $val }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <select name="priority" id="prioritySelect" class="select-field w-full">
                                @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('priority', 'normal') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="repair-label">Técnico</label>
                            <select name="technician_id" class="select-field w-full">
                                <option value="">Sin asignar</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" {{ old('technician_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                            <div>
                                <label class="repair-label">Recepción *</label>
                                <input type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required class="input-field w-full">
                            </div>
                            <div>
                                <label class="repair-label">Hora *</label>
                                <input type="time" name="received_time" value="{{ old('received_time', date('H:i')) }}" required class="input-field w-full">
                            </div>
                            <div>
                                <label class="repair-label">Entrega est.</label>
                                <input type="date" name="estimated_date" value="{{ old('estimated_date') }}" class="input-field w-full">
                            </div>
                            <div>
                                <label class="repair-label">Hora est.</label>
                                <input type="time" name="estimated_delivery_time" value="{{ old('estimated_delivery_time') }}" class="input-field w-full">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="repair-section">
                    <div class="repair-section-head">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Costos
                    </div>
                    <div class="repair-section-body space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="repair-label">Mano de obra</label>
                                <input type="number" step="0.01" min="0" name="labor_cost" value="{{ old('labor_cost', 0) }}" class="input-field w-full" id="laborCostInput" oninput="updateTotal()">
                            </div>
                            <div>
                                <label class="repair-label">Anticipo</label>
                                <input type="number" step="0.01" min="0" name="advance_payment" value="{{ old('advance_payment', 0) }}" class="input-field w-full" id="advanceInput" oninput="updateTotal()">
                            </div>
                            <div>
                                <label class="repair-label">Desc. %</label>
                                <input type="number" step="0.01" min="0" max="100" name="discount_percentage" value="{{ old('discount_percentage', 0) }}" class="input-field w-full" id="discountPercentageInput" oninput="updateTotal()">
                            </div>
                            <div>
                                <label class="repair-label">Desc. C$</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" value="{{ old('discount_amount', 0) }}" class="input-field w-full" id="discountFixedInput" oninput="updateTotal()">
                            </div>
                        </div>

                        <div class="repair-total-box text-xs space-y-1">
                            <div class="flex justify-between opacity-90"><span>Repuestos</span><span id="partsCostDisplay">C$ 0.00</span></div>
                            <div class="flex justify-between opacity-90"><span>M. de obra</span><span id="laborDisplay">C$ 0.00</span></div>
                            <div class="flex justify-between text-red-200 hidden" id="discountRow"><span>Descuento</span><span id="discountDisplay">C$ 0.00</span></div>
                            <div class="flex justify-between font-bold text-sm border-t border-white/20 pt-1 mt-1"><span>Total</span><span id="totalDisplay">C$ 0.00</span></div>
                            <div class="flex justify-between font-semibold text-amber-200"><span>Saldo</span><span id="balanceDisplay">C$ 0.00</span></div>
                        </div>

                        <div>
                            <label class="repair-label">Método de pago</label>
                            <select name="payment_type" class="select-field w-full">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="repair-section">
                    <div class="repair-section-head justify-between">
                        <span class="flex items-center gap-2">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="!text-emerald-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Garantía
                        </span>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer text-[10px] text-slate-600">
                            <input type="hidden" name="warranty_enabled" value="0">
                            <input type="checkbox" name="warranty_enabled" id="warrantyEnabled" value="1"
                                {{ old('warranty_enabled', '1') === '1' || old('warranty_enabled') === true ? 'checked' : '' }}
                                onchange="toggleWarrantyText()"
                                class="w-3.5 h-3.5 rounded border-slate-300 text-indigo-600">
                            En ticket
                        </label>
                    </div>
                    <div class="repair-section-body space-y-1">
                        <textarea id="warrantyTextField" name="warranty_text" rows="2"
                            class="input-field w-full resize-none text-xs"
                            placeholder="Texto de garantía en ticket...">{{ old('warranty_text', $companyProfile['repair_warranty_text'] ?? '') }}</textarea>
                        <button type="button" onclick="loadDefaultWarranty()" class="text-[10px] text-indigo-600 hover:text-indigo-800">Usar texto predeterminado</button>
                    </div>
                </div>

                <button type="submit" class="hidden lg:flex w-full btn-primary justify-center py-2.5 text-sm font-semibold shadow-md">
                    Guardar Orden
                </button>
            </aside>
        </div>
    </form>

    {{-- MODAL: Agregar Nueva Marca --}}
    <div id="addBrandModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-900">Nueva Marca</h2>
                <button type="button" onclick="closeAddBrandModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <input type="text" id="newBrandInput" class="input-field w-full" placeholder="Vivo, Alcatel, HTC...">
                <input type="hidden" id="customBrands" value="{{ old('custom_brands', '') }}">
            </div>
            <div class="px-4 py-3 border-t border-slate-200 flex gap-2">
                <button type="button" onclick="closeAddBrandModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold py-2 rounded-lg">Cancelar</button>
                <button type="button" onclick="addNewBrand(this)" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg">Agregar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Agregar Nuevo Servicio --}}
    <div id="addServiceModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-900">Nuevo Servicio</h2>
                <button type="button" onclick="closeAddServiceModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-2">
                <input type="text" id="newServiceName" class="input-field w-full" placeholder="Nombre del servicio *">
                <textarea id="newServiceDescription" class="input-field w-full" rows="2" placeholder="Descripción"></textarea>
                <input type="number" id="newServicePrice" class="input-field w-full" placeholder="Precio C$ *" step="0.01" min="0">
            </div>
            <div class="px-4 py-3 border-t border-slate-200 flex gap-2">
                <button type="button" onclick="closeAddServiceModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold py-2 rounded-lg">Cancelar</button>
                <button type="button" onclick="addNewService(this)" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg">Agregar</button>
            </div>
        </div>
    </div>
</div>

@php
    $productsJson = $products->map(function ($p) {
        return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'price' => (float) $p->sale_price];
    })->values()->toJson();
    $defaultRepairWarrantyJson = json_encode($companyProfile['repair_warranty_text'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp
<script>
const productsData = {!! $productsJson !!};
const defaultRepairWarranty = {!! $defaultRepairWarrantyJson !!};

let itemIndex = 0;
let partsCost = 0;
let patternPoints = [];
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
    document.getElementById('patternPreview').textContent = '—';
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
    document.getElementById('patternPreview').textContent = points.join('→');
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
    path.setAttribute('stroke-width', '4');
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

    document.getElementById('patternPreview').textContent = patternPoints.join('→');
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

    document.addEventListener('pointerup', () => { isDrawingPattern = false; });
    document.addEventListener('pointercancel', () => { isDrawingPattern = false; });
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
    const wrapper = document.getElementById(field === 'diagnosis' ? 'diagnosisWrapper' : 'repairNotesWrapper');
    const textarea = document.getElementById(field === 'diagnosis' ? 'diagnosisField' : 'repair_notesField');
    if (!textarea || !checkbox) {
        return;
    }

    checkbox.checked = !checkbox.checked;
    textarea.disabled = !checkbox.checked;
    textarea.classList.toggle('hidden', !checkbox.checked);
    wrapper.classList.toggle('hidden', !checkbox.checked);

    if (!checkbox.checked) {
        textarea.value = '';
    } else {
        textarea.focus();
    }
}

function fmt(v) { return 'C$ ' + parseFloat(v || 0).toFixed(2); }

function addItem(desc = '', qty = 1, price = 0, productId = '', itemType = 'part', deviceBrand = '') {
    document.getElementById('noItemsMsg').classList.add('hidden');
    const idx = itemIndex++;
    const opts = productsData.map(p =>
        `<option value="${p.id}" data-price="${p.price}" ${productId == p.id ? 'selected' : ''}>${p.name}</option>`
    ).join('');

    const html = `
    <div class="item-row" data-idx="${idx}">
        <div class="flex flex-wrap items-end gap-1.5">
            <div class="w-20 shrink-0">
                <label class="repair-label">Tipo</label>
                <select name="items[${idx}][item_type]" class="select-field w-full text-xs py-1" onchange="toggleItemTypeFields(${idx})">
                    <option value="part" ${itemType === 'part' ? 'selected' : ''}>Repuesto</option>
                    <option value="service" ${itemType === 'service' ? 'selected' : ''}>Servicio</option>
                </select>
            </div>
            <div class="flex-1 min-w-[8rem]">
                <label class="repair-label">Descripción *</label>
                <input type="text" name="items[${idx}][description]" value="${desc}" required class="input-field w-full text-xs py-1" placeholder="Pantalla, batería...">
            </div>
            <div class="w-14 shrink-0">
                <label class="repair-label">Cant.</label>
                <input type="number" name="items[${idx}][quantity]" value="${qty}" min="0.01" step="0.01" required class="input-field w-full text-xs py-1 item-qty" data-idx="${idx}" oninput="calcItemSubtotal(${idx})">
            </div>
            <div class="w-20 shrink-0">
                <label class="repair-label">Precio</label>
                <input type="number" name="items[${idx}][price]" value="${price}" min="0" step="0.01" required class="input-field w-full text-xs py-1 item-price" data-idx="${idx}" oninput="calcItemSubtotal(${idx})">
            </div>
            <div class="w-20 shrink-0 text-right">
                <label class="repair-label">Subtotal</label>
                <p class="text-xs font-bold text-slate-800 item-subtotal py-1.5" data-idx="${idx}">${fmt(qty * price)}</p>
            </div>
            <button type="button" onclick="removeItem(this)" class="shrink-0 mb-0.5 h-8 w-8 rounded-lg text-red-500 hover:bg-red-50 text-sm" title="Quitar">×</button>
        </div>
        <div class="mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
            <div class="service-select-field ${itemType === 'service' ? '' : 'hidden'}">
                <div class="flex gap-1">
                    <select name="items[${idx}][service_id]" class="select-field flex-1 text-xs py-1" onchange="onServiceSelect(this, ${idx})">
                        <option value="">Servicio predefinido</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-description="{{ $service->name }}">{{ $service->name }} · C$ {{ number_format($service->price, 2) }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="showAddServiceModal()" class="shrink-0 h-[30px] w-[30px] rounded-lg bg-indigo-600 text-white text-xs font-bold" title="Nuevo servicio">+</button>
                </div>
            </div>
            <div class="part-product-field ${itemType === 'part' ? '' : 'hidden'}">
                <select name="items[${idx}][product_id]" class="select-field w-full text-xs py-1 part-product-sel" data-idx="${idx}" onchange="onProductSelect(this, ${idx})">
                    <option value="">Vincular producto inventario</option>
                    ${opts}
                </select>
            </div>
            <div class="service-brand-field ${itemType === 'service' ? '' : 'hidden'}">
                <input type="text" name="items[${idx}][device_brand]" value="${deviceBrand}" class="input-field w-full text-xs py-1" placeholder="Marca del dispositivo (servicio)">
            </div>
        </div>
    </div>`;

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    recalcParts();
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

function onServiceSelect(sel, idx) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        const descField = document.querySelector(`[name="items[${idx}][description]"]`);
        const priceField = document.querySelector(`.item-price[data-idx="${idx}"]`);
        descField.value = opt.dataset.description || opt.text.split(' · ')[0];
        priceField.value = opt.dataset.price || 0;
        calcItemSubtotal(idx);
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

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (data.errors) {
                alert('Error: ' + Object.values(data.errors)[0]);
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
        alert('Error de conexión: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = 'Agregar';
    }
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
    const qty = parseFloat(document.querySelector(`.item-qty[data-idx="${idx}"]`)?.value || 0);
    const price = parseFloat(document.querySelector(`.item-price[data-idx="${idx}"]`)?.value || 0);
    const el = document.querySelector(`.item-subtotal[data-idx="${idx}"]`);
    if (el) {
        el.textContent = fmt(qty * price);
    }
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
        const idx = row.dataset.idx;
        const qty = parseFloat(document.querySelector(`.item-qty[data-idx="${idx}"]`)?.value || 0);
        const price = parseFloat(document.querySelector(`.item-price[data-idx="${idx}"]`)?.value || 0);
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
    const balance = Math.max(0, total - advance);

    document.getElementById('laborDisplay').textContent = fmt(labor);
    document.getElementById('totalDisplay').textContent = fmt(total);
    document.getElementById('balanceDisplay').textContent = fmt(balance);

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
        const option = document.createElement('option');
        option.value = brand.name;
        brandsList.appendChild(option);

        const brandInput = document.querySelector('[name="device_brand"]');
        brandInput.value = brand.name;
        brandInput.dispatchEvent(new Event('change'));
        closeAddBrandModal();

        const successMsg = document.createElement('div');
        successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm';
        successMsg.textContent = 'Marca agregada';
        document.body.appendChild(successMsg);
        setTimeout(() => successMsg.remove(), 2500);
    } catch (error) {
        alert('Error al agregar la marca. Por favor intenta nuevamente.');
    } finally {
        const button = buttonEl ?? document.querySelector('#addBrandModal button[onclick^="addNewBrand"]');
        if (button) {
            button.disabled = false;
            button.textContent = 'Agregar';
        }
    }
}

function initPriorityChips() {
    const select = document.getElementById('prioritySelect');
    const chips = document.querySelectorAll('.priority-chip');

    function syncChips() {
        chips.forEach(chip => {
            chip.classList.toggle('active', chip.dataset.value === select.value);
        });
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            select.value = chip.dataset.value;
            syncChips();
        });
    });

    syncChips();
}

function initOptionalFields() {
    ['diagnosis', 'repair_notes'].forEach(field => {
        const checkbox = document.getElementById(field === 'diagnosis' ? 'diagnosisToggle' : 'repairNotesToggle');
        const wrapper = document.getElementById(field === 'diagnosis' ? 'diagnosisWrapper' : 'repairNotesWrapper');
        const textarea = document.getElementById(field === 'diagnosis' ? 'diagnosisField' : 'repair_notesField');
        if (!checkbox || !wrapper || !textarea) {
            return;
        }
        const visible = Boolean(checkbox.checked || textarea.value.trim());
        checkbox.checked = visible;
        textarea.disabled = !visible;
        textarea.classList.toggle('hidden', !visible);
        wrapper.classList.toggle('hidden', !visible);
    });
}

function toggleWarrantyText() {
    const checkbox = document.getElementById('warrantyEnabled');
    const textarea = document.getElementById('warrantyTextField');
    if (!checkbox || !textarea) {
        return;
    }
    textarea.disabled = !checkbox.checked;
    textarea.classList.toggle('opacity-50', !checkbox.checked);
    textarea.classList.toggle('bg-slate-50', !checkbox.checked);
}

function loadDefaultWarranty() {
    const textarea = document.getElementById('warrantyTextField');
    if (!textarea) {
        return;
    }
    textarea.value = defaultRepairWarranty;
}

async function loadBrands() {
    const cachedBrands = localStorage.getItem('device_brands');
    const cacheTime = localStorage.getItem('device_brands_timestamp');
    const cacheDuration = 30 * 60 * 1000;

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
            return;
        }
    }

    try {
        const response = await fetch('{{ route('device-brands.index') }}');
        if (response.ok) {
            const brands = await response.json();
            const brandsList = document.getElementById('brands_list');
            if (brandsList) {
                brandsList.innerHTML = '';
                brands.forEach(brand => {
                    const option = document.createElement('option');
                    option.value = brand.name;
                    brandsList.appendChild(option);
                });
                localStorage.setItem('device_brands', JSON.stringify(brands));
                localStorage.setItem('device_brands_timestamp', Date.now().toString());
            }
        }
    } catch (error) {
        const brandsList = document.getElementById('brands_list');
        if (brandsList && brandsList.options.length === 0) {
            ['Samsung', 'Apple', 'Xiaomi', 'Huawei', 'Motorola', 'LG', 'Sony', 'Nokia', 'OPPO', 'Realme', 'OnePlus', 'Tecno', 'ZTE'].forEach(brand => {
                const option = document.createElement('option');
                option.value = brand;
                brandsList.appendChild(option);
            });
        }
    }
}

window.addEventListener('DOMContentLoaded', () => {
    initPatternPad();
    toggleLockFields();
    initOptionalFields();
    initPriorityChips();
    toggleWarrantyText();
    loadBrands();
    updateTotal();
});
</script>
@endsection
