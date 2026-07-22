@extends('layouts.app')

@section('title', 'POS - Punto de Venta')
@section('hide-header')
@section('main-class', 'p-0 overflow-hidden')

@section('content')

<div id="posApp" class="h-full overflow-hidden bg-slate-50 flex"
     data-products='@json($products)'
     data-clients='@json($clients)'
     data-categories='@json($categories)'
     data-daily-report-url="{{ route('facturacion.pos-daily-report') }}">

    {{-- COLUMNA IZQUIERDA: TICKET --}}
    <div class="w-2/5 bg-white flex flex-col border-r border-slate-200">

        {{-- Barra de acciones rápidas --}}
        <div class="px-4 py-2 bg-slate-800 text-white flex items-center justify-between text-xs shrink-0">
            <span class="font-semibold">Ticket #<span id="ticketNumber">1</span></span>
            <div class="flex gap-2">
                <button type="button" onclick="holdTicket()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg" title="F4 - Apartar">Apartar</button>
                <button type="button" onclick="showHeldTickets()" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg">
                    Recuperar <span id="heldCount" class="bg-white/20 px-1 rounded">0</span>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto border-b border-slate-200">
            <div id="ticketItems" class="divide-y divide-slate-100"></div>
            <div id="emptyTicket" class="flex flex-col items-center justify-center h-full text-slate-400 py-12">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <p class="text-base font-medium text-slate-500">Ticket vacío</p>
                <p class="text-xs text-slate-400 mt-1">F2 buscar · F9 cobrar · F4 apartar</p>
            </div>
        </div>

        <div class="bg-slate-100 px-6 py-4 border-b border-slate-200 shrink-0">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span id="discountLabel" class="hidden">Descuento</span>
                    <span id="discountDisplay" class="font-medium text-red-600 hidden">-C$ 0.00</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>IVA (15%)</span>
                    <span id="taxDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="border-t border-slate-300 pt-2 flex justify-between">
                    <span class="text-sm font-semibold text-slate-700">Total</span>
                    <span id="totalDisplay" class="text-3xl font-bold text-slate-900">C$ 0.00</span>
                </div>
            </div>
        </div>

        <div class="p-4 space-y-3 overflow-y-auto shrink-0 max-h-[45%]">
            <button type="button" id="clientBtn" onclick="document.getElementById('clientModal').classList.remove('hidden')"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl px-4 transition-all shadow-md text-sm flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span id="clientDisplay">Cliente General</span>
            </button>

            <div id="selectedItemBar" class="hidden bg-indigo-50 border border-indigo-200 rounded-xl px-3 py-2 text-xs text-indigo-800">
                Editando: <span id="selectedItemName" class="font-semibold"></span> · Cant: <span id="selectedItemQty" class="font-bold">0</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                @foreach(['7','8','9','4','5','6','1','2','3'] as $key)
                <button type="button" onclick="padInput('{{ $key }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-3 rounded-xl text-sm shadow-sm">{{ $key }}</button>
                @endforeach
                <button type="button" onclick="padInput('0')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-3 rounded-xl text-sm shadow-sm col-span-2">0</button>
                <button type="button" onclick="padInput('.')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-3 rounded-xl text-sm shadow-sm">.</button>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="padBackspace()" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-xl text-sm">Borrar</button>
                <button type="button" onclick="padConfirm()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-xl text-sm">Confirmar cant.</button>
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA: PRODUCTOS Y PAGO --}}
    <div class="flex-1 bg-white flex flex-col min-w-0">

        <div class="p-3 border-b border-slate-200 bg-white shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" id="productSearch" placeholder="Buscar por nombre o código de barras..."
                    class="flex-1 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600" autocomplete="off">
                <button type="button" onclick="applyOrderDiscount()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium" title="Descuento global">% Dto.</button>
            </div>
            <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-1"></div>
        </div>

        <div id="productsGrid" class="flex-1 overflow-y-auto p-4 bg-slate-50">
            <div class="grid grid-cols-3 xl:grid-cols-4 gap-3"></div>
        </div>

        <div class="bg-white border-t border-slate-200 p-4 space-y-2 shrink-0 max-h-[50%] overflow-y-auto">
            <label class="block text-sm font-semibold text-slate-700">Método de Pago</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach([
                    ['cash', 'Efectivo', 'Pago en efectivo', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['card', 'Tarjeta', 'Crédito / débito', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ['transfer', 'Transferencia', 'Bancaria', 'M8 7h12m0 0l-4-4m4 4l-4 4m0-6H4m6 4v12a3 3 0 003 3h6a3 3 0 003-3V11a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3z'],
                    ['credit', 'Crédito', 'Cuenta cliente', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ] as [$method, $title, $sub, $icon])
                <button type="button" class="payment-method flex items-center gap-2 px-3 py-2 border-2 border-slate-200 rounded-xl text-left hover:border-slate-300 text-sm {{ $method === 'cash' ? 'border-indigo-600 bg-indigo-50' : '' }}"
                    data-method="{{ $method }}" @if($method === 'credit') id="creditMethodBtn" disabled @endif>
                    <div class="w-8 h-8 {{ $method === 'cash' ? 'bg-indigo-600' : 'bg-slate-200' }} rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 {{ $method === 'cash' ? 'text-white' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 truncate">{{ $title }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $sub }}</p>
                    </div>
                </button>
                @endforeach
            </div>

            <button type="button" id="payBtn" onclick="initiatePayment()"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>PAGAR (F9)</span>
                <span id="payBtnAmount">C$ 0.00</span>
            </button>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="clearTicket()" class="bg-slate-400 hover:bg-slate-500 text-white font-semibold py-2 rounded-xl text-sm">Descartar</button>
                <div class="flex gap-2">
                    <button type="button" id="dailyReportBtn" class="bg-slate-600 hover:bg-slate-700 text-white font-semibold py-2 rounded-xl text-sm">Corte del Día</button>
                    <button type="button" id="closeCashBtn" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 rounded-xl text-sm" title="Cierre de Caja (Arqueo)">Cierre Caja</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Cliente --}}
    <div id="clientModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Seleccionar Cliente</h2>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4">
                <input type="text" id="clientSearch" placeholder="Buscar cliente..." class="input-field mb-3">
            </div>
            <div class="px-4 pb-4 space-y-2 max-h-72 overflow-y-auto">
                <button type="button" onclick="selectClient(null, 'Cliente General')"
                    class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm">
                    <p class="font-semibold text-slate-800">Cliente General</p>
                    <p class="text-xs text-slate-500">Contado - Sin crédito</p>
                </button>
                <div id="clientsList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Pago --}}
    <div id="paymentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white">
                <h2 class="text-lg font-bold text-slate-900">Procesar Pago</h2>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p id="paymentMethodDisplay" class="text-sm text-slate-600 px-5 pt-3"></p>

            <form id="paymentForm" action="{{ route('facturacion.pos-store') }}" method="POST">
                @csrf
                <div class="p-5 space-y-4">
                    <div class="text-center">
                        <p class="text-sm text-slate-600">Total a cobrar</p>
                        <p class="text-4xl font-bold text-indigo-600" id="paymentTotalDisplay">C$ 0.00</p>
                    </div>

                    <div id="cashSection" class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700">Monto recibido</label>
                        <input type="number" id="amountReceived" step="0.01" placeholder="0.00"
                            class="w-full px-4 py-3 text-2xl font-bold border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none text-center bg-slate-50">
                        <p class="text-sm text-slate-600 text-center">Cambio: <span id="changeDisplay" class="font-bold text-slate-900">C$ 0.00</span></p>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([10, 20, 50, 100, 200, 500, 1000] as $bill)
                            <button type="button" onclick="addBill({{ $bill }})" class="py-2 bg-slate-100 rounded-xl text-xs font-bold hover:bg-indigo-100 hover:text-indigo-700">C$ {{ $bill }}</button>
                            @endforeach
                            <button type="button" onclick="setExactAmount()" class="py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700">Exacto</button>
                        </div>
                    </div>

                    <div id="transferSection" class="space-y-2 hidden">
                        <label class="text-sm font-semibold text-slate-700">Número de referencia</label>
                        <input type="text" id="referenceNumber" placeholder="Ej: 123456789" class="input-field">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Notas (opcional)</label>
                        <textarea id="saleNotes" rows="2" placeholder="Observaciones..." class="input-field resize-none"></textarea>
                    </div>
                </div>

                <input type="hidden" name="payment_type" id="paymentTypeInput">
                <input type="hidden" name="client_id" id="clientIdInput">
                <input type="hidden" name="items" id="itemsInput" value="[]">
                <input type="hidden" name="notes" id="notesInput">
                <input type="hidden" name="amount_received" id="amountReceivedInput">
                <input type="hidden" name="reference_number" id="referenceNumberInput">

                <div class="p-4 border-t border-slate-200 space-y-2 sticky bottom-0 bg-white">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl">Confirmar Pago</button>
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Tickets apartados --}}
    <div id="heldModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Tickets Apartados</h2>
                <button type="button" onclick="document.getElementById('heldModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="heldTicketsList" class="p-4 space-y-2 max-h-80 overflow-y-auto"></div>
        </div>
    </div>

    {{-- MODAL: Corte del día --}}
    <div id="dailyReportModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Corte de Caja del Día</h2>
                <button type="button" onclick="document.getElementById('dailyReportModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="dailyReportContent" class="p-5 space-y-4">
                <p class="text-slate-500 text-center">Cargando...</p>
            </div>
            <div class="p-4 border-t border-slate-200">
                <button type="button" onclick="window.open('{{ route('facturacion.index') }}?date=' + new Date().toISOString().split('T')[0], '_blank')"
                    class="w-full btn-primary justify-center">Ver detalle de ventas</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const app = document.getElementById('posApp');
    const products = JSON.parse(app.dataset.products).map(p => ({
        id: p.id,
        code: p.code ?? '',
        name: p.name,
        price: parseFloat(p.sale_price ?? 0),
        stock: parseInt(p.stock ?? 0),
        category_id: p.category_id,
        category_name: p.category?.name ?? 'Sin categoría',
        image_url: p.image_url ?? null,
    }));
    const clientsData = JSON.parse(app.dataset.clients)
        .filter(c => c.code !== 'GEN')
        .map(c => ({
            id: c.id,
            name: c.name ?? '',
            business_name: c.business_name ?? '',
            ruc: c.ruc ?? '',
            credit_enabled: !!c.credit_enabled,
            credit_limit: parseFloat(c.credit_limit ?? 0),
            credit_days: parseInt(c.credit_days ?? 30),
            balance: parseFloat(c.balance ?? 0),
            available_credit: c.available_credit === null ? null : parseFloat(c.available_credit ?? 0),
            over_limit: !!c.over_limit,
        }));
    const categories = JSON.parse(app.dataset.categories);
    const dailyReportUrl = app.dataset.dailyReportUrl;

    let ticket = [];
    let currentClient = null;
    let selectedItemIndex = -1;
    let padBuffer = '';
    let currentCategory = 'all';
    let orderDiscountPct = 0;
    let ticketCounter = parseInt(localStorage.getItem('pos_ticket_counter') || '1');
    let currentPaymentMethod = 'cash';
    const TAX_RATE = 0.15;
    const HELD_KEY = 'pos_held_tickets';

    document.getElementById('ticketNumber').textContent = ticketCounter;

    // Cierre de caja button - abre la pantalla de arqueo
    const closeBtn = document.getElementById('closeCashBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(){
            // Abrir en la misma pestaña la vista de apertura/cierre
            window.location.href = '{{ route('arqueo.index') }}';
        });
    }

    function formatMoney(v) {
        return 'C$ ' + parseFloat(v || 0).toFixed(2);
    }

    function lineSubtotal(item) {
        return item.price * item.quantity * (1 - item.discount / 100);
    }

    function getTotal() {
        const subtotal = ticket.reduce((sum, item) => sum + lineSubtotal(item), 0);
        const orderDiscount = subtotal * (orderDiscountPct / 100);
        const taxable = subtotal - orderDiscount;
        const tax = taxable * TAX_RATE;
        return { subtotal, orderDiscount, tax, total: taxable + tax };
    }

    function updateTotals() {
        const { subtotal, orderDiscount, tax, total } = getTotal();
        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('taxDisplay').textContent = formatMoney(tax);
        document.getElementById('totalDisplay').textContent = formatMoney(total);
        document.getElementById('paymentTotalDisplay').textContent = formatMoney(total);
        document.getElementById('payBtnAmount').textContent = formatMoney(total);

        const discLabel = document.getElementById('discountLabel');
        const discDisplay = document.getElementById('discountDisplay');
        if (orderDiscount > 0) {
            discLabel.classList.remove('hidden');
            discDisplay.classList.remove('hidden');
            discDisplay.textContent = '-' + formatMoney(orderDiscount) + ` (${orderDiscountPct}%)`;
        } else {
            discLabel.classList.add('hidden');
            discDisplay.classList.add('hidden');
        }
    }

    function renderTicket() {
        const container = document.getElementById('ticketItems');
        const emptyMsg = document.getElementById('emptyTicket');

        if (ticket.length === 0) {
            container.innerHTML = '';
            emptyMsg.classList.remove('hidden');
            selectedItemIndex = -1;
            document.getElementById('selectedItemBar').classList.add('hidden');
            updateTotals();
            return;
        }

        emptyMsg.classList.add('hidden');
        container.innerHTML = ticket.map((item, idx) => `
            <div onclick="selectTicketItem(${idx})" class="p-3 cursor-pointer transition-colors group border-b border-slate-100 ${selectedItemIndex === idx ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : 'hover:bg-slate-50'}">
                <div class="flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">${item.name}</p>
                        <div class="flex gap-2 text-xs text-slate-600 mt-1">
                            <span>Cant: <b>${item.quantity}</b></span>
                            <span>${formatMoney(item.price)}</span>
                            ${item.discount > 0 ? `<span class="text-red-600">-${item.discount}%</span>` : ''}
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold text-slate-900 text-sm">${formatMoney(lineSubtotal(item))}</p>
                        <div class="flex gap-2 mt-1 justify-end">
                            <button type="button" onclick="event.stopPropagation(); applyDiscount(${idx})" class="text-xs text-indigo-600 hover:text-indigo-800">Dto.</button>
                            <button type="button" onclick="event.stopPropagation(); removeTicketItem(${idx})" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        if (selectedItemIndex >= 0 && ticket[selectedItemIndex]) {
            document.getElementById('selectedItemBar').classList.remove('hidden');
            document.getElementById('selectedItemName').textContent = ticket[selectedItemIndex].name;
            document.getElementById('selectedItemQty').textContent = ticket[selectedItemIndex].quantity;
        }
        updateTotals();
    }

    window.selectTicketItem = function(idx) {
        selectedItemIndex = idx;
        padBuffer = String(ticket[idx].quantity);
        renderTicket();
    };

    window.addProductToTicket = function(productId, qty = 1) {
        const product = products.find(p => p.id == productId);
        if (!product) return;

        if (product.stock <= 0) {
            alert('Producto sin stock disponible');
            return;
        }

        const existing = ticket.find(item => item.product_id == productId);
        const newQty = (existing ? existing.quantity : 0) + qty;

        if (newQty > product.stock) {
            alert(`Stock insuficiente. Disponible: ${product.stock}`);
            return;
        }

        if (existing) {
            existing.quantity = newQty;
        } else {
            ticket.push({
                product_id: productId,
                name: product.name,
                price: product.price,
                quantity: qty,
                discount: 0,
                max_stock: product.stock,
            });
        }
        renderTicket();
    };

    window.applyDiscount = function(idx) {
        const discount = prompt('Porcentaje de descuento (0-100):', ticket[idx].discount || '0');
        if (discount !== null) {
            const v = parseFloat(discount);
            if (!isNaN(v) && v >= 0 && v <= 100) {
                ticket[idx].discount = v;
                renderTicket();
            }
        }
    };

    window.applyOrderDiscount = function() {
        const discount = prompt('Descuento global del ticket (%):', orderDiscountPct || '0');
        if (discount !== null) {
            const v = parseFloat(discount);
            if (!isNaN(v) && v >= 0 && v <= 100) {
                orderDiscountPct = v;
                updateTotals();
            }
        }
    };

    window.removeTicketItem = function(idx) {
        ticket.splice(idx, 1);
        selectedItemIndex = -1;
        padBuffer = '';
        renderTicket();
    };

    window.padInput = function(key) {
        if (selectedItemIndex < 0) {
            alert('Selecciona un producto del ticket para editar cantidad');
            return;
        }
        if (padBuffer === '0' && key !== '.') padBuffer = key;
        else padBuffer += key;
        document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padBackspace = function() {
        padBuffer = padBuffer.slice(0, -1);
        if (selectedItemIndex >= 0) {
            document.getElementById('selectedItemQty').textContent = padBuffer || '0';
        }
    };

    window.padConfirm = function() {
        if (selectedItemIndex < 0) return;
        const qty = parseFloat(padBuffer) || 1;
        const item = ticket[selectedItemIndex];
        const product = products.find(p => p.id == item.product_id);
        const maxStock = product?.stock ?? item.max_stock;

        if (qty <= 0) { alert('Cantidad inválida'); return; }
        if (qty > maxStock) { alert(`Stock máximo: ${maxStock}`); return; }

        item.quantity = qty;
        padBuffer = '';
        renderTicket();
    };

    function renderProducts(filter = '') {
        const grid = document.querySelector('#productsGrid > div');
        let filtered = products;

        if (currentCategory !== 'all') {
            filtered = filtered.filter(p => p.category_id == currentCategory);
        }
        if (filter.trim()) {
            const q = filter.toLowerCase();
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(q) ||
                (p.code && p.code.toLowerCase().includes(q))
            );
        }

        if (filtered.length === 0) {
            grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-8">No se encontraron productos</p>';
            return;
        }

        grid.innerHTML = filtered.map(p => {
            const lowStock = p.stock <= 5;
            const outStock = p.stock <= 0;
            return `
            <button type="button" onclick="addProductToTicket(${p.id})" ${outStock ? 'disabled' : ''}
                class="bg-white border-2 ${outStock ? 'border-slate-100 opacity-50 cursor-not-allowed' : 'border-slate-200 hover:border-indigo-500 hover:shadow-md'} rounded-xl p-3 transition-all group text-left">
                <div class="w-full h-20 mb-2 rounded-lg overflow-hidden bg-slate-100 flex items-center justify-center mx-auto">
                    ${p.image_url
                        ? `<img src="${p.image_url}" alt="${p.name}" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                           <span style="display:none" class="w-full h-full items-center justify-center"><svg class=\"w-8 h-8 text-slate-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4\"></path></svg></span>`
                        : `<svg class="w-8 h-8 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>`
                    }
                </div>
                <p class="font-semibold text-slate-800 text-xs line-clamp-2 text-center">${p.name}</p>
                <p class="text-xs text-slate-400 text-center mt-0.5">${p.code || '—'}</p>
                <p class="text-base font-bold text-indigo-600 text-center mt-1">${formatMoney(p.price)}</p>
                <p class="text-xs text-center mt-1 ${outStock ? 'text-red-600 font-bold' : lowStock ? 'text-amber-600' : 'text-slate-400'}">
                    ${outStock ? 'Sin stock' : 'Stock: ' + p.stock}
                </p>
            </button>`;
        }).join('');
    }

    function renderCategoryTabs() {
        const tabs = document.getElementById('categoryTabs');
        let html = `<button type="button" onclick="setCategory('all')" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">Todos</button>`;
        categories.forEach(c => {
            html += `<button type="button" onclick="setCategory(${c.id})" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory == c.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${c.name}</button>`;
        });
        tabs.innerHTML = html;
    }

    window.setCategory = function(id) {
        currentCategory = id;
        renderCategoryTabs();
        renderProducts(document.getElementById('productSearch').value);
    };

    const searchInput = document.getElementById('productSearch');
    searchInput.addEventListener('input', (e) => renderProducts(e.target.value));
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = e.target.value.trim();
            const exact = products.find(p => p.code && p.code.toLowerCase() === q.toLowerCase());
            if (exact) {
                addProductToTicket(exact.id);
                e.target.value = '';
                renderProducts();
            }
        }
    });

    window.selectClient = function(clientId, clientName) {
        currentClient = clientId;
        document.getElementById('clientDisplay').textContent = clientName;
        document.getElementById('clientIdInput').value = clientId || '';

        const client = clientId ? clientsData.find(c => c.id == clientId) : null;
        const creditBtn = document.getElementById('creditMethodBtn');
        if (creditBtn) {
            const canCredit = client && client.credit_enabled;
            creditBtn.disabled = !canCredit;
            creditBtn.classList.toggle('opacity-50', !canCredit);
            if (canCredit && client.credit_limit > 0) {
                const avail = client.available_credit ?? 0;
                creditBtn.title = `Disponible: C$ ${parseFloat(avail).toFixed(2)} · Plazo: ${client.credit_days} días`;
            }
        }

        if (currentPaymentMethod === 'credit' && (!client || !client.credit_enabled)) {
            currentPaymentMethod = 'cash';
            updatePaymentMethodSelection();
        }

        document.getElementById('clientModal').classList.add('hidden');
    };

    function renderClientsList(filter = '') {
        const container = document.getElementById('clientsList');
        const q = filter.toLowerCase();
        const filtered = clientsData.filter(c =>
            !q || c.name.toLowerCase().includes(q) || (c.ruc && c.ruc.includes(q))
        );
        container.innerHTML = filtered.map(c => `
            <button type="button" onclick="selectClient(${c.id}, '${c.name.replace(/'/g, "\\'")}')"
                class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm ${c.over_limit ? 'border-red-300 bg-red-50' : ''}">
                <div class="flex justify-between items-start">
                    <p class="font-semibold text-slate-800">${c.name}</p>
                    ${c.credit_enabled ? '<span class="badge-info text-xs">Crédito</span>' : ''}
                </div>
                ${c.credit_enabled ? `<p class="text-xs text-slate-500 mt-1">Límite: C$ ${parseFloat(c.credit_limit || 0).toFixed(0)} · Saldo: C$ ${parseFloat(c.balance || 0).toFixed(2)} · ${c.credit_days}d</p>` : '<p class="text-xs text-slate-400">Solo contado</p>'}
            </button>
        `).join('');
    }

    document.getElementById('clientSearch')?.addEventListener('input', (e) => renderClientsList(e.target.value));

    function updatePaymentMethodSelection() {
        document.querySelectorAll('.payment-method').forEach(btn => {
            const active = btn.dataset.method === currentPaymentMethod;
            btn.classList.toggle('border-indigo-600', active);
            btn.classList.toggle('bg-indigo-50', active);
            btn.classList.toggle('border-slate-200', !active);
            const icon = btn.querySelector('div');
            if (icon) {
                icon.classList.toggle('bg-indigo-600', active);
                icon.classList.toggle('bg-slate-200', !active);
                const svg = icon.querySelector('svg');
                if (svg) {
                    svg.classList.toggle('text-white', active);
                    svg.classList.toggle('text-slate-600', !active);
                }
            }
        });
    }

    document.querySelectorAll('.payment-method').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            currentPaymentMethod = btn.dataset.method;
            updatePaymentMethodSelection();
        });
    });

    window.initiatePayment = function() {
        const { total } = getTotal();
        if (total === 0) { alert('El ticket está vacío'); return; }

        document.getElementById('paymentTypeInput').value = currentPaymentMethod;
        document.getElementById('clientIdInput').value = currentClient || '';
        document.getElementById('cashSection').classList.toggle('hidden', currentPaymentMethod !== 'cash');
        document.getElementById('transferSection').classList.toggle('hidden', !['transfer', 'card'].includes(currentPaymentMethod));

        const names = { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia', credit: 'Crédito' };
        document.getElementById('paymentMethodDisplay').textContent = 'Método: ' + names[currentPaymentMethod];
        document.getElementById('paymentModal').classList.remove('hidden');

        if (currentPaymentMethod === 'cash') {
            document.getElementById('amountReceived').focus();
        }
    };

    window.addBill = function(amount) {
        const input = document.getElementById('amountReceived');
        input.value = ((parseFloat(input.value) || 0) + amount).toFixed(2);
        input.dispatchEvent(new Event('input'));
    };

    window.setExactAmount = function() {
        document.getElementById('amountReceived').value = getTotal().total.toFixed(2);
        document.getElementById('amountReceived').dispatchEvent(new Event('input'));
    };

    document.getElementById('amountReceived').addEventListener('input', (e) => {
        const amount = parseFloat(e.target.value) || 0;
        const change = amount - getTotal().total;
        document.getElementById('changeDisplay').textContent = formatMoney(change);
        document.getElementById('amountReceivedInput').value = amount;
    });

    document.getElementById('paymentForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const { total } = getTotal();
        const type = document.getElementById('paymentTypeInput').value;

        if (type === 'cash') {
            const amount = parseFloat(document.getElementById('amountReceived').value) || 0;
            if (amount < total) { alert('Monto recibido insuficiente'); return; }
        }
        if (['transfer', 'card'].includes(type)) {
            const ref = document.getElementById('referenceNumber').value.trim();
            if (!ref) { alert('Ingresa el número de referencia'); return; }
            document.getElementById('referenceNumberInput').value = ref;
        }

        let notes = document.getElementById('saleNotes').value;
        if (orderDiscountPct > 0) notes += (notes ? ' | ' : '') + `Descuento global: ${orderDiscountPct}%`;

        document.getElementById('itemsInput').value = JSON.stringify(ticket.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price,
            discount: item.discount || 0,
        })));
        document.getElementById('notesInput').value = notes;
        e.target.submit();
    });

    window.clearTicket = function() {
        if (ticket.length > 0 && !confirm('¿Descartar ticket actual?')) return;
        ticket = [];
        orderDiscountPct = 0;
        selectedItemIndex = -1;
        padBuffer = '';
        searchInput.value = '';
        renderTicket();
        renderProducts();
    };

    function getHeldTickets() {
        return JSON.parse(localStorage.getItem(HELD_KEY) || '[]');
    }

    function updateHeldCount() {
        document.getElementById('heldCount').textContent = getHeldTickets().length;
    }

    window.holdTicket = function() {
        if (ticket.length === 0) { alert('No hay productos en el ticket'); return; }
        const held = getHeldTickets();
        held.push({
            id: Date.now(),
            label: `Ticket ${held.length + 1} - ${document.getElementById('clientDisplay').textContent}`,
            items: ticket,
            client: currentClient,
            clientName: document.getElementById('clientDisplay').textContent,
            orderDiscountPct,
            savedAt: new Date().toLocaleString(),
        });
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        ticket = [];
        orderDiscountPct = 0;
        currentClient = null;
        document.getElementById('clientDisplay').textContent = 'Cliente General';
        renderTicket();
        updateHeldCount();
        ticketCounter++;
        localStorage.setItem('pos_ticket_counter', ticketCounter);
        document.getElementById('ticketNumber').textContent = ticketCounter;
    };

    window.showHeldTickets = function() {
        const held = getHeldTickets();
        const list = document.getElementById('heldTicketsList');
        if (held.length === 0) {
            list.innerHTML = '<p class="text-slate-500 text-center py-4">No hay tickets apartados</p>';
        } else {
            list.innerHTML = held.map((h, idx) => `
                <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${h.label}</p>
                        <p class="text-xs text-slate-500">${h.items.length} productos · ${h.savedAt}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="resumeHeldTicket(${idx})" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs">Recuperar</button>
                        <button type="button" onclick="deleteHeldTicket(${idx})" class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs">Eliminar</button>
                    </div>
                </div>
            `).join('');
        }
        document.getElementById('heldModal').classList.remove('hidden');
    };

    window.resumeHeldTicket = function(idx) {
        const held = getHeldTickets();
        const saved = held[idx];
        if (!saved) return;
        if (ticket.length > 0 && !confirm('¿Reemplazar ticket actual?')) return;

        ticket = saved.items;
        currentClient = saved.client;
        orderDiscountPct = saved.orderDiscountPct || 0;
        document.getElementById('clientDisplay').textContent = saved.clientName;
        held.splice(idx, 1);
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        updateHeldCount();
        document.getElementById('heldModal').classList.add('hidden');
        renderTicket();
    };

    window.deleteHeldTicket = function(idx) {
        const held = getHeldTickets();
        held.splice(idx, 1);
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        updateHeldCount();
        showHeldTickets();
    };

    document.getElementById('dailyReportBtn').addEventListener('click', async () => {
        const modal = document.getElementById('dailyReportModal');
        const content = document.getElementById('dailyReportContent');
        modal.classList.remove('hidden');
        content.innerHTML = '<p class="text-slate-500 text-center">Cargando...</p>';

        try {
            const res = await fetch(dailyReportUrl);
            const data = await res.json();
            let paymentsHtml = '';
            Object.entries(data.by_payment).forEach(([type, row]) => {
                if (row.count > 0) {
                    paymentsHtml += `<div class="flex justify-between text-sm py-1"><span>${row.label} (${row.count})</span><span class="font-semibold">${formatMoney(row.total)}</span></div>`;
                }
            });

            content.innerHTML = `
                <div class="text-center mb-4">
                    <p class="text-xs text-slate-500">${data.date} · Cajero: ${data.cashier}</p>
                    <p class="text-4xl font-black text-indigo-600 mt-2">${formatMoney(data.total_sales)}</p>
                    <p class="text-sm text-slate-600">${data.invoice_count} ventas · Ticket prom: ${formatMoney(data.average_ticket)}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 space-y-1">
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Por método de pago</p>
                    ${paymentsHtml || '<p class="text-sm text-slate-400">Sin ventas hoy</p>'}
                </div>`;
        } catch {
            content.innerHTML = '<p class="text-red-600 text-center">Error al cargar el reporte</p>';
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            if (e.key === 'Escape') e.target.blur();
            return;
        }
        if (e.key === 'F2') { e.preventDefault(); searchInput.focus(); }
        if (e.key === 'F9') { e.preventDefault(); initiatePayment(); }
        if (e.key === 'F4') { e.preventDefault(); holdTicket(); }
        if (e.key === 'Escape') clearTicket();
    });

    renderCategoryTabs();
    renderClientsList();
    renderProducts();
    renderTicket();
    updateHeldCount();
    updatePaymentMethodSelection();
});
</script>
@endpush

@endsection
