# Compatibilidad con instrucciones anteriores.
# El despliegue de produccion se encuentra en deployment/windows.

$DeployScript = Join-Path $PSScriptRoot "deployment\windows\Deploy-EsteliPOS.ps1"

if (-not (Test-Path $DeployScript)) {
    Write-Error "No se encontro $DeployScript"
    exit 1
}

Write-Host "Iniciando el despliegue de produccion de EsteliPOS..." -ForegroundColor Cyan
& $DeployScript @args
exit $LASTEXITCODE
