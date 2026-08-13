[CmdletBinding()]
param(
    [string]$ProjectRoot = "C:\Northlink\EsteliPOS"
)

$ErrorActionPreference = "Stop"
$PatchName = "ticket-patch-3.0"
$PayloadReceipt = Join-Path $PSScriptRoot "payload\receipt.blade.php"
$ManifestPath = Join-Path $PSScriptRoot "SHA256SUMS.txt"
$ProjectRoot = [System.IO.Path]::GetFullPath($ProjectRoot)
$DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
$ReceiptPath = Join-Path $ProjectRoot "resources\views\facturacion\receipt.blade.php"
$StopScript = Join-Path $ProjectRoot "deployment\windows\Stop-EsteliPOS.ps1"
$StartScript = Join-Path $ProjectRoot "deployment\windows\Start-EsteliPOS.ps1"
$BackupDirectory = Join-Path $ProjectRoot ("backups\{0}-{1}" -f $PatchName, (Get-Date -Format "yyyyMMdd_HHmmss"))
$BackupReceipt = Join-Path $BackupDirectory "receipt.blade.php"
$TemporaryReceipt = "$ReceiptPath.patch3.tmp"
$ServerStopped = $false
$StartFailed = $false
$ReceiptReplaced = $false

function Assert-File([string]$Path, [string]$Label) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "$Label no existe: $Path"
    }
}

function Assert-Database([string]$Path, [string]$PhpPath) {
    Assert-File -Path $Path -Label "La base de datos"
    if ((Get-Item -LiteralPath $Path).Length -le 1024) {
        throw "La base de datos esta vacia o incompleta. El parche fue cancelado sin modificar archivos."
    }

    $CheckScript = '$path = $argv[1]; $pdo = new PDO(''sqlite:''.$path); echo $pdo->query(''PRAGMA quick_check;'')->fetchColumn();'
    $CheckResult = & $PhpPath -r $CheckScript $Path
    if ($LASTEXITCODE -ne 0 -or ($CheckResult -join "").Trim() -ne "ok") {
        throw "La base de datos no paso la comprobacion de integridad. El parche fue cancelado."
    }
}

Assert-File -Path (Join-Path $ProjectRoot "artisan") -Label "La instalacion de EsteliPOS"
Assert-File -Path $PayloadReceipt -Label "El ticket incluido en el parche"
Assert-File -Path $ManifestPath -Label "El manifiesto SHA-256"
Assert-File -Path $ReceiptPath -Label "El ticket instalado"
Assert-File -Path $StopScript -Label "El script para detener EsteliPOS"
Assert-File -Path $StartScript -Label "El script para iniciar EsteliPOS"

$PhpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
if (-not $PhpCommand) {
    throw "php.exe no esta disponible en PATH. El parche no realizo cambios."
}
$PhpPath = $PhpCommand.Source

$ManifestLine = Get-Content -LiteralPath $ManifestPath |
    Where-Object { $_ -match "\s+payload/receipt\.blade\.php$" } |
    Select-Object -First 1
if (-not $ManifestLine) {
    throw "SHA256SUMS.txt no contiene el hash del ticket."
}
$ExpectedPayloadHash = (($ManifestLine -split "\s+")[0]).ToUpperInvariant()
$ActualPayloadHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $PayloadReceipt).Hash
if ($ActualPayloadHash -ne $ExpectedPayloadHash) {
    throw "El ticket del parche no coincide con su SHA-256. Descargue nuevamente parcheticket.zip."
}

try {
    Write-Host "Deteniendo EsteliPOS..." -ForegroundColor Cyan
    & $StopScript -ServerProfile Auto
    if (-not $?) {
        throw "No se pudo detener EsteliPOS de forma segura."
    }
    $ServerStopped = $true

    Assert-Database -Path $DatabasePath -PhpPath $PhpPath
    $DatabaseHashBefore = (Get-FileHash -Algorithm SHA256 -LiteralPath $DatabasePath).Hash
    Write-Host "Base SQLite verificada. No sera copiada, movida ni eliminada." -ForegroundColor Green

    New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null
    Copy-Item -LiteralPath $ReceiptPath -Destination $BackupReceipt -Force

    Copy-Item -LiteralPath $PayloadReceipt -Destination $TemporaryReceipt -Force
    if ((Get-FileHash -Algorithm SHA256 -LiteralPath $TemporaryReceipt).Hash -ne $ExpectedPayloadHash) {
        throw "La copia temporal del ticket no coincide con el parche."
    }

    Copy-Item -LiteralPath $TemporaryReceipt -Destination $ReceiptPath -Force
    Remove-Item -LiteralPath $TemporaryReceipt -Force
    $ReceiptReplaced = $true

    Set-Location $ProjectRoot
    & $PhpPath artisan view:clear
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo limpiar la cache de vistas."
    }

    if ((Get-FileHash -Algorithm SHA256 -LiteralPath $ReceiptPath).Hash -ne $ExpectedPayloadHash) {
        throw "El ticket instalado no coincide con el parche."
    }

    $DatabaseHashAfter = (Get-FileHash -Algorithm SHA256 -LiteralPath $DatabasePath).Hash
    if ($DatabaseHashAfter -ne $DatabaseHashBefore) {
        throw "La base cambio durante el parche. El ticket sera revertido; la base no sera reemplazada."
    }

    Write-Host "Ticket termico actualizado." -ForegroundColor Green
    Write-Host "Respaldo del ticket anterior: $BackupReceipt"
    Write-Host "Base de datos intacta: $DatabasePath" -ForegroundColor Green
} catch {
    $Failure = $_.Exception.Message
    if ($ReceiptReplaced -and (Test-Path -LiteralPath $BackupReceipt)) {
        Copy-Item -LiteralPath $BackupReceipt -Destination $ReceiptPath -Force
        Set-Location $ProjectRoot
        & $PhpPath artisan view:clear | Out-Null
    }
    Remove-Item -LiteralPath $TemporaryReceipt -Force -ErrorAction SilentlyContinue
    throw "Parche 3.0 cancelado/revertido: $Failure"
} finally {
    if ($ServerStopped) {
        Write-Host "Iniciando EsteliPOS..." -ForegroundColor Cyan
        & $StartScript
        if (-not $?) {
            $StartFailed = $true
        }
    }
}

if ($StartFailed) {
    throw "El ticket fue actualizado, pero EsteliPOS no pudo reiniciarse. Ejecute Start-EsteliPOS.ps1."
}

Write-Host "PARCHE 3.0 COMPLETADO. La base de datos NO fue modificada." -ForegroundColor Green
