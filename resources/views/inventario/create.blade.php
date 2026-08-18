@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Crear Producto (Modo Pro)')

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Modo Pro — Producto Completo</h1>
            <p class="page-subtitle">Datos generales del producto. Lote y vencimiento son opcionales (agroquímicos).</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventario.quick') }}" class="btn-primary text-sm">← Registro Rápido</a>
            <a href="{{ route('inventario.index') }}" class="btn-outline text-sm">Volver</a>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('inventario.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        {{-- Información Básica --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Información Básica</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           placeholder="Ej: FERT-001"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Ej: Fertilizante 15-15-15"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoría *</label>
                    <select name="category_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unidad de medida *</label>
                    <select name="base_unit_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach($units ?? [] as $u)
                            <option value="{{ $u->id }}" @selected(old('base_unit_id', $units->firstWhere('abbreviation', 'und')?->id) == $u->id)>
                                {{ $u->name }} ({{ $u->abbreviation }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Stock y kardex usan esta unidad. Crea más en Inventario → Unidades.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="description" rows="2"
                              placeholder="Descripción del producto..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Imagen del producto --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-1">Imagen del Producto</h2>
            <p class="text-sm text-gray-500 mb-4">Se mostrará en el catálogo y en el punto de venta para identificar el producto rápidamente.</p>

            <div class="grid grid-cols-1 md:grid-cols-[180px_1fr] gap-5 items-center">
                <div class="h-40 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                    <div id="createImagePlaceholder" class="text-center text-gray-400 px-4">
                        <div class="text-4xl mb-1">📷</div>
                        <span class="text-xs">Vista previa</span>
                    </div>
                    <img id="createImagePreview" class="hidden w-full h-full object-contain" alt="Vista previa del producto">
                </div>
                <div>
                    <label for="product_image" class="block text-sm font-medium text-gray-700">Seleccionar imagen</label>
                    <input id="product_image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                           class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700">
                    <p class="mt-2 text-xs text-gray-500">Formatos JPG, PNG o WebP. Máximo 3 MB y 3000 × 3000 px.</p>
                </div>
            </div>
        </div>

        {{-- Precios y Stock --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Precios y Stock</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Compra (C$) *</label>
                    <input type="number" name="purchase_price" id="create_purchase_price"
                           value="{{ old('purchase_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Venta (C$) *</label>
                    <input type="number" name="sale_price" id="create_sale_price"
                           value="{{ old('sale_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock Inicial *</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bodega del stock inicial *</label>
                    <select name="warehouse_id" id="createWarehouse" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @forelse($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id) == $warehouse->id)>
                                {{ $warehouse->name }}@if($warehouse->is_default) (principal)@endif
                            </option>
                        @empty
                            <option value="">Sin bodegas activas</option>
                        @endforelse
                    </select>
                    <p class="mt-1 text-xs text-gray-500">El stock inicial se registra en esta bodega.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estante</label>
                    <select name="shelf_id" id="createShelf" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Sin asignar</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            @foreach($warehouse->shelves as $shelf)
                                <option value="{{ $shelf->id }}" data-warehouse="{{ $warehouse->id }}" @selected(old('shelf_id') == $shelf->id)>{{ $shelf->label() }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock Mínimo (alerta)</label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}" min="1"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Descontinuado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Impuesto (IVA)</label>
                    <select name="tax_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Usar impuesto predeterminado</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>
                                {{ $tax->name }} ({{ number_format($tax->rate * 100, 2) }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="location" value="{{ old('location') }}">

                <div class="md:col-span-3">
                    @include('inventario._price_calc', [
                        'purchaseInputId' => 'create_purchase_price',
                        'saleInputId' => 'create_sale_price',
                        'compact' => true,
                    ])
                </div>
            </div>
        </div>

        {{-- Descuento del Producto --}}
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-amber-400">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Descuento / Promoción</h2>
            <p class="text-xs text-gray-500 mb-3">Si se configura un descuento, se aplica automáticamente al agregar este producto al ticket/factura en el POS.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Descuento (%)</label>
                    <input type="number" name="discount_pct" value="{{ old('discount_pct', 0) }}"
                           step="0.01" min="0" max="100"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           placeholder="0 = sin descuento">
                    <p class="text-xs text-gray-400 mt-1">Ej: 10 = 10% de descuento automático</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Etiqueta de promoción</label>
                    <input type="text" name="discount_label" value="{{ old('discount_label') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                           placeholder="Ej: Oferta, Promo Verano...">
                </div>
                <div class="flex items-end">
                    <div class="p-3 bg-amber-50 rounded-xl w-full text-center">
                        <p class="text-xs text-gray-500">Precio con descuento</p>
                        <p class="font-bold text-amber-700 text-lg" id="discountPreview">C$ —</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Opcional: solo agroquímicos / productos con vencimiento --}}
        @php
            $showAgro = filled(old('lot'))
                || filled(old('expiry_date'))
                || filled(old('registration_number'))
                || filled(old('active_ingredient'))
                || filled(old('concentration'));
        @endphp
        <details class="bg-white rounded-xl shadow group" @if($showAgro) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 [&::-webkit-details-marker]:hidden">
                <div>
                    <h2 class="text-lg font-semibold text-gray-700">Lote, vencimiento y agroquímicos</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Opcional — úsalo solo si el producto lo requiere (agroquímicos, medicamentos, etc.)</p>
                </div>
                <span class="shrink-0 text-xs font-medium text-slate-500 group-open:hidden">Mostrar</span>
                <span class="shrink-0 text-xs font-medium text-slate-500 hidden group-open:inline">Ocultar</span>
            </summary>
            <div class="border-t border-gray-100 px-4 pb-4 pt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de lote</label>
                    <input type="text" name="lot" value="{{ old('lot') }}"
                           placeholder="Ej: LOT-2024-001"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de vencimiento</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Registro sanitario</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number') }}"
                           placeholder="Ej: AG-12345-2024"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ingrediente activo</label>
                    <input type="text" name="active_ingredient" value="{{ old('active_ingredient') }}"
                           placeholder="Ej: Glifosato"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Concentración</label>
                    <input type="text" name="concentration" value="{{ old('concentration') }}"
                           placeholder="Ej: 48% SL"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
        </details>

        {{-- Observaciones --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Observaciones</h2>

            <div>
                <textarea name="observations" rows="3"
                          placeholder="Observaciones adicionales sobre el producto..."
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observations') }}</textarea>
            </div>
        </div>

        <div class="flex flex-col items-end gap-2 sm:flex-row sm:justify-end sm:items-center">
            <p class="text-xs text-gray-500 sm:mr-auto">Al guardar, el formulario se limpia para seguir agregando productos.</p>
            <a href="{{ route('inventario.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Volver al listado</a>
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow">
                Guardar y seguir
            </button>
        </div>
    </form>
</div>

<script>
    const createWarehouse = document.getElementById('createWarehouse');
    const createShelf = document.getElementById('createShelf');
    function filterCreateShelves() {
        const warehouseId = createWarehouse?.value || '';
        Array.from(createShelf?.options || []).forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.warehouse !== warehouseId;
            option.disabled = option.hidden;
        });
        if (createShelf?.selectedOptions[0]?.disabled) createShelf.value = '';
    }
    createWarehouse?.addEventListener('change', filterCreateShelves);
    filterCreateShelves();

    document.getElementById('product_image')?.addEventListener('change', function () {
        const file = this.files?.[0];
        const preview = document.getElementById('createImagePreview');
        const placeholder = document.getElementById('createImagePlaceholder');

        if (!file) {
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
    });
</script>

@endsection
