@extends('layouts.app')
@section('title', 'Unidades de Medida')
@section('content')
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div>
        <h1 class="page-title">Unidades de medida</h1>
        <p class="page-subtitle">Catálogo para ferretería y materiales de construcción en Nicaragua</p>
    </div>

    <div class="card p-4">
        <h2 class="font-semibold text-slate-800 mb-3">Conversor rápido</h2>
        <div class="grid md:grid-cols-5 gap-3 items-end" id="unitConverter">
            <div class="md:col-span-2">
                <label class="form-label">Producto</label>
                <select id="convProduct" class="select-field">
                    @foreach(\App\Models\Product::where('status','active')->orderBy('name')->limit(200)->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="form-label">Cantidad</label><input type="number" step="0.0001" id="convQty" class="input-field" value="1"></div>
            <div><label class="form-label">De</label><select id="convFrom" class="select-field">@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->abbreviation }}</option>@endforeach</select></div>
            <div><label class="form-label">A</label><select id="convTo" class="select-field">@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->abbreviation }}</option>@endforeach</select></div>
        </div>
        <button type="button" id="convBtn" class="btn-primary mt-3 text-sm">Convertir</button>
        <p id="convResult" class="mt-2 text-sm font-semibold text-indigo-700"></p>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full table-agro text-sm">
            <thead><tr><th>Unidad</th><th>Abrev.</th><th>Tipo</th><th class="text-right">Productos base</th></tr></thead>
            <tbody>
                @foreach($units as $unit)
                <tr>
                    <td class="font-medium">{{ $unit->name }}</td>
                    <td class="font-mono">{{ $unit->abbreviation }}</td>
                    <td>{{ $unit->typeLabel() }}</td>
                    <td class="text-right">{{ $unit->products_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('convBtn')?.addEventListener('click', async () => {
    const res = await fetch('{{ route('inventario.convert') }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
        body: JSON.stringify({
            product_id: document.getElementById('convProduct').value,
            quantity: document.getElementById('convQty').value,
            from_unit_id: document.getElementById('convFrom').value,
            to_unit_id: document.getElementById('convTo').value,
        })
    });
    const data = await res.json();
    if (!res.ok) { document.getElementById('convResult').textContent = data.message || 'Error en conversión'; return; }
    document.getElementById('convResult').textContent = `${data.converted_quantity} (base: ${data.base_quantity} ${data.base_unit})`;
});
</script>
@endpush
