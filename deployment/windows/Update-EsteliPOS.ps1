[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)][string]$UpdateZip = "",
    [int]$Port = 0
)

$ErrorActionPreference = "Stop"

$CommonScript = Join-Path $PSScriptRoot "EsteliPOS-Common.ps1"
if (-not (Test-Path -LiteralPath $CommonScript)) {
    $fallbackCommon = Join-Path (Get-Location) "deployment\windows\EsteliPOS-Common.ps1"
    if (Test-Path -LiteralPath $fallbackCommon) {
        $CommonScript = $fallbackCommon
    }
}
if (-not (Test-Path -LiteralPath $CommonScript)) {
    throw @"
No se encontro EsteliPOS-Common.ps1.

Copie estos archivos DENTRO de la instalacion existente:
  C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat
  C:\Northlink\EsteliPOS\deployment\windows\Actualizar-EsteliPOS.bat
  C:\Northlink\EsteliPOS\deployment\windows\Update-EsteliPOS.ps1
  (EsteliPOS-Common.ps1 ya debe existir en deployment\windows)

No coloque Update-EsteliPOS.ps1 en la raiz de EsteliPOS.
Luego ejecute: C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat C:\Northlink\parche1.0.zip
"@
}

. $CommonScript

$ProjectRoot = Get-EsteliPOSProjectRoot
$WindowsScriptsDir = Get-EsteliPOSWindowsScriptsDir
Set-Location $ProjectRoot
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
if ($Port -le 0 -and $DeploymentConfig) {
    $Port = [int]$DeploymentConfig.port
}
if ($Port -le 0) {
    $Port = 8080
}

$ServerProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile "Auto"
$BackupDir = Join-Path $ProjectRoot "backups\$(Get-Date -Format 'yyyyMMdd_HHmmss')"
$FilesBackupDir = Join-Path $BackupDir "files"
$PhpPath = (Get-Command php.exe -ErrorAction Stop).Source
$CopyMap = [ordered]@{}
$CreatedDestinations = New-Object System.Collections.Generic.List[string]
$TempDir = $null
$NestedTempDir = $null

$CurrentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
$CurrentPrincipal = New-Object Security.Principal.WindowsPrincipal($CurrentIdentity)
if (-not $CurrentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw "Ejecute la actualizacion como administrador."
}

function Write-Step([string]$Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Assert-EsteliPOSDatabase([string]$Path, [string]$Label) {
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "$Label no existe: $Path"
    }

    $DatabaseFile = Get-Item -LiteralPath $Path
    if ($DatabaseFile.Length -le 1024) {
        throw "$Label esta vacia o incompleta: $Path"
    }

    $CheckScript = '$path = $argv[1]; $pdo = new PDO(''sqlite:''.$path); echo $pdo->query(''PRAGMA quick_check;'')->fetchColumn();'
    $CheckResult = & $PhpPath -r $CheckScript $DatabaseFile.FullName
    if ($LASTEXITCODE -ne 0 -or ($CheckResult -join '').Trim() -ne "ok") {
        throw "$Label no paso PRAGMA quick_check: $Path"
    }
}

function Find-EsteliPOSNewestUpdateZip([string]$ProjectRootPath) {
    $searchRoots = @(
        $ProjectRootPath,
        (Join-Path $ProjectRootPath "deployment"),
        (Split-Path -Parent $ProjectRootPath),
        "C:\Northlink",
        (Get-Location).Path
    ) | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -Unique

    $patterns = @(
        "parche1.0.zip",
        "parche*.zip",
        "produccion1.0.zip",
        "EsteliPOSProduccion1.0.zip",
        "produccion*.zip",
        "EsteliPOSProduccion*.zip"
    )

    $candidates = @()
    foreach ($root in $searchRoots) {
        foreach ($pattern in $patterns) {
            $candidates += Get-ChildItem -Path $root -Filter $pattern -File -ErrorAction SilentlyContinue
        }
    }

    $candidates = $candidates | Sort-Object FullName -Unique | Sort-Object LastWriteTime -Descending
    if (-not $candidates -or $candidates.Count -eq 0) {
        return $null
    }

    return $candidates[0]
}

function Resolve-EsteliPOSPackageRoot([string]$ZipPath) {
    $extractRoot = Join-Path $env:TEMP ("estelipos-update-" + [guid]::NewGuid().ToString())
    New-Item -ItemType Directory -Force -Path $extractRoot | Out-Null
    Expand-Archive -Path $ZipPath -DestinationPath $extractRoot -Force

    $directRoot = Join-Path $extractRoot "EsteliPOS"
    if (Test-Path $directRoot) {
        return @{ TempDir = $extractRoot; SourceRoot = $directRoot; NestedTempDir = $null }
    }

    $innerCandidates = @(
        (Join-Path $extractRoot "EsteliPOSProduccion1.0.zip"),
        (Join-Path $extractRoot "EsteliPOSProduccion2.0.zip"),
        (Join-Path $extractRoot "EsteliPOSProduccion3.0.zip")
    ) + @(Get-ChildItem -Path $extractRoot -Filter "EsteliPOS*.zip" -File -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName)

    foreach ($innerZip in $innerCandidates | Select-Object -Unique) {
        if (-not (Test-Path $innerZip)) {
            continue
        }

        $nestedRoot = Join-Path $env:TEMP ("estelipos-update-inner-" + [guid]::NewGuid().ToString())
        New-Item -ItemType Directory -Force -Path $nestedRoot | Out-Null
        Expand-Archive -Path $innerZip -DestinationPath $nestedRoot -Force
        $nestedSource = Join-Path $nestedRoot "EsteliPOS"
        if (Test-Path $nestedSource) {
            return @{ TempDir = $extractRoot; SourceRoot = $nestedSource; NestedTempDir = $nestedRoot }
        }
    }

    throw "El ZIP no contiene la carpeta EsteliPOS. Use parche1.0.zip o EsteliPOSProduccion1.0.zip."
}

function Sync-EsteliPOSPath([string]$Source, [string]$Destination) {
    if (-not (Test-Path -LiteralPath $Source)) {
        return $false
    }

    if (Test-Path -LiteralPath $Destination) {
        Remove-Item -LiteralPath $Destination -Recurse -Force
    }

    $DestinationParent = Split-Path -Parent $Destination
    if ($DestinationParent -and -not (Test-Path -LiteralPath $DestinationParent)) {
        New-Item -ItemType Directory -Force -Path $DestinationParent | Out-Null
    }

    Copy-Item -LiteralPath $Source -Destination $Destination -Recurse -Force
    return $true
}

if ([string]::IsNullOrWhiteSpace($UpdateZip)) {
    $AutoZip = Find-EsteliPOSNewestUpdateZip -ProjectRootPath $ProjectRoot
    if (-not $AutoZip) {
        throw "No se encontro parche1.0.zip. Indique la ruta completa del ZIP."
    }
    $UpdateZip = $AutoZip.FullName
    Write-Host "Paquete auto-detectado (mas reciente): $UpdateZip" -ForegroundColor Yellow
    Write-Host ("Fecha del archivo: {0}" -f $AutoZip.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss")) -ForegroundColor Yellow
}

if (-not (Test-Path -LiteralPath $UpdateZip)) {
    throw "No se encontro el ZIP de actualizacion: $UpdateZip"
}

$UpdateZip = (Resolve-Path -LiteralPath $UpdateZip).Path
$UpdateZipInfo = Get-Item -LiteralPath $UpdateZip
$PreviousVersion = if (Test-Path (Join-Path $ProjectRoot "VERSION")) {
    (Get-Content (Join-Path $ProjectRoot "VERSION") -Raw).Trim()
} else {
    "(sin VERSION)"
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " ESTELIPOS - ACTUALIZADOR (sin perder datos)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " Instalacion: $ProjectRoot"
Write-Host " Version actual: $PreviousVersion"
Write-Host " Paquete:     $UpdateZip"
Write-Host (" Fecha ZIP:   {0}" -f $UpdateZipInfo.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss"))
Write-Host (" Tamano ZIP:  {0:N1} MB" -f ($UpdateZipInfo.Length / 1MB))
Write-Host " Se conservan: .env, database.sqlite, storage\app, uploads"
Write-Host "============================================================" -ForegroundColor Cyan

$DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
Assert-EsteliPOSDatabase -Path $DatabasePath -Label "La base de datos instalada"

try {
    Write-Step "Deteniendo EsteliPOS"
    & (Join-Path $WindowsScriptsDir "Stop-EsteliPOS.ps1") -ServerProfile $ServerProfile

    Write-Step "Creando respaldo consistente antes de actualizar"
    New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null
    Set-Location $ProjectRoot
    Assert-EsteliPOSDatabase -Path $DatabasePath -Label "La base de datos instalada"
    & $PhpPath -r '$pdo = new PDO("sqlite:database/database.sqlite"); $pdo->exec("PRAGMA wal_checkpoint(TRUNCATE);");'
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo consolidar SQLite antes del respaldo. La actualizacion fue cancelada sin modificar archivos."
    }
    if (Test-Path (Join-Path $ProjectRoot ".env")) {
        Copy-Item (Join-Path $ProjectRoot ".env") $BackupDir
    }
    if (Test-Path -LiteralPath $DatabasePath) {
        Copy-Item $DatabasePath $BackupDir
        Assert-EsteliPOSDatabase -Path (Join-Path $BackupDir "database.sqlite") -Label "El respaldo de la base de datos"
    }
    if (Test-Path (Join-Path $ProjectRoot "database\database.sqlite-wal")) {
        Copy-Item (Join-Path $ProjectRoot "database\database.sqlite-wal") $BackupDir -ErrorAction SilentlyContinue
    }
    if (Test-Path (Join-Path $ProjectRoot "database\database.sqlite-shm")) {
        Copy-Item (Join-Path $ProjectRoot "database\database.sqlite-shm") $BackupDir -ErrorAction SilentlyContinue
    }
    if (Test-Path (Join-Path $ProjectRoot "storage\app\deployment.json")) {
        Copy-Item (Join-Path $ProjectRoot "storage\app\deployment.json") $BackupDir
    }
    if (Test-Path (Join-Path $ProjectRoot "VERSION")) {
        Copy-Item (Join-Path $ProjectRoot "VERSION") $BackupDir
    }
    Write-Host "Respaldo consistente en: $BackupDir"

    Write-Step "Extrayendo actualizacion"
    $Resolved = Resolve-EsteliPOSPackageRoot -ZipPath $UpdateZip
    $TempDir = $Resolved.TempDir
    $NestedTempDir = $Resolved.NestedTempDir
    $SourceRoot = $Resolved.SourceRoot

    $PackageVersionPath = Join-Path $SourceRoot "VERSION"
    if (-not (Test-Path -LiteralPath $PackageVersionPath)) {
        throw "El paquete no incluye VERSION. Use el ZIP oficial generado con deployment/build-release.sh."
    }
    $PackageVersion = (Get-Content $PackageVersionPath -Raw).Trim()
    Write-Host "Version del paquete: $PackageVersion" -ForegroundColor Green

    if ($PackageVersion -eq $PreviousVersion) {
        Write-Warning "La instalacion ya reporta la misma VERSION ($PreviousVersion). Se reaplicaran archivos de todas formas."
    }

    $CopyMap = [ordered]@{
        "app" = "app"
        "bootstrap" = "bootstrap"
        "config" = "config"
        "database\migrations" = "database\migrations"
        "database\seeders" = "database\seeders"
        "lang" = "lang"
        "vendor" = "vendor"
        "public\build" = "public\build"
        "public\css" = "public\css"
        "public\js" = "public\js"
        "public\index.php" = "public\index.php"
        "public\web.config" = "public\web.config"
        "resources" = "resources"
        "routes" = "routes"
        "deployment\windows" = "deployment\windows"
        "artisan" = "artisan"
        "composer.json" = "composer.json"
        "composer.lock" = "composer.lock"
        "VERSION" = "VERSION"
        "Instalar-EsteliPOS.bat" = "Instalar-EsteliPOS.bat"
        "Actualizar-EsteliPOS.bat" = "Actualizar-EsteliPOS.bat"
    }

    Write-Step "Respaldando archivos que se actualizaran"
    foreach ($Pair in $CopyMap.GetEnumerator()) {
        $Destination = Join-Path $ProjectRoot $Pair.Value
        if (Test-Path -LiteralPath $Destination) {
            $BackupDestination = Join-Path $FilesBackupDir $Pair.Value
            New-Item -ItemType Directory -Force -Path (Split-Path $BackupDestination -Parent) | Out-Null
            Copy-Item -LiteralPath $Destination -Destination $BackupDestination -Recurse -Force
        } else {
            $CreatedDestinations.Add($Destination)
        }
    }

    Write-Step "Reemplazando archivos (no se mezclan carpetas viejas)"
    $UpdatedCount = 0
    foreach ($Pair in $CopyMap.GetEnumerator()) {
        $Source = Join-Path $SourceRoot $Pair.Key
        $Destination = Join-Path $ProjectRoot $Pair.Value
        if (Sync-EsteliPOSPath -Source $Source -Destination $Destination) {
            $UpdatedCount++
            Write-Host "Actualizado: $($Pair.Value)"
        } else {
            Write-Warning "No venia en el paquete (omitido): $($Pair.Key)"
        }
    }

    if ($UpdatedCount -lt 5) {
        throw "Se actualizaron muy pocos elementos ($UpdatedCount). Revise que el ZIP sea el paquete completo."
    }

    $InstalledVersionPath = Join-Path $ProjectRoot "VERSION"
    if (-not (Test-Path -LiteralPath $InstalledVersionPath)) {
        throw "VERSION no quedo instalado tras la copia."
    }
    $InstalledVersion = (Get-Content $InstalledVersionPath -Raw).Trim()
    if ($InstalledVersion -ne $PackageVersion) {
        throw "VERSION instalada ($InstalledVersion) no coincide con el paquete ($PackageVersion)."
    }

    $PosView = Join-Path $ProjectRoot "resources\views\facturacion\pos.blade.php"
    if (-not (Test-Path -LiteralPath $PosView)) {
        throw "Falta resources\views\facturacion\pos.blade.php despues de actualizar."
    }

    Write-Step "Migraciones y limpieza de cache (no borra ventas ni inventario)"
    Set-Location $ProjectRoot
    Assert-EsteliPOSDatabase -Path $DatabasePath -Label "La base de datos antes de migrar"
    & $PhpPath artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw "Las migraciones fallaron." }

    & $PhpPath artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw "No se pudo limpiar el cache." }
    & $PhpPath artisan view:clear
    & $PhpPath artisan cache:clear
    & $PhpPath artisan config:clear
    & $PhpPath artisan route:clear
    & $PhpPath artisan optimize
    if ($LASTEXITCODE -ne 0) { throw "No se pudo optimizar la aplicacion." }

    Write-Step "Reiniciando EsteliPOS"
    if ($ServerProfile -eq "IIS") {
        & (Join-Path $WindowsScriptsDir "Start-EsteliPOS.ps1") -Port $Port -ServerProfile IIS
        try {
            Import-Module WebAdministration -ErrorAction SilentlyContinue
            $AppPoolName = "EsteliPOS"
            if (Get-Command Restart-WebAppPool -ErrorAction SilentlyContinue) {
                if (Test-Path "IIS:\AppPools\$AppPoolName") {
                    Restart-WebAppPool -Name $AppPoolName
                    Write-Host "App pool IIS reiniciado: $AppPoolName"
                }
            }
        } catch {
            Write-Warning "No se pudo reiniciar el app pool IIS explicitamente (puede estar bien si Start-EsteliPOS ya lo hizo)."
        }
    } else {
        & (Join-Path $WindowsScriptsDir "Start-EsteliPOS.ps1") -Port $Port -ServerProfile Simple -HostAddress "0.0.0.0"
    }

    Write-Step "Verificando instalacion"
    & (Join-Path $WindowsScriptsDir "Test-EsteliPOSInstallation.ps1") -Port $Port -ServerProfile $ServerProfile
    if ($LASTEXITCODE -ne 0) {
        throw "La verificacion post-actualizacion fallo."
    }

    Write-Host "`nACTUALIZACION COMPLETADA" -ForegroundColor Green
    Write-Host "Version anterior: $PreviousVersion"
    Write-Host "Version nueva:    $InstalledVersion"
    Write-Host "Datos conservados: .env + database.sqlite + storage\app"
    Write-Host "Respaldo: $BackupDir"
    Write-Host "Si el navegador sigue igual: cierre pestañas, Ctrl+F5 o abra en ventana privada."
} catch {
    $FailureMessage = $_.Exception.Message
    Write-Warning "La actualizacion fallo. Restaurando la version anterior..."
    & (Join-Path $WindowsScriptsDir "Stop-EsteliPOS.ps1") -ServerProfile $ServerProfile

    foreach ($Pair in $CopyMap.GetEnumerator()) {
        $Destination = Join-Path $ProjectRoot $Pair.Value
        $BackupSource = Join-Path $FilesBackupDir $Pair.Value
        if (Test-Path -LiteralPath $BackupSource) {
            Remove-Item -LiteralPath $Destination -Recurse -Force -ErrorAction SilentlyContinue
            $DestinationParent = Split-Path -Parent $Destination
            if ($DestinationParent) {
                New-Item -ItemType Directory -Force -Path $DestinationParent | Out-Null
            }
            Copy-Item -LiteralPath $BackupSource -Destination $Destination -Recurse -Force
        }
    }
    foreach ($Destination in $CreatedDestinations) {
        Remove-Item -LiteralPath $Destination -Recurse -Force -ErrorAction SilentlyContinue
    }
    if (Test-Path (Join-Path $BackupDir ".env")) {
        Copy-Item (Join-Path $BackupDir ".env") (Join-Path $ProjectRoot ".env") -Force
    }
    $BackupDatabasePath = Join-Path $BackupDir "database.sqlite"
    $InstalledDatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
    $RestoreDatabasePath = Join-Path $ProjectRoot "database\database.sqlite.restore"
    if (-not (Test-Path -LiteralPath $BackupDatabasePath)) {
        throw "No se encontro la base respaldada; no se reemplazo la base instalada. Respaldo esperado: $BackupDatabasePath"
    }
    Assert-EsteliPOSDatabase -Path $BackupDatabasePath -Label "La base respaldada para rollback"
    Copy-Item -LiteralPath $BackupDatabasePath -Destination $RestoreDatabasePath -Force
    Assert-EsteliPOSDatabase -Path $RestoreDatabasePath -Label "La copia temporal de restauracion"
    Remove-Item (Join-Path $ProjectRoot "database\database.sqlite-wal") -Force -ErrorAction SilentlyContinue
    Remove-Item (Join-Path $ProjectRoot "database\database.sqlite-shm") -Force -ErrorAction SilentlyContinue
    Move-Item -LiteralPath $RestoreDatabasePath -Destination $InstalledDatabasePath -Force
    Assert-EsteliPOSDatabase -Path $InstalledDatabasePath -Label "La base restaurada"
    if (Test-Path (Join-Path $BackupDir "database.sqlite-wal")) {
        Copy-Item (Join-Path $BackupDir "database.sqlite-wal") (Join-Path $ProjectRoot "database\database.sqlite-wal") -Force
    }
    if (Test-Path (Join-Path $BackupDir "database.sqlite-shm")) {
        Copy-Item (Join-Path $BackupDir "database.sqlite-shm") (Join-Path $ProjectRoot "database\database.sqlite-shm") -Force
    }
    if (Test-Path (Join-Path $BackupDir "VERSION")) {
        Copy-Item (Join-Path $BackupDir "VERSION") (Join-Path $ProjectRoot "VERSION") -Force
    }

    try {
        & (Join-Path $WindowsScriptsDir "Start-EsteliPOS.ps1") -Port $Port -ServerProfile $ServerProfile
    } catch {
        Write-Warning "No se pudo reiniciar automaticamente despues del rollback."
    }

    throw "Actualizacion revertida: $FailureMessage. Respaldo: $BackupDir"
} finally {
    if ($NestedTempDir -and (Test-Path $NestedTempDir)) {
        Remove-Item $NestedTempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
    if ($TempDir -and (Test-Path $TempDir)) {
        Remove-Item $TempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}
