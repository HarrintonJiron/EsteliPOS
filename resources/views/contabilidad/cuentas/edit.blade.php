@extends('layouts.app')

@section('title', 'Editar Cuenta')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="page-title">Editar Cuenta Contable</h1>
        <p class="page-subtitle">{{ $account->code }} — {{ $account->name }}</p>
    </div>

    <form method="POST" action="{{ route('contabilidad.cuentas.update', $account) }}" class="card p-6">
        @method('PUT')
        @include('contabilidad.cuentas._form')
    </form>
</div>

@endsection
