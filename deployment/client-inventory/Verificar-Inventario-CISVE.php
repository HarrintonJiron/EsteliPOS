<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Uso: php Verificar-Inventario-CISVE.php BASE.sqlite checkpoint|scalar [SQL]\n");
    exit(2);
}

$database = realpath($argv[1]);
$mode = $argv[2];

if ($database === false || ! is_file($database) || ! is_readable($database) || ! is_writable($database)) {
    fwrite(STDERR, "La base SQLite no existe o no tiene permisos de lectura y escritura.\n");
    exit(3);
}

try {
    $pdo = new PDO('sqlite:'.$database, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if ($mode === 'checkpoint') {
        $result = $pdo->query('PRAGMA wal_checkpoint(TRUNCATE)')->fetch();
        if ($result === false || (int) ($result['busy'] ?? 1) !== 0) {
            throw new RuntimeException('SQLite informo que la base continua ocupada.');
        }

        echo "[OK] WAL sincronizado.\n";
        exit(0);
    }

    if ($mode === 'scalar') {
        if ($argc < 4 || ! preg_match('/^\s*(SELECT|PRAGMA)\b/i', $argv[3])) {
            throw new InvalidArgumentException('La consulta de verificacion no es valida.');
        }

        $value = $pdo->query($argv[3])->fetchColumn();
        if ($value === false) {
            throw new RuntimeException('La consulta no devolvio ningun valor.');
        }

        echo (string) $value;
        exit(0);
    }

    throw new InvalidArgumentException('Modo de verificacion desconocido.');
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(4);
}
