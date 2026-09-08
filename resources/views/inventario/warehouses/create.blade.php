@extends('layouts.app')
@section('title', 'Nueva Bodega')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    @include('inventario._hub-nav')

    <div>
        <h1 class="page-title">Nueva bodega</h1>
        <p class="page-subtitle">Crea una ubicación física para separar existencias</p>
    </div>

    <form method="POST" action="{{ route('inventario.warehouses.store') }}" class="card p-5 space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Código *</label>
                <input name="code" value="{{ old('code') }}" class="input-field @error('code') border-red-500 @enderror" required placeholder="BOD-04">
                @error('code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Nombre *</label>
                <input name="name" value="{{ old('name') }}" class="input-field @error('name') border-red-500 @enderror" required placeholder="Bodega Patio">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Dirección</label>
                <input name="address" value="{{ old('address') }}" class="input-field">
            </div>
            <div>
                <label class="form-label">Ciudad</label>
                <input name="city" value="{{ old('city', 'Estelí') }}" class="input-field">
            </div>
            <div>
                <label class="form-label">Teléfono</label>
                <input name="phone" value="{{ old('phone') }}" class="input-field">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Notas</label>
                <textarea name="notes" rows="3" class="input-field" placeholder="Ubicación, responsable, etc.">{{ old('notes') }}</textarea>
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
            Marcar como bodega principal
        </label>
        <p class="text-xs text-slate-500">La bodega principal se usa por defecto en compras y ajustes si no se elige otra.</p>
        <div class="flex gap-2">
            <a href="{{ route('inventario.warehouses.index') }}" class="btn-outline">Cancelar</a>
            <button class="btn-primary">Guardar</button>
        </div>
    </form>
</div>
@endsection
