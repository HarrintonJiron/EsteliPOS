<?php

test('the frontend announces when Chart.js is ready', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain('window.Chart = Chart;')
        ->toContain("window.dispatchEvent(new CustomEvent('charts:ready'));");
});

test('dashboards wait for Chart.js when the module loads after their inline script', function () {
    foreach ([
        'dashboard-general.blade.php',
        'inventario/dashboard.blade.php',
        'contabilidad/dashboard.blade.php',
        'planilla/partials/dynamic-payroll-charts.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path("views/{$view}"));

        expect($contents)->toContain("window.addEventListener('charts:ready'");
    }
});
