<?php

test('pro product monetary prefixes reserve space inside their inputs', function () {
    $view = file_get_contents(resource_path('views/inventario/_cellphone_fields.blade.php'));
    $styles = file_get_contents(resource_path('css/app-ui.css'));

    expect($view)
        ->toContain('name="wholesale_price"')
        ->toContain('name="special_price"')
        ->toContain('money-prefix-input')
        ->and($styles)
        ->toContain('.input-field.money-prefix-input')
        ->toContain('padding-left: 2.75rem !important;');
});
