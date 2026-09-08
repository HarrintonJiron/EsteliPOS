@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Código</label>
        <input type="text" name="code" value="{{ old('code', $tax->code ?? '') }}" placeholder="Ej. IVA-15" required class="input-field">
        @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $tax->name ?? '') }}" placeholder="Ej. IVA General" required class="input-field">
        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tasa (%)</label>
        <input type="number" name="rate" value="{{ old('rate', isset($tax) ? $tax->rate * 100 : '') }}" step="0.01" min="0" max="100" required class="input-field">
        @error('rate')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-6 mt-6">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $tax->is_default ?? false) ? 'checked' : '' }}>
            Impuesto predeterminado
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tax->is_active ?? true) ? 'checked' : '' }}>
            Activo
        </label>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('settings.taxes.index') }}" class="btn-outline">Cancelar</a>
    <button type="submit" class="btn-primary">Guardar</button>
</div>
