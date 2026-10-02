@php
    $defaultWarehouseId = $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id;
    $locationRows = old('locations', [[
        'warehouse_id' => old('warehouse_id', $defaultWarehouseId),
        'shelf_id' => old('shelf_id'),
        'quantity' => old('stock', 0),
    ]]);
    $locationRows = is_array($locationRows) && count($locationRows) ? $locationRows : [[]];
@endphp

<div id="{{ $locationPrefix }}Rows" class="space-y-3 rounded-2xl border-2 border-amber-300 bg-amber-50/70 p-4 shadow-sm {{ $locationWrapperClass ?? '' }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-base font-bold text-amber-950">Stock inicial del producto</p>
            <p class="text-sm text-amber-900">Ingresa la cantidad recibida y elige su bodega antes de guardar.</p>
        </div>
        <button type="button" data-add-location class="btn-outline px-2.5 py-1.5 text-xs">+ Agregar ubicación</button>
    </div>
    <div data-location-list class="space-y-2">
        @foreach($locationRows as $index => $row)
            <div data-location-row class="grid grid-cols-1 items-end gap-3 rounded-xl border border-amber-200 bg-white p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(10rem,13rem)_auto]">
                <div class="flex min-w-0 gap-1">
                    <select name="locations[{{ $index }}][warehouse_id]" data-location-warehouse @if($index === 0) id="{{ $warehouseSelectId }}" aria-label="Bodega del stock inicial" @else aria-label="Bodega adicional del stock inicial" @endif class="select-field min-w-0 py-1.5 text-sm" required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" data-code="{{ $warehouse->code }}" data-name="{{ $warehouse->name }}" @selected(($row['warehouse_id'] ?? null) == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" data-open-location-modal="warehouse" class="btn-outline shrink-0 px-3 py-1.5 text-base font-bold" title="Agregar nueva bodega" aria-label="Agregar nueva bodega">+</button>
                </div>
                <div class="flex min-w-0 gap-1">
                    <select name="locations[{{ $index }}][shelf_id]" data-location-shelf @if($index === 0) id="{{ $shelfSelectId }}" @endif class="select-field min-w-0 py-1.5 text-sm">
                        <option value="">Sin estante</option>
                        @foreach($warehouses as $warehouse)
                            @foreach($warehouse->shelves as $shelf)
                                <option value="{{ $shelf->id }}" data-warehouse="{{ $warehouse->id }}" data-code="{{ $shelf->code }}" data-name="{{ $shelf->name }}" @selected(($row['shelf_id'] ?? null) == $shelf->id)>{{ $shelf->label() }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    <button type="button" data-open-location-modal="shelf" class="btn-outline shrink-0 px-3 py-1.5 text-base font-bold" title="Agregar nuevo estante" aria-label="Agregar nuevo estante">+</button>
                </div>
                <label class="block min-w-0 text-sm font-bold text-amber-950">Cantidad inicial
                    <input type="number" name="locations[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 0 }}" min="0" step="0.0001" inputmode="decimal" data-location-quantity class="input-field mt-1 min-h-12 w-full border-2 border-amber-400 bg-amber-50 px-3 text-xl font-bold tabular-nums text-slate-900 focus:border-amber-600 focus:ring-amber-200" placeholder="0" required>
                </label>
                <button type="button" data-remove-location class="rounded-lg px-2 text-rose-600 hover:bg-rose-50" title="Quitar ubicación">×</button>
            </div>
        @endforeach
    </div>
    <p data-location-total-banner class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-right text-sm font-bold text-rose-800" role="status">Stock inicial total: <span data-location-total>0</span></p>
</div>

<template id="{{ $locationPrefix }}Template">
    <div data-location-row class="grid grid-cols-1 items-end gap-3 rounded-xl border border-amber-200 bg-white p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(10rem,13rem)_auto]">
        <div class="flex min-w-0 gap-1">
            <select name="locations[__INDEX__][warehouse_id]" data-location-warehouse class="select-field min-w-0 py-1.5 text-sm" required>
                @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" data-code="{{ $warehouse->code }}" data-name="{{ $warehouse->name }}">{{ $warehouse->name }}</option>@endforeach
            </select>
            <button type="button" data-open-location-modal="warehouse" class="btn-outline shrink-0 px-3 py-1.5 text-base font-bold" title="Agregar nueva bodega" aria-label="Agregar nueva bodega">+</button>
        </div>
        <div class="flex min-w-0 gap-1">
            <select name="locations[__INDEX__][shelf_id]" data-location-shelf class="select-field min-w-0 py-1.5 text-sm">
                <option value="">Sin estante</option>
                @foreach($warehouses as $warehouse)@foreach($warehouse->shelves as $shelf)<option value="{{ $shelf->id }}" data-warehouse="{{ $warehouse->id }}" data-code="{{ $shelf->code }}" data-name="{{ $shelf->name }}">{{ $shelf->label() }}</option>@endforeach @endforeach
            </select>
            <button type="button" data-open-location-modal="shelf" class="btn-outline shrink-0 px-3 py-1.5 text-base font-bold" title="Agregar nuevo estante" aria-label="Agregar nuevo estante">+</button>
        </div>
        <label class="block min-w-0 text-sm font-bold text-amber-950">Cantidad inicial
            <input type="number" name="locations[__INDEX__][quantity]" value="0" min="0" step="0.0001" inputmode="decimal" data-location-quantity class="input-field mt-1 min-h-12 w-full border-2 border-amber-400 bg-amber-50 px-3 text-xl font-bold tabular-nums text-slate-900 focus:border-amber-600 focus:ring-amber-200" placeholder="0" required>
        </label>
        <button type="button" data-remove-location class="rounded-lg px-2 text-rose-600 hover:bg-rose-50" title="Quitar ubicación">×</button>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const root = document.getElementById(@json($locationPrefix.'Rows'));
    const list = root?.querySelector('[data-location-list]');
    const template = document.getElementById(@json($locationPrefix.'Template'));
    if (!root || !list || !template) return;
    let nextIndex = list.querySelectorAll('[data-location-row]').length;

    const updateRow = row => {
        const warehouse = row.querySelector('[data-location-warehouse]');
        const shelf = row.querySelector('[data-location-shelf]');
        Array.from(shelf.options).forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.warehouse !== warehouse.value;
            option.disabled = option.hidden;
        });
        if (shelf.selectedOptions[0]?.disabled) shelf.value = '';
    };
    const updateTotal = () => {
        const total = Array.from(root.querySelectorAll('[data-location-quantity]'))
            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
        root.querySelector('[data-location-total]').textContent = total.toLocaleString('es-NI', {maximumFractionDigits: 4});
        const banner = root.querySelector('[data-location-total-banner]');
        banner.classList.toggle('border-rose-200', total <= 0);
        banner.classList.toggle('bg-rose-50', total <= 0);
        banner.classList.toggle('text-rose-800', total <= 0);
        banner.classList.toggle('border-emerald-200', total > 0);
        banner.classList.toggle('bg-emerald-50', total > 0);
        banner.classList.toggle('text-emerald-800', total > 0);
    };
    const bindRow = row => {
        row.querySelector('[data-location-warehouse]').addEventListener('change', () => updateRow(row));
        row.querySelector('[data-location-quantity]').addEventListener('input', updateTotal);
        row.querySelector('[data-remove-location]').addEventListener('click', () => {
            if (list.querySelectorAll('[data-location-row]').length === 1) return;
            row.remove();
            updateTotal();
        });
        updateRow(row);
    };
    list.querySelectorAll('[data-location-row]').forEach(bindRow);
    root.querySelector('[data-add-location]').addEventListener('click', () => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', nextIndex++).trim();
        const row = wrapper.firstElementChild;
        list.appendChild(row);
        bindRow(row);
        updateTotal();
    });
    root.closest('form')?.addEventListener('submit', event => {
        const total = Array.from(root.querySelectorAll('[data-location-quantity]'))
            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
        if (total <= 0 && !window.confirm('El stock inicial está en 0. ¿Guardar el producto sin existencias?')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            root.querySelector('[data-location-quantity]')?.focus();
        }
    }, true);
    updateTotal();
});
</script>
@endpush
