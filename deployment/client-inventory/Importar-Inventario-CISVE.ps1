[CmdletBinding()]
param(
    [string]$ProjectRoot = "",
    [string]$InventoryCsv = (Join-Path $PSScriptRoot "inventario-cisve-4094.csv")
)

$ErrorActionPreference = "Stop"
$ExpectedProducts = 4094
$Succeeded = $false
$ServerStopped = $false
$TranscriptStarted = $false
$BackupPath = ""
$LogPath = ""
$ExitCode = 1

function Write-Step([string]$Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Resolve-ProjectRoot([string]$PreferredPath) {
    $Candidates = New-Object System.Collections.Generic.List[string]
    if (-not [string]::IsNullOrWhiteSpace($PreferredPath)) {
        $Candidates.Add($PreferredPath)
    }
    $Candidates.Add("C:\Northlink\EsteliPOS")
    $Candidates.Add("C:\EsteliPOS")

    foreach ($Candidate in $Candidates) {
        if ((Test-Path -LiteralPath (Join-Path $Candidate "artisan")) -and
            (Test-Path -LiteralPath (Join-Path $Candidate "database\database.sqlite"))) {
            return (Resolve-Path -LiteralPath $Candidate).Path
        }
    }

    throw "No se encontro EsteliPOS. Use -ProjectRoot para indicar la carpeta instalada."
}

function Invoke-SqliteScalar([string]$Sql) {
    $Result = & $script:PhpPath $script:VerifierPath $script:DatabasePath scalar $Sql
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo verificar SQLite con la consulta: $Sql"
    }
    return [string]$Result
}

function Invoke-SqliteCheckpoint {
    & $script:PhpPath $script:VerifierPath $script:DatabasePath checkpoint
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo ejecutar el checkpoint WAL de SQLite."
    }
}

try {
    $Identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $Principal = New-Object Security.Principal.WindowsPrincipal($Identity)
    if (-not $Principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw "Ejecute CARGAR-INVENTARIO.bat como administrador."
    }

    $ProjectRoot = Resolve-ProjectRoot $ProjectRoot
    $DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
    $WindowsScripts = Join-Path $ProjectRoot "deployment\windows"
    $CommonScript = Join-Path $WindowsScripts "EsteliPOS-Common.ps1"
    $StopScript = Join-Path $WindowsScripts "Stop-EsteliPOS.ps1"
    $StartScript = Join-Path $WindowsScripts "Start-EsteliPOS.ps1"
    $ImportCommand = Join-Path $ProjectRoot "app\Console\Commands\ImportClientInventoryCommand.php"
    $VerifierPath = Join-Path $PSScriptRoot "Verificar-Inventario-CISVE.php"

    foreach ($RequiredPath in @($DatabasePath, $CommonScript, $StopScript, $StartScript, $ImportCommand, $VerifierPath, $InventoryCsv)) {
        if (-not (Test-Path -LiteralPath $RequiredPath)) {
            throw "Falta un archivo requerido: $RequiredPath"
        }
    }

    $InventoryCsv = (Resolve-Path -LiteralPath $InventoryCsv).Path
    $ChecksumPath = "$InventoryCsv.sha256"
    if (-not (Test-Path -LiteralPath $ChecksumPath)) {
        throw "Falta el checksum del inventario: $ChecksumPath"
    }

    Write-Step "Validando el archivo de 4,094 productos"
    $ExpectedHash = ((Get-Content -LiteralPath $ChecksumPath -Raw).Trim() -split '\s+')[0].ToLowerInvariant()
    $ActualHash = (Get-FileHash -LiteralPath $InventoryCsv -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($ExpectedHash -ne $ActualHash) {
        throw "El CSV no supera la verificacion SHA-256. No se modifico la base."
    }

    $Rows = @(Import-Csv -LiteralPath $InventoryCsv)
    $UniqueCodes = @($Rows | Select-Object -ExpandProperty code -Unique).Count
    if ($Rows.Count -ne $ExpectedProducts -or $UniqueCodes -ne $ExpectedProducts) {
        throw "El archivo contiene $($Rows.Count) filas y $UniqueCodes codigos unicos; se esperaban $ExpectedProducts."
    }
    if (@($Rows | Where-Object { [string]::IsNullOrWhiteSpace($_.code) -or [string]::IsNullOrWhiteSpace($_.name) -or [string]::IsNullOrWhiteSpace($_.category) }).Count -gt 0) {
        throw "El inventario contiene codigos, nombres o categorias vacios."
    }
    Write-Host "[OK] CSV verificado: $ExpectedProducts productos unicos."

    . $CommonScript
    Set-EsteliPOSProjectRootOverride $ProjectRoot
    $PhpPath = Resolve-EsteliPOSPhpExecutable

    $VersionPath = Join-Path $ProjectRoot "VERSION"
    $Version = if (Test-Path -LiteralPath $VersionPath) { (Get-Content -LiteralPath $VersionPath -Raw).Trim() } else { "desconocida" }
    Write-Host "Instalacion: $ProjectRoot"
    Write-Host "Version: $Version"
    Write-Host "PHP: $PhpPath"

    $LogDirectory = Join-Path $ProjectRoot "storage\logs"
    $BackupDirectory = Join-Path $ProjectRoot "storage\app\backups"
    New-Item -ItemType Directory -Force -Path $LogDirectory | Out-Null
    New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null
    $Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $LogPath = Join-Path $LogDirectory "import-inventory-$Timestamp.log"
    $BackupPath = Join-Path $BackupDirectory "pre-inventory-$Timestamp.sqlite"
    Start-Transcript -Path $LogPath -Force | Out-Null
    $TranscriptStarted = $true

    $EnvLine = Get-Content -LiteralPath (Join-Path $ProjectRoot ".env") | Where-Object { $_ -match '^DB_CONNECTION=' } | Select-Object -First 1
    if (-not $EnvLine -or (($EnvLine -split '=', 2)[1].Trim().ToLowerInvariant() -ne "sqlite")) {
        throw "La instalacion no esta configurada con SQLite. No se modifico la base."
    }

    Write-Step "Deteniendo EsteliPOS para obtener un respaldo consistente"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StopScript -ServerProfile Auto
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo detener EsteliPOS de forma segura."
    }
    $ServerStopped = $true

    Write-Step "Creando y verificando el respaldo"
    Invoke-SqliteCheckpoint
    Copy-Item -LiteralPath $DatabasePath -Destination $BackupPath -Force
    $OriginalHash = (Get-FileHash -LiteralPath $DatabasePath -Algorithm SHA256).Hash
    $BackupHash = (Get-FileHash -LiteralPath $BackupPath -Algorithm SHA256).Hash
    if ($OriginalHash -ne $BackupHash -or (Get-Item -LiteralPath $BackupPath).Length -le 0) {
        throw "El respaldo SQLite no pudo verificarse. No se inicio la limpieza."
    }
    $UsersBefore = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM users")
    if ($UsersBefore -lt 1) {
        throw "La base no contiene usuarios para conservar."
    }
    Write-Host "[OK] Respaldo: $BackupPath"
    Write-Host "[OK] SHA-256: $BackupHash"

    Write-Step "Borrando datos demo y cargando el inventario real"
    Push-Location $ProjectRoot
    try {
        & $PhpPath artisan app:import-client-inventory $InventoryCsv --replace-demo --force
        if ($LASTEXITCODE -ne 0) {
            throw "El importador devolvio codigo $LASTEXITCODE."
        }
    } finally {
        Pop-Location
    }

    Write-Step "Verificando integridad de la nueva base"
    $Products = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM products")
    $UniqueProducts = [int](Invoke-SqliteScalar "SELECT COUNT(DISTINCT code) FROM products")
    $PriceItems = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM price_list_items")
    $UsersAfter = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM users")
    $Sales = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM sales")
    $Purchases = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM purchases")
    $ForeignKeyErrors = [int](Invoke-SqliteScalar "SELECT COUNT(*) FROM pragma_foreign_key_check")

    if ($Products -ne $ExpectedProducts -or $UniqueProducts -ne $ExpectedProducts -or $PriceItems -ne $ExpectedProducts) {
        throw "Conteos incorrectos: productos=$Products, unicos=$UniqueProducts, precios=$PriceItems."
    }
    if ($UsersAfter -ne $UsersBefore -or $Sales -ne 0 -or $Purchases -ne 0 -or $ForeignKeyErrors -ne 0) {
        throw "Integridad incorrecta: usuarios=$UsersAfter/$UsersBefore, ventas=$Sales, compras=$Purchases, FK=$ForeignKeyErrors."
    }

    Write-Host "[OK] Productos: $Products"
    Write-Host "[OK] Usuarios conservados: $UsersAfter"
    Write-Host "[OK] Ventas y compras demo eliminadas."
    Write-Host "[OK] Claves foraneas sin errores."
    $Succeeded = $true
    $ExitCode = 0
} catch {
    Write-Host "`n[ERROR] $($_.Exception.Message)" -ForegroundColor Red
    if (-not [string]::IsNullOrWhiteSpace($BackupPath) -and (Test-Path -LiteralPath $BackupPath)) {
        try {
            Write-Host "Restaurando automaticamente el respaldo..." -ForegroundColor Yellow
            Remove-Item -LiteralPath "$DatabasePath-wal" -Force -ErrorAction SilentlyContinue
            Remove-Item -LiteralPath "$DatabasePath-shm" -Force -ErrorAction SilentlyContinue
            Copy-Item -LiteralPath $BackupPath -Destination $DatabasePath -Force
            Write-Host "[OK] Base anterior restaurada: $BackupPath" -ForegroundColor Green
        } catch {
            Write-Host "[CRITICO] No se pudo restaurar automaticamente: $($_.Exception.Message)" -ForegroundColor Red
            Write-Host "Respaldo disponible en: $BackupPath" -ForegroundColor Yellow
        }
    }
    $ExitCode = 1
} finally {
    if ($ServerStopped -and (Test-Path -LiteralPath $StartScript)) {
        Write-Step "Iniciando EsteliPOS"
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -ServerProfile Auto
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[ERROR] La base quedo protegida, pero EsteliPOS no pudo iniciar." -ForegroundColor Red
            $ExitCode = 1
        }
    }

    if ($TranscriptStarted) {
        Stop-Transcript | Out-Null
    }
}

if ($Succeeded -and $ExitCode -eq 0) {
    Write-Host "`nCARGA COMPLETADA: EsteliPOS esta listo con 4,094 productos." -ForegroundColor Green
    Write-Host "Respaldo anterior: $BackupPath"
    Write-Host "Log: $LogPath"
}

exit $ExitCode
