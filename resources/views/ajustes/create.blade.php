@extends('layouts.app')

@section('title', 'Nuevo Ajuste de Inventario')

@section('content')

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nuevo Ajuste de Inventario</h1>
            <p class="text-sm text-gray-500">Ajuste de stock por aumento, disminución o conteo físico</p>
        </div>

        <a href="{{ route('ajustes.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Volver</a>
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

    <form action="{{ route('ajustes.store') }}" method="POST" class="space-y-4" id="adjustmentForm">
        @csrf

        <input type="hidden" id="preselected_stock" value="{{ $preselectedProduct->stock ?? 0 }}">

        {{-- Selección de Producto --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Producto</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Seleccionar Producto *</label>
                    <select name="product_id" id="product_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione un producto...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ (old('product_id') ?? ($preselectedProduct->id ?? '')) == $product->id ? 'selected' : '' }}>
                                {{ $product->code ? '[' . $product->code . '] ' : '' }}{{ $product->name }} (Stock: {{ $product->stock }} {{ $product->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Información del Producto Seleccionado --}}
            <div id="productInfo" class="mt-4 p-4 bg-gray-50 rounded-lg {{ $preselectedProduct ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Código</p>
                        <p class="font-semibold" id="productCode">{{ $preselectedProduct->code ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Stock Actual</p>
                        <p class="font-semibold" id="currentStock">{{ $preselectedProduct->stock ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Lote</p>
                        <p class="font-semibold" id="productLot">{{ $preselectedProduct->lot ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Ubicación</p>
                        <p class="font-semibold" id="productLocation">{{ $preselectedProduct->location ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tipo de Ajuste --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Tipo de Ajuste *</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="increase" {{ old('type') == 'increase' ? 'checked' : '' }} class="sr-only peer" required>
                    <div class="p-4 border-2 rounded-lg text-center peer-checked:border-green-500 peer-checked:bg-green-50">
                        <div class="text-2xl mb-2">📈</div>
                        <div class="font-semibold text-green-700">Aumento</div>
                        <div class="text-xs text-gray-500">Agregar stock al inventario</div>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="type" value="decrease" {{ old('type') == 'decrease' ? 'checked' : '' }} class="sr-only peer">
                    <div class="p-4 border-2 rounded-lg text-center peer-checked:border-red-500 peer-checked:bg-red-50">
                        <div class="text-2xl mb-2">📉</div>
                        <div class="font-semibold text-red-700">Disminución</div>
                        <div class="text-xs text-gray-500">Reducir stock del inventario</div>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="type" value="count" {{ old('type') == 'count' ? 'checked' : '' }} class="sr-only peer">
                    <div class="p-4 border-2 rounded-lg text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                        <div class="text-2xl mb-2">📝</div>
                        <div class="font-semibold text-blue-700">Ajuste por Conteo</div>
                        <div class="text-xs text-gray-500">Establecer stock según conteo físico</div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Cantidad --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Cantidad *</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" min="0" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <p class="text-sm text-gray-500 mt-1">
                        Para "Ajuste por Conteo" ingrese el stock real encontrado en bodega.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Referencia</label>
                    <input type="text" name="reference" value="{{ old('reference') }}"
                           placeholder="Ej: Conteo #123, Orden #456"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            {{-- Preview del cambio --}}
            <div id="changePreview" class="mt-4 p-4 bg-gray-50 rounded-lg hidden">
                <p class="text-sm font-medium text-gray-700 mb-2">Vista previa del cambio:</p>
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Stock Actual</p>
                        <p class="text-xl font-bold" id="previewBefore">—</p>
                    </div>
                    <div class="text-2xl">→</div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Nuevo Stock</p>
                        <p class="text-xl font-bold" id="previewAfter">—</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Motivo --}}
        <div class="bg-white p-4 rounded-xl shadow">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Motivo *</h2>

            <div>
                <textarea name="reason" required rows="3"
                          placeholder="Describa el motivo del ajuste (ej: Vencimiento de producto, Daño en transporte, Conteo físico realizado el día X, etc.)"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('reason') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('ajustes.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Cancelar</a>
            <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-green-800 shadow">
                Guardar Ajuste
            </button>
        </div>
    </form>
</div>

<script>
const productSelect = document.getElementById('product_id');
const productInfo = document.getElementById('productInfo');
const currentStockEl = document.getElementById('currentStock');
const productCodeEl = document.getElementById('productCode');
const productLotEl = document.getElementById('productLot');
const productLocationEl = document.getElementById('productLocation');
const quantityInput = document.getElementById('quantity');
const changePreview = document.getElementById('changePreview');
const previewBefore = document.getElementById('previewBefore');
const previewAfter = document.getElementById('previewAfter');
const typeRadios = document.querySelectorAll('input[name="type"]');
const preselectedStockInput = document.getElementById('preselected_stock');

let currentStock = preselectedStockInput ? parseInt(preselectedStockInput.value) || 0 : 0;

productSelect.addEventListener('change', async function() {
    const productId = this.value;
    if (!productId) {
        productInfo.classList.add('hidden');
        currentStock = 0;
        return;
    }

    try {
        const response = await fetch(`/api/products/${productId}/info`);
        const data = await response.json();

        currentStock = data.current_stock;
        currentStockEl.textContent = data.current_stock;
        productCodeEl.textContent = data.code || '—';
        productLotEl.textContent = data.lot || '—';
        productLocationEl.textContent = data.location || '—';

        productInfo.classList.remove('hidden');
        updatePreview();
    } catch (error) {
        console.error('Error fetching product info:', error);
    }
});

quantityInput.addEventListener('input', updatePreview);
typeRadios.forEach(radio => radio.addEventListener('change', updatePreview));

function updatePreview() {
    const quantity = parseInt(quantityInput.value) || 0;
    const selectedType = document.querySelector('input[name="type"]:checked')?.value;

    if (!selectedType || quantity <= 0) {
        changePreview.classList.add('hidden');
        return;
    }

    let newStock = currentStock;
    if (selectedType === 'increase') {
        newStock = currentStock + quantity;
    } else if (selectedType === 'decrease') {
        newStock = Math.max(0, currentStock - quantity);
    } else if (selectedType === 'count') {
        newStock = quantity;
    }

    previewBefore.textContent = currentStock;
    previewAfter.textContent = newStock;
    changePreview.classList.remove('hidden');
}

// Inicializar
if (productSelect.value) {
    updatePreview();
}
</script>

@endsection
