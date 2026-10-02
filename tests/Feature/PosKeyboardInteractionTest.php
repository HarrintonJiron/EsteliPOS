<?php

test('pos supports fast keyboard quantity editing and authoritative exact payment', function () {
    $view = file_get_contents(resource_path('views/facturacion/pos.blade.php'));

    expect($view)
        ->toContain('const shouldOpenQuantityEditor = openQuantityEditor;')
        ->toContain("setMobilePosView('ticket')")
        ->toContain("document.getElementById('selectedItemBar').classList.toggle('hidden', !quantityEditorOpen)")
        ->toContain("quantityInput?.addEventListener('blur'")
        ->toContain('addProductToTicket(exact.id, 1, null, true)')
        ->toContain('name="payment_exact"')
        ->toContain("document.getElementById('paymentExactInput').value = '1'")
        ->toContain('Math.max(0, roundMoney(amount - getAmountDue()))');
});
