#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

archive="${1:-deployment/parche1.0.zip}"
[[ -f "$archive" ]] || { echo "error: no existe $archive" >&2; exit 2; }
command -v unzip >/dev/null 2>&1 || { echo "error: falta unzip" >&2; exit 2; }

echo "release: $(basename "$archive")"
bash -n deployment/build-release.sh
composer validate --no-check-publish --no-interaction >/dev/null
php artisan test tests/Feature/DeploymentArtifactsTest.php

tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/estelipos-release.XXXXXX")"
trap 'rm -rf "$tmp_dir"' EXIT

outer_entries="$(unzip -Z1 "$archive")"
for required in INSTALAR.bat INSTALAR-CONSOLA.bat EsteliPOSProduccion1.0.zip EsteliPOSProduccion1.0.zip.sha256; do
    grep -Fxq "$required" <<< "$outer_entries" || { echo "error: falta $required" >&2; exit 1; }
done

unzip -p "$archive" EsteliPOSProduccion1.0.zip > "$tmp_dir/EsteliPOSProduccion1.0.zip"
unzip -p "$archive" EsteliPOSProduccion1.0.zip.sha256 > "$tmp_dir/EsteliPOSProduccion1.0.zip.sha256"
(
    cd "$tmp_dir"
    shasum -a 256 -c EsteliPOSProduccion1.0.zip.sha256 >/dev/null
)

inner="$tmp_dir/EsteliPOSProduccion1.0.zip"
package_version="$(unzip -p "$inner" EsteliPOS/VERSION | tr -d '\r\n')"
source_version="$(tr -d '\r\n' < VERSION)"
[[ "$package_version" == "$source_version" ]] || {
    echo "error: VERSION del paquete ($package_version) != repositorio ($source_version)" >&2
    exit 1
}

unzip -Z1 "$inner" > "$tmp_dir/inner-entries.txt"
if grep -Eq '^EsteliPOS/(\.env$|\.env\.backup($|-)|\.env\.production$|\.env\.local$|database/database\.sqlite([.-]|$)|node_modules/|tests/)' "$tmp_dir/inner-entries.txt"; then
    echo "error: el paquete contiene datos o archivos de desarrollo prohibidos" >&2
    exit 1
fi
if grep -Eq '^EsteliPOS/deployment/client-inventory/|^EsteliPOS/scripts/extract-client-inventory\.py$|inventario-cisve-4094' "$tmp_dir/inner-entries.txt"; then
    echo "error: el paquete contiene el inventario real del cliente" >&2
    exit 1
fi
if grep -E '^EsteliPOS/storage/app/' "$tmp_dir/inner-entries.txt" | grep -Ev '/$' >/dev/null; then
    echo "error: el paquete contiene archivos persistentes de storage/app" >&2
    exit 1
fi

unzip -p "$inner" EsteliPOS/resources/views/facturacion/receipt.blade.php > "$tmp_dir/receipt.blade.php"
unzip -p "$inner" EsteliPOS/deployment/windows/Update-EsteliPOS.ps1 > "$tmp_dir/Update-EsteliPOS.ps1"
grep -Fq 'color: #000 !important' "$tmp_dir/receipt.blade.php"
grep -Fq 'PRAGMA quick_check' "$tmp_dir/Update-EsteliPOS.ps1"

echo "release: OK version=$package_version sha256=$(shasum -a 256 "$archive" | awk '{print $1}')"
