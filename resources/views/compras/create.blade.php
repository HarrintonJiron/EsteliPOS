@extends('layouts.app')

@section('title', 'Nueva compra')
@section('main-class', 'p-0 overflow-hidden')
@section('hide_back', true)

@section('content')
    @include('compras._form', [
        'action' => route('compras.store'),
        'method' => 'POST',
        'purchase' => null,
        'suppliers' => $suppliers,
        'warehouses' => $warehouses,
        'initialItems' => [],
        'title' => 'Ingreso a inventario',
        'submitLabel' => 'Registrar compra',
    ])
@endsection
