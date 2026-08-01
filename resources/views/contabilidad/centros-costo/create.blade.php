@extends('layouts.app')

@section('title', 'Nuevo Centro de Costo')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Nuevo Centro de Costo</h1>
        <p class="page-subtitle">Agregar un centro de costo</p>
    </div>

    <form method="POST" action="{{ route('contabilidad.centros-costo.store') }}" class="card p-6">
        @include('contabilidad.centros-costo._form')
    </form>
</div>

@endsection
