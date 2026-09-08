@extends('layouts.app')
@section('title', 'Editar '.$priceList->name)
@section('content')
<div class="max-w-xl mx-auto card p-5 space-y-4">
    <form method="POST" action="{{ route('inventario.price-lists.update', $priceList) }}" class="space-y-3">
        @csrf @method('PUT')
        <div><label class="form-label">Código</label><input name="code" class="input-field" value="{{ old('code', $priceList->code) }}" required></div>
        <div><label class="form-label">Nombre</label><input name="name" class="input-field" value="{{ old('name', $priceList->name) }}" required></div>
        <div><label class="form-label">Descripción</label><textarea name="description" class="input-field" rows="2">{{ old('description', $priceList->description) }}</textarea></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label">Válida desde</label><input type="date" name="valid_from" value="{{ old('valid_from', $priceList->valid_from?->format('Y-m-d')) }}" class="input-field"></div>
            <div><label class="form-label">Válida hasta</label><input type="date" name="valid_to" value="{{ old('valid_to', $priceList->valid_to?->format('Y-m-d')) }}" class="input-field"></div>
        </div>
        <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked($priceList->is_default)> Predeterminada</label>
        <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($priceList->is_active)> Activa</label>
        <button class="btn-primary">Guardar</button>
    </form>
</div>
@endsection
