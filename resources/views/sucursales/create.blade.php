@extends('layouts.app')
@section('title', 'Nueva sucursal')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">
    <div><h1 class="page-title">Nueva sucursal</h1><p class="page-subtitle">Registra un punto operativo y vincúlalo con su bodega para obtener indicadores reales.</p></div>
    @include('sucursales._form')
</div>
@endsection
