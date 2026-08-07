@extends('layouts.app')

@section('title', 'Registro Rápido de Producto')

@section('content')

<div class="mx-auto max-w-3xl space-y-3" id="quickApp" data-lookup-url="{{ url('/inventario/buscar') }}">

    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Producto rápido</h2>
            <p class="text-xs text-slate-500">Escanea, precios y stock en segundos</p>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('inventario.create') }}" class="btn-outline text-xs py-1.5">Modo pro</a>
            <a href="{{ route('inventario.bulk') }}" class="btn-outline text-xs py-1.5">Masiva</a>
        </div>
    </div>

    <div id="existsAlert" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
        <p class="font-semibold text-amber-800">Código duplicado</p>
        <p class="text-amber-700" id="existsInfo"></p>
        <a id="existsLink" href="#" class="text-indigo-600 text-xs font-medium">Ver producto →</a>
    </div>

    <form action="{{ route('inventario.quick-store') }}" method="POST" enctype="multipart/form-data" id="quickForm" class="card p-4 space-y-3">
        @csrf

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1.4fr]">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Código · Enter busca</label>
                <input type="text" name="code" id="barcodeInput" value="{{ old('code') }}" required autofocus
                    placeholder="Escanear..."
                    class="input-field py-2 font-mono text-center tracking-wide">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Nombre *</label>
                <input type="text" name="name" id="nameInput" value="{{ old('name') }}" required
                    placeholder="Nombre del producto"
                    class="input-field py-2">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <div>
                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Costo</label>
                <input type="number" name="purchase_price" id="quick_purchase_price" step="0.01" min="0"
                    value="{{ old('purchase_price') }}" placeholder="0.00" class="input-field py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-indigo-600">Público *</label>
                <input type="number" name="sale_price" id="quick_sale_price" step="0.01" min="0" required
                    value="{{ old('sale_price') }}" placeholder="0.00"
                    class="input-field py-1.5 text-sm font-bold text-indigo-700">
            </div>
            <div>
                <label class="mb-1 flex items-center justify-between text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                    <span>Mayorista</span>
                    @if($wholesaleList)
                        <button type="button" id="applyWholesalePct" class="normal-case text-[10px] text-emerald-600 hover:underline">−10%</button>
                    @endif
                </label>
                <input type="number" name="wholesale_price" id="quick_wholesale_price" step="0.01" min="0"
                    value="{{ old('wholesale_price') }}" placeholder="Opcional"
                    class="input-field py-1.5 text-sm font-semibold text-emerald-700">
            </div>
        </div>

        @if($wholesaleList)
            <p class="text-[11px] text-slate-500">Lista mayorista: <strong>{{ $wholesaleList->name }}</strong> — se sincroniza al guardar.</p>
        @else
            <p class="text-[11px] text-amber-600">No hay lista MAYOR activa; el precio mayorista no se aplicará en POS.</p>
        @endif

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs text-slate-500">Stock inicial</label>
                <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0" class="input-field py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-500">Alerta mín.</label>
                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="1" class="input-field py-1.5 text-sm">
            </div>
            <div class="relative sm:col-span-2">
                <label class="mb-1 block text-xs text-slate-500">Categoría</label>
                <div class="flex gap-1">
                    <select id="categorySelectQuick" name="category_id" class="select-field flex-1 py-1.5 text-sm">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $defaultCategory?->id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="toggleNewCategoryForm('Quick')" class="btn-outline px-2 py-1.5 text-xs">+</button>
                </div>
                <div id="newCategoryFormQuick" class="hidden absolute left-0 right-0 z-20 mt-1 rounded-lg border bg-white p-2 shadow-lg">
                    <input id="newCategoryNameQuick" type="text" placeholder="Nueva categoría" class="input-field py-1.5 text-sm" />
                    <button type="button" onclick="submitNewCategory('Quick')" class="btn-primary mt-1 w-full py-1.5 text-xs">Agregar</button>
                    <p id="newCategoryErrorQuick" class="mt-1 text-xs text-red-600 hidden"></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-[auto_1fr] gap-3 items-center rounded-lg border border-dashed border-slate-200 p-2">
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100 flex items-center justify-center">
                <div id="quickImagePlaceholder" class="text-center text-slate-400 text-lg">📷</div>
                <img id="quickImagePreview" class="hidden h-full w-full object-contain" alt="">
            </div>
            <input id="quick_product_image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                class="text-xs text-slate-600 file:mr-2 file:rounded file:border-0 file:bg-slate-800 file:px-2 file:py-1 file:text-xs file:text-white">
        </div>

        <details class="text-sm">
            <summary class="cursor-pointer text-xs font-medium text-indigo-600">Calculadora de utilidad</summary>
            <div class="mt-2 border-t border-slate-100 pt-2">
                @include('inventario._price_calc', [
                    'purchaseInputId' => 'quick_purchase_price',
                    'saleInputId' => 'quick_sale_price',
                ])
            </div>
        </details>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" name="add_another" value="1" checked class="rounded border-slate-300 text-indigo-600">
                Seguir agregando
            </label>
            <button type="submit" class="btn-primary px-6 py-2">Guardar</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const barcode = document.getElementById('barcodeInput');
    const nameInput = document.getElementById('nameInput');
    const saleInput = document.getElementById('quick_sale_price');
    const wholesaleInput = document.getElementById('quick_wholesale_price');
    const purchaseInput = document.getElementById('quick_purchase_price');
    const existsAlert = document.getElementById('existsAlert');
    const imageInput = document.getElementById('quick_product_image');
    const imagePreview = document.getElementById('quickImagePreview');
    const imagePlaceholder = document.getElementById('quickImagePlaceholder');
    const lookupBase = document.getElementById('quickApp').dataset.lookupUrl;

    document.getElementById('applyWholesalePct')?.addEventListener('click', () => {
        const sale = parseFloat(saleInput.value);
        if (!sale || sale <= 0) return;
        wholesaleInput.value = (sale * 0.9).toFixed(2);
    });

    saleInput?.addEventListener('change', () => {
        if (!purchaseInput.value && saleInput.value) {
            purchaseInput.value = (parseFloat(saleInput.value) * 0.85).toFixed(2);
        }
    });

    async function lookupCode(code) {
        if (!code.trim()) return;
        try {
            const res = await fetch(lookupBase + '/' + encodeURIComponent(code.trim()));
            const data = await res.json();
            if (data.exists) {
                existsAlert.classList.remove('hidden');
                document.getElementById('existsInfo').textContent =
                    `${data.product.name} · Stock ${data.product.stock} · C$ ${parseFloat(data.product.sale_price).toFixed(2)}`;
                document.getElementById('existsLink').href = data.product.url;
                barcode.classList.add('border-amber-400');
            } else {
                existsAlert.classList.add('hidden');
                barcode.classList.remove('border-amber-400');
                nameInput.focus();
            }
        } catch (e) {}
    }

    barcode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); lookupCode(barcode.value); }
    });

    document.addEventListener('keydown', (e) => {
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

function toggleNewCategoryForm(mode) {
    document.getElementById(`newCategoryForm${mode}`)?.classList.toggle('hidden');
}

function submitNewCategory(mode) {
    const input = document.getElementById(`newCategoryName${mode}`);
    const error = document.getElementById(`newCategoryError${mode}`);
    const select = document.getElementById(`categorySelect${mode}`);
    const name = input?.value.trim();
    if (!name) {
        error.textContent = 'Ingresa un nombre';
        error.classList.remove('hidden');
        return;
    }
    error.classList.add('hidden');
    fetch('{{ route('categorias.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ name })
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok) throw new Error(d?.errors?.name?.[0] || 'Error');
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.name;
        opt.selected = true;
        select.appendChild(opt);
        input.value = '';
        document.getElementById(`newCategoryForm${mode}`).classList.add('hidden');
    })
    .catch(err => { error.textContent = err.message; error.classList.remove('hidden'); });
}
</script>
@endpush
@endsection
