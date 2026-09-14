@extends('layouts.app')

@section('title', 'Editar compra #' . $purchase->id)
@section('main-class', 'p-0 overflow-hidden')
@section('hide_back', true)

@section('content')
    @include('compras._form', [
        'action' => route('compras.update', $purchase->id),
        'method' => 'PUT',
        'purchase' => $purchase,
        'suppliers' => $suppliers,
        'warehouses' => $warehouses,
        'categories' => $categories,
        'units' => $units,
        'currencies' => $currencies,
        'companyCurrency' => $companyCurrency,
        'companySymbol' => $companySymbol,
        'exchangeRates' => $exchangeRates,
        'initialItems' => $purchase->details->map(function ($detail) {
            $product = $detail->product;
            $availableUnits = $product
                ? app(\App\Services\PurchaseCostingService::class)->purchaseUnitsFor($product)
                : [];

            return [
                'id' => $detail->product_id,
                'name' => $product->name ?? 'Producto',
                'code' => $product->code ?? '',
                'quantity' => (float) $detail->quantity,
                'price' => (float) $detail->price,
                'unit_id' => $detail->unit_id ?? $product?->base_unit_id,
                'units' => $availableUnits,
            ];
        })->values(),
        'title' => 'Actualizar mercadería',
        'submitLabel' => 'Guardar cambios',
    ])
@endsection
