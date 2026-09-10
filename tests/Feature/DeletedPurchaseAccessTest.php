<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('viewing a purchase deleted from another window redirects with a clear message', function (string $routeName) {
    $response = $this->withoutMiddleware()->get(route($routeName, 999999));

    $response
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas(
            'error',
            'La compra ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
})->with([
    'detail' => 'compras.show',
    'edit' => 'compras.edit',
]);

test('canceling a purchase deleted from another window redirects with a clear message', function () {
    $response = $this->withoutMiddleware()->post(route('compras.status', 999999), [
        'status' => 'canceled',
    ]);

    $response
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas(
            'error',
            'La compra ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
});
