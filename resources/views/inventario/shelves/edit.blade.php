@extends('layouts.app')
@section('title', 'Editar Estante')
@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    @include('inventario._hub-nav')
    <div><h1 class="page-title">Editar estante {{ $shelf->code }}</h1><p class="page-subtitle">Los productos conservarán la ubicación si cambias el código.</p></div>
    @if(session('error'))<div class="card bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('inventario.shelves.update', $shelf) }}" class="card space-y-4 p-5">@csrf @method('PUT')
        <div><label class="form-label">Bodega *</label><select name="warehouse_id" class="select-field" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $shelf->warehouse_id) == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
        <div><label class="form-label">Código *</label><input name="code" value="{{ old('code', $shelf->code) }}" class="input-field" required>@error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Nombre o descripción</label><input name="name" value="{{ old('name', $shelf->name) }}" class="input-field"></div>
        <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shelf->is_active))>Estante activo</label>
        <div class="flex gap-2"><a href="{{ route('inventario.shelves.show', $shelf) }}" class="btn-outline">Cancelar</a><button class="btn-primary">Actualizar</button></div>
    </form>
</div>
@endsection
