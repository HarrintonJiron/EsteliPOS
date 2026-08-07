@props([
    'action',
    'method' => 'POST',
    'purchase' => null,
    'suppliers',
    'warehouses',
    'initialItems' => [],
    'title',
    'submitLabel' => 'Registrar compra',
])

@php
    $isEdit = $purchase !== null;
    $defaultWarehouseId = old(
        'warehouse_id',
        $purchase?->warehouse_id ?? $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id,
    );
@endphp

<div
    id="purchaseApp"
    class="flex min-h-[calc(100dvh-4rem)] flex-col bg-slate-50 lg:flex-row"
    data-initial-items='@json($initialItems)'
    data-search-url="{{ route('compras.products.search') }}"
>
    <form
        action="{{ $action }}"
        method="POST"
        id="purchaseForm"
        class="contents"
    >
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        {{-- Panel lateral: contexto y totales --}}
        <aside class="flex w-full shrink-0 flex-col border-b border-slate-200 bg-white lg:w-[340px] lg:border-b-0 lg:border-r">
            <div class="border-b border-slate-100 px-5 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    {{ $isEdit ? 'Editar compra' : 'Nueva compra' }}
                </p>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $title }}</h1>
                @if($isEdit)
                    <p class="mt-0.5 text-sm text-slate-500">#{{ $purchase->id }}</p>
                @endif
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="supplier_id" class="mb-1 block text-xs font-medium text-slate-500">Proveedor</label>
                    <select name="supplier_id" id="supplier_id" class="select-field" required>
                        <option value="">Seleccionar proveedor</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase?->supplier_id) == $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="date" class="mb-1 block text-xs font-medium text-slate-500">Fecha</label>
                        <input
                            type="date"
                            name="date"
                            id="date"
                            value="{{ old('date', $purchase?->date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                            class="input-field"
                            required
                        />
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-xs font-medium text-slate-500">Estado</label>
                        <select name="status" id="status" class="select-field">
                            @foreach(['completed' => 'Completada', 'pending' => 'Pendiente', 'canceled' => 'Anulada'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $purchase?->status ?? 'completed') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="warehouse_id" class="mb-1 block text-xs font-medium text-slate-500">Bodega destino</label>
                    <select name="warehouse_id" id="warehouse_id" class="select-field">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected($defaultWarehouseId == $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">
                    <p class="font-medium text-slate-700">Atajos</p>
                    <p class="mt-1"><kbd class="rounded bg-white px-1.5 py-0.5 shadow-sm">/</kbd> buscar producto</p>
                    <p class="mt-0.5"><kbd class="rounded bg-white px-1.5 py-0.5 shadow-sm">Enter</kbd> agregar primer resultado</p>
                </div>
            </div>

            <div class="mt-auto border-t border-slate-100 bg-slate-50 px-5 py-4">
                <div class="mb-3 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Líneas</span>
                        <span id="purchaseLineCount" class="font-medium text-slate-800">0</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Unidades</span>
                        <span id="purchaseUnitCount" class="font-medium text-slate-800">0</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2">
                        <span class="font-semibold text-slate-700">Total estimado</span>
                        <span id="purchaseTotal" class="text-2xl font-bold text-slate-900">C$ 0.00</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ $isEdit ? route('compras.show', $purchase->id) : route('compras.index') }}" class="btn-outline flex-1 justify-center text-center">
                        Cancelar
                    </a>
                    <button type="submit" id="purchaseSubmit" class="btn-primary flex-[1.4] justify-center" disabled>
                        {{ $submitLabel }}
                    </button>
                </div>
            </div>
        </aside>

        {{-- Área principal: búsqueda + líneas --}}
        <section class="flex min-h-0 flex-1 flex-col">
            <div class="border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                <div class="relative max-w-3xl">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="search"
                        id="productSearch"
                        placeholder="Buscar producto por nombre o código…"
                        class="input-field pl-10 text-base"
                        autocomplete="off"
                    />
                    <div
                        id="searchResults"
                        class="absolute z-30 mt-1 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                        role="listbox"
                    ></div>
                </div>
                <p id="searchHint" class="mt-2 text-xs text-slate-500">Busca en todo el inventario. Si el producto tiene costo del proveedor, se usa automáticamente.</p>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                <div id="purchaseLines" class="mx-auto max-w-3xl space-y-2"></div>

                <div id="purchaseEmpty" class="mx-auto flex max-w-md flex-col items-center justify-center py-20 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="text-base font-medium text-slate-700">Sin productos en la compra</p>
                    <p class="mt-1 text-sm text-slate-500">Busca cualquier producto del inventario y agrégalo a la compra.</p>
                </div>
            </div>
        </section>
    </form>
</div>

<script>
(() => {
    const app = document.getElementById('purchaseApp');
    const form = document.getElementById('purchaseForm');
    const supplierSelect = document.getElementById('supplier_id');
    const searchInput = document.getElementById('productSearch');
    const searchResults = document.getElementById('searchResults');
    const searchHint = document.getElementById('searchHint');
    const linesContainer = document.getElementById('purchaseLines');
    const emptyState = document.getElementById('purchaseEmpty');
    const totalEl = document.getElementById('purchaseTotal');
    const lineCountEl = document.getElementById('purchaseLineCount');
    const unitCountEl = document.getElementById('purchaseUnitCount');
    const submitBtn = document.getElementById('purchaseSubmit');
    const searchUrl = app.dataset.searchUrl;

    let items = JSON.parse(app.dataset.initialItems || '[]');
    let searchCache = [];
    let searchTimer = null;
    let activeResultIndex = -1;

    const money = (value) => `C$ ${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    function updateSearchState() {
        searchHint.textContent = supplierSelect.value
            ? 'Todo el inventario. Costo del proveedor cuando exista en su catálogo.'
            : 'Todo el inventario. Selecciona proveedor para aplicar su costo de catálogo si existe.';
    }

    function hideResults() {
        searchResults.classList.add('hidden');
        searchResults.innerHTML = '';
        searchCache = [];
        activeResultIndex = -1;
    }

    function renderResults(products) {
        searchCache = products;
        activeResultIndex = products.length ? 0 : -1;

        if (!products.length) {
            searchResults.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Sin coincidencias en el inventario.</div>';
            searchResults.classList.remove('hidden');
            return;
        }

        searchResults.innerHTML = products.map((product, index) => `
            <button
                type="button"
                class="purchase-result flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-slate-50 ${index === activeResultIndex ? 'bg-indigo-50' : ''}"
                data-index="${index}"
                role="option"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium text-slate-800">${escapeHtml(product.name)}</p>
                    <p class="text-xs text-slate-500">${escapeHtml(product.code)}${product.has_supplier_price ? ' · costo proveedor' : ''}</p>
                </div>
                <span class="shrink-0 text-sm font-semibold text-emerald-700">${money(product.price)}</span>
            </button>
        `).join('');

        searchResults.classList.remove('hidden');

        searchResults.querySelectorAll('.purchase-result').forEach((button) => {
            button.addEventListener('click', () => addProduct(searchCache[Number(button.dataset.index)]));
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    async function runSearch(term) {
        if (term.length < 2) {
            hideResults();
            return;
        }

        const params = new URLSearchParams({ search: term });
        if (supplierSelect.value) {
            params.set('supplier_id', supplierSelect.value);
        }

        const response = await fetch(`${searchUrl}?${params.toString()}`);
        const products = await response.json();
        renderResults(products);
    }

    function addProduct(product) {
        const existing = items.find((item) => item.id === product.id);

        if (existing) {
            existing.quantity += 1;
        } else {
            items.push({
                id: product.id,
                name: product.name,
                code: product.code,
                quantity: 1,
                price: Number(product.price) || 0,
            });
        }

        searchInput.value = '';
        hideResults();
        renderLines();
        searchInput.focus();
    }

    function removeItem(index) {
        items.splice(index, 1);
        renderLines();
    }

    function updateItem(index, field, value) {
        if (field === 'quantity') {
            items[index].quantity = Math.max(1, parseInt(value, 10) || 1);
        }

        if (field === 'price') {
            items[index].price = Math.max(0, parseFloat(value) || 0);
        }

        renderLines();
    }

    function stepQty(index, delta) {
        items[index].quantity = Math.max(1, items[index].quantity + delta);
        renderLines();
    }

    function renderLines() {
        linesContainer.innerHTML = '';

        let total = 0;
        let units = 0;

        items.forEach((item, index) => {
            const subtotal = item.quantity * item.price;
            total += subtotal;
            units += item.quantity;

            const row = document.createElement('div');
            row.className = 'rounded-xl border border-slate-200 bg-white p-3 shadow-sm';
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">${escapeHtml(item.name)}</p>
                        <p class="text-xs text-slate-500">${escapeHtml(item.code)}</p>
                    </div>
                    <button type="button" data-remove="${index}" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Quitar">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mt-3 grid grid-cols-[auto_1fr_1fr_auto] items-end gap-3">
                    <div>
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Cant.</p>
                        <div class="flex items-center rounded-lg border border-slate-200 bg-slate-50">
                            <button type="button" data-step="${index}" data-delta="-1" class="px-2.5 py-1.5 text-slate-600 hover:text-slate-900">−</button>
                            <input type="number" min="1" value="${item.quantity}" data-qty="${index}" class="w-14 border-0 bg-transparent py-1.5 text-center text-sm font-semibold focus:ring-0" />
                            <button type="button" data-step="${index}" data-delta="1" class="px-2.5 py-1.5 text-slate-600 hover:text-slate-900">+</button>
                        </div>
                    </div>
                    <div>
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Costo unit.</p>
                        <input type="number" min="0" step="0.01" value="${item.price}" data-price="${index}" class="input-field py-1.5 text-sm" />
                    </div>
                    <div class="text-right">
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Subtotal</p>
                        <p class="text-sm font-bold text-slate-800">${money(subtotal)}</p>
                    </div>
                </div>
                <input type="hidden" name="items[${index}][product_id]" value="${item.id}" />
                <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}" />
                <input type="hidden" name="items[${index}][price]" value="${item.price}" />
            `;

            linesContainer.appendChild(row);
        });

        linesContainer.querySelectorAll('[data-remove]').forEach((button) => {
            button.addEventListener('click', () => removeItem(Number(button.dataset.remove)));
        });

        linesContainer.querySelectorAll('[data-step]').forEach((button) => {
            button.addEventListener('click', () => stepQty(Number(button.dataset.step), Number(button.dataset.delta)));
        });

        linesContainer.querySelectorAll('[data-qty]').forEach((input) => {
            input.addEventListener('change', (event) => updateItem(Number(input.dataset.qty), 'quantity', event.target.value));
        });

        linesContainer.querySelectorAll('[data-price]').forEach((input) => {
            input.addEventListener('change', (event) => updateItem(Number(input.dataset.price), 'price', event.target.value));
        });

        emptyState.classList.toggle('hidden', items.length > 0);
        lineCountEl.textContent = String(items.length);
        unitCountEl.textContent = String(units);
        totalEl.textContent = money(total);
        submitBtn.disabled = items.length === 0;
    }

    supplierSelect.addEventListener('change', () => {
        updateSearchState();
        if (searchInput.value.trim().length >= 2) {
            runSearch(searchInput.value.trim());
        }
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => runSearch(searchInput.value.trim()), 220);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && searchCache.length) {
            event.preventDefault();
            activeResultIndex = Math.min(activeResultIndex + 1, searchCache.length - 1);
            renderResults(searchCache);
        }

        if (event.key === 'ArrowUp' && searchCache.length) {
            event.preventDefault();
            activeResultIndex = Math.max(activeResultIndex - 1, 0);
            renderResults(searchCache);
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            if (activeResultIndex >= 0 && searchCache[activeResultIndex]) {
                addProduct(searchCache[activeResultIndex]);
            }
        }

        if (event.key === 'Escape') {
            hideResults();
        }
    });

    document.addEventListener('click', (event) => {
        if (!searchResults.contains(event.target) && event.target !== searchInput) {
            hideResults();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && document.activeElement !== searchInput && !event.metaKey && !event.ctrlKey && !event.altKey) {
            const tag = document.activeElement?.tagName?.toLowerCase();
            if (tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
                event.preventDefault();
                searchInput.focus();
            }
        }
    });

    form.addEventListener('submit', (event) => {
        linesContainer.querySelectorAll('[data-qty]').forEach((input) => {
            const index = Number(input.dataset.qty);
            items[index].quantity = Math.max(1, parseInt(input.value, 10) || 1);
        });

        linesContainer.querySelectorAll('[data-price]').forEach((input) => {
            const index = Number(input.dataset.price);
            items[index].price = Math.max(0, parseFloat(input.value) || 0);
        });

        if (!items.length) {
            event.preventDefault();
            alert('Agrega al menos un producto a la compra.');
            return;
        }

        renderLines();
    });

    updateSearchState();
    renderLines();
})();
</script>
