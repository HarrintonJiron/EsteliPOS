@extends('layouts.app')
@section('title', 'Nueva Lista de Precios')
@section('content')
<div class="max-w-xl mx-auto card p-5 space-y-4">
    <h1 class="page-title">Nueva lista de precios</h1>
    <form method="POST" action="{{ route('inventario.price-lists.store') }}" class="space-y-3">
        @csrf
        <div><label class="form-label">Código</label><input name="code" class="input-field" required placeholder="MAYOR"></div>
        <div><label class="form-label">Nombre</label><input name="name" class="input-field" required></div>
        <div><label class="form-label">Descripción</label><textarea name="description" class="input-field" rows="2"></textarea></div>
        <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Lista predeterminada</label>
        <div class="flex gap-2"><a href="{{ route('inventario.price-lists.index') }}" class="btn-outline">Cancelar</a><button class="btn-primary">Crear</button></div>
    </form>
</div>
@endsection
