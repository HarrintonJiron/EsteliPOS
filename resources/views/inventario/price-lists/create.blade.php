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
        <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label">Válida desde</label><input type="date" name="valid_from" value="{{ old('valid_from') }}" class="input-field"></div>
            <div><label class="form-label">Válida hasta</label><input type="date" name="valid_to" value="{{ old('valid_to') }}" class="input-field"></div>
        </div>
        <p class="text-xs text-slate-500">Deja las fechas vacías si la lista no vence.</p>
        <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Lista predeterminada</label>
        <div class="flex gap-2"><a href="{{ route('inventario.price-lists.index') }}" class="btn-outline">Cancelar</a><button class="btn-primary">Crear</button></div>
    </form>
</div>
@endsection
