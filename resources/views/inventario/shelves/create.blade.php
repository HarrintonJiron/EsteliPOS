@extends('layouts.app')
@section('title', 'Nuevo Estante')
@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    @include('inventario._hub-nav')
    <div><h1 class="page-title">Nuevo estante</h1><p class="page-subtitle">Agrega una ubicación dentro de una bodega</p></div>
    <form method="POST" action="{{ route('inventario.shelves.store') }}" class="card space-y-4 p-5">@csrf
        <div><label class="form-label">Bodega *</label><select name="warehouse_id" class="select-field" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $warehouses->firstWhere('is_default', true)?->id) == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select>@error('warehouse_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Código *</label><input name="code" value="{{ old('code') }}" class="input-field" maxlength="50" required placeholder="A-01">@error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Nombre o descripción</label><input name="name" value="{{ old('name') }}" class="input-field" maxlength="120" placeholder="Fertilizantes"></div>
        <div class="flex gap-2"><a href="{{ route('inventario.shelves.index') }}" class="btn-outline">Cancelar</a><button class="btn-primary">Guardar estante</button></div>
    </form>
</div>
@endsection
