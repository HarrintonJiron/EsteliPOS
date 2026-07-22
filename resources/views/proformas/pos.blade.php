@extends('layouts.app')

@section('title', 'Nueva Proforma')
@section('hide-header')
@section('main-class', 'p-0 overflow-hidden')

@section('content')

<div id="proformaApp" class="h-full overflow-hidden bg-slate-50 flex"
     data-products='@json($products)'
     data-clients='@json($clients)'
     data-categories='@json($categories)'>

    {{-- COLUMNA IZQUIERDA: ITEMS --}}
    <div class="w-2/5 bg-white flex flex-col border-r border-slate-200">

        <div class="px-4 py-2 bg-indigo-700 text-white flex items-center justify-between text-xs shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="font-semibold">Nueva Proforma / Cotización</span>
            </div>
            <a href="{{ route('proformas.index') }}" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg">← Volver</a>
        </div>

        <div class="flex-1 overflow-y-auto border-b border-slate-200">
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
                    <span>IVA (15%)</span>
                    <span id="taxDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="border-t border-slate-300 pt-2 flex justify-between">
                    <span class="text-sm font-semibold text-slate-700">Total</span>
                    <span id="totalDisplay" class="text-3xl font-bold text-slate-900">C$ 0.00</span>
                </div>
            </div>
        </div>

        <div class="p-4 space-y-3 overflow-y-auto shrink-0">
            <button type="button" id="clientBtn"
                onclick="document.getElementById('clientModal').classList.remove('hidden')"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl px-4 text-sm flex items-center justify-center gap-2 transition-all shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span id="clientDisplay">Sin cliente asignado</span>
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
    <div class="flex-1 bg-white flex flex-col min-w-0">

        <div class="p-3 border-b border-slate-200 bg-white shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" id="productSearch" placeholder="Buscar producto por nombre o código..."
                    class="flex-1 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600" autocomplete="off">
                <button type="button" onclick="applyOrderDiscount()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium" title="Descuento global">% Dto.</button>
            </div>
            <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-1"></div>
        </div>

        <div id="productsGrid" class="flex-1 overflow-y-auto p-4 bg-slate-50">
            <div class="grid grid-cols-3 xl:grid-cols-4 gap-3"></div>
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
            <div class="p-4 border-t border-slate-200">
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl text-sm">Cerrar</button>
            </div>
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
    const products = JSON.parse(app.dataset.products).map(p => ({
        id: p.id,
        code: p.code ?? '',
        name: p.name,
        price: parseFloat(p.sale_price ?? 0),
        stock: parseInt(p.stock ?? 0),
        category_id: p.category_id,
        image_url: p.image_url ?? null,
    }));
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
    const TAX = 0.15;

    function fmt(v) { return 'C$ ' + parseFloat(v || 0).toFixed(2); }

    function lineSubtotal(item) {
        return item.price * item.quantity * (1 - item.discount / 100);
    }

    function getTotal() {
        const sub = items.reduce((s, i) => s + lineSubtotal(i), 0);
        const ordDisc = sub * (orderDiscountPct / 100);
        const taxable = sub - ordDisc;
        const tax = taxable * TAX;
        return { sub, ordDisc, tax, total: taxable + tax };
    }

    function updateTotals() {
        const { sub, ordDisc, tax, total } = getTotal();
        document.getElementById('subtotalDisplay').textContent = fmt(sub);
        document.getElementById('taxDisplay').textContent = fmt(tax);
        document.getElementById('totalDisplay').textContent = fmt(total);
        document.getElementById('saveTotalDisplay').textContent = fmt(total);
        const discRow = document.getElementById('discountRow');
        if (ordDisc > 0) {
            discRow.classList.remove('hidden');
            document.getElementById('discountDisplay').textContent = '-' + fmt(ordDisc);
        } else {
            discRow.classList.add('hidden');
        }
    }

    function renderItems() {
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
            <div onclick="selectItem(${idx})" class="p-3 cursor-pointer transition-colors group ${selectedIdx === idx ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : 'hover:bg-slate-50'}">
                <div class="flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">${item.name}</p>
                        <div class="flex gap-2 text-xs text-slate-600 mt-1">
                            <span>Cant: <b>${item.quantity}</b></span>
                            <span>${fmt(item.price)}</span>
                            ${item.discount > 0 ? `<span class="text-red-600">-${item.discount}%</span>` : ''}
                        </div>
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
    };

    window.addProduct = function(productId) {
        const p = products.find(x => x.id == productId);
        if (!p) return;
        const existing = items.find(i => i.product_id == productId);
        if (existing) {
            existing.quantity += 1;
        } else {
            items.push({ product_id: productId, name: p.name, price: p.price, quantity: 1, discount: 0 });
        }
        renderItems();
    };

    window.applyItemDiscount = function(idx) {
        const v = parseFloat(prompt('Descuento (%) para este item:', items[idx].discount || '0'));
        if (!isNaN(v) && v >= 0 && v <= 100) { items[idx].discount = v; renderItems(); }
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

    window.padInput = function(key) {
        if (selectedIdx < 0) { alert('Selecciona un producto para editar cantidad'); return; }
        if (padBuffer === '0' && key !== '.') padBuffer = key;
        else padBuffer += key;
        document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padBackspace = function() {
        padBuffer = padBuffer.slice(0, -1);
        if (selectedIdx >= 0) document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padConfirm = function() {
        if (selectedIdx < 0) return;
        const qty = parseFloat(padBuffer) || 1;
        if (qty <= 0) { alert('Cantidad inválida'); return; }
        items[selectedIdx].quantity = qty;
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
        document.getElementById('saveClientId').value = currentClient || '';
        document.getElementById('saveItems').value = JSON.stringify(items.map(i => ({
            product_id: i.product_id,
            name: i.name,
            quantity: i.quantity,
            price: i.price,
            discount: i.discount || 0,
        })));
        updateTotals();
        document.getElementById('saveModal').classList.remove('hidden');
    };

    window.selectClient = function(id, name) {
        currentClient = id;
        document.getElementById('clientDisplay').textContent = name;
        document.getElementById('clientModal').classList.add('hidden');
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
                class="bg-white border-2 border-slate-200 hover:border-indigo-500 hover:shadow-md rounded-xl p-3 transition-all text-left">
                <div class="w-full h-16 mb-2 rounded-lg overflow-hidden bg-slate-100 flex items-center justify-center">
                    ${p.image_url
                        ? `<img src="${p.image_url}" alt="${p.name}" class="w-full h-full object-cover" loading="lazy">`
                        : `<svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>`
                    }
                </div>
                <p class="font-semibold text-slate-800 text-xs line-clamp-2 text-center">${p.name}</p>
                <p class="text-base font-bold text-indigo-600 text-center mt-1">${fmt(p.price)}</p>
                <p class="text-xs text-center mt-1 ${p.stock <= 0 ? 'text-red-500' : p.stock <= 5 ? 'text-amber-600' : 'text-slate-400'}">
                    Stock: ${p.stock}
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
