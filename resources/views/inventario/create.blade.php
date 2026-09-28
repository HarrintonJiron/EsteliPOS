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
                    <label class="block text-sm font-medium text-gray-700">Código <span class="font-normal text-gray-400">(o IMEI — déjalo vacío para generarlo automático)</span></label>
                    <input type="text" name="code" value="{{ old('code') }}"
                           placeholder="Ej: FERT-001 o IMEI del equipo"
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
                    <select name="category_id" aria-label="Categoría" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                    <select name="base_unit_id" aria-label="Unidad de medida" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach($units ?? [] as $u)
                            <option value="{{ $u->id }}" @selected(old('base_unit_id', $units->firstWhere('abbreviation', 'und')?->id) == $u->id)>
                                {{ $u->name }} ({{ $u->abbreviation }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Así se cuenta el inventario. Ejemplo: jabón en <strong>unidades</strong>. Después, en la ficha, puedes venderlo en ristra o caja sin crear otro producto.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Proveedor <span class="font-normal text-gray-400">(opcional)</span></label>
                    <select name="supplier_id" aria-label="Proveedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Sin proveedor</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Código del proveedor <span class="font-normal text-gray-400">(opcional)</span></label>
                    <input type="text" name="supplier_code" value="{{ old('supplier_code') }}"
                           placeholder="Referencia del proveedor para este producto"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="description" rows="2"
                              placeholder="Descripción del producto..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        @include('inventario._cellphone_fields')

        @include('inventario.partials._gallery_field')


        {{-- Precios y Stock --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Precios y Stock</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Compra ({{ $currencySymbol }}) *</label>
                    <input type="number" name="purchase_price" id="create_purchase_price"
                           aria-label="Precio de compra"
                           value="{{ old('purchase_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <input type="hidden" name="price_currency" value="USD">
                    <label class="block text-sm font-medium text-gray-700">Moneda del precio</label>
                    <p class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm">USD ($)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Venta (moneda registrada) *</label>
                    <input type="number" name="sale_price" id="create_sale_price"
                           aria-label="Precio de venta"
                           value="{{ old('sale_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                @include('inventario._initial_locations', [
                    'locationPrefix' => 'proInitial',
                    'warehouseSelectId' => 'createWarehouse',
                    'shelfSelectId' => 'createShelf',
                    'locationWrapperClass' => 'md:col-span-3',
                ])

                @include('inventario._inline_location_creator', [
                    'locationPrefix' => 'proLocation',
                    'warehouseSelectId' => 'createWarehouse',
                    'shelfSelectId' => 'createShelf',
                    'locationWrapperClass' => 'md:col-span-3',
                ])

                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock Mínimo (alerta)</label>
                    <input type="number" name="low_stock_threshold" aria-label="Stock mínimo" value="{{ old('low_stock_threshold', 10) }}" min="1"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" aria-label="Estado" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Descontinuado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Impuesto (IVA)</label>
                    <select name="tax_id" aria-label="Impuesto" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                        <p class="font-bold text-amber-700 text-lg" id="discountPreview">{{ $currencySymbol }} —</p>
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
                    <input type="date" name="expiry_date" aria-label="Fecha de vencimiento" value="{{ old('expiry_date') }}"
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
            <p class="text-xs text-gray-500 sm:mr-auto">Puedes seguir agregando productos o configurar sus cajas, ristras y otras presentaciones.</p>
            <a href="{{ route('inventario.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Volver al listado</a>
            <button type="submit" name="configure_presentations" value="1" class="btn-primary px-6 py-2">
                Guardar y configurar presentaciones
            </button>
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-slate-700 shadow">
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

</script>

@endsection
