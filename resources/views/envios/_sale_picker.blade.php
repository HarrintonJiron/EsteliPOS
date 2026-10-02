@php
    /** @var \Illuminate\Support\Collection $sales */
    $selectedSaleId = (string) old('sale_id', $shipment?->sale_id);
    $shippedSales = $shippedSales ?? [];
    $paymentLabels = ['cash' => 'Contado', 'transfer' => 'Transferencia / tarjeta', 'card' => 'Tarjeta', 'credit' => 'Crédito'];
    $dateFormat = $companyProfile['date_format'] ?? 'd/m/Y';
@endphp

<div class="md:col-span-2" id="sale-picker">
    <span class="text-sm font-semibold text-slate-700">Venta relacionada <span class="font-normal text-slate-400">(opcional)</span></span>
    <p class="text-xs text-slate-500">Toca el recuadro para ver las ventas, buscar por cliente, producto, teléfono o factura, y elegir una. Al elegirla se completan los datos vacíos del envío.</p>

    <button type="button" id="sale-picker-toggle" aria-expanded="false" aria-controls="sale-picker-panel"
            class="mt-2 flex w-full items-center gap-3 rounded-2xl border border-slate-300 bg-white p-2 text-left shadow-sm transition hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300">
        <span id="sale-picker-summary" class="min-w-0 flex-1 text-sm">
            <strong class="block px-1 text-slate-800">Sin factura relacionada</strong>
        </span>
        <span class="flex shrink-0 items-center gap-1 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700">
            <span id="sale-picker-toggle-label">Cambiar</span>
            <svg id="sale-picker-chevron" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </span>
    </button>

    <div id="sale-picker-panel" class="mt-2 hidden rounded-2xl border border-slate-200 bg-slate-50 p-2 shadow-lg">
    <input type="search" id="sale-picker-search" class="input-field mb-2 w-full" placeholder="Buscar por cliente, producto, teléfono o factura…" autocomplete="off" aria-label="Buscar venta">
    <div class="max-h-72 space-y-2 overflow-y-auto" id="sale-picker-list" role="radiogroup" aria-label="Venta relacionada">
        <label class="sale-option block cursor-pointer" data-search="sin factura relacionada ninguna">
            <input type="radio" name="sale_id" value="" class="peer sr-only" @checked($selectedSaleId === '')>
            <span class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm transition peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-2 peer-checked:ring-sky-200 hover:border-sky-300">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">—</span>
                <span><strong class="block text-slate-800">Sin factura relacionada</strong><span class="text-xs text-slate-500">El envío no estará ligado a una venta.</span></span>
            </span>
        </label>

        @foreach($sales as $sale)
            @php
                $customer = $sale->billing_name ?: ($sale->client?->name ?: 'Cliente general');
                $products = $sale->details->map(fn ($detail) => $detail->product?->name)->filter()->values();
                $shown = $products->take(2)->map(fn ($name) => \Illuminate\Support\Str::limit($name, 32))->implode(', ');
                $extra = max(0, $products->count() - 2);
                $phone = collect([$sale->billing_phone, $sale->client?->phone])
                    ->first(fn ($value) => filled($value) && ! in_array(\Illuminate\Support\Str::lower(trim($value)), ['n/a', 'na', '-', '--', 'sin telefono', 'sin teléfono'], true));
                $searchText = \Illuminate\Support\Str::lower($customer.' '.$products->implode(' ').' '.$sale->notes.' '.$phone.' '.$sale->invoice_number);
                $existingShipment = $shippedSales[$sale->id] ?? null;
            @endphp
            <label class="sale-option block cursor-pointer" data-search="{{ $searchText }}">
                <input type="radio" name="sale_id" value="{{ $sale->id }}" class="peer sr-only" @checked($selectedSaleId === (string) $sale->id)
                       data-client-id="{{ $sale->client_id }}"
                       data-name="{{ $customer }}"
                       data-phone="{{ $phone }}"
                       data-department="{{ $sale->client?->department }}"
                       data-municipality="{{ $sale->client?->municipality }}"
                       data-address="{{ $sale->billing_address ?: $sale->client?->address }}">
                <span class="block rounded-xl border border-slate-200 bg-white p-3 text-sm transition peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:ring-2 peer-checked:ring-sky-200 hover:border-sky-300">
                    <span class="flex items-start justify-between gap-3">
                        <strong class="min-w-0 truncate text-slate-900">{{ $customer }}</strong>
                        <strong class="shrink-0 text-slate-900">@money($sale->total, 2)</strong>
                    </span>
                    <span class="mt-0.5 block truncate text-slate-600">
                        @if($products->isNotEmpty()){{ $shown }}@if($extra > 0) <span class="text-slate-400">+{{ $extra }} más</span>@endif
                        @elseif(filled($sale->notes))<span class="italic">{{ \Illuminate\Support\Str::limit($sale->notes, 70) }}</span>
                        @else<span class="italic text-slate-400">Sin detalle de productos</span>@endif
                    </span>
                    <span class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                        <span>{{ \Illuminate\Support\Carbon::parse($sale->date)->format($dateFormat) }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">{{ $paymentLabels[$sale->payment_type] ?? ucfirst((string) $sale->payment_type) }}</span>
                        @if($sale->status === 'pending')<span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">Pendiente de pago</span>@endif
                        <span class="text-slate-400">Factura {{ $sale->invoice_number }}</span>
                        @if($existingShipment)<span class="rounded-full bg-violet-100 px-2 py-0.5 font-semibold text-violet-800">Ya tiene envío {{ $existingShipment }}</span>@endif
                    </span>
                </span>
            </label>
        @endforeach

        <p id="sale-picker-empty" class="hidden p-4 text-center text-sm text-slate-500">Ninguna venta coincide con la búsqueda.</p>
    </div>
    @if($sales->count() >= 100)
        <p class="mt-2 px-1 text-xs text-slate-400">Se muestran las últimas 100 ventas. Usa el buscador para acotar.</p>
    @endif
    </div>
    @error('sale_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('sale-picker');
    if (!root) return;

    const search = document.getElementById('sale-picker-search');
    const empty = document.getElementById('sale-picker-empty');
    const options = Array.from(root.querySelectorAll('.sale-option'));
    const toggle = document.getElementById('sale-picker-toggle');
    const panel = document.getElementById('sale-picker-panel');
    const summary = document.getElementById('sale-picker-summary');
    const toggleLabel = document.getElementById('sale-picker-toggle-label');
    const chevron = document.getElementById('sale-picker-chevron');

    function setOpen(open) {
        panel.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggleLabel.textContent = open ? 'Cerrar' : 'Cambiar';
        chevron.classList.toggle('rotate-180', open);
        if (open) {
            search.focus();
            const checkedOption = root.querySelector('input[name=sale_id]:checked');
            if (checkedOption && checkedOption.value) checkedOption.closest('.sale-option').scrollIntoView({ block: 'nearest' });
        }
    }

    // El recuadro cerrado muestra la tarjeta de la venta elegida (o "Sin factura relacionada").
    function refreshSummary() {
        const checkedInput = root.querySelector('input[name=sale_id]:checked');
        const card = checkedInput ? checkedInput.closest('.sale-option').lastElementChild : null;
        if (!card) return;
        summary.innerHTML = '';
        const copy = card.cloneNode(true);
        copy.className = 'block p-1 text-sm';
        summary.appendChild(copy);
        toggleLabel.textContent = panel.classList.contains('hidden') ? (checkedInput.value ? 'Cambiar' : 'Elegir') : 'Cerrar';
    }

    toggle.addEventListener('click', function () { setOpen(panel.classList.contains('hidden')); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.classList.contains('hidden')) { setOpen(false); toggle.focus(); }
    });
    document.addEventListener('click', function (event) {
        if (!panel.classList.contains('hidden') && !root.contains(event.target)) setOpen(false);
    });

    search.addEventListener('input', function () {
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        options.forEach(function (option) {
            const isNone = !option.querySelector('input').value;
            const match = isNone || term === '' || option.dataset.search.includes(term);
            option.classList.toggle('hidden', !match);
            if (match && !isNone) visible++;
        });
        empty.classList.toggle('hidden', visible > 0 || term === '');
    });

    // Al elegir una venta se completan solo los campos que siguen vacíos.
    function fillIfEmpty(field, value) {
        if (field && !field.value && value) field.value = value;
    }

    root.addEventListener('change', function (event) {
        const input = event.target;
        if (input.name !== 'sale_id') return;

        // Al elegir, se actualiza el resumen y el desplegable se cierra.
        refreshSummary();
        setOpen(false);
        if (!input.value) return;

        const form = input.closest('form');
        const client = form.querySelector('#shipment-client');
        if (client && !client.value && input.dataset.clientId) {
            client.value = input.dataset.clientId;
        }
        fillIfEmpty(form.querySelector('#recipient-name'), input.dataset.name);
        fillIfEmpty(form.querySelector('#recipient-phone'), input.dataset.phone);
        fillIfEmpty(form.querySelector('[name=department]'), input.dataset.department);
        fillIfEmpty(form.querySelector('[name=municipality]'), input.dataset.municipality);
        fillIfEmpty(form.querySelector('[name=address]'), input.dataset.address);
    });

    refreshSummary();
});
</script>
