@extends('layouts.app')

@section('title', 'Editar Impuesto')

@section('content')

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Editar Impuesto</h1>
        <p class="page-subtitle">{{ $tax->code }} — {{ $tax->name }}</p>
    </div>

    <form method="POST" action="{{ route('settings.taxes.update', $tax) }}" class="card p-6">
        @method('PUT')
        @include('contabilidad.impuestos._form')
    </form>
</div>

@endsection
