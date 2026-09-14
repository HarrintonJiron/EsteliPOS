<?php

test('the client inventory package contains the validated 4094 product catalog', function () {
    $csvPath = base_path('deployment/client-inventory/inventario-cisve-4094.csv');
    $reportPath = base_path('deployment/client-inventory/inventario-cisve-4094.json');

    expect($csvPath)->toBeFile()
        ->and($reportPath)->toBeFile()
        ->and($csvPath.'.sha256')->toBeFile();

    $handle = fopen($csvPath, 'rb');
    expect($handle)->not->toBeFalse();

    $headers = fgetcsv($handle, escape: '');
    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
    $rows = [];
    while (($values = fgetcsv($handle, escape: '')) !== false) {
        $rows[] = array_combine($headers, $values);
    }
    fclose($handle);

    $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
    $expectedHash = str(file_get_contents($csvPath.'.sha256'))->before(' ')->trim()->toString();

    expect($rows)->toHaveCount(4094)
        ->and(array_unique(array_column($rows, 'code')))->toHaveCount(4094)
        ->and(array_unique(array_column($rows, 'category')))->toHaveCount(20)
        ->and(array_filter($rows, fn (array $row): bool => $row['requires_review'] === 'negative_stock'))->toHaveCount(2)
        ->and($report['pages'])->toBe(173)
        ->and($report['rows'])->toBe(4094)
        ->and($report['unique_codes'])->toBe(4094)
        ->and($expectedHash)->toBe(hash_file('sha256', $csvPath));
});

test('the windows inventory loader backs up restores and verifies sqlite before success', function () {
    $batch = file_get_contents(base_path('deployment/client-inventory/CARGAR-INVENTARIO.bat'));
    $loader = file_get_contents(base_path('deployment/client-inventory/Importar-Inventario-CISVE.ps1'));
    $verifier = base_path('deployment/client-inventory/Verificar-Inventario-CISVE.php');

    expect($batch)
        ->toContain('net session')
        ->toContain('Escriba CARGAR')
        ->toContain('Importar-Inventario-CISVE.ps1')
        ->and($loader)
        ->toContain('Get-FileHash')
        ->toContain('Invoke-SqliteCheckpoint')
        ->toContain('Verificar-Inventario-CISVE.php')
        ->not->toContain('$PhpPath -r')
        ->toContain('pre-inventory-$Timestamp.sqlite')
        ->toContain('app:import-client-inventory')
        ->toContain('--replace-demo')
        ->toContain('pragma_foreign_key_check')
        ->toContain('Copy-Item -LiteralPath $BackupPath -Destination $DatabasePath -Force')
        ->toContain('Usuarios conservados')
        ->toContain('Start-EsteliPOS.ps1')
        ->and($verifier)->toBeFile()
        ->and(file_get_contents($verifier))
        ->toContain('PRAGMA wal_checkpoint(TRUNCATE)')
        ->toContain("PDO('sqlite:'.\$database");
});
