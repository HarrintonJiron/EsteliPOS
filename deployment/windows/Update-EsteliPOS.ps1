[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$UpdateZip,
    [int]$Port = 0
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
if ($Port -le 0 -and $DeploymentConfig) {
    $Port = [int]$DeploymentConfig.port
}
if ($Port -le 0) {
    $Port = 8080
}

$ServerProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile "Auto"
$BackupDir = Join-Path $ProjectRoot "backups\$(Get-Date -Format 'yyyyMMdd_HHmmss')"
$PhpPath = (Get-Command php.exe -ErrorAction Stop).Source

function Write-Step([string]$Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

if (-not (Test-Path $UpdateZip)) {
    throw "No se encontro el ZIP de actualizacion: $UpdateZip"
}

Write-Step "Creando respaldo antes de actualizar"
New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null
if (Test-Path (Join-Path $ProjectRoot ".env")) {
    Copy-Item (Join-Path $ProjectRoot ".env") $BackupDir
}
if (Test-Path (Join-Path $ProjectRoot "database\database.sqlite")) {
    Copy-Item (Join-Path $ProjectRoot "database\database.sqlite") $BackupDir
}
if (Test-Path (Join-Path $ProjectRoot "storage\app\deployment.json")) {
    Copy-Item (Join-Path $ProjectRoot "storage\app\deployment.json") $BackupDir
}
Write-Host "Respaldo en: $BackupDir"

Write-Step "Deteniendo EsteliPOS"
& (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1") -ServerProfile $ServerProfile

Write-Step "Extrayendo actualizacion"
$TempDir = Join-Path $env:TEMP ("estelipos-update-" + [guid]::NewGuid().ToString())
New-Item -ItemType Directory -Force -Path $TempDir | Out-Null
Expand-Archive -Path $UpdateZip -DestinationPath $TempDir -Force
$SourceRoot = Join-Path $TempDir "EsteliPOS"
if (-not (Test-Path $SourceRoot)) {
    Remove-Item $TempDir -Recurse -Force
    throw "El ZIP no contiene la carpeta EsteliPOS en la raiz."
}

$CopyMap = @{
    "app" = "app"
    "bootstrap" = "bootstrap"
    "config" = "config"
    "database\migrations" = "database\migrations"
    "public\build" = "public\build"
    "public\web.config" = "public\web.config"
    "resources\views" = "resources\views"
    "routes" = "routes"
    "deployment\windows" = "deployment\windows"
}

foreach ($Pair in $CopyMap.GetEnumerator()) {
    $Source = Join-Path $SourceRoot $Pair.Key
    $Destination = Join-Path $ProjectRoot $Pair.Value
    if (-not (Test-Path $Source)) {
        continue
    }
    $DestinationParent = Split-Path $Destination -Parent
    New-Item -ItemType Directory -Force -Path $DestinationParent | Out-Null
    Copy-Item $Source $Destination -Recurse -Force
    Write-Host "Actualizado: $($Pair.Value)"
}

Remove-Item $TempDir -Recurse -Force

Write-Step "Migraciones y optimizacion"
Set-Location $ProjectRoot
& $PhpPath artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw "Las migraciones fallaron." }

& $PhpPath artisan optimize:clear
& $PhpPath artisan optimize
if ($LASTEXITCODE -ne 0) { throw "No se pudo optimizar la aplicacion." }

Write-Step "Reiniciando EsteliPOS"
if ($ServerProfile -eq "IIS") {
    & (Join-Path $PSScriptRoot "Start-EsteliPOS.ps1") -Port $Port -ServerProfile IIS
} else {
    & (Join-Path $PSScriptRoot "Start-EsteliPOS.ps1") -Port $Port -ServerProfile Simple -HostAddress "0.0.0.0"
}

Write-Step "Verificando instalacion"
& (Join-Path $PSScriptRoot "Test-EsteliPOSInstallation.ps1") -Port $Port -ServerProfile $ServerProfile
if ($LASTEXITCODE -ne 0) {
    throw "La verificacion post-actualizacion fallo. Restaure desde $BackupDir"
}

Write-Host "`nACTUALIZACION COMPLETADA" -ForegroundColor Green
Write-Host "Respaldo: $BackupDir"
