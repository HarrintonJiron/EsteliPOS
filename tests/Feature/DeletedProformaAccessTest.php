<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stale proforma pages redirect with a clear message', function (string $routeName) {
    $response = $this->withoutMiddleware()->get(route($routeName, ['id' => 999999]));

    $response
        ->assertRedirect(route('proformas.index'))
        ->assertSessionHas(
            'error',
            'La proforma ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
})->with([
    'detail' => 'proformas.show',
    'PDF' => 'proformas.pdf',
    'ticket' => 'proformas.ticket',
]);

test('deleting a proforma already removed from another window redirects with a clear message', function () {
    $response = $this->withoutMiddleware()->delete(route('proformas.destroy', 999999));

    $response
        ->assertRedirect(route('proformas.index'))
        ->assertSessionHas(
            'error',
            'La proforma ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
});

test('updating a proforma already removed from another window redirects with a clear message', function () {
    $response = $this->withoutMiddleware()->patch(route('proformas.status', 999999), [
        'status' => 'sent',
    ]);

    $response
        ->assertRedirect(route('proformas.index'))
        ->assertSessionHas(
            'error',
            'La proforma ya no está disponible; posiblemente fue eliminada en otra ventana.',
        );
});
