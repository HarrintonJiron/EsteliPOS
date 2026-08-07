@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Instalador Northlink

:: ---------------------------------------------------------------------------
::  Instalador principal - doble clic o "Ejecutar como administrador"
::  Uso: Instalar-EsteliPOS.bat [IIS|Simple]
:: ---------------------------------------------------------------------------

set "SCRIPT_DIR=%~dp0"
set "PROJECT_ROOT=%SCRIPT_DIR%..\.."
cd /d "%PROJECT_ROOT%"

if /i "%~1"=="IIS" goto :profile_iis
if /i "%~1"=="Simple" goto :profile_simple
if /i "%~1"=="SIMPLE" goto :profile_simple
if not "%~1"=="" goto :bad_arg

goto :menu

:bad_arg
echo.
echo [ERROR] Parametro no valido: %~1
echo Use: Instalar-EsteliPOS.bat   o   Instalar-EsteliPOS.bat IIS ^| Simple
echo.
pause
exit /b 1

:menu
cls
echo.
echo  ============================================================
echo    ESTELIPOS - INSTALADOR DE PRODUCCION (Windows)
echo    Northlink Microsystem
echo  ============================================================
echo.
echo   Seleccione el tipo de instalacion:
echo.
echo   [1] IIS + PHP FastCGI  ^(RECOMENDADO - instala IIS y PHP automaticamente^)
echo   [2] Simple ^(php artisan serve - 1 caja, pruebas^)
echo   [3] Solo verificar PHP y paquete ^(sin instalar^)
echo   [Q] Salir
echo.
choice /c 123Q /n /m "Opcion: "
if errorlevel 4 exit /b 0
if errorlevel 3 goto :verify_only
if errorlevel 2 goto :profile_simple
goto :profile_iis

:verify_only
call :ensure_admin
if errorlevel 1 exit /b 1
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%Verify-PHP-EsteliPOS.ps1" -ServerProfile IIS
set "EC=%ERRORLEVEL%"
echo.
if not "%EC%"=="0" (
    call :show_failure %EC% "Verificacion previa"
) else (
    echo [OK] Entorno listo para instalar. Ejecute de nuevo y elija opcion 1 o 2.
)
pause
exit /b %EC%

:profile_iis
set "SERVER_PROFILE=IIS"
goto :run_install

:profile_simple
set "SERVER_PROFILE=Simple"
goto :run_install

:run_install
call :ensure_admin
if errorlevel 1 exit /b 1

call :print_header
echo   Perfil seleccionado: %SERVER_PROFILE%
echo   Carpeta del sistema: %CD%
echo.
echo   El proceso puede tardar varios minutos.
if /i "%SERVER_PROFILE%"=="IIS" (
    echo   Se instalara IIS, PHP Thread Safe, URL Rewrite y el sitio en puerto 8080.
)
echo   No cierre esta ventana hasta ver "DESPLIEGUE COMPLETADO".
echo.
echo  ============================================================
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%Deploy-EsteliPOS.ps1" -ServerProfile %SERVER_PROFILE%
set "EC=%ERRORLEVEL%"

echo.
if not "%EC%"=="0" (
    call :show_failure %EC% "Instalacion"
    pause
    exit /b %EC%
)

echo.
echo  ============================================================
echo   INSTALACION COMPLETADA CORRECTAMENTE
echo  ============================================================
echo   Revise los accesos directos en el escritorio.
echo   Log: storage\logs\install-*.log
echo  ============================================================
echo.
pause
exit /b 0

:ensure_admin
net session >nul 2>&1
if %errorLevel%==0 exit /b 0

echo.
echo  [AVISO] Se requieren permisos de administrador.
echo  Solicitando elevacion UAC...
echo.

powershell.exe -NoProfile -Command "Start-Process -FilePath '%~f0' -ArgumentList '%*' -Verb RunAs"
exit /b 0

:print_header
cls
echo.
echo  ============================================================
echo    ESTELIPOS - INSTALADOR DE PRODUCCION
echo    Northlink Microsystem
echo  ============================================================
exit /b 0

:show_failure
set "FAIL_CODE=%~1"
set "FAIL_PHASE=%~2"

echo.
echo  ============================================================
echo   LA INSTALACION NO SE COMPLETO
echo  ============================================================
echo   Fase: %FAIL_PHASE%
echo   Codigo de error: %FAIL_CODE%
echo.
echo   Consultando soluciones recomendadas...
echo  ============================================================
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_DIR%Show-InstallError.ps1" -ExitCode %FAIL_CODE% -NoWait
exit /b 0
