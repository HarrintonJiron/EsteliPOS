#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_version="${1:-$(date +%Y%m%d)-$(git -C "$project_root" rev-parse --short HEAD)}"
release_dir="$project_root/releases"
work_dir="$(mktemp -d)"
archive_file="$work_dir/source.tar"
stage_dir="$work_dir/EsteliPOS"

cleanup() {
    rm -rf "$work_dir"
}
trap cleanup EXIT

if [[ -n "$(git -C "$project_root" status --porcelain)" ]]; then
    echo "El repositorio tiene cambios sin guardar. Crea un commit antes de generar el paquete." >&2
    exit 1
fi

for command_name in php composer npm zip git tar; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Falta el comando requerido: $command_name" >&2
        exit 1
    fi
done

mkdir -p "$stage_dir" "$release_dir"
git -C "$project_root" archive --format=tar HEAD -o "$archive_file"
tar -xf "$archive_file" -C "$stage_dir"

composer install --working-dir="$stage_dir" --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm --prefix "$stage_dir" ci
npm --prefix "$stage_dir" run build
php "$stage_dir/artisan" list --raw | grep -q '^app:install-production'
rm -rf "$stage_dir/node_modules" "$stage_dir/tests" "$stage_dir/.github"
rm -f "$stage_dir/phpunit.xml" "$stage_dir/setup-windows.ps1"

package_path="$release_dir/EsteliPOS-$release_version-windows.zip"
(
    cd "$work_dir"
    zip -qr "$package_path" EsteliPOS
)

if command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$package_path" > "$package_path.sha256"
elif command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$package_path" > "$package_path.sha256"
fi

echo "Paquete generado: $package_path"
