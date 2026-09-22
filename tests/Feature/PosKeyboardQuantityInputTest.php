<?php

test('the point of sale supports physical keyboard quantity entry', function () {
    $numpad = file_get_contents(resource_path('views/facturacion/_numpad.blade.php'));
    $pos = file_get_contents(resource_path('views/facturacion/pos.blade.php'));
    $styles = file_get_contents(resource_path('css/app-ui.css'));

    expect($numpad)
        ->toContain('id="posQuantityTools" class="pos-quantity-tools hidden"')
        ->toContain('id="selectedItemQty"')
        ->toContain('type="text"')
        ->toContain('inputmode="decimal"')
        ->toContain('id="posNumpad" class="pos-pad hidden"')
        ->toContain('Mostrar teclado')
        ->toContain('onclick="hideQuantityEditor()"')
        ->and($pos)
        ->toContain("quantityInput?.addEventListener('input'")
        ->toContain("quantityInput?.addEventListener('keydown'")
        ->toContain("event.key === 'Enter'")
        ->toContain("event.key === 'Escape'")
        ->toContain("replace(',', '.')")
        ->toContain('if (padConfirm())')
        ->toContain('qty > maxStock')
        ->toContain('function applyTicketQuantity(idx, rawQuantity)')
        ->toContain('function focusQuantityInput()')
        ->toContain('let quantityEditorOpen = false')
        ->toContain('window.hideQuantityEditor = function()')
        ->toContain("pad.classList.toggle('hidden', !canOpen)")
        ->toContain('replaceQuantityOnNextInput = true')
        ->toContain('addProductToTicket(${p.id}, 1, cardUnitId(${p.id}), true)')
        ->toContain('if (!openModal && quantityEditorOpen && selectedItemIndex >= 0 && !editingText)')
        ->toContain("padInput(e.key === ',' ? '.' : e.key)")
        ->toContain('quantityInput.focus({ preventScroll: true })')
        ->toContain('quantityInput.setSelectionRange(0, quantityInput.value.length)')
        ->toContain('ticketColumn.scrollTop += editorBox.bottom - columnBox.bottom + margin')
        ->and($styles)
        ->toContain('overflow: clip;')
        ->toContain('overflow-y: auto;');
});
