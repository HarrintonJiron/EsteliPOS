@extends('layouts.app')

@section('title', 'Editar Centro de Costo')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Editar Centro de Costo</h1>
        <p class="page-subtitle">{{ $costCenter->code }} — {{ $costCenter->name }}</p>
    </div>

    <form method="POST" action="{{ route('contabilidad.centros-costo.update', $costCenter) }}" class="card p-6">
        @method('PUT')
        @include('contabilidad.centros-costo._form')
    </form>
</div>

@endsection
