@echo off
setlocal EnableExtensions
chcp 65001 >nul
title EsteliPOS - Carga de inventario CISVE

echo ============================================================
echo   EsteliPOS - CARGA DEL INVENTARIO REAL DEL CLIENTE
echo   Northlink Microsystem
echo ============================================================
echo.

net session >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Debe ejecutar este archivo como Administrador.
    echo Haga clic derecho sobre CARGAR-INVENTARIO.bat y elija
    echo "Ejecutar como administrador".
    echo.
    pause
    exit /b 1
)

echo Esta operacion:
echo   - crea un respaldo verificado de la base SQLite actual;
echo   - borra productos, ventas, compras y datos de demostracion;
echo   - conserva las cuentas de usuario y sus permisos;
echo   - carga 4,094 productos del inventario CISVE;
echo   - restaura el respaldo automaticamente si ocurre un error.
echo.
set /p CONFIRMACION=Escriba CARGAR para continuar:
if /I not "%CONFIRMACION%"=="CARGAR" (
    echo Operacion cancelada. No se modifico EsteliPOS.
    pause
    exit /b 2
)

echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Importar-Inventario-CISVE.ps1" -InventoryCsv "%~dp0inventario-cisve-4094.csv"
set "RESULTADO=%ERRORLEVEL%"

echo.
if "%RESULTADO%"=="0" (
    echo [OK] La carga termino correctamente.
) else (
    echo [ERROR] La carga no termino. Revise el mensaje y el log mostrado arriba.
)
echo.
pause
exit /b %RESULTADO%
