@extends('layouts.app')

@section('title', 'Editar ' . $product->name)

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar Producto</h1>
            <p class="text-sm text-gray-500">{{ $product->name }} ({{ $product->code ?? 'Sin código' }})</p>
        </div>

        <a href="{{ route('inventario.show', $product->id) }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
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

    <form action="{{ route('inventario.update', $product->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Información Básica --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Información Básica</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código *</label>
                    <input type="text" name="code" value="{{ old('code', $product->code) }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoría *</label>
                    <select name="category_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unidad de Medida *</label>
                    <select name="unit" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="kg" {{ old('unit', $product->unit) == 'kg' ? 'selected' : '' }}>Kilogramos (kg)</option>
                        <option value="lb" {{ old('unit', $product->unit) == 'lb' ? 'selected' : '' }}>Libras (lb)</option>
                        <option value="lt" {{ old('unit', $product->unit) == 'lt' ? 'selected' : '' }}>Litros (lt)</option>
                        <option value="gal" {{ old('unit', $product->unit) == 'gal' ? 'selected' : '' }}>Galones (gal)</option>
                        <option value="unidad" {{ old('unit', $product->unit) == 'unidad' ? 'selected' : '' }}>Unidad</option>
                        <option value="saco" {{ old('unit', $product->unit) == 'saco' ? 'selected' : '' }}>Saco</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="description" rows="2"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Precios y Stock --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Precios</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Compra (C$) *</label>
                    <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Venta (C$) *</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock Mínimo (alerta)</label>
                    <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="1"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="discontinued" {{ old('status', $product->status) == 'discontinued' ? 'selected' : '' }}>Descontinuado</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Stock Actual (se modifica mediante ajustes, compras y ventas)</p>
                <p class="text-2xl font-bold">{{ $product->stock }} {{ $product->unit }}</p>
            </div>
        </div>

        {{-- Información de Trazabilidad --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Trazabilidad (Agroquímicos)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Lote</label>
                    <input type="text" name="lot" value="{{ old('lot', $product->lot) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de Vencimiento</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Registro Sanitario</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number', $product->registration_number) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ingrediente Activo</label>
                    <input type="text" name="active_ingredient" value="{{ old('active_ingredient', $product->active_ingredient) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Concentración</label>
                    <input type="text" name="concentration" value="{{ old('concentration', $product->concentration) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ubicación en Bodega</label>
                    <input type="text" name="location" value="{{ old('location', $product->location) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
        </div>

        {{-- Observaciones --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Observaciones</h2>

            <div>
                <textarea name="observations" rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observations', $product->observations) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('inventario.show', $product->id) }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Cancelar</a>
            <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

@endsection
