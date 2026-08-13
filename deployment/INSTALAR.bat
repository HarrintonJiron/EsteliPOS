@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title EsteliPOS - Instalador facil

:: ============================================================================
::  INSTALADOR A PRUEBA DE ERRORES
::  Uso: doble clic en INSTALAR.bat (junto a EsteliPOSProduccion1.0.zip)
::  - Extrae el paquete si hace falta
::  - Instala NUEVO o ACTUALIZA sin borrar datos
::  - Pide administrador automaticamente
:: ============================================================================

set "START_DIR=%~dp0"
set "TARGET_DIR=C:\Northlink\EsteliPOS"
set "PACKAGE_ZIP="
set "OUTER_ZIP="
set "MODE="
set "ORIGINAL_ARGS=%*"

call :ensure_admin
if errorlevel 1 exit /b 1

call :find_package
if errorlevel 1 (
    echo.
    echo [ERROR] No se encontro el paquete EsteliPOS.
    echo.
    echo  Coloque este INSTALAR.bat en la MISMA carpeta que:
    echo    - EsteliPOSProduccion1.0.zip
    echo    - o parche1.0.zip
    echo.
    echo  Carpeta actual: %START_DIR%
    echo.
    pause
    exit /b 2
)

call :detect_existing_install
if /i "%DAMAGED%"=="1" (
    echo.
    echo [ERROR] Se encontro una instalacion existente SIN una base de datos valida.
    echo No se iniciara una instalacion nueva porque podria ocultar la perdida de datos.
    echo Restaure primero database.sqlite desde:
    echo   %TARGET_DIR%\backups
    echo.
    pause
    exit /b 6
)

cls
echo.
echo  ============================================================
echo    ESTELIPOS - INSTALADOR FACIL
echo    Northlink Microsystem  ^|  Version final 1.0
echo  ============================================================
echo.
echo   Carpeta de trabajo: %START_DIR%
if defined PACKAGE_ZIP echo   Paquete:           !PACKAGE_ZIP!
if defined OUTER_ZIP if not defined PACKAGE_ZIP echo   Paquete:           !OUTER_ZIP!
echo   Destino:            %TARGET_DIR%
echo.

if /i "%EXISTING%"=="1" (
    echo   Se detecto una instalacion EXISTENTE con datos.
    echo   Elija con cuidado:
    echo.
    echo   [1] ACTUALIZAR  ^(recomendado - conserva ventas e inventario^)
    echo   [2] Reinstalar IIS sobre la misma carpeta
    echo       ^(NO borra la base de datos, pero reinstala servicios^)
    echo   [Q] Cancelar
    echo.
    choice /c 12Q /n /m "Opcion [1/2/Q]: "
    if errorlevel 3 exit /b 0
    if errorlevel 2 (
        set "MODE=INSTALL_IIS"
    ) else (
        set "MODE=UPDATE"
    )
) else (
    echo   No hay instalacion previa con datos en %TARGET_DIR%
    echo.
    echo   [1] Instalar ahora ^(IIS - RECOMENDADO para ferreteria^)
    echo   [2] Instalar modo Simple ^(1 caja / pruebas^)
    echo   [Q] Cancelar
    echo.
    choice /c 12Q /n /m "Opcion [1/2/Q]: "
    if errorlevel 3 exit /b 0
    if errorlevel 2 (
        set "MODE=INSTALL_SIMPLE"
    ) else (
        set "MODE=INSTALL_IIS"
    )
)

echo.
echo  ------------------------------------------------------------
if /i "%MODE%"=="UPDATE" (
    echo   Accion: ACTUALIZAR sin perder datos
) else if /i "%MODE%"=="INSTALL_SIMPLE" (
    echo   Accion: Instalacion NUEVA - perfil Simple
) else (
    echo   Accion: Instalacion NUEVA - perfil IIS
)
echo   Destino: %TARGET_DIR%
echo  ------------------------------------------------------------
echo.
choice /c SN /n /m "Continuar? [S/N]: "
if errorlevel 2 exit /b 0

call :prepare_install_folder
if errorlevel 1 (
    pause
    exit /b 3
)

if /i "%MODE%"=="UPDATE" goto :do_update
goto :do_install

:: ---------------------------------------------------------------------------
:do_update
echo.
echo  ==> Actualizando instalacion existente...
echo.
if not exist "%TARGET_DIR%\Actualizar-EsteliPOS.bat" (
    echo [ERROR] Falta Actualizar-EsteliPOS.bat en %TARGET_DIR%
    echo Extraiga primero el paquete completo o elija instalacion nueva.
    pause
    exit /b 4
)

if defined PACKAGE_ZIP (
    call "%TARGET_DIR%\Actualizar-EsteliPOS.bat" "%PACKAGE_ZIP%"
) else if defined OUTER_ZIP (
    call "%TARGET_DIR%\Actualizar-EsteliPOS.bat" "%OUTER_ZIP%"
) else (
    call "%TARGET_DIR%\Actualizar-EsteliPOS.bat"
)
set "EC=%ERRORLEVEL%"
goto :finish

:: ---------------------------------------------------------------------------
:do_install
echo.
echo  ==> Instalando EsteliPOS...
echo.
if not exist "%TARGET_DIR%\Instalar-EsteliPOS.bat" (
    echo [ERROR] Falta Instalar-EsteliPOS.bat en %TARGET_DIR%
    echo El paquete no se extrajo correctamente.
    pause
    exit /b 5
)

if /i "%MODE%"=="INSTALL_SIMPLE" (
    call "%TARGET_DIR%\Instalar-EsteliPOS.bat" Simple
) else (
    call "%TARGET_DIR%\Instalar-EsteliPOS.bat" IIS
)
set "EC=%ERRORLEVEL%"
goto :finish

:: ---------------------------------------------------------------------------
:finish
echo.
if not "%EC%"=="0" (
    echo  ============================================================
    echo   NO SE COMPLETO LA OPERACION  ^(codigo %EC%^)
    echo  ============================================================
    echo   Revise los mensajes en rojo de arriba.
    echo   Log tipico: %TARGET_DIR%\storage\logs\install-*.log
    echo  ============================================================
    pause
    exit /b %EC%
)

echo  ============================================================
echo   LISTO
echo  ============================================================
echo   Carpeta: %TARGET_DIR%
echo   Abra el acceso directo del escritorio o:
echo     http://localhost:8080
echo   Si no carga: Ctrl+F5 en el navegador.
echo  ============================================================
pause
exit /b 0

:: ---------------------------------------------------------------------------
:ensure_admin
net session >nul 2>&1
if %errorLevel%==0 exit /b 0
echo.
echo  Se necesitan permisos de administrador.
echo  Acepte la ventana de Windows ^(UAC^)...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
  "Start-Process -FilePath '%~f0' -ArgumentList '%ORIGINAL_ARGS%' -Verb RunAs"
exit /b 1

:: ---------------------------------------------------------------------------
:find_package
set "PACKAGE_ZIP="
set "OUTER_ZIP="

:: Prefer inner package next to this bat
for %%F in (
    "%START_DIR%EsteliPOSProduccion1.0.zip"
    "%START_DIR%EsteliPOSProduccion*.zip"
) do if exist "%%~fF" (
    set "PACKAGE_ZIP=%%~fF"
    goto :package_ok
)

:: Outer delivery zip
for %%F in (
    "%START_DIR%parche1.0.zip"
    "%START_DIR%parche*.zip"
    "%START_DIR%produccion1.0.zip"
    "%START_DIR%produccion*.zip"
) do if exist "%%~fF" (
    set "OUTER_ZIP=%%~fF"
    goto :package_ok
)

:: Already extracted EsteliPOS folder beside this bat
if exist "%START_DIR%EsteliPOS\artisan" if exist "%START_DIR%EsteliPOS\Instalar-EsteliPOS.bat" (
    set "TARGET_DIR=%START_DIR%EsteliPOS"
    goto :package_ok
)

:: Running from inside extracted EsteliPOS
if exist "%START_DIR%artisan" if exist "%START_DIR%Instalar-EsteliPOS.bat" (
    set "TARGET_DIR=%START_DIR:~0,-1%"
    goto :package_ok
)

:: Common drop locations
for %%F in (
    "C:\Northlink\EsteliPOSProduccion1.0.zip"
    "C:\Northlink\parche1.0.zip"
    "C:\Northlink\produccion1.0.zip"
) do if exist "%%~fF" (
    if /i "%%~nxF"=="parche1.0.zip" (set "OUTER_ZIP=%%~fF") else if /i "%%~nxF"=="produccion1.0.zip" (set "OUTER_ZIP=%%~fF") else (set "PACKAGE_ZIP=%%~fF")
    goto :package_ok
)

exit /b 1

:package_ok
exit /b 0

:: ---------------------------------------------------------------------------
:detect_existing_install
set "EXISTING=0"
set "DAMAGED=0"
if exist "%TARGET_DIR%\database\database.sqlite" if exist "%TARGET_DIR%\.env" (
    for %%A in ("%TARGET_DIR%\database\database.sqlite") do (
        if %%~zA GTR 1024 set "EXISTING=1"
    )
)
if exist "%TARGET_DIR%\artisan" if exist "%TARGET_DIR%\.env" if /i "%EXISTING%"=="0" set "DAMAGED=1"
exit /b 0

:: ---------------------------------------------------------------------------
:prepare_install_folder
:: If we only have outer zip, extract to temp-ish folder next to bat
if defined OUTER_ZIP if not defined PACKAGE_ZIP (
    echo  ==> Extrayendo parche1.0.zip ...
    set "EXTRACT_TMP=%START_DIR%_estelipos_extract"
    if exist "!EXTRACT_TMP!" rd /s /q "!EXTRACT_TMP!" >nul 2>&1
    mkdir "!EXTRACT_TMP!" >nul 2>&1
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
      "Expand-Archive -LiteralPath '%OUTER_ZIP%' -DestinationPath '%EXTRACT_TMP%' -Force"
    if errorlevel 1 (
        echo [ERROR] No se pudo extraer %OUTER_ZIP%
        exit /b 1
    )
    for %%F in ("!EXTRACT_TMP!\EsteliPOSProduccion*.zip") do (
        set "PACKAGE_ZIP=%%~fF"
    )
    if not defined PACKAGE_ZIP (
        echo [ERROR] El ZIP externo no contiene EsteliPOSProduccion1.0.zip
        exit /b 1
    )
)

:: If target already is a valid package root, nothing to extract
if exist "%TARGET_DIR%\artisan" if exist "%TARGET_DIR%\vendor\autoload.php" (
    if /i "%MODE%"=="UPDATE" exit /b 0
    if /i "%EXISTING%"=="1" exit /b 0
)

:: Extract inner package into C:\Northlink\EsteliPOS
if defined PACKAGE_ZIP (
    echo  ==> Preparando carpeta %TARGET_DIR% ...
    if not exist "C:\Northlink" mkdir "C:\Northlink" >nul 2>&1

    if /i "%EXISTING%"=="1" (
        :: Update mode: do not wipe install; Actualizar-EsteliPOS uses the zip
        exit /b 0
    )

    if exist "%TARGET_DIR%\artisan" (
        echo   Ya existe codigo en destino. Se reutilizara la carpeta.
        exit /b 0
    )

    set "NEST_TMP=%TEMP%\estelipos-install-%RANDOM%"
    if exist "!NEST_TMP!" rd /s /q "!NEST_TMP!" >nul 2>&1
    mkdir "!NEST_TMP!" >nul 2>&1

    echo  ==> Extrayendo paquete ^(puede tardar un minuto^)...
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
      "Expand-Archive -LiteralPath '%PACKAGE_ZIP%' -DestinationPath '%NEST_TMP%' -Force"
    if errorlevel 1 (
        echo [ERROR] No se pudo extraer el paquete.
        exit /b 1
    )

    if not exist "!NEST_TMP!\EsteliPOS\artisan" (
        echo [ERROR] El ZIP no contiene la carpeta EsteliPOS.
        exit /b 1
    )

    mkdir "C:\Northlink" >nul 2>&1
    if exist "%TARGET_DIR%" rd /s /q "%TARGET_DIR%" >nul 2>&1
    move "!NEST_TMP!\EsteliPOS" "%TARGET_DIR%" >nul
    if errorlevel 1 (
        echo [ERROR] No se pudo mover EsteliPOS a %TARGET_DIR%
        exit /b 1
    )
    rd /s /q "!NEST_TMP!" >nul 2>&1
)

:: Final sanity
if not exist "%TARGET_DIR%\artisan" (
    echo [ERROR] Destino incompleto: falta artisan en %TARGET_DIR%
    exit /b 1
)
if not exist "%TARGET_DIR%\vendor\autoload.php" (
    echo [ERROR] Destino incompleto: falta vendor. Use el ZIP oficial de produccion.
    exit /b 1
)
if not exist "%TARGET_DIR%\public\build\manifest.json" (
    if not exist "%TARGET_DIR%\public\css\app-ui.css" (
        echo [ERROR] Destino incompleto: faltan assets ^(public\build^).
        exit /b 1
    )
)

exit /b 0
