@echo off
:: Repara arranque LAN (0.0.0.0), firewall, tarea y acceso directo.
cd /d "%~dp0"
if not exist "%~dp0deployment\windows\Repair-EsteliPOS-LAN.ps1" (
    echo [ERROR] Falta deployment\windows\Repair-EsteliPOS-LAN.ps1
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0deployment\windows\Repair-EsteliPOS-LAN.ps1" %*
echo.
pause
exit /b %ERRORLEVEL%
