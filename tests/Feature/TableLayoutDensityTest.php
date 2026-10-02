<?php

test('application tables size columns from their content instead of fixed empty space', function () {
    $styles = file_get_contents(resource_path('css/app-ui.css'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($styles)
        ->toContain('main table {')
        ->toContain('width: auto !important;')
        ->toContain('max-width: 100%;')
        ->toContain('table-layout: auto;')
        ->toContain('display: inline-table;')
        ->toContain('main table:has(thead):has(tbody) :where(th, td)')
        ->toContain('padding-inline: clamp(0.4rem, 0.8vw, 0.75rem) !important;')
        ->toContain('th.text-right, td.text-right, th.text-center, td.text-center')
        ->toContain('.table-col-compact')
        ->toContain('width: 1px !important;')
        ->toContain('width: 10rem !important;')
        ->toContain('width: 5.5rem !important;')
        ->toContain('max-width: 28rem;')
        ->toContain('overflow-wrap: anywhere;')
        ->toContain('main .card:has(table)')
        ->not->toContain('.table-agro { min-width: 42rem; }')
        ->and($javascript)
        ->toContain('compactApplicationTableColumns')
        ->toContain('compactTableHeading')
        ->toContain('#(?:\\s+factura)?')
        ->toContain('|factura|')
        ->toContain("heading.classList.add('table-col-compact')")
        ->toContain('thead tr.ui-data-grid-filters')
        ->toContain('new MutationObserver');
});
