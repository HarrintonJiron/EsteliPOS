@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Actualizador Northlink

:: ---------------------------------------------------------------------------
::  Actualiza una instalacion existente SIN borrar datos.
::  Puede vivir en:
::    C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat
::    C:\Northlink\EsteliPOS\deployment\windows\Actualizar-EsteliPOS.bat
::  Uso:
::    Actualizar-EsteliPOS.bat
::    Actualizar-EsteliPOS.bat C:\Northlink\parche1.0.zip
:: ---------------------------------------------------------------------------

set "START_DIR=%~dp0"
set "PROJECT_ROOT="
set "WINDOWS_DIR="

if exist "%START_DIR%artisan" if exist "%START_DIR%deployment\windows\Update-EsteliPOS.ps1" (
    set "PROJECT_ROOT=%START_DIR%"
    set "WINDOWS_DIR=%START_DIR%deployment\windows\"
    goto :root_ready
)

if exist "%START_DIR%Update-EsteliPOS.ps1" if exist "%START_DIR%..\..\artisan" (
    for %%I in ("%START_DIR%..\..") do set "PROJECT_ROOT=%%~fI\"
    set "WINDOWS_DIR=%START_DIR%"
    goto :root_ready
)

if exist "%START_DIR%EsteliPOS\artisan" if exist "%START_DIR%EsteliPOS\deployment\windows\Update-EsteliPOS.ps1" (
    set "PROJECT_ROOT=%START_DIR%EsteliPOS\"
    set "WINDOWS_DIR=%START_DIR%EsteliPOS\deployment\windows\"
    goto :root_ready
)

echo.
echo [ERROR] No se encontro la instalacion de EsteliPOS.
echo.
echo  Coloque este archivo en:
echo    C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat
echo  o en:
echo    C:\Northlink\EsteliPOS\deployment\windows\Actualizar-EsteliPOS.bat
echo.
echo  Y asegurese de tener tambien:
echo    deployment\windows\Update-EsteliPOS.ps1
echo    deployment\windows\EsteliPOS-Common.ps1
echo.
pause
exit /b 1

:root_ready
cd /d "%PROJECT_ROOT%"
if not exist "%WINDOWS_DIR%Update-EsteliPOS.ps1" (
    echo [ERROR] Falta: %WINDOWS_DIR%Update-EsteliPOS.ps1
    pause
    exit /b 1
)
if not exist "%WINDOWS_DIR%EsteliPOS-Common.ps1" (
    echo [ERROR] Falta: %WINDOWS_DIR%EsteliPOS-Common.ps1
    echo Copie TODOS los scripts de deployment\windows del paquete nuevo,
    echo no solo Update-EsteliPOS.ps1.
    pause
    exit /b 1
)

set "UPDATE_ZIP=%~1"
if not "%UPDATE_ZIP%"=="" goto :have_zip

echo.
echo  ============================================================
echo    ESTELIPOS - ACTUALIZADOR DEFINITIVO (sin perder datos)
echo    Northlink Microsystem
echo  ============================================================
echo.
echo   Instalacion detectada: %CD%
echo   Buscando paquete COMPLETO mas reciente...
echo   Preferido: produccion1.0.zip / EsteliPOSProduccion1.0.zip
echo   (parche*.zip solo si no hay paquete completo)
echo.

for /f "usebackq delims=" %%F in (`powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
  "$roots=@('%PROJECT_ROOT%','%PROJECT_ROOT%deployment','%PROJECT_ROOT%..','C:\Northlink','%~dp0');" ^
  "$files=@(); foreach($r in $roots){ if(Test-Path $r){ $files+=Get-ChildItem $r -File -ErrorAction SilentlyContinue |" ^
  " Where-Object { $_.Name -match '^(produccion|EsteliPOSProduccion|parche).*\.zip$' } } };" ^
  "$best=$files | Sort-Object FullName -Unique | Sort-Object @{Expression={ if($_.Name -match '^EsteliPOSProduccion'){3} elseif($_.Name -match '^produccion'){2} else {1} };Descending=$true},@{Expression='LastWriteTime';Descending=$true},@{Expression='Length';Descending=$true} | Select-Object -First 1;" ^
  "if($best){ $best.FullName }"`) do (
    set "UPDATE_ZIP=%%F"
)

if not "%UPDATE_ZIP%"=="" (
    echo   ZIP recomendado encontrado:
    echo   %UPDATE_ZIP%
    echo.
    set /p "CONFIRM=   Usar este archivo? [S/N]: "
    if /i not "!CONFIRM!"=="S" if /i not "!CONFIRM!"=="Y" (
        set "UPDATE_ZIP="
    )
)

if "%UPDATE_ZIP%"=="" (
    set /p "UPDATE_ZIP=   Ruta completa del ZIP NUEVO (produccion1.0.zip): "
)
if "%UPDATE_ZIP%"=="" (
    echo [ERROR] Debe indicar el archivo ZIP.
    pause
    exit /b 1
)

:have_zip
if not exist "%UPDATE_ZIP%" (
    echo [ERROR] No existe el archivo: %UPDATE_ZIP%
    pause
    exit /b 1
)

net session >nul 2>&1
if not %errorLevel%==0 (
    echo.
    echo [INFO] Se necesitan permisos de administrador. Reintentando...
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
      "Start-Process -FilePath '%~f0' -ArgumentList '\"%UPDATE_ZIP%\"' -Verb RunAs"
    exit /b %ERRORLEVEL%
)

echo.
echo  ============================================================
echo   Instalacion actual: %CD%
echo   Paquete:            %UPDATE_ZIP%
echo.
echo   Se CONSERVAN:
echo     - Base de datos ^(ventas, inventario, clientes^)
echo     - Archivo .env y APP_KEY
echo     - Archivos en storage\app
echo   Se ACTUALIZAN:
echo     - Codigo completo, vistas, migraciones, vendor y assets
echo     - Scripts Windows ^(arranque LAN 0.0.0.0, firewall, launcher^)
echo     - Acceso directo y tarea de arranque automatico
echo  ============================================================
echo.
echo   No cierre esta ventana hasta ver ACTUALIZACION COMPLETADA.
echo.

:: Quitar barra final: en Windows "ruta\" escapa la comilla y rompe PowerShell.
set "WIN_SCRIPTS=%WINDOWS_DIR%"
if "%WIN_SCRIPTS:~-1%"=="\" set "WIN_SCRIPTS=%WIN_SCRIPTS:~0,-1%"

:: Arrancar SIEMPRE con el Update-EsteliPOS.ps1 del ZIP nuevo.
echo   Preparando motor de actualizacion desde el paquete...
set "BOOTSTRAP=%WIN_SCRIPTS%\Bootstrap-UpdateFromZip.ps1"
if not exist "%BOOTSTRAP%" (
    echo   Bootstrap local no encontrado; extrayendo desde el ZIP...
    set "ESTELI_UPD_ZIP=%UPDATE_ZIP%"
    set "ESTELI_WIN_DIR=%WIN_SCRIPTS%"
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
      "$ErrorActionPreference='Stop';" ^
      "$zip=$env:ESTELI_UPD_ZIP; $wd=$env:ESTELI_WIN_DIR;" ^
      "if([string]::IsNullOrWhiteSpace($zip) -or -not (Test-Path -LiteralPath $zip)){ throw 'ZIP invalido' };" ^
      "$tmp=Join-Path $env:TEMP ('estelipos-bootfile-' + [guid]::NewGuid());" ^
      "New-Item -ItemType Directory -Force -Path $tmp | Out-Null;" ^
      "Expand-Archive -LiteralPath $zip -DestinationPath $tmp -Force;" ^
      "$boot=Get-ChildItem -Path $tmp -Filter 'Bootstrap-UpdateFromZip.ps1' -Recurse -File -ErrorAction SilentlyContinue | Select-Object -First 1;" ^
      "if(-not $boot){ throw 'El ZIP no incluye Bootstrap-UpdateFromZip.ps1. Use produccion1.0.zip v1.0.6+' };" ^
      "New-Item -ItemType Directory -Force -Path $wd | Out-Null;" ^
      "Copy-Item -LiteralPath $boot.FullName -Destination (Join-Path $wd 'Bootstrap-UpdateFromZip.ps1') -Force;" ^
      "Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue"
    if errorlevel 1 (
        echo [ERROR] No se pudo extraer Bootstrap-UpdateFromZip.ps1 del ZIP.
        echo         Asegurese de usar el produccion1.0.zip NUEVO ^(v1.0.6+^), no el de 1.0.4.
        pause
        exit /b 1
    )
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%WIN_SCRIPTS%\Bootstrap-UpdateFromZip.ps1" -UpdateZip "%UPDATE_ZIP%" -WindowsDir "%WIN_SCRIPTS%"
if errorlevel 1 (
    echo [ERROR] No se pudo preparar el actualizador desde el ZIP.
    echo         Compruebe que el archivo es produccion1.0.zip v1.0.6 o superior.
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%WIN_SCRIPTS%\Update-EsteliPOS.ps1" -UpdateZip "%UPDATE_ZIP%"
set "EC=%ERRORLEVEL%"

echo.
if not "%EC%"=="0" (
    echo  ============================================================
    echo   ACTUALIZACION FALLIDA
    echo   Si habia respaldo, revise: %CD%\backups\
    echo  ============================================================
    pause
    exit /b %EC%
)

echo  ============================================================
echo   ACTUALIZACION COMPLETADA - DATOS CONSERVADOS
echo  ============================================================
echo   Respaldo en: %CD%\backups\
echo   Puede cerrar esta ventana.
echo  ============================================================
pause
exit /b 0
