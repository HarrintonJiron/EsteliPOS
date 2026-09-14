@extends('layouts.app')

@section('title', 'Nuevo Impuesto')

@section('content')

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Nuevo Impuesto</h1>
        <p class="page-subtitle">Agregar una tasa de impuesto al catálogo</p>
    </div>

    <form method="POST" action="{{ route('settings.taxes.store') }}" class="card p-6">
        @include('contabilidad.impuestos._form')
    </form>
</div>

@endsection
