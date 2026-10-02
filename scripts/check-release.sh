#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

version="$(tr -d '\r\n' < VERSION)"
installer="${1:-deployment/installer/dist/EsteliPOS-Setup-$version.exe}"

echo "installer: Apache + PHP + MySQL"
echo "version: $version"

for required in \
    deployment/installer/EsteliPOS.nsi \
    deployment/installer/scripts/Build-EsteliPOSInstaller.ps1 \
    deployment/installer/scripts/Install-EsteliPOS.ps1 \
    deployment/installer/scripts/Test-EsteliPOSInstaller.ps1 \
    deployment/installer/scripts/Uninstall-EsteliPOS.ps1; do
    [[ -f "$required" ]] || { echo "error: falta $required" >&2; exit 1; }
done

for forbidden in deployment/windows deployment/ticket-patch deployment/client-inventory deployment/INSTALAR.bat; do
    [[ ! -e "$forbidden" ]] || { echo "error: permanece el instalador legado: $forbidden" >&2; exit 1; }
done

composer validate --no-check-publish --no-interaction >/dev/null
php artisan test tests/Feature/DeploymentArtifactsTest.php

if [[ ! -f "$installer" ]]; then
    echo "source: OK"
    echo "artifact: PENDIENTE ($installer no existe)"
    exit 2
fi

checksum="$installer.sha256"
[[ -f "$checksum" ]] || { echo "error: falta $checksum" >&2; exit 1; }
(
    cd "$(dirname "$installer")"
    shasum -a 256 -c "$(basename "$checksum")" >/dev/null
)

case "$(basename "$installer")" in
    "EsteliPOS-Setup-$version.exe") ;;
    *) echo "error: el instalador no corresponde a VERSION=$version" >&2; exit 1 ;;
esac

echo "release: OK version=$version sha256=$(shasum -a 256 "$installer" | awk '{print $1}')"
