@echo off
:: Actualizador EsteliPOS - conserve .env y base de datos
:: Uso: Actualizar-EsteliPOS.bat [ruta\al\produccion1.0.zip]
:: Ejecute este archivo desde la carpeta de instalacion, por ejemplo:
::   C:\Northlink\EsteliPOS\Actualizar-EsteliPOS.bat
cd /d "%~dp0"
if not exist "%~dp0deployment\windows\Actualizar-EsteliPOS.bat" (
    echo [ERROR] Falta deployment\windows\Actualizar-EsteliPOS.bat
    echo Copie el actualizador completo dentro de la carpeta EsteliPOS instalada.
    pause
    exit /b 1
)
call "%~dp0deployment\windows\Actualizar-EsteliPOS.bat" %*
