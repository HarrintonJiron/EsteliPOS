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
        'initialItems' => $purchase->details->map(fn ($detail) => [
            'id' => $detail->product_id,
            'name' => $detail->product->name ?? 'Producto',
            'code' => $detail->product->code ?? '',
            'quantity' => (int) $detail->quantity,
            'price' => (float) $detail->price,
        ])->values(),
        'title' => 'Actualizar mercadería',
        'submitLabel' => 'Guardar cambios',
    ])
@endsection
