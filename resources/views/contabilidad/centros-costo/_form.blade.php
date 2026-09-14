@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Código</label>
        <input type="text" name="code" value="{{ old('code', $costCenter->code ?? '') }}" placeholder="Ej. SUC-01" required class="input-field">
        @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $costCenter->name ?? '') }}" required class="input-field">
        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
        <textarea name="description" rows="2" class="input-field">{{ old('description', $costCenter->description ?? '') }}</textarea>
        @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
        <select name="type" required class="select-field">
            <option value="">Seleccione...</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" {{ old('type', $costCenter->type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-6 mt-6">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $costCenter->is_active ?? true) ? 'checked' : '' }}>
            Activo
        </label>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('contabilidad.centros-costo.index') }}" class="btn-outline">Cancelar</a>
    <button type="submit" class="btn-primary">Guardar</button>
</div>
