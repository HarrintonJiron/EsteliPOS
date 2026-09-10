<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stale sale output links redirect with a clear message', function (string $routeName) {
    $response = $this->withoutMiddleware()->get(route($routeName, ['sale_id' => 999999]));

    $response
        ->assertRedirect(route('facturacion.index'))
        ->assertSessionHas(
            'error',
            'La factura ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
})->with([
    'PDF' => 'facturacion.pdf',
    'print view' => 'facturacion.print',
]);

test('stale sale pages redirect with a clear message', function (string $routeName, array $parameters) {
    $response = $this->withoutMiddleware()->get(route($routeName, $parameters));

    $response
        ->assertRedirect(route('facturacion.index'))
        ->assertSessionHas(
            'error',
            'La factura ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
})->with([
    'detail' => ['facturacion.show', ['id' => 999999]],
    'edit' => ['facturacion.edit', ['id' => 999999]],
    'receipt' => ['facturacion.receipt', ['saleId' => 999999]],
    'change confirmation' => ['facturacion.change', ['saleId' => 999999]],
]);

test('deleting a sale already removed from another window redirects with a clear message', function () {
    $response = $this->withoutMiddleware()->delete(route('facturacion.destroy', 999999));

    $response
        ->assertRedirect(route('facturacion.index'))
        ->assertSessionHas(
            'error',
            'La factura ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
});
