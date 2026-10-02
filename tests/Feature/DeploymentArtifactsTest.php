<?php

test('the only windows installer targets apache and mysql', function () {
    $installScript = file_get_contents(base_path('deployment/installer/scripts/Install-EsteliPOS.ps1'));
    $buildScript = file_get_contents(base_path('deployment/installer/scripts/Build-EsteliPOSInstaller.ps1'));
    $backupScript = file_get_contents(base_path('deployment/installer/scripts/Backup-EsteliPOS.ps1'));

    expect($installScript)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('DB_CONNECTION=mysql')
        ->toContain('DB_HOST=127.0.0.1')
        ->toContain('EsteliPOSApache_')
        ->toContain('EsteliPOSMySQL_')
        ->toContain('migrate --force --no-interaction')
        ->toContain('Validando Apache')
        ->toContain('Invoke-WebRequest -Uri $healthUrl')
        ->toContain('$globalStatePath')
        ->toContain('Test-PortAvailable')
        ->toContain('Wait-ForMySQL')
        ->toContain('Assert-PHPDependencies')
        ->toContain('Backup-MySQLDatabase')
        ->toContain('Restore-MySQLDatabase')
        ->toContain('Register-DailyBackup')
        ->toContain("Set-EnvValue \$envFile 'DB_PORT'")
        ->and($buildScript)
        ->toContain("'Apache HTTP Server'")
        ->toContain("'MySQL 8.0.21'")
        ->toContain("'PHP 8.5.10 Thread Safe'")
        ->toContain("'.env'")
        ->toContain("'database\\database.sqlite'")
        ->and($backupScript)
        ->toContain('mysqldump')
        ->toContain('Compress-Archive')
        ->toContain('RetentionDays = 30')
        ->and(file_exists(base_path('deployment/installer/EsteliPOS.nsi')))->toBeTrue()
        ->and(file_exists(base_path('deployment/installer/scripts/Diagnose-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows')))->toBeFalse()
        ->and(file_exists(base_path('deployment/INSTALAR.bat')))->toBeFalse();
});

test('production views do not depend on internet CDNs', function () {
    $viewPaths = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'))
    );
    $views = '';

    foreach ($viewPaths as $path) {
        if ($path->isFile() && $path->getExtension() === 'php') {
            $views .= file_get_contents($path->getPathname());
        }
    }

    expect($views)
        ->not->toContain('cdn.tailwindcss.com')
        ->not->toContain('cdn.jsdelivr.net')
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.bunny.net')
        ->and(file_get_contents(resource_path('js/app.js')))->toContain('chart.js/auto');
});

test('the receipt button uses the silent print flow', function () {
    $changeView = file_get_contents(resource_path('views/facturacion/change.blade.php'));
    $receiptView = file_get_contents(resource_path('views/facturacion/receipt.blade.php'));

    expect($changeView)->toContain('autoprint=1')
        ->and($receiptView)
        ->toContain("request()->boolean('autoprint')")
        ->toContain("window.addEventListener('load', () => window.print())")
        ->toContain("window.addEventListener('afterprint'")
        ->toContain("window.parent.postMessage({type: 'estelipos-print-complete'}")
        ->toContain('window.close();');
});

test('legacy sqlite installer artifacts are not distributed', function () {
    expect(file_exists(base_path('deployment/ticket-patch')))->toBeFalse()
        ->and(file_exists(base_path('deployment/client-inventory')))->toBeFalse()
        ->and(glob(base_path('deployment/EsteliPOS-Consola-*.zip')) ?: [])->toBeEmpty()
        ->and(glob(base_path('deployment/produccion*.zip')) ?: [])->toBeEmpty();
});
