#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_dir="$project_root/deployment"
work_dir="$(mktemp -d)"
archive_file="$work_dir/source.tar"
stage_dir="$work_dir/EsteliPOS"
allow_dirty=false
release_version=""

for arg in "$@"; do
    case "$arg" in
        --allow-dirty)
            allow_dirty=true
            ;;
        *)
            release_version="$arg"
            ;;
    esac
done

if [[ -z "$release_version" ]]; then
    release_version="$(date +%Y%m%d)-$(git -C "$project_root" rev-parse --short HEAD)"
fi

cleanup() {
    rm -rf "$work_dir"
}
trap cleanup EXIT

dirty_files="$(git -C "$project_root" status --porcelain)"
if [[ -n "$dirty_files" && "$allow_dirty" != true ]]; then
    echo "El repositorio tiene cambios sin guardar." >&2
    echo "Crea un commit o usa: deployment/build-release.sh --allow-dirty" >&2
    exit 1
fi

for command_name in php composer npm zip git tar; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Falta el comando requerido: $command_name" >&2
        exit 1
    fi
done

mkdir -p "$stage_dir" "$release_dir"

if [[ "$allow_dirty" == true ]]; then
    echo "Empaquetando arbol de trabajo actual (--allow-dirty)..."
    rsync -a \
        --exclude='.cursor' \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='releases' \
        --exclude='.env' \
        --exclude='.env.backup' \
        --exclude='.env.production' \
        --exclude='public/build' \
        --exclude='public/hot' \
        --exclude='storage/logs/*.log' \
        --exclude='storage/browser-qa' \
        --exclude='.playwright' \
        --exclude='.DS_Store' \
        --exclude='composer.phar' \
        --exclude='deployment/*.zip' \
        --exclude='deployment/*.sha256' \
        "$project_root/" "$stage_dir/"
else
    git -C "$project_root" archive --format=tar HEAD -o "$archive_file"
    tar -xf "$archive_file" -C "$stage_dir"
fi

echo "Instalando dependencias PHP..."
composer install --working-dir="$stage_dir" --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [[ -f "$stage_dir/package.json" ]] && [[ -f "$stage_dir/package-lock.json" ]]; then
    echo "Compilando assets frontend..."
    npm --prefix "$stage_dir" ci
    npm --prefix "$stage_dir" run build
fi

php "$stage_dir/artisan" list --raw | grep -q '^app:install-production'

for required_path in \
    "$stage_dir/Instalar-EsteliPOS.bat" \
    "$stage_dir/public/web.config" \
    "$stage_dir/deployment/windows/Install-EsteliPOS.bat" \
    "$stage_dir/deployment/windows/EsteliPOS-IIS.ps1" \
    "$stage_dir/deployment/windows/EsteliPOS-PHP.ps1" \
    "$stage_dir/deployment/windows/EsteliPOS-InstallErrors.ps1" \
    "$stage_dir/deployment/windows/Verify-PHP-EsteliPOS.ps1" \
    "$stage_dir/deployment/windows/Test-EsteliPOSInstallation.ps1"; do
    if [[ ! -f "$required_path" ]]; then
        echo "Falta archivo requerido para produccion Windows: $required_path" >&2
        exit 1
    fi
done

if [[ ! -f "$stage_dir/public/build/manifest.json" && ! -f "$stage_dir/public/css/app-ui.css" ]]; then
    echo "El paquete no contiene recursos web compilados (manifest.json o public/css/app-ui.css)." >&2
    exit 1
fi

rm -rf "$stage_dir/node_modules" "$stage_dir/tests" "$stage_dir/.github"
rm -f "$stage_dir/phpunit.xml" "$stage_dir/setup-windows.ps1" "$stage_dir/composer.phar"
rm -rf "$stage_dir/storage/framework/cache/data/"* "$stage_dir/storage/framework/views/"*

package_path="$release_dir/EsteliPOSProduccion2.0.zip"
(
    cd "$work_dir"
    zip -qr "$package_path" EsteliPOS
)

if command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$package_path" > "$package_path.sha256"
elif command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$package_path" > "$package_path.sha256"
fi

delivery_path="$release_dir/produccion2.0.zip"
rm -f "$delivery_path" "$release_dir/produccion.zip"
(
    cd "$release_dir"
    zip -j "$delivery_path" EsteliPOSProduccion2.0.zip EsteliPOSProduccion2.0.zip.sha256
)
rm -f "$package_path" "$package_path.sha256"

package_size="$(du -h "$delivery_path" | awk '{print $1}')"
echo ""
echo "Paquete generado: $delivery_path"
echo "Tamano: $package_size"
echo "Contenido: EsteliPOSProduccion2.0.zip + EsteliPOSProduccion2.0.zip.sha256"
echo ""
echo "Enviar al tecnico:"
echo "  1. deployment/produccion2.0.zip"
echo "  2. Extraer, luego extraer EsteliPOSProduccion2.0.zip y ejecutar Instalar-EsteliPOS.bat"
