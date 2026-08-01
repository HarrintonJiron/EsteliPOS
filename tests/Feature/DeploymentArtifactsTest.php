<?php

test('the windows deployment package is self contained and production safe', function () {
    $deployScript = file_get_contents(base_path('deployment/windows/Deploy-EsteliPOS.ps1'));
    $buildScript = file_get_contents(base_path('deployment/build-release.sh'));

    expect($deployScript)
        ->toContain('APP_ENV" "production"')
        ->toContain('APP_DEBUG" "false"')
        ->toContain('DB_CONNECTION" "sqlite"')
        ->toContain('app:install-production')
        ->toContain('Register-ScheduledTask')
        ->toContain('ExternalBackupPath')
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
