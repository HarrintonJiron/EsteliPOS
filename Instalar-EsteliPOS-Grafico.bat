@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Asistente grafico

:: Asistente WinForms de instalacion / actualizacion
:: Uso: Instalar-EsteliPOS-Grafico.bat
::      Instalar-EsteliPOS-Grafico.bat C:\ruta\produccion1.0.zip

cd /d "%~dp0"
set "ORIGINAL_ARGS=%*"

if not exist "%~dp0artisan" (
    echo.
    echo [ERROR] Este archivo debe estar dentro de la carpeta EsteliPOS.
    echo Extraiga EsteliPOSProduccion1.0.zip y ejecute Instalar-EsteliPOS-Grafico.bat
    echo desde la carpeta que contiene artisan.
    echo.
    pause
    exit /b 2
)

if not exist "%~dp0deployment\windows\Install-EsteliPOS-GUI.ps1" (
    echo.
    echo [ERROR] Falta deployment\windows\Install-EsteliPOS-GUI.ps1
    echo Use el ZIP oficial de produccion o Instalar-EsteliPOS.bat IIS
    echo.
    pause
    exit /b 2
)

net session >nul 2>&1
if not %errorLevel%==0 (
    echo.
    echo  [AVISO] Se requieren permisos de administrador.
    echo  Solicitando elevacion UAC...
    echo.
powershell.exe -NoProfile -Command "$p = Start-Process -FilePath '%~f0' -ArgumentList '%ORIGINAL_ARGS%' -Verb RunAs -Wait -PassThru; if ($null -eq $p) { exit 1 }; exit $p.ExitCode"
exit /b %ERRORLEVEL%
)

set "ZIP_ARG="
if not "%~1"=="" (
    if exist "%~1" set "ZIP_ARG=-UpdateZip ""%~1"""
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -File "%~dp0deployment\windows\Install-EsteliPOS-GUI.ps1" %ZIP_ARG%
set "EC=%ERRORLEVEL%"
if not "%EC%"=="0" (
    echo.
    echo [ERROR] El asistente termino con codigo %EC%
    pause
)
exit /b %EC%
