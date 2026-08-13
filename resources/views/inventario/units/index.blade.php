@extends('layouts.app')
@section('title', 'Unidades de Medida')
@section('content')
@php
    $activeUnits = $units->where('is_active', true);
@endphp
<div class="space-y-4">
    @include('inventario._hub-nav')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="page-title">Unidades de medida</h1>
            <p class="page-subtitle">Catálogo editable para inventario, conversiones y compras</p>
        </div>
        <button type="button" onclick="document.getElementById('createUnitModal').showModal()" class="btn-primary text-sm">
            + Nueva unidad
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
        <div class="card overflow-hidden p-0">
            <div class="bg-gradient-to-br from-slate-800 to-slate-700 px-5 py-4 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Calculadora</p>
                <h2 class="mt-1 text-lg font-bold">Convertir por producto</h2>
                <p class="mt-0.5 text-xs text-slate-300">Usa las equivalencias guardadas en cada producto</p>
            </div>
            <div class="space-y-3 p-5">
                <div>
                    <label class="form-label">Producto</label>
                    <select id="convProduct" class="select-field">
                        @foreach(\App\Models\Product::where('status','active')->orderBy('name')->limit(200)->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="form-label">Cantidad</label>
                        <input type="number" step="0.0001" id="convQty" class="input-field text-center font-bold" value="1">
                    </div>
                    <div>
                        <label class="form-label">De</label>
                        <select id="convFrom" class="select-field">
                            @foreach($activeUnits as $u)<option value="{{ $u->id }}">{{ $u->abbreviation }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">A</label>
                        <select id="convTo" class="select-field">
                            @foreach($activeUnits as $u)<option value="{{ $u->id }}">{{ $u->abbreviation }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <button type="button" id="convBtn" class="btn-primary w-full text-sm">Convertir</button>
                <div class="rounded-2xl bg-indigo-50 px-4 py-4 text-center ring-1 ring-indigo-100">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Resultado</p>
                    <p id="convResult" class="mt-1 text-xl font-bold text-indigo-800">—</p>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-3">
            <h2 class="font-semibold text-slate-800">¿Dónde configuro 1 carga = 2 qq?</h2>
            <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-600">
                <li>Abre el <strong>producto</strong> en inventario (ficha del producto).</li>
                <li>Arriba verás <strong>Calculadora</strong> + <strong>Tabla de equivalencias</strong>.</li>
                <li>En la tabla: <code class="rounded bg-slate-100 px-1">1 carga = 2 qq</code> → Guardar.</li>
            </ol>
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Aquí solo creas unidades (carga, qq, saco…). La equivalencia se guarda por producto.
            </p>
            <a href="{{ route('inventario.index') }}" class="btn-outline text-sm inline-flex">Ir al inventario</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="table-agro w-full text-sm">
            <thead>
                <tr>
                    <th>Unidad</th>
                    <th>Abrev.</th>
                    <th>Tipo</th>
                    <th class="text-right">Productos</th>
                    <th class="text-right">Conversiones</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr class="{{ $unit->is_active ? '' : 'opacity-60' }}">
                    <td class="font-medium">{{ $unit->name }}</td>
                    <td class="font-mono">{{ $unit->abbreviation }}</td>
                    <td>{{ $unit->typeLabel() }}</td>
                    <td class="text-right">{{ $unit->products_count }}</td>
                    <td class="text-right">{{ $unit->conversions_count }}</td>
                    <td>
                        <span class="{{ $unit->is_active ? 'badge-success' : 'badge-warning' }}">
                            {{ $unit->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <button
                            type="button"
                            class="btn-outline text-xs"
                            data-edit-unit
                            data-id="{{ $unit->id }}"
                            data-name="{{ $unit->name }}"
                            data-abbreviation="{{ $unit->abbreviation }}"
                            data-type="{{ $unit->unit_type }}"
                            data-active="{{ $unit->is_active ? '1' : '0' }}"
                        >Editar</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay unidades. Crea la primera.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<dialog id="createUnitModal" class="w-full max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" action="{{ route('inventario.units.store') }}" class="space-y-4 p-5">
        @csrf
        <div>
            <h2 class="text-lg font-bold text-slate-900">Nueva unidad</h2>
            <p class="text-xs text-slate-500">Quedará disponible en productos, conversiones y compras.</p>
        </div>
        <div>
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="input-field" required placeholder="Ej: Quintal">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Abreviatura</label>
                <input type="text" name="abbreviation" class="input-field font-mono" required maxlength="20" placeholder="qq">
            </div>
            <div>
                <label class="form-label">Tipo</label>
                <select name="unit_type" class="select-field" required>
                    @foreach($unitTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
            Activa
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
            <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="btn-primary">Guardar</button>
        </div>
    </form>
</dialog>

<dialog id="editUnitModal" class="w-full max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" id="editUnitForm" class="space-y-4 p-5">
        @csrf
        @method('PUT')
        <div>
            <h2 class="text-lg font-bold text-slate-900">Editar unidad</h2>
            <p class="text-xs text-slate-500">Los productos conservan su vínculo por ID.</p>
        </div>
        <div>
            <label class="form-label">Nombre</label>
            <input type="text" name="name" id="editUnitName" class="input-field" required>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Abreviatura</label>
                <input type="text" name="abbreviation" id="editUnitAbbreviation" class="input-field font-mono" required maxlength="20">
            </div>
            <div>
                <label class="form-label">Tipo</label>
                <select name="unit_type" id="editUnitType" class="select-field" required>
                    @foreach($unitTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="editUnitActive" value="1" class="rounded border-slate-300">
            Activa
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
            <button type="button" class="btn-outline" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="btn-primary">Actualizar</button>
        </div>
    </form>
</dialog>
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
    if (!res.ok) { document.getElementById('convResult').textContent = data.message || 'Error: configura la equivalencia en la ficha del producto'; return; }
    document.getElementById('convResult').textContent = `${data.converted_quantity} (base: ${data.base_quantity} ${data.base_unit})`;
});

const editModal = document.getElementById('editUnitModal');
const editForm = document.getElementById('editUnitForm');
document.querySelectorAll('[data-edit-unit]').forEach((button) => {
    button.addEventListener('click', () => {
        editForm.action = `{{ url('/inventario/unidades') }}/${button.dataset.id}`;
        document.getElementById('editUnitName').value = button.dataset.name;
        document.getElementById('editUnitAbbreviation').value = button.dataset.abbreviation;
        document.getElementById('editUnitType').value = button.dataset.type;
        document.getElementById('editUnitActive').checked = button.dataset.active === '1';
        editModal.showModal();
    });
});
</script>
@endpush
