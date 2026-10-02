[CmdletBinding()]
param(
    [string]$InstallRoot,
    [int]$RetentionDays = 30
)

$ErrorActionPreference = 'Stop'
$stateRoot = Join-Path $env:ProgramData 'EsteliPOS'
$logRoot = Join-Path $stateRoot 'Logs'
$backupRoot = Join-Path $stateRoot 'backups\automatic'
$logPath = Join-Path $logRoot 'backup.log'
New-Item -ItemType Directory -Path $logRoot, $backupRoot -Force | Out-Null
$backupMutex = [Threading.Mutex]::new($false, 'Global\EsteliPOSBackup')
$backupLockAcquired = $false
$workRoot = $null
$clientFile = $null

function Write-BackupLog([string]$Message) {
    Add-Content -LiteralPath $logPath -Value ("{0} {1}" -f (Get-Date -Format 's'), $Message) -Encoding UTF8
}

function Get-EnvValue([string]$Path, [string]$Key) {
    $line = Get-Content -LiteralPath $Path | Where-Object { $_ -match "^$([regex]::Escape($Key))=" } | Select-Object -First 1
    if (-not $line) { throw "Falta $Key en $Path" }
    return ($line -split '=', 2)[1].Trim().Trim('"')
}

try {
    $backupLockAcquired = $backupMutex.WaitOne(0)
    if (-not $backupLockAcquired) {
        throw 'Ya existe otro respaldo de EsteliPOS en curso.'
    }
    if (-not $InstallRoot) {
        $statePath = Join-Path $stateRoot 'installation.json'
        if (-not (Test-Path -LiteralPath $statePath -PathType Leaf)) {
            throw 'No existe el registro de instalación.'
        }
        $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
        $InstallRoot = [string] $state.installRoot
    }

    $InstallRoot = [IO.Path]::GetFullPath($InstallRoot).TrimEnd('\')
    $appRoot = Join-Path $InstallRoot 'application'
    $envFile = Join-Path $appRoot '.env'
    $mysqlDump = Get-ChildItem -LiteralPath (Join-Path $InstallRoot 'mysql') -Filter 'mysqldump.exe' -Recurse -File | Select-Object -First 1
    if (-not $mysqlDump -or -not (Test-Path -LiteralPath $envFile -PathType Leaf)) {
        throw 'No se encontraron MySQL o el archivo .env de la instalación.'
    }

    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $workRoot = Join-Path $backupRoot ".work-$stamp"
    $archive = Join-Path $backupRoot "EsteliPOS-backup-$stamp.zip"
    New-Item -ItemType Directory -Path (Join-Path $workRoot 'database'), (Join-Path $workRoot 'application\storage\app') -Force | Out-Null

    $clientFile = Join-Path $workRoot 'mysql-client.ini'
    @"
[client]
host=$(Get-EnvValue $envFile 'DB_HOST')
port=$(Get-EnvValue $envFile 'DB_PORT')
user=$(Get-EnvValue $envFile 'DB_USERNAME')
password="$(Get-EnvValue $envFile 'DB_PASSWORD')"
"@ | Set-Content -LiteralPath $clientFile -Encoding ASCII

    $dumpPath = Join-Path $workRoot 'database\estelipos.sql'
    $process = Start-Process -FilePath $mysqlDump.FullName -ArgumentList "--defaults-extra-file=`"$clientFile`" --single-transaction --routines --events --triggers --result-file=`"$dumpPath`" estelipos" -Wait -PassThru -WindowStyle Hidden
    if ($process.ExitCode -ne 0 -or -not (Test-Path -LiteralPath $dumpPath) -or (Get-Item -LiteralPath $dumpPath).Length -lt 128) {
        throw "mysqldump no produjo un respaldo válido (código $($process.ExitCode))."
    }

    Copy-Item -LiteralPath $envFile -Destination (Join-Path $workRoot 'application\.env') -Force
    $storageApp = Join-Path $appRoot 'storage\app'
    if (Test-Path -LiteralPath $storageApp -PathType Container) {
        Get-ChildItem -LiteralPath $storageApp -Force | Copy-Item -Destination (Join-Path $workRoot 'application\storage\app') -Recurse -Force
    }
    Remove-Item -LiteralPath $clientFile -Force
    Compress-Archive -Path (Join-Path $workRoot '*') -DestinationPath $archive -CompressionLevel Optimal -Force
    Remove-Item -LiteralPath $workRoot -Recurse -Force

    if ((Get-Item -LiteralPath $archive).Length -lt 256) {
        throw 'El archivo final de respaldo está vacío o incompleto.'
    }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [IO.Compression.ZipFile]::OpenRead($archive)
    try {
        if (-not ($zip.Entries | Where-Object { $_.FullName -eq 'database/estelipos.sql' } | Select-Object -First 1)) {
            throw 'El respaldo comprimido no contiene database/estelipos.sql.'
        }
    } finally {
        $zip.Dispose()
    }
    Get-ChildItem -LiteralPath $backupRoot -Filter 'EsteliPOS-backup-*.zip' -File |
        Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } |
        Remove-Item -Force
    Write-BackupLog "[OK] $archive"
} catch {
    Write-BackupLog "[ERROR] $($_.Exception.Message)"
    throw
} finally {
    if ($clientFile) { Remove-Item -LiteralPath $clientFile -Force -ErrorAction SilentlyContinue }
    if ($workRoot -and (Test-Path -LiteralPath $workRoot -PathType Container)) {
        Remove-Item -LiteralPath $workRoot -Recurse -Force -ErrorAction SilentlyContinue
    }
    if ($backupLockAcquired) { $backupMutex.ReleaseMutex() }
    $backupMutex.Dispose()
}
