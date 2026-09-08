# Script para hacer pull del último commit
# Ejecutar como: PowerShell -ExecutionPolicy Bypass -File Git-Pull-Latest.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Git Pull - Ultimo Commit" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectPath

Write-Host "Directorio del proyecto: $projectPath" -ForegroundColor Cyan
Write-Host ""
Write-Host "Haciendo pull de la rama versionproduccion1.0..." -ForegroundColor Cyan

try {
    git pull origin versionproduccion1.0
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Pull completado" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Ultimos commits:" -ForegroundColor Yellow
    git log --oneline -5
    Write-Host ""
}
catch {
    Write-Host ""
    Write-Host "ERROR: No se pudo hacer pull: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Posibles soluciones:" -ForegroundColor Yellow
    Write-Host "1. Usar GitHub Desktop" -ForegroundColor White
    Write-Host "2. Usar VS Code (Source Control -> Sync Changes)" -ForegroundColor White
    Write-Host "3. Ejecutar manualmente: git pull origin versionproduccion1.0" -ForegroundColor White
    Write-Host ""
}

Read-Host "Presione Enter para salir"
