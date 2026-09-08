@extends('layouts.app')

@php($isPurchaseProforma = ($purchaseMode ?? 'immediate') === 'proforma')

@section('title', $isPurchaseProforma ? 'Nueva proforma de compra' : 'Nueva compra')
@section('main-class', 'p-0 overflow-hidden')
@section('hide_back', true)

@section('content')
    @include('compras._form', [
        'action' => route('compras.store'),
        'method' => 'POST',
        'purchase' => null,
        'suppliers' => $suppliers,
        'warehouses' => $warehouses,
        'categories' => $categories,
        'units' => $units,
        'currencies' => $currencies,
        'companyCurrency' => $companyCurrency,
        'companySymbol' => $companySymbol,
        'exchangeRates' => $exchangeRates,
        'initialItems' => [],
        'purchaseMode' => $purchaseMode ?? 'immediate',
        'title' => $isPurchaseProforma ? 'Proforma de compra' : 'Ingreso a inventario',
        'submitLabel' => $isPurchaseProforma ? 'Guardar proforma' : 'Registrar compra',
    ])
@endsection
