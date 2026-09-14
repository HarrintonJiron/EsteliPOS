@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Instalador

:: Instalador desde la raiz del paquete extraido (carpeta EsteliPOS)
:: Uso:
::   Instalar-EsteliPOS.bat              -> menu de consola
::   Instalar-EsteliPOS.bat IIS
::   Instalar-EsteliPOS.bat Simple

cd /d "%~dp0"

if not exist "%~dp0artisan" (
    echo.
    echo [ERROR] Este archivo debe estar dentro de la carpeta EsteliPOS.
    echo Extraiga EsteliPOSProduccion1.0.zip y ejecute Instalar-EsteliPOS.bat
    echo desde la carpeta que contiene artisan.
    echo.
    pause
    exit /b 2
)

if not exist "%~dp0deployment\windows\Install-EsteliPOS.bat" (
    echo.
    echo [ERROR] Falta deployment\windows\Install-EsteliPOS.bat
    echo El paquete esta incompleto. Use el ZIP oficial produccion1.0.zip
    echo.
    pause
    exit /b 2
)

if not exist "%~dp0vendor\autoload.php" (
    echo.
    echo [ERROR] Falta la carpeta vendor.
    echo No instale desde codigo fuente: use el ZIP de produccion.
    echo.
    pause
    exit /b 7
)

if not exist "%~dp0public\build\manifest.json" (
    if not exist "%~dp0public\css\app-ui.css" (
        echo.
        echo [ERROR] Faltan recursos web ^(public\build^).
        echo Use el ZIP oficial generado para produccion.
        echo.
        pause
        exit /b 8
    )
)

if /i "%~1"=="IIS" goto :delegate
if /i "%~1"=="Simple" goto :delegate
if /i "%~1"=="SIMPLE" goto :delegate
if /i "%~1"=="Verify" goto :delegate
if not "%~1"=="" (
    echo.
    echo [ERROR] Parametro no valido: %~1
    echo Use: Instalar-EsteliPOS.bat   o   Instalar-EsteliPOS.bat IIS ^| Simple
    echo.
    pause
    exit /b 1
)

:delegate
call "%~dp0deployment\windows\Install-EsteliPOS.bat" %*
exit /b %ERRORLEVEL%
