@extends('layouts.app')

@section('title', 'Carga Masiva de Productos')

@section('content')

<div class="space-y-6" id="bulkApp" data-next-code="{{ $suggestedCode }}" data-categories='@json($categories)'>

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h2 class="page-title">Carga Masiva de Productos</h2>
            <p class="page-subtitle">Agrega decenas de productos en minutos · Ideal para seteo inicial</p>
        </div>
        <a href="{{ route('inventario.index') }}" class="btn-outline">Volver al inventario</a>
    </div>

    <div class="card p-4">
        <h3 class="font-semibold text-slate-800 mb-3">Valores por defecto</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Categoría</label>
                <select id="defaultCategory" class="select-field">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Unidad</label>
                <select id="defaultUnit" class="select-field">
                    <option value="unidad">Unidad</option>
                    <option value="kg">Kilogramos</option>
                    <option value="lt">Litros</option>
                    <option value="saco">Saco</option>
                    <option value="gal">Galones</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Stock mínimo</label>
                <input type="number" id="defaultLowStock" value="10" min="1" class="input-field">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="addRows(5)" class="btn-outline w-full justify-center">+ 5 filas</button>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Tip: Puedes pegar desde Excel (columnas: código, nombre, precio compra, precio venta, stock)
        </p>
    </div>

    <form action="{{ route('inventario.bulk-store') }}" method="POST" id="bulkForm">
        @csrf
        <input type="hidden" name="default_category_id" id="hiddenCategory">
        <input type="hidden" name="default_unit" id="hiddenUnit">
        <input type="hidden" name="default_low_stock" id="hiddenLowStock">
        <input type="hidden" name="products" id="productsJson">

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="bulkTable">
                    <thead class="bg-slate-800 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left w-8">#</th>
                            <th class="px-3 py-2 text-left">Código</th>
                            <th class="px-3 py-2 text-left min-w-[200px]">Nombre *</th>
                            <th class="px-3 py-2 text-left">Categoría</th>
                            <th class="px-3 py-2 text-right">P. Compra</th>
                            <th class="px-3 py-2 text-right">P. Venta</th>
                            <th class="px-3 py-2 text-right">Stock Inicial</th>
                            <th class="px-3 py-2 text-left">Ubicación</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="bulkBody"></tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-4">
            <button type="button" onclick="addRow()" class="btn-outline">+ Agregar fila</button>
            <button type="button" onclick="autoCodes()" class="btn-outline">Auto-generar códigos</button>
            <button type="submit" class="btn-primary">Guardar todos los productos</button>
            <span id="rowCount" class="text-sm text-slate-500 self-center">0 productos listos</span>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categories = JSON.parse(document.getElementById('bulkApp').dataset.categories);
    let nextCodeNum = 1;
    const suggested = document.getElementById('bulkApp').dataset.nextCode;
    const match = suggested.match(/(\d+)$/);
    if (match) nextCodeNum = parseInt(match[1]);

    function categoryOptions(selected) {
        return categories.map(c =>
            `<option value="${c.id}" ${c.id == selected ? 'selected' : ''}>${c.name}</option>`
        ).join('');
    }

    window.addRow = function(data = {}) {
        const tbody = document.getElementById('bulkBody');
        const idx = tbody.children.length + 1;
        const defaultCat = document.getElementById('defaultCategory').value;

        const tr = document.createElement('tr');
        tr.className = 'border-t border-slate-100 hover:bg-slate-50';
        tr.innerHTML = `
            <td class="px-3 py-2 text-slate-400">${idx}</td>
            <td class="px-3 py-2"><input type="text" class="input-field code-input text-xs font-mono" value="${data.code || ''}" placeholder="auto"></td>
            <td class="px-3 py-2"><input type="text" class="input-field name-input" value="${data.name || ''}" placeholder="Nombre del producto" required></td>
            <td class="px-3 py-2"><select class="select-field cat-input text-xs">${categoryOptions(data.category_id || defaultCat)}</select></td>
            <td class="px-3 py-2"><input type="number" step="0.01" min="0" class="input-field purchase-input text-right" value="${data.purchase_price || ''}" placeholder="0.00"></td>
            <td class="px-3 py-2"><input type="number" step="0.01" min="0" class="input-field sale-input text-right" value="${data.sale_price || ''}" placeholder="0.00"></td>
            <td class="px-3 py-2"><input type="number" min="0" class="input-field stock-input text-right" value="${data.stock ?? ''}" placeholder="0"></td>
            <td class="px-3 py-2"><input type="text" class="input-field location-input text-xs" value="${data.location || ''}" placeholder="Estante A-1"></td>
            <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove(); renumber(); updateCount();" class="text-red-500 hover:text-red-700 text-lg">×</button></td>
        `;
        tbody.appendChild(tr);
        updateCount();
    };

    window.addRows = function(n) { for (let i = 0; i < n; i++) addRow(); };

    window.renumber = function() {
        document.querySelectorAll('#bulkBody tr').forEach((tr, i) => {
            tr.querySelector('td').textContent = i + 1;
        });
    };

    window.updateCount = function() {
        const filled = [...document.querySelectorAll('.name-input')].filter(i => i.value.trim()).length;
        document.getElementById('rowCount').textContent = filled + ' producto(s) listos';
    };

    window.autoCodes = function() {
        document.querySelectorAll('#bulkBody tr').forEach(tr => {
            const input = tr.querySelector('.code-input');
            if (!input.value.trim()) {
                input.value = 'PROD-' + String(nextCodeNum++).padStart(4, '0');
            }
        });
    };

    document.getElementById('bulkBody').addEventListener('input', updateCount);

    document.getElementById('bulkBody').addEventListener('paste', function(e) {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text.includes('\t') && !text.includes(',')) return;

        e.preventDefault();
        const lines = text.trim().split('\n');
        lines.forEach(line => {
            const cols = line.split('\t').length > 1 ? line.split('\t') : line.split(',');
            addRow({
                code: cols[0]?.trim() || '',
                name: cols[1]?.trim() || '',
                purchase_price: cols[2]?.trim() || '',
                sale_price: cols[3]?.trim() || '',
                stock: cols[4]?.trim() || '0',
            });
        });
    });

    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById('hiddenCategory').value = document.getElementById('defaultCategory').value;
        document.getElementById('hiddenUnit').value = document.getElementById('defaultUnit').value;
        document.getElementById('hiddenLowStock').value = document.getElementById('defaultLowStock').value;

        const products = [];
        document.querySelectorAll('#bulkBody tr').forEach(tr => {
            const name = tr.querySelector('.name-input').value.trim();
            if (!name) return;
            products.push({
                code: tr.querySelector('.code-input').value.trim(),
                name,
                category_id: parseInt(tr.querySelector('.cat-input').value),
                purchase_price: parseFloat(tr.querySelector('.purchase-input').value) || 0,
                sale_price: parseFloat(tr.querySelector('.sale-input').value) || 0,
                stock: parseInt(tr.querySelector('.stock-input').value) || 0,
                location: tr.querySelector('.location-input').value.trim() || null,
            });
        });

        if (products.length === 0) {
            alert('Agrega al menos un producto con nombre');
            return;
        }

        document.getElementById('productsJson').value = JSON.stringify(products);
        e.target.submit();
    });

    addRows(10);
});
</script>
@endpush
@endsection
