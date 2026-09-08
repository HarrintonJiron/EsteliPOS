@extends('layouts.app')
@section('title', 'Editar sucursal')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">
    <div><h1 class="page-title">Editar sucursal</h1><p class="page-subtitle">{{ $branch->name }}</p></div>
    @include('sucursales._form')
</div>
@endsection
