@extends('layouts.app')

@section('title', 'Crear Producto (Modo Pro)')

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Modo Pro — Producto Completo</h1>
            <p class="page-subtitle">Trazabilidad, lotes, agroquímicos y todos los campos</p>
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

    <form action="{{ route('inventario.store') }}" method="POST" class="space-y-4">
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
                    <label class="block text-sm font-medium text-gray-700">Unidad de Medida *</label>
                    <select name="unit" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogramos (kg)</option>
                        <option value="lb" {{ old('unit') == 'lb' ? 'selected' : '' }}>Libras (lb)</option>
                        <option value="lt" {{ old('unit') == 'lt' ? 'selected' : '' }}>Litros (lt)</option>
                        <option value="gal" {{ old('unit') == 'gal' ? 'selected' : '' }}>Galones (gal)</option>
                        <option value="unidad" {{ old('unit') == 'unidad' ? 'selected' : '' }}>Unidad</option>
                        <option value="saco" {{ old('unit') == 'saco' ? 'selected' : '' }}>Saco</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="description" rows="2"
                              placeholder="Descripción del producto..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Precios y Stock --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Precios y Stock</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Compra (C$) *</label>
                    <input type="number" name="purchase_price" value="{{ old('purchase_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Venta (C$) *</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock Inicial *</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
            </div>
        </div>

        {{-- Información de Trazabilidad (Agroquímicos) --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Trazabilidad (Agroquímicos)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Lote</label>
                    <input type="text" name="lot" value="{{ old('lot') }}"
                           placeholder="Ej: LOT-2024-001"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de Vencimiento</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Registro Sanitario</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number') }}"
                           placeholder="Ej: AG-12345-2024"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ingrediente Activo</label>
                    <input type="text" name="active_ingredient" value="{{ old('active_ingredient') }}"
                           placeholder="Ej: Glifosato"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Concentración</label>
                    <input type="text" name="concentration" value="{{ old('concentration') }}"
                           placeholder="Ej: 48% SL"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ubicación en Bodega</label>
                    <input type="text" name="location" value="{{ old('location') }}"
                           placeholder="Ej: Estante A-3, Sección Norte"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
        </div>

        {{-- Observaciones --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Observaciones</h2>

            <div>
                <textarea name="observations" rows="3"
                          placeholder="Observaciones adicionales sobre el producto..."
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('observations') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('inventario.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Cancelar</a>
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow">
                Guardar Producto
            </button>
        </div>
    </form>
</div>

@endsection
