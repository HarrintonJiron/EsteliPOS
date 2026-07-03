@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Crear Compra</h1>
            <p class="text-sm text-gray-500">Ingreso de mercadería al inventario</p>
        </div>
        <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm">Nueva</span>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('compras.store') }}" method="POST" class="bg-white shadow rounded-xl p-4 space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="text-sm text-gray-600" for="supplier_id">Proveedor</label>
                <select name="supplier_id" id="supplier_id" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" required>
                <option value="">Seleccionar Proveedor</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
                </select>
                @error('supplier_id')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

        {{-- user is set server-side --}}

            <div>
                <label class="text-sm text-gray-600" for="date">Fecha</label>
                <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white" required />
                @error('date')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm text-gray-600" for="status">Estado</label>
                <select name="status" id="status" class="mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                    <option value="completed" {{ old('status', 'completed') === 'completed' ? 'selected' : '' }}>Completada</option>
                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="canceled" {{ old('status') === 'canceled' ? 'selected' : '' }}>Anulada</option>
                </select>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-4 space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Detalle de productos</h3>
                <button type="button" id="add-item" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">+ Agregar</button>
            </div>

            <div id="items" class="space-y-3">
            <div class="item flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-0">
                    <label class="text-sm text-gray-600">Producto</label>
                    <select name="items[0][product_id]" class="product-select mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                        <option value="">Seleccionar Producto</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->purchase_price ?? $p->price ?? 0 }}" {{ old('items.0.product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-24">
                    <label class="text-sm text-gray-600">Cantidad</label>
                    <input name="items[0][quantity]" type="number" value="{{ old('items.0.quantity', 1) }}" class="quantity-input mt-1 w-full border rounded-lg px-3 py-2 bg-white" min="1" />
                </div>
                <div class="w-32">
                    <label class="text-sm text-gray-600">Costo</label>
                    <input name="items[0][price]" type="number" step="0.01" value="{{ old('items.0.price', $products->first()->purchase_price ?? $products->first()->price ?? 0) }}" class="price-input mt-1 w-full border rounded-lg px-3 py-2 bg-white" />
                </div>
                <button type="button" class="remove-item bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm" style="display: none;">Eliminar</button>
            </div>
        </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('compras.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 inline-block">Cancelar</a>
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-6 py-2 rounded-lg shadow">Guardar Compra</button>
        </div>
    </form>
</div>

<script>
let itemIndex = 1;

document.getElementById('add-item').addEventListener('click', function() {
    const itemsContainer = document.getElementById('items');
    const newItem = document.createElement('div');
    newItem.className = 'item flex flex-wrap gap-4 items-end';
    newItem.innerHTML = `
        <div class="flex-1 min-w-0">
            <label class="text-sm text-gray-600">Producto</label>
            <select name="items[${itemIndex}][product_id]" class="product-select mt-1 w-full border rounded-lg px-4 py-2 bg-white">
                <option value="">Seleccionar Producto</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-price="{{ $p->purchase_price ?? $p->price ?? 0 }}">{{ $p->name }} ({{ $p->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="w-24">
            <label class="text-sm text-gray-600">Cantidad</label>
            <input name="items[${itemIndex}][quantity]" type="number" value="1" class="quantity-input mt-1 w-full border rounded-lg px-3 py-2 bg-white" min="1" />
        </div>
        <div class="w-32">
            <label class="text-sm text-gray-600">Costo</label>
            <input name="items[${itemIndex}][price]" type="number" step="0.01" value="0" class="price-input mt-1 w-full border rounded-lg px-3 py-2 bg-white" />
        </div>
        <button type="button" class="remove-item bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm">Eliminar</button>
    `;
    itemsContainer.appendChild(newItem);
    itemIndex++;
    updateRemoveButtons();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        e.target.closest('.item').remove();
        updateRemoveButtons();
    }
});

function updateRemoveButtons() {
    const items = document.querySelectorAll('.item');
    items.forEach((item, index) => {
        const removeBtn = item.querySelector('.remove-item');
        if (items.length > 1) {
            removeBtn.style.display = 'block';
        } else {
            removeBtn.style.display = 'none';
        }
    });
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const selectedOption = e.target.selectedOptions[0];
        const priceInput = e.target.closest('.item').querySelector('.price-input');
        priceInput.value = selectedOption.getAttribute('data-price') || 0;
    }
});

updateRemoveButtons();
</script>

@endsection
