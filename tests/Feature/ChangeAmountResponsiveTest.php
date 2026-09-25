<?php

test('change amount uses fluid typography and remains inside its card', function () {
    $view = file_get_contents(resource_path('views/facturacion/change.blade.php'));

    expect($view)
        ->toContain('change-card')
        ->toContain('container-type: inline-size')
        ->toContain('font-size: clamp(1.75rem, 10cqw, 4.5rem)')
        ->toContain('overflow-wrap: anywhere')
        ->not->toContain('text-7xl font-black text-indigo-600');
});
