@extends('layouts.app')

@section('title', 'Registro Rápido de Producto')

@section('content')

<div class="max-w-2xl mx-auto space-y-4" id="quickApp" data-lookup-url="{{ url('/inventario/buscar') }}">

    <div class="flex justify-between items-center">
        <div>
            <h2 class="page-title">Registro Rápido</h2>
            <p class="page-subtitle">Escanea el código de barras y completa en segundos</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventario.create') }}" class="btn-outline text-sm">Modo Pro</a>
            <a href="{{ route('inventario.bulk') }}" class="btn-outline text-sm">Carga Masiva</a>
        </div>
    </div>

    <div id="existsAlert" class="hidden card p-4 border border-amber-300 bg-amber-50">
        <p class="font-semibold text-amber-800">Este código ya existe</p>
        <p class="text-sm text-amber-700 mt-1" id="existsInfo"></p>
        <a id="existsLink" href="#" class="text-indigo-600 text-sm font-medium mt-2 inline-block">Ver producto →</a>
    </div>

    <form action="{{ route('inventario.quick-store') }}" method="POST" enctype="multipart/form-data" id="quickForm" class="card p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">
                Código de barras
                <span class="text-xs font-normal text-slate-400 ml-1">(escáner + Enter)</span>
            </label>
            <input type="text" name="code" id="barcodeInput" value="{{ old('code') }}" required autofocus
                placeholder="Escanear o escribir código..."
                class="input-field text-xl font-mono py-3 text-center tracking-wider">
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre del producto *</label>
            <input type="text" name="name" id="nameInput" value="{{ old('name') }}" required
                placeholder="Ej: Arroz 1lb, Coca Cola 600ml..."
                class="input-field text-lg py-3">
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="h-28 w-28 shrink-0 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden">
                    <div id="quickImagePlaceholder" class="text-center text-slate-400">
                        <div class="text-3xl">📷</div>
                        <span class="text-xs">Vista previa</span>
                    </div>
                    <img id="quickImagePreview" class="hidden w-full h-full object-contain" alt="Vista previa del producto">
                </div>
                <div class="w-full min-w-0">
                    <label for="quick_product_image" class="block text-sm font-semibold text-slate-700">Imagen del producto</label>
                    <input id="quick_product_image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700">
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG o WebP · máximo 3 MB.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Precio venta (C$) *</label>
                <input type="number" name="sale_price" id="quick_sale_price" value="{{ old('sale_price') }}" step="0.01" min="0" required
                    class="input-field text-2xl font-bold text-center py-3 text-indigo-600">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Stock inicial</label>
                <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
                    class="input-field text-2xl font-bold text-center py-3">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Categoría</label>
                <select name="category_id" class="select-field">
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ ($defaultCategory?->id ?? null) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Unidad</label>
                <select name="unit" class="select-field">
                    <option value="unidad">Unidad</option>
                    <option value="lb">Libra</option>
                    <option value="kg">Kilogramo</option>
                    <option value="lt">Litro</option>
                    <option value="saco">Saco</option>
                </select>
            </div>
        </div>

        <details class="group" open>
            <summary class="cursor-pointer text-sm text-indigo-600 font-medium hover:text-indigo-800">
                Precio de compra + Calculadora de utilidad
            </summary>
            <div class="mt-4 space-y-4 pt-4 border-t border-slate-100">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Precio de compra (C$)</label>
                        <input type="number" name="purchase_price" id="quick_purchase_price"
                               step="0.01" min="0" placeholder="Costo del producto"
                               value="{{ old('purchase_price') }}"
                               class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Stock mínimo alerta</label>
                        <input type="number" name="low_stock_threshold" value="5" min="1" class="input-field">
                    </div>
                </div>

                @include('inventario._price_calc', [
                    'purchaseInputId' => 'quick_purchase_price',
                    'saleInputId'     => 'quick_sale_price',
                ])
            </div>
        </details>

        <div class="flex items-center gap-3 pt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="add_another" value="1" checked class="rounded border-slate-300 text-indigo-600">
                <span class="text-sm text-slate-700">Seguir agregando productos</span>
            </label>
        </div>

        <button type="submit" class="w-full btn-primary justify-center py-4 text-lg">
            Guardar Producto
        </button>
    </form>

    <p class="text-center text-xs text-slate-400">
        Atajos: Enter en código → busca · Tab para avanzar · F2 enfoca código
    </p>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const barcode = document.getElementById('barcodeInput');
    const nameInput = document.getElementById('nameInput');
    const priceInput = document.getElementById('quick_sale_price');
    const existsAlert = document.getElementById('existsAlert');
    const imageInput = document.getElementById('quick_product_image');
    const imagePreview = document.getElementById('quickImagePreview');
    const imagePlaceholder = document.getElementById('quickImagePlaceholder');
    const lookupBase = document.getElementById('quickApp').dataset.lookupUrl;

    async function lookupCode(code) {
        if (!code.trim()) return;
        try {
            const res = await fetch(lookupBase + '/' + encodeURIComponent(code.trim()));
            const data = await res.json();
            if (data.exists) {
                existsAlert.classList.remove('hidden');
                document.getElementById('existsInfo').textContent =
                    data.product.name + ' — Stock: ' + data.product.stock + ' — C$ ' + parseFloat(data.product.sale_price).toFixed(2);
                document.getElementById('existsLink').href = data.product.url;
                barcode.classList.add('border-amber-400');
            } else {
                existsAlert.classList.add('hidden');
                barcode.classList.remove('border-amber-400');
                nameInput.focus();
                nameInput.select();
            }
        } catch (e) { /* ignore */ }
    }

    barcode.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupCode(barcode.value);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') { e.preventDefault(); barcode.focus(); barcode.select(); }
    });

    imageInput?.addEventListener('change', function() {
        const file = this.files?.[0];
        if (!file) {
            imagePreview.classList.add('hidden');
            imagePlaceholder.classList.remove('hidden');
            return;
        }

        imagePreview.src = URL.createObjectURL(file);
        imagePreview.classList.remove('hidden');
        imagePlaceholder.classList.add('hidden');
    });

  barcode.focus();
});
</script>
@endpush
@endsection
