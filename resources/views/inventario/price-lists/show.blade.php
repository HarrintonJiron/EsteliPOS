@extends('layouts.app')
@section('title', $priceList->name)
@section('content')
<div class="space-y-5">
    <div class="flex justify-between gap-3 flex-wrap">
        <div><h1 class="page-title">{{ $priceList->name }}</h1><p class="page-subtitle">{{ $priceList->code }} · Crea varios precios para la misma presentación indicando desde qué cantidad aplican.</p></div>
        <a href="{{ route('inventario.price-lists.edit', $priceList) }}" class="btn-outline text-sm">Editar lista</a>
    </div>
    @if(session('success'))<div class="card p-3 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="card p-3 bg-red-50 text-red-800 text-sm">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('inventario.price-lists.items.store', $priceList) }}" class="card p-4 grid md:grid-cols-12 gap-3 items-end">
        @csrf
        <div class="md:col-span-4"><label class="form-label">1. Producto</label><select id="priceProduct" name="product_id" class="select-field" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="form-label">2. Presentación</label><select id="priceUnit" name="unit_id" class="select-field" required></select></div>
        <div class="md:col-span-2"><label class="form-label">3. Desde cantidad</label><input type="number" step="0.0001" min="0.0001" value="{{ old('min_quantity', 1) }}" name="min_quantity" class="input-field" required><p class="mt-1 text-[11px] text-slate-500">Ej.: 1, 6 o 12</p></div>
        <div class="md:col-span-2"><label class="form-label">4. Precio C$</label><input id="priceAmount" type="number" step="0.01" min="0" name="unit_price" class="input-field" required></div>
        <div class="md:col-span-2"><button class="btn-primary w-full">Guardar escala</button></div>
        <div class="md:col-span-12 flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs">
            <span id="priceReference" class="font-semibold text-slate-600"></span>
            <button type="button" data-price-discount="10" class="rounded-lg border border-slate-200 bg-white px-2 py-1 font-semibold text-indigo-700">Público −10%</button>
            <button type="button" data-price-discount="15" class="rounded-lg border border-slate-200 bg-white px-2 py-1 font-semibold text-indigo-700">Público −15%</button>
            <button type="button" data-cost-margin="25" class="rounded-lg border border-slate-200 bg-white px-2 py-1 font-semibold text-emerald-700">Costo +25%</button>
            <span id="pricePreview" class="ml-auto font-semibold"></span>
        </div>
    </form>
    <div class="card overflow-hidden">
        <table class="w-full table-agro text-sm">
            <thead><tr><th>Producto</th><th>Presentación</th><th class="text-right">Desde</th><th class="text-right">Precio</th><th class="text-right">Margen</th><th></th></tr></thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->unit?->abbreviation ?? 'Base' }}</td>
                    <td class="text-right font-semibold">{{ number_format($item->min_quantity, 4, '.', ',') }}</td>
                    <td class="text-right font-semibold">C$ {{ number_format($item->unit_price, 2) }}</td>
                    @php
                        $factor = (int) $item->unit_id === (int) $item->product->base_unit_id
                            ? 1
                            : (float) ($item->product->unitConversions->firstWhere('unit_id', $item->unit_id)?->factor_to_base ?? 1);
                        $unitCost = (float) $item->product->purchase_price * $factor;
                        $margin = (float) $item->unit_price > 0
                            ? (((float) $item->unit_price - $unitCost) / (float) $item->unit_price) * 100
                            : 0;
                    @endphp
                    <td class="text-right {{ $margin < 0 ? 'text-red-600' : 'text-slate-600' }}">{{ number_format($margin, 1) }}%</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('inventario.price-lists.items.destroy', [$priceList, $item]) }}" onsubmit="return confirm('¿Eliminar?')">@csrf @method('DELETE')<button class="text-red-600 text-xs">Quitar</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-slate-400">Sin precios configurados</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($items->hasPages())<div class="p-3 border-t">{{ $items->links() }}</div>@endif
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const products = @json($productOptions);
    const productSelect = document.getElementById('priceProduct');
    const unitSelect = document.getElementById('priceUnit');
    const amountInput = document.getElementById('priceAmount');
    const reference = document.getElementById('priceReference');
    const preview = document.getElementById('pricePreview');
    const selectedUnit = () => products.find(item => String(item.id) === productSelect.value)?.units
        ?.find(unit => String(unit.id) === unitSelect.value);
    const refreshPreview = () => {
        const unit = selectedUnit();
        if (!unit) return;
        reference.textContent = `Público: C$ ${unit.public_price.toFixed(2)} · Costo: C$ ${unit.cost.toFixed(2)}`;
        const price = parseFloat(amountInput.value);
        if (!Number.isFinite(price) || price <= 0) { preview.textContent = ''; return; }
        const margin = ((price - unit.cost) / price) * 100;
        preview.textContent = `Margen ${margin.toFixed(1)}%`;
        preview.className = `ml-auto font-semibold ${margin < 0 ? 'text-red-600' : 'text-emerald-700'}`;
    };
    const refreshUnits = () => {
        const product = products.find(item => String(item.id) === productSelect.value);
        unitSelect.innerHTML = (product?.units || []).map(unit => `<option value="${unit.id}">${unit.label}</option>`).join('');
        refreshPreview();
    };
    productSelect.addEventListener('change', refreshUnits);
    unitSelect.addEventListener('change', refreshPreview);
    amountInput.addEventListener('input', refreshPreview);
    document.querySelectorAll('[data-price-discount]').forEach(button => button.addEventListener('click', () => {
        const unit = selectedUnit();
        if (!unit) return;
        amountInput.value = (unit.public_price * (1 - parseFloat(button.dataset.priceDiscount) / 100)).toFixed(2);
        refreshPreview();
    }));
    document.querySelectorAll('[data-cost-margin]').forEach(button => button.addEventListener('click', () => {
        const unit = selectedUnit();
        if (!unit) return;
        amountInput.value = (unit.cost * (1 + parseFloat(button.dataset.costMargin) / 100)).toFixed(2);
        refreshPreview();
    }));
    refreshUnits();
});
</script>
@endpush
