[CmdletBinding()]
param([int]$Port = 0)

. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
if ($Port -le 0 -and $DeploymentConfig) {
    $Port = [int]$DeploymentConfig.port
}
if ($Port -le 0) {
    $Port = 8080
}

$Php = Get-Command php.exe -ErrorAction SilentlyContinue
$LanAddress = Get-EsteliPOSLanAddress
$MacAddress = Get-EsteliPOSMacAddress
$NetworkPage = Join-Path $ProjectRoot "storage\app\deployment\acceso-red.html"

Write-Host "Diagnostico EsteliPOS" -ForegroundColor Cyan
Write-Host "Ruta: $ProjectRoot"
Write-Host "PHP: $($Php.Source)"
if ($Php) { & $Php.Source -v }
Write-Host "Base SQLite: $(Test-Path (Join-Path $ProjectRoot 'database\database.sqlite'))"
Write-Host "Configuracion: $(Test-Path (Join-Path $ProjectRoot '.env'))"
Write-Host "Dependencias: $(Test-Path (Join-Path $ProjectRoot 'vendor\autoload.php'))"
Write-Host "Frontend: $(Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot)"
Write-Host "IP actual detectada: $LanAddress"
Write-Host "MAC detectada: $MacAddress"

if ($DeploymentConfig) {
    Write-Host "URL instalada: $($DeploymentConfig.app_url)" -ForegroundColor Yellow
    if ($DeploymentConfig.lan_address -and $LanAddress -and $DeploymentConfig.lan_address -ne $LanAddress) {
        Write-Host "ADVERTENCIA: la IP cambio desde la instalacion. Actualiza tablets o reserva DHCP en el router." -ForegroundColor Red
    }
}

if (Test-Path $NetworkPage) {
    Write-Host "Hoja de acceso en red: $NetworkPage"
}

try {
    $Response = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/login" -UseBasicParsing -TimeoutSec 5
    Write-Host "Servidor HTTP: OK ($($Response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "Servidor HTTP: ERROR - $($_.Exception.Message)" -ForegroundColor Red
}

if ($LanAddress) {
    try {
        $LanResponse = Invoke-WebRequest -Uri "http://${LanAddress}:$Port/login" -UseBasicParsing -TimeoutSec 5
        Write-Host "Acceso LAN: OK ($($LanResponse.StatusCode)) en http://${LanAddress}:$Port" -ForegroundColor Green
    } catch {
        Write-Host "Acceso LAN: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
}

if ($Php) {
    Set-Location $ProjectRoot
    & $Php.Source artisan about
}

Read-Host "Presiona Enter para cerrar"
