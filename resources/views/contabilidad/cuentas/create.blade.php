@extends('layouts.app')

@section('title', 'Nueva Cuenta')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Nueva Cuenta Contable</h1>
        <p class="page-subtitle">Agregar una cuenta al catálogo</p>
    </div>

    <form method="POST" action="{{ route('contabilidad.cuentas.store') }}" class="card p-6">
        @include('contabilidad.cuentas._form')
    </form>
</div>

@endsection
