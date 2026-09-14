<?php

test('the windows deployment package is self contained and production safe', function () {
    $deployScript = file_get_contents(base_path('deployment/windows/Deploy-EsteliPOS.ps1'));
    $buildScript = file_get_contents(base_path('deployment/build-release.sh'));
    $iisScript = file_get_contents(base_path('deployment/windows/EsteliPOS-IIS.ps1'));
    $phpScript = file_get_contents(base_path('deployment/windows/EsteliPOS-PHP.ps1'));
    $backupScript = file_get_contents(base_path('deployment/windows/Backup-EsteliPOS.ps1'));
    $installerBatch = file_get_contents(base_path('deployment/windows/Install-EsteliPOS.bat'));
    $verifyScript = file_get_contents(base_path('deployment/windows/Verify-PHP-EsteliPOS.ps1'));
    $updateScript = file_get_contents(base_path('deployment/windows/Update-EsteliPOS.ps1'));
    $commonScript = file_get_contents(base_path('deployment/windows/EsteliPOS-Common.ps1'));
    $exeBootstrap = file_get_contents(base_path('deployment/windows/installer/Bootstrap-EsteliPOS-Setup.ps1'));
    $innoSetup = file_get_contents(base_path('deployment/windows/installer/EsteliPOS-Setup.iss'));
    $exeBuilder = file_get_contents(base_path('deployment/build-installer-exe.ps1'));

    expect($deployScript)
        ->toContain('APP_ENV" "production"')
        ->toContain('APP_DEBUG" "false"')
        ->toContain('DB_CONNECTION" "sqlite"')
        ->toContain('DB_JOURNAL_MODE" "WAL"')
        ->toContain('[version]"8.4.1"')
        ->toContain('app:install-production')
        ->toContain('NonInteractive')
        ->toContain('SetStaticIp')
        ->toContain('Set-EsteliPOSStaticIpv4')
        ->toContain('INSTALL_ADMIN_PASSWORD')
        ->toContain('Set-EsteliPOSLanProfilePrivate')
        ->toContain('Get-EsteliPOSPrerequisiteReport')
        ->toContain('Get-EsteliPOSBrowserPath')
        ->toContain('SkipDemoData')
        ->toContain('--demo')
        ->toContain('SkipSlowChecks')
        ->not->toContain('Start-Process $ShortcutPath')
        ->and(file_get_contents(base_path('app/Console/Commands/InstallProductionCommand.php')))
        ->toContain('--admin-password=')
        ->toContain('--demo')
        ->toContain('INSTALL_ADMIN_PASSWORD')
        ->and($deployScript)
        ->toContain('Register-ScheduledTask')
        ->toContain('Register-EsteliPOSServerTask')
        ->toContain('Save-EsteliPOSDeploymentConfig')
        ->toContain('Write-EsteliPOSNetworkAccessPage')
        ->toContain('ServerProfile')
        ->toContain('Install-EsteliPOSIISSite')
        ->toContain('Install-EsteliPOSIISPlatform')
        ->toContain('EsteliPOS-PHP.ps1')
        ->toContain('Ensure-EsteliPOSPhp')
        ->toContain('AutoInstallPhp')
        ->toContain('Test-EsteliPOSInstallation.ps1')
        ->toContain('Se conserva la clave APP_KEY existente')
        ->toContain('Get-EsteliPOSAvailablePort')
        ->toContain('Windows no permite usar el puerto')
        ->toContain('public\web.config')
        ->toContain('ExternalBackupPath')
        ->toContain('HostAddress 0.0.0.0')
        ->toContain('Register-EsteliPOSFirewallRule')
        ->toContain('Launch-EsteliPOS.ps1')
        ->toContain('Abrir-EsteliPOS.bat')
        ->not->toContain('composer install')
        ->not->toContain('npm install')
        ->and(file_get_contents(base_path('deployment/windows/EsteliPOS-Common.ps1')))
        ->toContain('RemoteAddress LocalSubnet')
        ->toContain('EsteliPOS PHP $Port')
        ->and($buildScript)
        ->toContain('composer install')
        ->toContain('--no-dev')
        ->toContain('deployment/windows/EsteliPOS-IIS.ps1')
        ->toContain('deployment/windows/assets/php-ts.zip')
        ->toContain('deployment/windows/assets/rewrite_amd64_en-US.msi')
        ->toContain('deployment/windows/assets/vc_redist.x64.exe')
        ->toContain('deployment/windows/Verify-PHP-EsteliPOS.ps1')
        ->toContain('public/web.config')
        ->toContain('npm --prefix "$stage_dir" run build')
        ->toContain('git -C "$project_root" archive')
        ->toContain('storage/*.sqlite')
        ->toContain('bootstrap/cache/routes-*.php')
        ->and(is_executable(base_path('deployment/build-release.sh')))->toBeTrue()
        ->and(file_exists(base_path('public/web.config')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/EsteliPOS-IIS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Verify-PHP-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Test-EsteliPOSInstallation.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Update-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Start-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Launch-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Stop-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('Abrir-EsteliPOS.bat')))->toBeTrue()
        ->and(file_exists(base_path('Reparar-EsteliPOS-LAN.bat')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Repair-EsteliPOS-LAN.ps1')))->toBeTrue()
        ->and(file_get_contents(base_path('deployment/windows/Start-EsteliPOS.ps1')))
        ->toContain('-S')
        ->toContain('server.php')
        ->toContain('Stop-EsteliPOSSimpleListeners')
        ->toContain('Get-EsteliPOSSimpleListenHost')
        ->toContain('Resolve-EsteliPOSPhpExecutable')
        ->and(file_get_contents(base_path('deployment/windows/Launch-EsteliPOS.ps1')))
        ->toContain('--kiosk-printing')
        ->toContain('Wait-EsteliPOSHttpReady')
        ->toContain('HostAddress", "0.0.0.0')
        ->and(file_get_contents(base_path('deployment/windows/EsteliPOS-Common.ps1')))
        ->toContain('function Resolve-EsteliPOSPhpExecutable')
        ->toContain('function Get-EsteliPOSSimpleListenHost')
        ->toContain('return "0.0.0.0"')
        ->toContain('Register-EsteliPOSFirewallRule')
        ->toContain('function Stop-EsteliPOSSimpleListeners')
        ->toContain('function Invoke-EsteliPOSLocalHttp')
        ->toContain('New-Object System.Net.WebProxy')
        ->and(file_exists(base_path('deployment/windows/Backup-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Diagnose-EsteliPOS.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Install-EsteliPOS.bat')))->toBeTrue()
        ->and(file_exists(base_path('deployment/INSTALAR.bat')))->toBeTrue()
        ->and(file_exists(base_path('Instalar-EsteliPOS.bat')))->toBeTrue()
        ->and(file_exists(base_path('Instalar-EsteliPOS-Grafico.bat')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Install-EsteliPOS-GUI.ps1')))->toBeTrue()
        ->and(file_get_contents(base_path('deployment/INSTALAR.bat')))
        ->toContain('INSTALADOR DE CONSOLA A PRUEBA DE ERRORES')
        ->toContain('C:\\Northlink\\EsteliPOS')
        ->toContain('ACTUALIZAR')
        ->and(file_get_contents(base_path('deployment/build-release.sh')))
        ->toContain('INSTALAR.bat')
        ->and(file_get_contents(base_path('Instalar-EsteliPOS.bat')))
        ->toContain('vendor\\autoload.php')
        ->not->toContain('Install-EsteliPOS-GUI.ps1')
        ->and(file_get_contents(base_path('Instalar-EsteliPOS-Grafico.bat')))
        ->toContain('Install-EsteliPOS-GUI.ps1')
        ->and(file_get_contents(base_path('deployment/windows/Install-EsteliPOS-GUI.ps1')))
        ->toContain('System.Windows.Forms')
        ->toContain('Fijar esta IP en Windows')
        ->toContain('Set-EsteliPOSStaticIpv4')
        ->toContain('-NonInteractive')
        ->toContain('Copiar log')
        ->toContain('Copiar informe')
        ->toContain('Get-EsteliPOSPrerequisiteReport')
        ->toContain('$deployArgs')
        ->toContain('SkipDemoData')
        ->toContain('datos de prueba')
        ->and(file_exists(base_path('deployment/windows/EsteliPOS-InstallErrors.ps1')))->toBeTrue()
        ->and(file_get_contents(base_path('deployment/windows/EsteliPOS-InstallErrors.ps1')))
        ->toContain('COPIAR PARA SOPORTE')
        ->toContain('Format-EsteliPOSInstallErrorReport')
        ->toContain('Identifying')
        ->and(file_exists(base_path('deployment/windows/EsteliPOS-PHP.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/EsteliPOS-Common.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/templates/acceso-red.html')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/assets/qrcode.min.js')))->toBeTrue()
        ->and($installerBatch)->toContain('exit /b 1')
        ->and($installerBatch)->not->toContain('Install-EsteliPOS-GUI.ps1')
        ->and($installerBatch)->not->toContain('-SkipDemoData')
        ->and($verifyScript)->toContain('$script:Failures += $Message')
        ->toContain('$script:Warnings += $Message');

    expect($backupScript)
        ->toContain('database.sqlite')
        ->toContain('estelipos-$Timestamp.sqlite')
        ->not->toContain('mysqldump.exe');

    expect($exeBootstrap)
        ->toContain('Get-FileHash')
        ->toContain('Expand-Archive')
        ->toContain('ProgramData')
        ->toContain('setup-exe-')
        ->toContain('Show-SetupFailure')
        ->toContain('notepad.exe')
        ->toContain('Start-Process -FilePath $launcher')
        ->and($innoSetup)
        ->toContain('WizardStyle=modern')
        ->toContain('PrivilegesRequired=admin')
        ->toContain('ArchitecturesAllowed=x64compatible')
        ->toContain('Bootstrap-EsteliPOS-Setup.ps1')
        ->toContain('produccion1.0.zip.sha256')
        ->toContain('waituntilterminated')
        ->and($exeBuilder)
        ->toContain('Inno Setup 6')
        ->toContain('Get-FileHash')
        ->toContain('EsteliPOS-Setup-$Version.exe')
        ->and(file_exists(base_path('deployment/windows/installer/Bootstrap-EsteliPOS-Setup.ps1')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/installer/EsteliPOS-Setup.iss')))->toBeTrue()
        ->and(file_exists(base_path('deployment/build-installer-exe.ps1')))->toBeTrue();

    expect($iisScript)
        ->toContain('rewrite_amd64_en-US.msi')
        ->toContain('37342FF2F585F263F34F48E9DE59EB1051D61015A8E967DBDE4075716230A32A')
        ->toContain('msiexec.exe')
        ->toContain('IIS-ManagementScriptingTools')
        ->toContain('Register-EsteliPOSPhpHandler')
        ->toContain('Registrando handler PHP con appcmd...')
        ->toContain('$Existing = $FastCgiConfiguration -match [regex]::Escape($PhpCgiPath)')
        ->not->toContain('/text:fullPath')
        ->not->toContain('Clear-WebConfiguration -Filter "system.webServer/handlers"')
        ->not->toContain('Add-WebHandler `')
        ->and(hash_file('sha256', base_path('deployment/windows/assets/rewrite_amd64_en-US.msi')))
        ->toBe('37342ff2f585f263f34f48e9de59eb1051d61015a8e967dbde4075716230a32a');

    expect($phpScript)
        ->toContain('7b57fc9840273ab153834d0e2bd06e0bcf4fead36e381182b4b8fe9cedff3174')
        ->toContain('cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b')
        ->toContain('Get-AuthenticodeSignature')
        ->toContain('$ExtensionPattern')
        ->toContain('$SeenExtensions')
        ->toContain('-PhpPath $ManagedPhpPath')
        ->toContain('-n -r "echo PHP_VERSION;"')
        ->toContain('pdo_sqlite')
        ->toContain('sqlite3')
        ->and(hash_file('sha256', base_path('deployment/windows/assets/php-ts.zip')))
        ->toBe('7b57fc9840273ab153834d0e2bd06e0bcf4fead36e381182b4b8fe9cedff3174')
        ->and(hash_file('sha256', base_path('deployment/windows/assets/vc_redist.x64.exe')))
        ->toBe('cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b');

    expect($updateScript)
        ->toContain('"database\factories" = "database\factories"')
        ->toContain('"public\images" = "public\images"')
        ->toContain('Repair-EsteliPOS-LAN.ps1')
        ->toContain('Register-EsteliPOSFirewallRule')
        ->toContain('Resolve-EsteliPOSPhpExecutable')
        ->toContain('"vendor" = "vendor"')
        ->toContain('"config" = "config"')
        ->toContain('"public\css" = "public\css"')
        ->toContain('"VERSION" = "VERSION"')
        ->toContain('Instalar-EsteliPOS-Grafico.bat" = "Instalar-EsteliPOS-Grafico.bat')
        ->toContain('Sync-EsteliPOSPath')
        ->toContain('Find-EsteliPOSNewestUpdateZip')
        ->toContain('Resolve-EsteliPOSPackageRoot')
        ->toContain('EsteliPOSProduccion1.0.zip')
        ->toContain('Restaurando la version anterior')
        ->toContain('Actualizacion revertida')
        ->toContain('database.sqlite')
        ->toContain('PRAGMA wal_checkpoint(TRUNCATE)')
        ->toContain('PRAGMA quick_check')
        ->toContain('Assert-EsteliPOSDatabase')
        ->toContain('database.sqlite.restore')
        ->toContain('Version nueva:')
        ->toContain('app:migrate-production')
        ->toContain('Invoke-EsteliPOSPhpFile')
        ->toContain('Invoke-EsteliPOSWalCheckpoint')
        ->toContain('PRAGMA wal_checkpoint(TRUNCATE)')
        ->and(file_get_contents(base_path('VERSION')))
        ->toContain('1.0.14')
        ->and(file_exists(base_path('deployment/windows/Bootstrap-UpdateFromZip.ps1')))->toBeTrue()
        ->and(file_get_contents(base_path('deployment/windows/Actualizar-EsteliPOS.bat')))
        ->toContain('Bootstrap-UpdateFromZip.ps1')
        ->toContain('WIN_SCRIPTS')
        ->and(file_exists(base_path('Actualizar-EsteliPOS.bat')))->toBeTrue()
        ->and(file_exists(base_path('deployment/windows/Actualizar-EsteliPOS.bat')))->toBeTrue()
        ->and(file_get_contents(base_path('Actualizar-EsteliPOS.bat')))
        ->toContain('deployment\\windows\\Actualizar-EsteliPOS.bat')
        ->and(file_get_contents(base_path('deployment/windows/Actualizar-EsteliPOS.bat')))
        ->toContain('Update-EsteliPOS.ps1')
        ->toContain('sin perder datos')
        ->toContain('ACTUALIZADOR DEFINITIVO')
        ->toContain('produccion1.0.zip');

    expect(file_get_contents(base_path('deployment/INSTALAR.bat')))
        ->toContain('set "DAMAGED=1"')
        ->toContain('instalacion existente SIN una base de datos valida')
        ->toContain('INSTALADOR DE CONSOLA')
        ->not->toContain('Install-EsteliPOS-GUI.ps1')
        ->not->toContain('-STA')
        ->toContain("DestinationPath '!NEST_TMP!'")
        ->toContain("DestinationPath '!EXTRACT_TMP!'")
        ->not->toContain("DestinationPath '%NEST_TMP%'")
        ->not->toContain("DestinationPath '%EXTRACT_TMP%'");

    expect(strpos($updateScript, 'Stop-EsteliPOS.ps1'))
        ->toBeLessThan(strpos($updateScript, 'Copy-Item $DatabasePath $BackupDir'));

    expect($commonScript)
        ->toContain('Test-EsteliPOSPortBindable')
        ->toContain('[Net.Sockets.TcpListener]::new')
        ->toContain('Get-EsteliPOSAvailablePort')
        ->toContain('function Get-EsteliPOSLanInterface')
        ->toContain('function Set-EsteliPOSStaticIpv4')
        ->toContain('function Get-EsteliPOSNetworkSnapshot')
        ->toContain('function Set-EsteliPOSLanProfilePrivate')
        ->toContain('Identifying')
        ->toContain('function Get-EsteliPOSPrerequisiteReport')
        ->toContain('function Get-EsteliPOSBrowserPath')
        ->toContain('System.Collections.ArrayList')
        ->not->toContain('Items = @($items)')
        ->toContain('Dhcp Disabled');
});

test('the windows release archive is complete clean and verifiable', function () {
    $outerArchive = new ZipArchive;
    $outerPath = base_path('deployment/parche1.0.zip');

    expect($outerArchive->open($outerPath))->toBeTrue();

    $innerName = 'EsteliPOSProduccion1.0.zip';
    $checksumName = $innerName.'.sha256';
    $easyInstaller = $outerArchive->getFromName('INSTALAR.bat');
    $consoleInstaller = $outerArchive->getFromName('INSTALAR-CONSOLA.bat');
    $checksumContents = $outerArchive->getFromName($checksumName);
    $temporaryDirectory = sys_get_temp_dir().'/estelipos-release-'.bin2hex(random_bytes(6));
    mkdir($temporaryDirectory, 0700, true);
    expect($outerArchive->extractTo($temporaryDirectory, [$innerName]))->toBeTrue();
    $outerArchive->close();

    $temporaryInnerPath = $temporaryDirectory.'/'.$innerName;

    expect($easyInstaller)->not->toBeFalse()
        ->and((string) $easyInstaller)->toContain('INSTALADOR DE CONSOLA A PRUEBA DE ERRORES')
        ->and((string) $easyInstaller)->not->toContain('Install-EsteliPOS-GUI.ps1')
        ->and((string) $easyInstaller)->not->toContain('-STA')
        ->and($consoleInstaller)->toBe($easyInstaller)
        ->and(file_exists($temporaryInnerPath))->toBeTrue()
        ->and($checksumContents)->not->toBeFalse()
        ->and(trim((string) $checksumContents))->toMatch('/^[a-f0-9]{64}\s+EsteliPOSProduccion1\.0\.zip$/');

    $expectedHash = strtok(trim((string) $checksumContents), " \t");
    expect(hash_file('sha256', $temporaryInnerPath))->toBe($expectedHash);

    $innerArchive = new ZipArchive;
    expect($innerArchive->open($temporaryInnerPath))->toBeTrue();

    $entries = [];
    for ($index = 0; $index < $innerArchive->numFiles; $index++) {
        $entries[] = $innerArchive->getNameIndex($index);
    }
    $innerArchive->close();
    unlink($temporaryInnerPath);
    rmdir($temporaryDirectory);

    expect($entries)
        ->toContain('EsteliPOS/Instalar-EsteliPOS.bat')
        ->toContain('EsteliPOS/Actualizar-EsteliPOS.bat')
        ->toContain('EsteliPOS/deployment/windows/Actualizar-EsteliPOS.bat')
        ->toContain('EsteliPOS/deployment/windows/Update-EsteliPOS.ps1')
        ->toContain('EsteliPOS/vendor/autoload.php')
        ->toContain('EsteliPOS/public/build/manifest.json')
        ->toContain('EsteliPOS/public/css/app-ui.css')
        ->toContain('EsteliPOS/deployment/windows/assets/php-ts.zip')
        ->toContain('EsteliPOS/deployment/windows/assets/rewrite_amd64_en-US.msi')
        ->toContain('EsteliPOS/deployment/windows/assets/vc_redist.x64.exe')
        ->not->toContain('EsteliPOS/Instalar-EsteliPOS-Grafico.bat')
        ->not->toContain('EsteliPOS/deployment/windows/Install-EsteliPOS-GUI.ps1')
        ->and(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'EsteliPOS/deployment/windows/installer/')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_ends_with($entry, '.sqlite')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_contains(strtolower($entry), 'mysql-8.4')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => preg_match('#^EsteliPOS/bootstrap/cache/(config|events|routes-[^/]+)\.php$#', $entry) === 1))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'EsteliPOS/tests/')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'EsteliPOS/deployment/client-inventory/')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => $entry === 'EsteliPOS/scripts/extract-client-inventory.py'))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'EsteliPOS/database/seeders/assets/')))->toBeTrue()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'EsteliPOS/storage/app/') && ! str_ends_with($entry, '/')))->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => $entry === 'EsteliPOS/.env'))->toBeFalse();
})->skip(
    ! file_exists(dirname(__DIR__, 2).'/deployment/parche1.0.zip'),
    'El release completo se valida después de ejecutar deployment/build-release.sh.'
);

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

test('ticket printing is optional, manual and high contrast for thermal printers', function () {
    $changeView = file_get_contents(resource_path('views/facturacion/change.blade.php'));
    $receiptView = file_get_contents(resource_path('views/facturacion/receipt.blade.php'));

    expect($changeView)
        ->toContain('Imprimir ticket')
        ->not->toContain('autoprint=1')
        ->and($receiptView)
        ->toContain('onclick="window.print()"')
        ->toContain('font-weight: 700 !important')
        ->toContain('color: #000 !important')
        ->toContain('border-top: 2px dashed #000')
        ->not->toContain("request()->boolean('autoprint')");
});

test('sqlite is tuned for light lan concurrency', function () {
    expect(config('database.connections.sqlite.busy_timeout'))->toBe(5000)
        ->and(config('database.connections.sqlite.journal_mode'))->toBe('WAL')
        ->and(config('database.connections.sqlite.synchronous'))->toBe('NORMAL')
        ->and(config('database.connections.sqlite.transaction_mode'))->toBe('IMMEDIATE');
});
