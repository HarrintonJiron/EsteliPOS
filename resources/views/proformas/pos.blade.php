@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Nueva Proforma')
@section('hide-header', 'true')
@section('main-fluid', true)
@section('main-class', 'p-0')

@section('content')

<div id="proformaApp" class="flex h-full min-h-0 flex-col overflow-y-auto bg-slate-50 sm:flex-row sm:overflow-hidden"
     data-products='@json($products)'
     data-products-url="{{ route('proformas.products') }}"
     data-clients='@json($clients)'
     data-categories='@json($categories)'
     data-default-tax-rate="{{ $defaultTaxRate }}">

    {{-- COLUMNA IZQUIERDA: ITEMS --}}
    <div class="pos-ticket-col min-h-0 w-full flex-1 overflow-hidden border-b border-slate-200 bg-white sm:h-full sm:max-h-none sm:min-w-[280px] sm:max-w-[520px] sm:w-2/5 sm:flex-none sm:border-b-0 sm:border-r">

        <div class="px-4 py-2 bg-indigo-700 text-white flex items-center justify-between text-xs shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="font-semibold">Nueva Proforma / Cotización</span>
            </div>
            <a href="{{ route('proformas.index') }}" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg">← Volver</a>
        </div>

        <div id="ticketScroller" class="min-h-0 overflow-y-auto border-b border-slate-200">
            <div id="proformaItems" class="divide-y divide-slate-100"></div>
            <div id="emptyProforma" class="flex flex-col items-center justify-center h-full text-slate-400 py-12">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-base font-medium text-slate-500">Cotización vacía</p>
                <p class="text-xs text-slate-400 mt-1">Busca y agrega productos</p>
            </div>
        </div>

        <div class="bg-slate-100 px-6 py-4 border-b border-slate-200 shrink-0">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600 hidden" id="discountRow">
                    <span>Descuento</span>
                    <span id="discountDisplay" class="font-medium text-red-600">-C$ 0.00</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span id="taxLabel">IVA ({{ number_format($defaultTaxRate * 100, 2) }}%)</span>
                    <span id="taxDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="border-t border-slate-300 pt-2 flex justify-between">
                    <span class="text-sm font-semibold text-slate-700">Total</span>
                    <span id="totalDisplay" class="text-3xl font-bold text-slate-900">C$ 0.00</span>
                </div>
            </div>
        </div>

        <div class="pos-ticket-actions space-y-2 p-3">
            <button type="button" id="clientBtn"
                onclick="document.getElementById('clientModal').classList.remove('hidden')"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl px-4 text-sm flex items-center justify-center gap-2 transition-all shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span id="clientDisplay">Sin cliente asignado</span>
            </button>

            @include('facturacion._numpad')

            <button type="button" onclick="openSaveModal()"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar Proforma
            </button>

            <button type="button" onclick="clearItems()"
                class="w-full bg-slate-400 hover:bg-slate-500 text-white font-semibold py-2 rounded-xl text-sm">
                Limpiar
            </button>
        </div>
    </div>

    {{-- COLUMNA DERECHA: PRODUCTOS --}}
    <div class="flex min-h-[30rem] min-w-0 flex-1 flex-col bg-white sm:min-h-0">

        <div class="p-3 border-b border-slate-200 bg-white shrink-0 space-y-2">
            <div class="flex min-w-0 gap-2">
                <input type="text" id="productSearch" placeholder="Buscar producto por nombre o código..."
                    class="min-w-0 flex-1 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600" autocomplete="off">
                <button type="button" onclick="applyOrderDiscount()" class="shrink-0 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium" title="Descuento global">% Dto.</button>
            </div>
            <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-1"></div>
        </div>

        <div id="productsGrid" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-1.5 sm:p-2">
            <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4 lg:grid-cols-5"></div>
        </div>
    </div>

    {{-- MODAL: Cliente --}}
    <div id="clientModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Seleccionar Cliente</h2>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="p-4">
                <input type="text" id="clientSearch" placeholder="Buscar cliente..." class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
            </div>
            <div class="px-4 pb-4 space-y-2 max-h-72 overflow-y-auto">
                <button type="button" onclick="selectClient(null, 'Sin cliente asignado')"
                    class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm">
                    <p class="font-semibold text-slate-800">Sin cliente asignado</p>
                    <p class="text-xs text-slate-500">Proforma general</p>
                </button>
                <div id="clientsList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t border-slate-200 space-y-2">
                <button type="button" onclick="openQuickClientModal()"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-xl text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Cliente Rápido
                </button>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl text-sm">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Cliente Rápido --}}
    <div id="quickClientModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Cliente Rápido</h2>
                <button type="button" onclick="document.getElementById('quickClientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="quickClientForm" action="{{ route('clientes.quick-store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre *</label>
                        <input type="text" name="name" required placeholder="Nombre del cliente" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" placeholder="Opcional" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tipo</label>
                        <select name="client_type" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                            <option value="natural">Natural</option>
                            <option value="company">Empresa</option>
                        </select>
                    </div>
                    <div id="cedulaField">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Cédula</label>
                        <input type="text" name="cedula" placeholder="Opcional" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div id="rucField" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">RUC</label>
                        <input type="text" name="ruc" placeholder="Opcional" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div id="businessNameField" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre Empresa</label>
                        <input type="text" name="business_name" placeholder="Opcional" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Dirección</label>
                        <input type="text" name="address" placeholder="Opcional" class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div class="p-4 border-t border-slate-200 space-y-2">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-xl text-sm">Guardar y Seleccionar</button>
                    <button type="button" onclick="document.getElementById('quickClientModal').classList.add('hidden')"
                        class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl text-sm">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Guardar Proforma --}}
    <div id="saveModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Guardar Proforma</h2>
                <button type="button" onclick="document.getElementById('saveModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="saveForm" action="{{ route('proformas.store') }}" method="POST">
                @csrf
                <div class="p-5 space-y-4">
                    <div class="text-center bg-indigo-50 rounded-xl p-4">
                        <p class="text-sm text-slate-600">Total de la cotización</p>
                        <p class="text-3xl font-bold text-indigo-600" id="saveTotalDisplay">C$ 0.00</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Vigencia (días)</label>
                        <input type="number" name="expiry_days" value="15" min="1" max="365"
                            class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                        <p class="text-xs text-slate-400 mt-1">La proforma vencerá en estos días a partir de hoy</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Notas / Condiciones</label>
                        <textarea name="notes" id="proformaNotes" rows="3" placeholder="Observaciones, condiciones de pago, tiempo de entrega..."
                            class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:border-indigo-500 resize-none"></textarea>
                    </div>
                </div>

                <input type="hidden" name="client_id" id="saveClientId">
                <input type="hidden" name="items" id="saveItems" value="[]">
                <input type="hidden" name="order_discount_pct" id="orderDiscountPctInput" value="0">

                <div class="p-4 border-t border-slate-200 space-y-2">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl">
                        Guardar Cotización
                    </button>
                    <button type="button" onclick="document.getElementById('saveModal').classList.add('hidden')"
                        class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('proformaApp');
    const normalizeProduct = p => {
        const defaultUnit = (p.sale_units || []).find(unit => unit.is_default) || (p.sale_units || [])[0];
        return {
        id: p.id,
        code: p.code ?? '',
        name: p.name,
        price: parseFloat(defaultUnit?.price ?? p.sale_price ?? 0),
        stock: parseFloat(p.stock ?? 0),
        category_id: p.category_id,
        image_url: p.image_url ?? null,
        tax_rate: parseFloat(p.effective_tax_rate ?? app.dataset.defaultTaxRate ?? 0),
        default_unit_id: defaultUnit?.id ?? p.base_unit_id ?? null,
        sale_units: (p.sale_units || []).map(unit => ({
            id: unit.id,
            abbreviation: unit.abbreviation,
            price: parseFloat(unit.price ?? 0),
            factor_to_base: parseFloat(unit.factor_to_base ?? 1) || 1,
            price_breaks: (unit.price_breaks || []).map(priceBreak => ({
                min_quantity: parseFloat(priceBreak.min_quantity ?? 1),
                price: parseFloat(priceBreak.price ?? unit.price ?? 0),
            })),
        })),
    };};
    let products = JSON.parse(app.dataset.products).map(normalizeProduct);
    const clientsData = JSON.parse(app.dataset.clients).map(c => ({
        id: c.id,
        name: c.name ?? '',
        phone: c.phone ?? '',
        email: c.email ?? '',
    }));
    const categories = JSON.parse(app.dataset.categories);

    let items = [];
    let currentClient = null;
    let selectedIdx = -1;
    let padBuffer = '';
    let currentCategory = 'all';
    let orderDiscountPct = 0;

    function fmt(v) { return 'C$ ' + parseFloat(v || 0).toFixed(2); }

    function formatQuantity(value) {
        return parseFloat(value || 0).toLocaleString('es-NI', { maximumFractionDigits: 4 });
    }

    function unitPrice(unit, quantity) {
        const applicable = (unit?.price_breaks || [])
            .filter(priceBreak => priceBreak.min_quantity <= parseFloat(quantity || 0))
            .sort((a, b) => b.min_quantity - a.min_quantity)[0];
        return applicable?.price ?? parseFloat(unit?.price ?? 0);
    }

    function refreshItemPrices() {
        items.forEach(item => {
            const product = products.find(candidate => candidate.id == item.product_id);
            const unit = product?.sale_units.find(candidate => candidate.id == item.unit_id)
                || product?.sale_units[0];
            if (unit) item.price = unitPrice(unit, item.quantity);
        });
    }

    function stockWarning(item, quantity = item.quantity) {
        const stock = parseFloat(item.stock || 0);
        const requested = parseFloat(quantity || 0) * parseFloat(item.unit_factor || 1);

        if (stock <= 0) {
            return `${item.name} no tiene existencias disponibles.`;
        }

        if (requested > stock) {
            return `${item.name}: solicitaste ${formatQuantity(requested)} y solo hay ${formatQuantity(stock)} disponibles.`;
        }

        return null;
    }

    function currentStockWarnings() {
        return items.map(item => stockWarning(item)).filter(Boolean);
    }

    function lineSubtotal(item) {
        return item.price * item.quantity * (1 - item.discount / 100);
    }

    function getTotal() {
        const sub = items.reduce((s, i) => s + lineSubtotal(i), 0);
        const ordDisc = sub * (orderDiscountPct / 100);
        const discountedLines = items.map(item => lineSubtotal(item) * (1 - orderDiscountPct / 100));
        const taxable = discountedLines.reduce((sum, value) => sum + value, 0);
        const tax = items.reduce((sum, item, index) => sum + discountedLines[index] * parseFloat(item.tax_rate || 0), 0);
        return { sub, ordDisc, tax, total: taxable + tax };
    }

    function updateTotals() {
        const { sub, ordDisc, tax, total } = getTotal();
        document.getElementById('subtotalDisplay').textContent = fmt(sub);
        document.getElementById('taxDisplay').textContent = fmt(tax);
        document.getElementById('totalDisplay').textContent = fmt(total);
        document.getElementById('saveTotalDisplay').textContent = fmt(total);
        const rates = [...new Set(items.map(item => parseFloat(item.tax_rate || 0).toFixed(4)))];
        document.getElementById('taxLabel').textContent = rates.length === 1
            ? `IVA (${(parseFloat(rates[0]) * 100).toFixed(2)}%)`
            : (rates.length > 1 ? 'IVA (mixto)' : `IVA (${(parseFloat(app.dataset.defaultTaxRate || 0) * 100).toFixed(2)}%)`);
        const discRow = document.getElementById('discountRow');
        if (ordDisc > 0) {
            discRow.classList.remove('hidden');
            document.getElementById('discountDisplay').textContent = '-' + fmt(ordDisc);
        } else {
            discRow.classList.add('hidden');
        }
    }

    function revealTicketLine(index) {
        requestAnimationFrame(() => {
            const scroller = document.getElementById('ticketScroller');
            const row = scroller?.querySelector(`[data-ticket-idx="${index}"]`);
            if (!scroller || !row) {
                return;
            }

            row.classList.add('ticket-line--fresh');
            const rowBox = row.getBoundingClientRect();
            const viewBox = scroller.getBoundingClientRect();
            const padding = 8;
            if (rowBox.bottom > viewBox.bottom - padding) {
                scroller.scrollTop += rowBox.bottom - viewBox.bottom + padding;
            } else if (rowBox.top < viewBox.top + padding) {
                scroller.scrollTop -= viewBox.top - rowBox.top + padding;
            }
        });
    }

    function renderItems() {
        refreshItemPrices();
        const container = document.getElementById('proformaItems');
        const empty = document.getElementById('emptyProforma');

        if (items.length === 0) {
            container.innerHTML = '';
            empty.classList.remove('hidden');
            selectedIdx = -1;
            document.getElementById('selectedItemBar').classList.add('hidden');
            updateTotals();
            return;
        }

        empty.classList.add('hidden');
        container.innerHTML = items.map((item, idx) => `
            <div onclick="selectItem(${idx})" data-ticket-idx="${idx}" class="p-3 cursor-pointer transition-colors group ${selectedIdx === idx ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : 'hover:bg-slate-50'}">
                <div class="flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">${item.name}</p>
                        <div class="flex gap-2 text-xs text-slate-600 mt-1">
                            <span>Cant: <b>${item.quantity}</b></span>
                            <span>${(() => {
                                const product = products.find(p => p.id == item.product_id);
                                const units = product?.sale_units || [];
                                if (units.length < 2) return item.unit_label || '';
                                return `<select onclick="event.stopPropagation()" onchange="event.stopPropagation(); changeItemUnit(${idx}, this.value)" class="rounded border border-indigo-200 bg-indigo-50 px-1 py-0.5 text-xs font-semibold text-indigo-800">${units.map(unit => `<option value="${unit.id}" ${unit.id == item.unit_id ? 'selected' : ''}>${unit.abbreviation}</option>`).join('')}</select>`;
                            })()}
                            <span>Stock base: <b>${formatQuantity(item.stock)}</b></span>
                            <span>${fmt(item.price)}</span>
                            ${item.discount > 0 ? `<span class="text-red-600">-${item.discount}%</span>` : ''}
                        </div>
                        ${stockWarning(item) ? `<p class="mt-1 text-xs font-semibold text-red-600">⚠ ${stockWarning(item)}</p>` : ''}
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold text-slate-900 text-sm">${fmt(lineSubtotal(item))}</p>
                        <div class="flex gap-2 mt-1 justify-end">
                            <button type="button" onclick="event.stopPropagation(); applyItemDiscount(${idx})" class="text-xs text-indigo-600 hover:text-indigo-800">Dto.</button>
                            <button type="button" onclick="event.stopPropagation(); removeItem(${idx})" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        if (selectedIdx >= 0 && items[selectedIdx]) {
            document.getElementById('selectedItemBar').classList.remove('hidden');
            document.getElementById('selectedItemName').textContent = items[selectedIdx].name;
            document.getElementById('selectedItemQty').textContent = items[selectedIdx].quantity;
        }
        updateTotals();
    }

    window.selectItem = function(idx) {
        selectedIdx = idx;
        padBuffer = String(items[idx].quantity);
        renderItems();
        expandPosPad();
    };

    window.addProduct = function(productId) {
        const p = products.find(x => x.id == productId);
        if (!p) return;
        const existing = items.find(i => i.product_id == productId);
        if (existing) {
            existing.quantity += 1;
            const warning = stockWarning(existing);
            if (warning) alert(`Advertencia de stock\n\n${warning}`);
            renderItems();
            revealTicketLine(items.indexOf(existing));
            return;
        }

        const unit = p.sale_units.find(unit => unit.id == p.default_unit_id) || p.sale_units[0];
        const item = { product_id: productId, unit_id: unit?.id ?? null, unit_label: unit?.abbreviation ?? '', unit_factor: unit?.factor_to_base ?? 1, name: p.name, price: unit?.price ?? p.price, stock: p.stock, quantity: 1, discount: 0, tax_rate: p.tax_rate };
        items.push(item);
        const warning = stockWarning(item);
        if (warning) alert(`Advertencia de stock\n\n${warning}`);
        renderItems();
        revealTicketLine(items.length - 1);
    };

    window.applyItemDiscount = function(idx) {
        const v = parseFloat(prompt('Descuento (%) para este item:', items[idx].discount || '0'));
        if (!isNaN(v) && v >= 0 && v <= 100) { items[idx].discount = v; renderItems(); }
    };

    window.changeItemUnit = function(idx, unitId) {
        const item = items[idx];
        const product = products.find(p => p.id == item.product_id);
        const unit = product?.sale_units.find(candidate => candidate.id == unitId);
        if (!unit) return;
        item.unit_id = unit.id;
        item.unit_label = unit.abbreviation;
        item.unit_factor = unit.factor_to_base;
        item.price = unitPrice(unit, item.quantity);
        renderItems();
    };

    window.applyOrderDiscount = function() {
        const v = parseFloat(prompt('Descuento global del ticket (%):', orderDiscountPct || '0'));
        if (!isNaN(v) && v >= 0 && v <= 100) { orderDiscountPct = v; updateTotals(); }
    };

    window.removeItem = function(idx) {
        items.splice(idx, 1);
        selectedIdx = -1;
        padBuffer = '';
        renderItems();
    };

    function setPosPadOpen(open) {
        const pad = document.getElementById('posNumpad');
        const toggle = document.getElementById('posPadToggle');
        if (!pad) return;
        pad.classList.toggle('pos-pad--open', open);
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && selectedIdx >= 0) {
            window.setTimeout(() => revealTicketLine(selectedIdx), 280);
        }
    }

    window.togglePosPad = function() {
        const pad = document.getElementById('posNumpad');
        if (!pad) return;
        setPosPadOpen(!pad.classList.contains('pos-pad--open'));
    };

    window.expandPosPad = function() {
        setPosPadOpen(true);
    };

    window.padInput = function(key) {
        if (selectedIdx < 0) { alert('Selecciona un producto para editar cantidad'); return; }
        expandPosPad();
        if (padBuffer === '0' && key !== '.') padBuffer = key;
        else padBuffer += key;
        document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padBackspace = function() {
        padBuffer = padBuffer.slice(0, -1);
        if (selectedIdx >= 0) document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padAdjust = function(delta) {
        if (selectedIdx < 0) return;
        const current = parseFloat(padBuffer || items[selectedIdx].quantity) || 1;
        const next = Math.round((current + delta) * 100) / 100;
        if (next < 0.01) return;
        items[selectedIdx].quantity = next;
        padBuffer = String(next);
        renderItems();
    };

    window.padConfirm = function() {
        if (selectedIdx < 0) return;
        const qty = parseFloat(padBuffer) || 1;
        if (qty <= 0) { alert('Cantidad inválida'); return; }
        items[selectedIdx].quantity = qty;
        const warning = stockWarning(items[selectedIdx]);
        if (warning) alert(`Advertencia de stock\n\n${warning}`);
        padBuffer = '';
        renderItems();
    };

    window.clearItems = function() {
        if (items.length > 0 && !confirm('¿Limpiar la cotización?')) return;
        items = [];
        orderDiscountPct = 0;
        selectedIdx = -1;
        padBuffer = '';
        document.getElementById('productSearch').value = '';
        renderItems();
        renderProducts();
    };

    window.openSaveModal = function() {
        if (items.length === 0) { alert('Agrega al menos un producto'); return; }
        const warnings = currentStockWarnings();
        if (warnings.length > 0 && !confirm(`Advertencia de stock:\n\n${warnings.join('\n')}\n\n¿Deseas guardar la proforma de todas formas?`)) {
            return;
        }
        document.getElementById('saveClientId').value = currentClient || '';
        document.getElementById('saveItems').value = JSON.stringify(items.map(i => ({
            product_id: i.product_id,
            unit_id: i.unit_id,
            name: i.name,
            quantity: i.quantity,
            price: i.price,
            discount: i.discount || 0,
        })));
        document.getElementById('orderDiscountPctInput').value = orderDiscountPct;
        updateTotals();
        document.getElementById('saveModal').classList.remove('hidden');
    };

    window.selectClient = async function(id, name) {
        currentClient = id;
        document.getElementById('clientDisplay').textContent = name;
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('quickClientModal')?.classList.add('hidden');
        try {
            const url = new URL(app.dataset.productsUrl, window.location.origin);
            if (id) url.searchParams.set('client_id', id);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudo cargar la lista de precios.');
            products = (await response.json()).map(normalizeProduct);
            renderProducts(document.getElementById('productSearch').value);
            renderItems();
        } catch (error) {
            alert('No pudimos actualizar los precios del cliente. Intente seleccionarlo nuevamente.');
        }
    };

    window.openQuickClientModal = function() {
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('quickClientModal').classList.remove('hidden');
        window.setTimeout(() => document.querySelector('#quickClientForm input[name="name"]')?.focus(), 0);
    };

    function renderClientsList(filter) {
        const q = (filter || '').toLowerCase();
        const el = document.getElementById('clientsList');
        const filtered = clientsData.filter(c => !q || c.name.toLowerCase().includes(q));
        el.innerHTML = filtered.map(c => `
            <button type="button" onclick="selectClient(${c.id}, '${c.name.replace(/'/g,"\\'")}')"
                class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm">
                <p class="font-semibold text-slate-800">${c.name}</p>
                ${c.phone ? `<p class="text-xs text-slate-500">${c.phone}</p>` : ''}
            </button>`).join('');
    }

    document.getElementById('clientSearch')?.addEventListener('input', e => renderClientsList(e.target.value));

    document.querySelector('#quickClientForm select[name="client_type"]')?.addEventListener('change', function (e) {
        const isCompany = e.target.value === 'company';
        document.getElementById('cedulaField').classList.toggle('hidden', isCompany);
        document.getElementById('rucField').classList.toggle('hidden', !isCompany);
        document.getElementById('businessNameField').classList.toggle('hidden', !isCompany);
    });

    document.getElementById('quickClientForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalLabel = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();

            if (! response.ok || ! data.success) {
                const message = data.message
                    || (data.errors ? Object.values(data.errors).flat().join('\n') : null)
                    || 'No se pudo crear el cliente.';
                alert(message);
                return;
            }

            clientsData.push({
                id: data.client.id,
                name: data.client.name,
                phone: data.client.phone || '',
                email: data.client.email || '',
            });
            renderClientsList(document.getElementById('clientSearch')?.value || '');
            selectClient(data.client.id, data.client.name);
            form.reset();
            document.getElementById('cedulaField').classList.remove('hidden');
            document.getElementById('rucField').classList.add('hidden');
            document.getElementById('businessNameField').classList.add('hidden');
        } catch (error) {
            alert('Error de red al crear el cliente.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalLabel || 'Guardar y Seleccionar';
            }
        }
    });

    function renderProducts(filter) {
        const grid = document.querySelector('#productsGrid > div');
        let list = products;
        if (currentCategory !== 'all') list = list.filter(p => p.category_id == currentCategory);
        if (filter?.trim()) {
            const q = filter.toLowerCase();
            list = list.filter(p => p.name.toLowerCase().includes(q) || (p.code && p.code.toLowerCase().includes(q)));
        }
        if (list.length === 0) {
            grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-8">No se encontraron productos</p>';
            return;
        }
        grid.innerHTML = list.map(p => `
            <button type="button" onclick="addProduct(${p.id})"
                class="rounded-lg border border-slate-200 bg-white p-1.5 text-left transition-all hover:border-indigo-500 hover:shadow-sm">
                <div class="mb-1 flex h-12 w-full items-center justify-center overflow-hidden rounded bg-slate-100">
                    ${p.image_url
                        ? `<img src="${p.image_url}" alt="${p.name}" class="h-full w-full object-cover" referrerpolicy="no-referrer">`
                        : `<svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>`
                    }
                </div>
                <p class="line-clamp-2 min-h-[1.65rem] text-[11px] font-semibold leading-tight text-slate-800">${p.name}</p>
                <p class="mt-0.5 text-xs font-bold text-indigo-600">${fmt(p.price)}</p>
                <p class="mt-0.5 text-[9px] ${p.stock <= 0 ? 'text-red-500' : p.stock <= 5 ? 'text-amber-600' : 'text-slate-400'}">
                    ${p.stock <= 0 ? 'Sin stock' : p.stock}
                </p>
            </button>`).join('');
    }

    function renderCategoryTabs() {
        const el = document.getElementById('categoryTabs');
        let html = `<button type="button" onclick="setCategory('all')" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">Todos</button>`;
        categories.forEach(c => {
            html += `<button type="button" onclick="setCategory(${c.id})" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory == c.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${c.name}</button>`;
        });
        el.innerHTML = html;
    }

    window.setCategory = function(id) {
        currentCategory = id;
        renderCategoryTabs();
        renderProducts(document.getElementById('productSearch').value);
    };

    const searchInput = document.getElementById('productSearch');
    searchInput.addEventListener('input', e => renderProducts(e.target.value));
    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            const q = e.target.value.trim();
            const exact = products.find(p => p.code && p.code.toLowerCase() === q.toLowerCase());
            if (exact) { addProduct(exact.id); e.target.value = ''; renderProducts(); }
        }
    });

    // Init
    renderClientsList();
    renderCategoryTabs();
    renderProducts();
    renderItems();
});
</script>
@endpush

@endsection
