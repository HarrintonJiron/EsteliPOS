@extends('layouts.app')
@section('hide_back', true)

@section('title', 'Editar Gasto Operativo')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Editar Gasto Operativo</h1>
            <p class="page-subtitle">Actualizar información y reflejo contable del egreso</p>
        </div>
        <a href="{{ route('reparaciones.gastos.show', $expense) }}" class="btn-outline text-sm">← Volver</a>
    </div>

    <form method="POST" action="{{ route('reparaciones.gastos.update', $expense) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('reparaciones.gastos._form', ['expense' => $expense])
    </form>
</div>
@endsection