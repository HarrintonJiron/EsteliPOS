<?php

test('the windows deployment package is self contained and production safe', function () {
    $deployScript = file_get_contents(base_path('deployment/windows/Deploy-EsteliPOS.ps1'));
    $buildScript = file_get_contents(base_path('deployment/build-release.sh'));

    expect($deployScript)
        ->toContain('APP_ENV" "production"')
        ->toContain('APP_DEBUG" "false"')
        ->toContain('DB_CONNECTION" "sqlite"')
        ->toContain('[version]"8.4.1"')
        ->toContain('app:install-production')
        ->toContain('Register-ScheduledTask')
        ->toContain('ExternalBackupPath')
        ->toContain('HostAddress 0.0.0.0')
        ->toContain('RemoteAddress LocalSubnet')
        ->toContain('--kiosk-printing')
        ->not->toContain('composer install')
        ->not->toContain('npm install')
        ->and($buildScript)
        ->toContain('composer install')
        ->toContain('--no-dev')
        ->toContain('npm --prefix "$stage_dir" run build')
        ->toContain('git -C "$project_root" archive')
        ->and(is_executable(base_path('deployment/build-release.sh')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Start-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Stop-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Backup-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Diagnose-EsteliPOS.ps1')))->toBeTrue();
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
        ->toContain("window.addEventListener('afterprint', () => window.close())");
});

test('sqlite is tuned for light lan concurrency', function () {
    expect(config('database.connections.sqlite.busy_timeout'))->toBe(5000)
        ->and(config('database.connections.sqlite.journal_mode'))->toBe('WAL')
        ->and(config('database.connections.sqlite.synchronous'))->toBe('NORMAL')
        ->and(config('database.connections.sqlite.transaction_mode'))->toBe('IMMEDIATE');
});
