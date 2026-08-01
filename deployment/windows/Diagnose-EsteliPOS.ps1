[CmdletBinding()]
param([int]$Port = 8080)

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$Php = Get-Command php.exe -ErrorAction SilentlyContinue

Write-Host "Diagnostico EsteliPOS" -ForegroundColor Cyan
Write-Host "Ruta: $ProjectRoot"
Write-Host "PHP: $($Php.Source)"
if ($Php) { & $Php.Source -v }
Write-Host "Base SQLite: $(Test-Path (Join-Path $ProjectRoot 'database\database.sqlite'))"
Write-Host "Configuracion: $(Test-Path (Join-Path $ProjectRoot '.env'))"
Write-Host "Dependencias: $(Test-Path (Join-Path $ProjectRoot 'vendor\autoload.php'))"
Write-Host "Frontend: $(Test-Path (Join-Path $ProjectRoot 'public\build\manifest.json'))"

try {
    $Response = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/login" -UseBasicParsing -TimeoutSec 5
    Write-Host "Servidor HTTP: OK ($($Response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "Servidor HTTP: ERROR - $($_.Exception.Message)" -ForegroundColor Red
}

if ($Php) {
    Set-Location $ProjectRoot
    & $Php.Source artisan about
}

Read-Host "Presiona Enter para cerrar"
