@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Código</label>
        <input type="text" name="code" value="{{ old('code', $account->code ?? '') }}" placeholder="Ej. 1.1.06" required class="input-field">
        @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}" required class="input-field">
        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
        <textarea name="description" rows="2" class="input-field">{{ old('description', $account->description ?? '') }}</textarea>
        @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
        <select name="type" id="type" required class="select-field">
            <option value="">Seleccione...</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" data-nature="{{ \App\Models\Account::NATURE_BY_TYPE[$value] }}" {{ old('type', $account->type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Naturaleza</label>
        <select name="nature" id="nature" required class="select-field">
            <option value="debit" {{ old('nature', $account->nature ?? '') === 'debit' ? 'selected' : '' }}>Deudora</option>
            <option value="credit" {{ old('nature', $account->nature ?? '') === 'credit' ? 'selected' : '' }}>Acreedora</option>
        </select>
        @error('nature')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Cuenta padre</label>
        <select name="parent_id" class="select-field">
            <option value="">Ninguna (cuenta raíz)</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ (int) old('parent_id', $account->parent_id ?? 0) === $parent->id ? 'selected' : '' }}>
                    {{ str_repeat('— ', $parent->level - 1) }}{{ $parent->code }} {{ $parent->name }}
                </option>
            @endforeach
        </select>
        @error('parent_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-6 mt-6">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_postable" value="1" {{ old('is_postable', $account->is_postable ?? true) ? 'checked' : '' }}>
            Acepta movimientos (cuenta de detalle)
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}>
            Activa
        </label>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('contabilidad.cuentas.index') }}" class="btn-outline">Cancelar</a>
    <button type="submit" class="btn-primary">Guardar</button>
</div>

<script>
    document.getElementById('type')?.addEventListener('change', function () {
        const nature = this.selectedOptions[0]?.dataset?.nature;
        if (nature) document.getElementById('nature').value = nature;
    });
</script>
