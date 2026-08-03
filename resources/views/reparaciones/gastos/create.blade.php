@extends('layouts.app')

@section('title', 'Nuevo Gasto Operativo')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Nuevo Gasto Operativo</h1>
            <p class="page-subtitle">Registrar una salida de dinero del taller</p>
        </div>
        <a href="{{ route('reparaciones.gastos.index') }}" class="btn-outline text-sm">← Volver</a>
    </div>

    <form method="POST" action="{{ route('reparaciones.gastos.store') }}" class="space-y-6">
        @csrf
        @include('reparaciones.gastos._form')
    </form>
</div>
@endsection