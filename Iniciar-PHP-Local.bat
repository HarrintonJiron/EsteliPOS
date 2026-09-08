@echo off
REM Script para iniciar EsteliPOS con PHP local (más rápido)
REM Requiere PHP 8.5+ instalado en Windows

echo ========================================
echo Iniciando EsteliPOS con PHP Local
echo ========================================
echo.

cd "C:\Documentos\UNI RUACS\Esteli pos\EsteliPOS"

echo Verificando PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP no esta instalado
    echo Descarga PHP desde: https://windows.php.net/download/
    pause
    exit /b 1
)

echo PHP detectado
echo.

echo Limpiando cache...
php artisan optimize:clear
echo.

echo Iniciando servidor...
echo El sistema estara disponible en: http://localhost:8000
echo Presiona Ctrl+C para detener
echo.

php artisan serve
