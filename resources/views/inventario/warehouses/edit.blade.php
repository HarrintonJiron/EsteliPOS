@extends('layouts.app')
@section('hide_back', true)
@section('title', 'Editar Bodega')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="page-title">Editar {{ $warehouse->name }}</h1>
    <form method="POST" action="{{ route('inventario.warehouses.update', $warehouse) }}" class="card p-5 space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-2 gap-4">
            <div><label class="form-label">Código *</label><input name="code" class="input-field" value="{{ old('code', $warehouse->code) }}" required></div>
            <div><label class="form-label">Nombre *</label><input name="name" class="input-field" value="{{ old('name', $warehouse->name) }}" required></div>
            <div class="col-span-2"><label class="form-label">Dirección</label><input name="address" class="input-field" value="{{ old('address', $warehouse->address) }}"></div>
            <div><label class="form-label">Ciudad</label><input name="city" class="input-field" value="{{ old('city', $warehouse->city) }}"></div>
            <div><label class="form-label">Teléfono</label><input name="phone" class="input-field" value="{{ old('phone', $warehouse->phone) }}"></div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked($warehouse->is_default)> Principal</label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($warehouse->is_active)> Activa</label>
        <div class="flex gap-2"><a href="{{ route('inventario.warehouses.show', $warehouse) }}" class="btn-outline">Volver</a><button class="btn-primary">Actualizar</button></div>
    </form>
</div>
@endsection
