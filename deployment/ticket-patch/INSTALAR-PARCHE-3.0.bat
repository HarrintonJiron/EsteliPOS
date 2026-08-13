@echo off
setlocal EnableExtensions
chcp 65001 >nul 2>&1
title EsteliPOS - Parche 3.0 Ticket Termico

net session >nul 2>&1
if not %errorLevel%==0 (
    echo Solicitando permisos de administrador...
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
      "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b %ERRORLEVEL%
)

echo.
echo ============================================================
echo  ESTELIPOS - PARCHE 3.0 - TICKET TERMICO
echo  Este parche NO ejecuta migraciones ni reemplaza la base.
echo ============================================================
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Aplicar-Parche-Ticket-3.0.ps1" -ProjectRoot "C:\Northlink\EsteliPOS"
set "EC=%ERRORLEVEL%"

echo.
if not "%EC%"=="0" (
    echo [ERROR] El parche NO fue aplicado. Codigo: %EC%
    echo Lea el mensaje anterior. No reinstale EsteliPOS.
    pause
    exit /b %EC%
)

echo ============================================================
echo  PARCHE 3.0 APLICADO CORRECTAMENTE
echo  La base de datos NO fue modificada.
echo ============================================================
pause
exit /b 0
