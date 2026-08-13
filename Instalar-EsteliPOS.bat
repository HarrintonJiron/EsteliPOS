@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Instalador

:: Instalador desde la raiz del paquete extraido (carpeta EsteliPOS)
:: Uso:
::   Instalar-EsteliPOS.bat
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

:: Si hay instalacion con datos y no pasaron perfil, ofrecer actualizar
if "%~1"=="" (
    if exist "%~dp0database\database.sqlite" if exist "%~dp0.env" (
        for %%A in ("%~dp0database\database.sqlite") do (
            if %%~zA GTR 1024 (
                cls
                echo.
                echo  ============================================================
                echo    ESTELIPOS - YA HAY DATOS EN ESTA CARPETA
                echo  ============================================================
                echo.
                echo   [1] ACTUALIZAR sin perder ventas/inventario
                echo   [2] Continuar con instalacion/reconfiguracion IIS
                echo   [Q] Cancelar
                echo.
                choice /c 12Q /n /m "Opcion: "
                if errorlevel 3 exit /b 0
                if errorlevel 2 goto :delegate
                if exist "%~dp0Actualizar-EsteliPOS.bat" (
                    call "%~dp0Actualizar-EsteliPOS.bat"
                    exit /b %ERRORLEVEL%
                )
                echo [ERROR] Falta Actualizar-EsteliPOS.bat
                pause
                exit /b 4
            )
        )
    )
)

:delegate
call "%~dp0deployment\windows\Install-EsteliPOS.bat" %*
exit /b %ERRORLEVEL%
