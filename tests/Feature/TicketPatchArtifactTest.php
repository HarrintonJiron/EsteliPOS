<?php

test('ticket patch validates but never replaces the sqlite database', function () {
    $updater = file_get_contents(base_path('deployment/ticket-patch/Aplicar-Parche-Ticket-3.0.ps1'));
    $installer = file_get_contents(base_path('deployment/ticket-patch/INSTALAR-PARCHE-3.0.bat'));

    expect($updater)
        ->toContain('PRAGMA quick_check')
        ->toContain('DatabaseHashBefore')
        ->toContain('DatabaseHashAfter')
        ->toContain('artisan view:clear')
        ->toContain('BackupReceipt')
        ->not->toContain('artisan migrate')
        ->not->toMatch('/Copy-Item[^\r\n]*DatabasePath/')
        ->not->toMatch('/Move-Item[^\r\n]*DatabasePath/')
        ->not->toMatch('/Remove-Item[^\r\n]*DatabasePath/')
        ->and($installer)
        ->toContain('PARCHE 3.0')
        ->toContain('La base de datos NO fue modificada');
});

test('ticket patch build includes only the thermal receipt payload', function () {
    $buildScript = file_get_contents(base_path('deployment/build-ticket-patch.sh'));

    expect($buildScript)
        ->toContain('resources/views/facturacion/receipt.blade.php')
        ->toContain('parcheticket.zip')
        ->not->toContain('composer install')
        ->not->toContain('npm install')
        ->not->toContain('database.sqlite');
});
