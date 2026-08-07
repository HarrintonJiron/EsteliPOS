@extends('layouts.app')
@section('title', 'Nueva Bodega')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="page-title">Nueva bodega</h1>
    <form method="POST" action="{{ route('inventario.warehouses.store') }}" class="card p-5 space-y-4">
        @csrf
        <div class="grid grid-cols-2 gap-4">
            <div><label class="form-label">Código *</label><input name="code" class="input-field" required placeholder="BOD-04"></div>
            <div><label class="form-label">Nombre *</label><input name="name" class="input-field" required></div>
            <div class="col-span-2"><label class="form-label">Dirección</label><input name="address" class="input-field"></div>
            <div><label class="form-label">Ciudad</label><input name="city" class="input-field" value="Estelí"></div>
            <div><label class="form-label">Teléfono</label><input name="phone" class="input-field"></div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Bodega principal</label>
        <div class="flex gap-2"><a href="{{ route('inventario.warehouses.index') }}" class="btn-outline">Cancelar</a><button class="btn-primary">Guardar</button></div>
    </form>
</div>
@endsection
