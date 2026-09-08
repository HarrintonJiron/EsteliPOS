@echo off
:: Abre EsteliPOS: inicia Laravel (si hace falta) y luego el navegador.
:: Uso: doble clic desde la carpeta de instalacion (ej. C:\Northlink\EsteliPOS)
cd /d "%~dp0"
if not exist "%~dp0deployment\windows\Launch-EsteliPOS.ps1" (
    echo [ERROR] Falta deployment\windows\Launch-EsteliPOS.ps1
    echo Ejecute este archivo desde la carpeta EsteliPOS instalada.
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0deployment\windows\Launch-EsteliPOS.ps1" %*
exit /b %ERRORLEVEL%
