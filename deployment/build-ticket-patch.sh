#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output="$project_root/deployment/parcheticket.zip"
checksum="$output.sha256"
source_dir="$project_root/deployment/ticket-patch"
receipt="$project_root/resources/views/facturacion/receipt.blade.php"

for required in \
    "$source_dir/INSTALAR-PARCHE-3.0.bat" \
    "$source_dir/Aplicar-Parche-Ticket-3.0.ps1" \
    "$source_dir/LEEME.txt" \
    "$receipt"; do
    [[ -f "$required" ]] || { echo "error: falta $required" >&2; exit 1; }
done

command -v zip >/dev/null 2>&1 || { echo "error: falta zip" >&2; exit 2; }
command -v shasum >/dev/null 2>&1 || { echo "error: falta shasum" >&2; exit 2; }

stage="$(mktemp -d "${TMPDIR:-/tmp}/estelipos-ticket-patch.XXXXXX")"
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/payload"

cp "$source_dir/INSTALAR-PARCHE-3.0.bat" "$stage/"
cp "$source_dir/Aplicar-Parche-Ticket-3.0.ps1" "$stage/"
cp "$source_dir/LEEME.txt" "$stage/"
cp "$receipt" "$stage/payload/receipt.blade.php"

(
    cd "$stage"
    shasum -a 256 payload/receipt.blade.php > SHA256SUMS.txt
    zip -q -r "$output" INSTALAR-PARCHE-3.0.bat Aplicar-Parche-Ticket-3.0.ps1 LEEME.txt SHA256SUMS.txt payload
)
shasum -a 256 "$output" > "$checksum"

echo "ticket-patch: $output"
echo "sha256: $(awk '{print $1}' "$checksum")"
