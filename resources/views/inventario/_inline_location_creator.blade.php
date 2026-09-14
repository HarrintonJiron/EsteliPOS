@php($locationPrefix = $locationPrefix ?? 'productLocation')
<div class="space-y-2 {{ $locationWrapperClass ?? '' }}"
     id="{{ $locationPrefix }}Creator"
     data-warehouse-select="{{ $warehouseSelectId }}"
     data-shelf-select="{{ $shelfSelectId }}"
     data-warehouse-url="{{ route('inventario.warehouses.store') }}"
     data-shelf-url="{{ url('/inventario/bodegas/__WAREHOUSE__/estantes') }}">
    <div data-location-panel="warehouse" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $locationPrefix }}WarehouseTitle">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="{{ $locationPrefix }}WarehouseTitle" class="font-semibold text-slate-800">Nueva bodega</h3>
                <button type="button" data-close-location-modal class="rounded-lg px-2 py-1 text-xl text-slate-500 hover:bg-slate-100" aria-label="Cerrar">×</button>
            </div>
            <div class="p-5">
                <p class="mb-3 text-sm text-slate-600">Crea una bodega sin salir del producto.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <input type="text" data-field="warehouse-code" maxlength="20" class="input-field py-1.5 text-sm" placeholder="Código (BOD-04)">
                    <input type="text" data-field="warehouse-name" maxlength="120" class="input-field py-1.5 text-sm" placeholder="Nombre de la bodega">
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <span data-status="warehouse" class="mr-auto text-xs text-slate-500"></span>
                    <button type="button" data-close-location-modal class="btn-outline px-3 py-1.5 text-xs">Cancelar</button>
                    <button type="button" data-save="warehouse" class="btn-primary px-3 py-1.5 text-xs">Crear y seleccionar</button>
                </div>
            </div>
        </div>
    </div>

    <div data-location-panel="shelf" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $locationPrefix }}ShelfTitle">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="{{ $locationPrefix }}ShelfTitle" class="font-semibold text-slate-800">Nuevo estante</h3>
                <button type="button" data-close-location-modal class="rounded-lg px-2 py-1 text-xl text-slate-500 hover:bg-slate-100" aria-label="Cerrar">×</button>
            </div>
            <div class="p-5">
                <p class="mb-3 text-sm text-slate-600">El estante se agregará a la bodega seleccionada.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <input type="text" data-field="shelf-code" maxlength="50" class="input-field py-1.5 text-sm" placeholder="Código (A-01)">
                    <input type="text" data-field="shelf-name" maxlength="120" class="input-field py-1.5 text-sm" placeholder="Descripción opcional">
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <span data-status="shelf" class="mr-auto text-xs text-slate-500"></span>
                    <button type="button" data-close-location-modal class="btn-outline px-3 py-1.5 text-xs">Cancelar</button>
                    <button type="button" data-save="shelf" class="btn-primary px-3 py-1.5 text-xs">Crear y seleccionar</button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const root = document.getElementById(@json($locationPrefix.'Creator'));
    if (!root) return;

    const warehouseSelect = document.getElementById(root.dataset.warehouseSelect);
    const shelfSelect = document.getElementById(root.dataset.shelfSelect);
    const formScope = root.closest('form') || document;
    const csrf = @json(csrf_token());
    let activeWarehouseSelect = warehouseSelect;
    let activeShelfSelect = shelfSelect;

    const showStatus = (type, message, error = false) => {
        const status = root.querySelector(`[data-status="${type}"]`);
        status.textContent = message;
        status.classList.toggle('text-red-600', error);
        status.classList.toggle('text-emerald-600', !error && message !== '');
    };
    const firstError = data => Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar.';

    const closePanel = panel => {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
    };
    const openPanel = type => {
        const panel = root.querySelector(`[data-location-panel="${type}"]`);
        root.querySelectorAll('[data-location-panel]').forEach(item => {
            if (item !== panel) closePanel(item);
        });
        showStatus(type, '');
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        panel.querySelector('input')?.focus();
    };

    formScope.addEventListener('click', event => {
        const button = event.target.closest('[data-open-location-modal]');
        if (!button || !formScope.contains(button)) return;
        const row = button.closest('[data-location-row]');
        activeWarehouseSelect = row?.querySelector('[data-location-warehouse]') || warehouseSelect;
        activeShelfSelect = row?.querySelector('[data-location-shelf]') || shelfSelect;
        openPanel(button.dataset.openLocationModal);
    });
    root.querySelectorAll('[data-close-location-modal]').forEach(button => {
        button.addEventListener('click', () => closePanel(button.closest('[data-location-panel]')));
    });
    root.querySelectorAll('[data-location-panel]').forEach(panel => {
        panel.addEventListener('click', event => {
            if (event.target === panel) closePanel(panel);
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') root.querySelectorAll('[data-location-panel]').forEach(closePanel);
    });

    root.querySelector('[data-save="warehouse"]').addEventListener('click', async () => {
        const code = root.querySelector('[data-field="warehouse-code"]').value.trim();
        const name = root.querySelector('[data-field="warehouse-name"]').value.trim();
        showStatus('warehouse', 'Guardando...');
        try {
            const response = await fetch(root.dataset.warehouseUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({code, name}),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(firstError(data));
            const warehouse = data.warehouse;
            formScope.querySelectorAll('[data-location-warehouse]').forEach(select => {
                const option = new Option(warehouse.name, warehouse.id);
                option.dataset.code = warehouse.code;
                option.dataset.name = warehouse.name;
                select.add(option);
            });
            formScope.querySelectorAll('template').forEach(template => {
                template.content.querySelectorAll('[data-location-warehouse]').forEach(select => {
                    const option = new Option(warehouse.name, warehouse.id);
                    option.dataset.code = warehouse.code;
                    option.dataset.name = warehouse.name;
                    select.add(option);
                });
            });
            activeWarehouseSelect.value = warehouse.id;
            activeWarehouseSelect.dispatchEvent(new Event('change'));
            root.querySelector('[data-field="warehouse-code"]').value = '';
            root.querySelector('[data-field="warehouse-name"]').value = '';
            showStatus('warehouse', 'Bodega creada y seleccionada.');
            closePanel(root.querySelector('[data-location-panel="warehouse"]'));
        } catch (error) {
            showStatus('warehouse', error.message, true);
        }
    });

    root.querySelector('[data-save="shelf"]').addEventListener('click', async () => {
        const warehouseId = activeWarehouseSelect?.value;
        if (!warehouseId) {
            showStatus('shelf', 'Selecciona una bodega primero.', true);
            return;
        }
        const code = root.querySelector('[data-field="shelf-code"]').value.trim();
        const name = root.querySelector('[data-field="shelf-name"]').value.trim();
        showStatus('shelf', 'Guardando...');
        try {
            const url = root.dataset.shelfUrl.replace('__WAREHOUSE__', warehouseId);
            const response = await fetch(url, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({code, name}),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(firstError(data));
            const shelf = data.shelf;
            const addShelfOption = select => {
                const option = new Option(shelf.label, shelf.id);
                option.dataset.warehouse = shelf.warehouse_id;
                option.dataset.code = shelf.code;
                option.dataset.name = shelf.name || '';
                const rowWarehouse = select.closest('[data-location-row]')?.querySelector('[data-location-warehouse]')?.value;
                if (rowWarehouse && String(rowWarehouse) !== String(shelf.warehouse_id)) {
                    option.hidden = true;
                    option.disabled = true;
                }
                select.add(option);
            };
            formScope.querySelectorAll('[data-location-shelf]').forEach(addShelfOption);
            formScope.querySelectorAll('template').forEach(template => {
                template.content.querySelectorAll('[data-location-shelf]').forEach(addShelfOption);
            });
            activeShelfSelect.value = shelf.id;
            root.querySelector('[data-field="shelf-code"]').value = '';
            root.querySelector('[data-field="shelf-name"]').value = '';
            showStatus('shelf', 'Estante creado y seleccionado.');
            closePanel(root.querySelector('[data-location-panel="shelf"]'));
        } catch (error) {
            showStatus('shelf', error.message, true);
        }
    });

});
</script>
@endpush
