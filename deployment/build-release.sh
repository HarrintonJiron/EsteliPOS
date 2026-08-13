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
    release_version="$(tr -d '[:space:]' < "$project_root/VERSION")"
fi

if [[ ! "$release_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][A-Za-z0-9]+)*$ ]]; then
    echo "VERSION invalida: $release_version" >&2
    exit 1
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

for command_name in php composer npm zip unzip git tar; do
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

printf '%s\n' "$release_version" > "$stage_dir/VERSION"

rm -f "$stage_dir"/deployment/*.zip "$stage_dir"/deployment/*.sha256
rm -f "$stage_dir"/database/*.sqlite "$stage_dir"/database/*.sqlite-* \
    "$stage_dir"/storage/*.sqlite "$stage_dir"/storage/*.sqlite-*

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
    "$stage_dir/Actualizar-EsteliPOS.bat" \
    "$stage_dir/.env.production.example" \
    "$stage_dir/public/web.config" \
    "$stage_dir/deployment/windows/Install-EsteliPOS.bat" \
    "$stage_dir/deployment/windows/Actualizar-EsteliPOS.bat" \
    "$stage_dir/deployment/windows/Update-EsteliPOS.ps1" \
    "$stage_dir/deployment/windows/EsteliPOS-IIS.ps1" \
    "$stage_dir/deployment/windows/EsteliPOS-PHP.ps1" \
    "$stage_dir/deployment/windows/EsteliPOS-InstallErrors.ps1" \
    "$stage_dir/deployment/windows/assets/php-ts.zip" \
    "$stage_dir/deployment/windows/assets/rewrite_amd64_en-US.msi" \
    "$stage_dir/deployment/windows/assets/vc_redist.x64.exe" \
    "$stage_dir/deployment/windows/Verify-PHP-EsteliPOS.ps1" \
    "$stage_dir/deployment/windows/Test-EsteliPOSInstallation.ps1"; do
    if [[ ! -f "$required_path" ]]; then
        echo "Falta archivo requerido para produccion Windows: $required_path" >&2
        exit 1
    fi
done

php_sha256="$(php -r 'echo hash_file("sha256", $argv[1]);' "$stage_dir/deployment/windows/assets/php-ts.zip")"
rewrite_sha256="$(php -r 'echo hash_file("sha256", $argv[1]);' "$stage_dir/deployment/windows/assets/rewrite_amd64_en-US.msi")"
vc_redist_sha256="$(php -r 'echo hash_file("sha256", $argv[1]);' "$stage_dir/deployment/windows/assets/vc_redist.x64.exe")"
if [[ "$php_sha256" != "7b57fc9840273ab153834d0e2bd06e0bcf4fead36e381182b4b8fe9cedff3174" ]]; then
    echo "SHA256 incorrecto para php-ts.zip." >&2
    exit 1
fi
if [[ "$rewrite_sha256" != "37342ff2f585f263f34f48e9de59eb1051d61015a8e967dbde4075716230a32a" ]]; then
    echo "SHA256 incorrecto para rewrite_amd64_en-US.msi." >&2
    exit 1
fi
if [[ "$vc_redist_sha256" != "cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b" ]]; then
    echo "SHA256 incorrecto para vc_redist.x64.exe." >&2
    exit 1
fi
unzip -tq "$stage_dir/deployment/windows/assets/php-ts.zip" >/dev/null

if [[ ! -f "$stage_dir/public/build/manifest.json" || ! -f "$stage_dir/public/css/app-ui.css" ]]; then
    echo "El paquete no contiene todos los recursos web compilados (manifest.json y public/css/app-ui.css)." >&2
    exit 1
fi

rm -rf "$stage_dir/node_modules" "$stage_dir/tests" "$stage_dir/.github"
rm -f "$stage_dir/phpunit.xml" "$stage_dir/setup-windows.ps1" "$stage_dir/composer.phar"
rm -rf "$stage_dir/storage/framework/cache/data/"* "$stage_dir/storage/framework/views/"*

package_path="$release_dir/EsteliPOSProduccion1.0.zip"
(
    cd "$work_dir"
    zip -qr "$package_path" EsteliPOS
)

if command -v shasum >/dev/null 2>&1; then
    (
        cd "$release_dir"
        shasum -a 256 "$(basename "$package_path")" > "$(basename "$package_path").sha256"
    )
elif command -v sha256sum >/dev/null 2>&1; then
    (
        cd "$release_dir"
        sha256sum "$(basename "$package_path")" > "$(basename "$package_path").sha256"
    )
fi

if [[ ! -f "$release_dir/INSTALAR.bat" ]]; then
    echo "Falta deployment/INSTALAR.bat (instalador facil)." >&2
    exit 1
fi

delivery_path="$release_dir/parche1.0.zip"
rm -f "$delivery_path" "$release_dir/produccion1.0.zip" "$release_dir/produccion.zip" "$release_dir/produccion2.0.zip" "$release_dir/produccion3.0.zip"
(
    cd "$release_dir"
    zip -j "$delivery_path" EsteliPOSProduccion1.0.zip EsteliPOSProduccion1.0.zip.sha256 INSTALAR.bat
)
rm -f "$package_path" "$package_path.sha256"

package_size="$(du -h "$delivery_path" | awk '{print $1}')"
echo ""
echo "Paquete generado: $delivery_path"
echo "Tamano: $package_size"
echo "Contenido: INSTALAR.bat + EsteliPOSProduccion1.0.zip + checksum"
echo ""
echo "Enviar al tecnico:"
echo "  1. deployment/parche1.0.zip"
echo "  2. Extraer y DOBLE CLIC en INSTALAR.bat  (recomendado)"
echo "  3. Alternativa manual: extraer EsteliPOSProduccion1.0.zip y Instalar-EsteliPOS.bat"
echo "  4. ACTUALIZAR: INSTALAR.bat detecta datos y ofrece actualizar, o use"
echo "     Actualizar-EsteliPOS.bat ruta\\parche1.0.zip"
