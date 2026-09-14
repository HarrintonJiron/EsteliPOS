#!/usr/bin/env bash
set -euo pipefail

# Script de actualización para instalaciones existentes de EsteliPOS
# Este script actualiza el código sin perder la base de datos ni configuraciones

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
backup_dir="$project_root/backups/$(date +%Y%m%d_%H%M%S)"
update_zip="${1:-$project_root/releases/EsteliPOS-$(date +%Y%m%d)-*.zip}"

echo "=== Actualización de EsteliPOS ==="
echo "Versión: $(date +%Y%m%d)"
echo ""

# Verificar si existe el archivo de actualización
if [[ ! -f $update_zip ]]; then
    echo "Error: No se encontró el archivo de actualización: $update_zip"
    echo "Uso: $0 [ruta/al/archivo.zip]"
    exit 1
fi

# Crear directorio de backup
echo "1. Creando backup..."
mkdir -p "$backup_dir"

# Backup de archivos importantes
echo "   - Backup de .env"
cp "$project_root/.env" "$backup_dir/" 2>/dev/null || true

echo "   - Backup de base de datos"
if [[ -f "$project_root/database/database.sqlite" ]]; then
    cp "$project_root/database/database.sqlite" "$backup_dir/"
fi

# Backup de uploads si existen
if [[ -d "$project_root/public/uploads" ]]; then
    echo "   - Backup de uploads"
    cp -r "$project_root/public/uploads" "$backup_dir/"
fi

echo "   - Backup de código actual"
tar -czf "$backup_dir/code_backup.tar.gz" -C "$project_root" \
    app/ database/migrations/ resources/views/ routes/ 2>/dev/null || true

echo "   ✓ Backup completado en: $backup_dir"

# Extraer actualización
echo ""
echo "2. Extrayendo actualización..."
temp_dir=$(mktemp -d)
unzip -q "$update_zip" -d "$temp_dir"

# Copiar archivos actualizados
echo "   - Actualizando archivos de aplicación"
cp -r "$temp_dir/EsteliPOS/app/" "$project_root/"
cp -r "$temp_dir/EsteliPOS/database/migrations/" "$project_root/database/"
cp -r "$temp_dir/EsteliPOS/resources/views/" "$project_root/resources/"
cp -r "$temp_dir/EsteliPOS/routes/" "$project_root/"
cp -r "$temp_dir/EsteliPOS/public/build/" "$project_root/public/build/" 2>/dev/null || true

# Limpiar directorio temporal
rm -rf "$temp_dir"

# Ejecutar migraciones
echo ""
echo "3. Ejecutando migraciones de base de datos..."
cd "$project_root"
php artisan migrate --force

# Limpiar cache
echo ""
echo "4. Limpiando cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar
echo ""
echo "5. Optimizando aplicación..."
php artisan optimize

echo ""
echo "=== Actualización completada exitosamente ==="
echo "Backup guardado en: $backup_dir"
echo ""
echo "Para revertir la actualización, restaura los archivos desde el backup."
