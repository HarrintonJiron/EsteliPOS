@echo off
:: Instalador EsteliPOS - ejecute desde la raiz del paquete extraido
cd /d "%~dp0"
call "%~dp0deployment\windows\Install-EsteliPOS.bat" %*
