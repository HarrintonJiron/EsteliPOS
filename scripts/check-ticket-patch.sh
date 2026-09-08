#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

archive="${1:-deployment/parcheticket.zip}"
[[ -f "$archive" ]] || { echo "error: no existe $archive" >&2; exit 2; }

bash -n deployment/build-ticket-patch.sh
php artisan test tests/Feature/SaleReceiptDiscountTest.php tests/Feature/TicketPatchArtifactTest.php

tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/estelipos-ticket-check.XXXXXX")"
trap 'rm -rf "$tmp_dir"' EXIT
unzip -q "$archive" -d "$tmp_dir"

for required in \
    INSTALAR-PARCHE-3.0.bat \
    Aplicar-Parche-Ticket-3.0.ps1 \
    LEEME.txt \
    SHA256SUMS.txt \
    payload/receipt.blade.php; do
    [[ -f "$tmp_dir/$required" ]] || { echo "error: falta $required" >&2; exit 1; }
done

(
    cd "$tmp_dir"
    shasum -a 256 -c SHA256SUMS.txt >/dev/null
)

cmp -s resources/views/facturacion/receipt.blade.php "$tmp_dir/payload/receipt.blade.php" || {
    echo "error: el ticket empaquetado no coincide con el repositorio" >&2
    exit 1
}

entries="$tmp_dir/entries.txt"
unzip -Z1 "$archive" > "$entries"
if rg -q '(^|/)(database\.sqlite|\.env|vendor|app|migrations)(/|$)' "$entries"; then
    echo "error: el parche contiene archivos fuera del alcance del ticket" >&2
    exit 1
fi

updater="$tmp_dir/Aplicar-Parche-Ticket-3.0.ps1"
rg -q 'PRAGMA quick_check' "$updater"
rg -q 'artisan view:clear' "$updater"
rg -q 'DatabaseHashBefore' "$updater"
if rg -q 'artisan migrate|Copy-Item[^\n]*DatabasePath|Move-Item[^\n]*DatabasePath|Remove-Item[^\n]*DatabasePath' "$updater"; then
    echo "error: el parche intenta migrar o reemplazar la base" >&2
    exit 1
fi

echo "ticket-patch: OK sha256=$(shasum -a 256 "$archive" | awk '{print $1}')"
