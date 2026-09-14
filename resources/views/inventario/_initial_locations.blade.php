@php
    $defaultWarehouseId = $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id;
    $locationRows = old('locations', [[
        'warehouse_id' => old('warehouse_id', $defaultWarehouseId),
        'shelf_id' => old('shelf_id'),
        'quantity' => old('stock', 0),
    ]]);
    $locationRows = is_array($locationRows) && count($locationRows) ? $locationRows : [[]];
@endphp

<div id="{{ $locationPrefix }}Rows" class="space-y-2 {{ $locationWrapperClass ?? '' }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-700">Existencias por ubicación</p>
            <p class="text-xs text-slate-500">Distribuye el stock entre bodegas y estantes.</p>
        </div>
        <button type="button" data-add-location class="btn-outline px-2.5 py-1.5 text-xs">+ Agregar ubicación</button>
    </div>
    <div data-location-list class="space-y-2">
        @foreach($locationRows as $index => $row)
            <div data-location-row class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 sm:grid-cols-[1fr_1fr_8rem_auto]">
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
                <input type="number" name="locations[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 0 }}" min="0" step="0.0001" data-location-quantity class="input-field py-1.5 text-sm" placeholder="Cantidad" required>
                <button type="button" data-remove-location class="rounded-lg px-2 text-rose-600 hover:bg-rose-50" title="Quitar ubicación">×</button>
            </div>
        @endforeach
    </div>
    <p class="text-right text-xs font-semibold text-slate-600">Stock inicial total: <span data-location-total>0</span></p>
</div>

<template id="{{ $locationPrefix }}Template">
    <div data-location-row class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 sm:grid-cols-[1fr_1fr_8rem_auto]">
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
        <input type="number" name="locations[__INDEX__][quantity]" value="0" min="0" step="0.0001" data-location-quantity class="input-field py-1.5 text-sm" placeholder="Cantidad" required>
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
    updateTotal();
});
</script>
@endpush
