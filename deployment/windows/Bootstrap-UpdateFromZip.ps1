[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$UpdateZip,
    [Parameter(Mandatory = $true)][string]$WindowsDir
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $UpdateZip)) {
    throw "No existe el ZIP: $UpdateZip"
}

$WindowsDir = $WindowsDir.Trim().TrimEnd('\', '/')
if ([string]::IsNullOrWhiteSpace($WindowsDir)) {
    throw "WindowsDir vacio."
}

$tmp = Join-Path $env:TEMP ("estelipos-upd-boot-" + [guid]::NewGuid().ToString())
New-Item -ItemType Directory -Force -Path $tmp | Out-Null

try {
    Expand-Archive -LiteralPath $UpdateZip -DestinationPath $tmp -Force

    $source = $null
    $direct = Join-Path $tmp "EsteliPOS\deployment\windows\Update-EsteliPOS.ps1"
    if (Test-Path -LiteralPath $direct) {
        $source = Split-Path -Parent $direct
    }

    if (-not $source) {
        $inner = Get-ChildItem -Path $tmp -Filter "EsteliPOS*.zip" -File -Recurse -ErrorAction SilentlyContinue |
            Select-Object -First 1
        if (-not $inner) {
            throw "No se encontro EsteliPOSProduccion*.zip dentro del paquete."
        }

        $nested = Join-Path $tmp "inner"
        New-Item -ItemType Directory -Force -Path $nested | Out-Null
        Expand-Archive -LiteralPath $inner.FullName -DestinationPath $nested -Force
        $direct2 = Join-Path $nested "EsteliPOS\deployment\windows\Update-EsteliPOS.ps1"
        if (-not (Test-Path -LiteralPath $direct2)) {
            throw "No se encontro Update-EsteliPOS.ps1 dentro del ZIP."
        }
        $source = Split-Path -Parent $direct2
    }

    New-Item -ItemType Directory -Force -Path $WindowsDir | Out-Null

    Copy-Item -LiteralPath (Join-Path $source "Update-EsteliPOS.ps1") `
        -Destination (Join-Path $WindowsDir "Update-EsteliPOS.ps1") -Force
    Copy-Item -LiteralPath (Join-Path $source "EsteliPOS-Common.ps1") `
        -Destination (Join-Path $WindowsDir "EsteliPOS-Common.ps1") -Force

    foreach ($optional in @(
            "Install-EsteliPOS-GUI.ps1",
            "EsteliPOS-IIS.ps1",
            "Actualizar-EsteliPOS.bat",
            "Bootstrap-UpdateFromZip.ps1",
            "Repair-EsteliPOS-LAN.ps1",
            "Launch-EsteliPOS.ps1",
            "Start-EsteliPOS.ps1",
            "Stop-EsteliPOS.ps1",
            "Test-EsteliPOSInstallation.ps1"
        )) {
        $candidate = Join-Path $source $optional
        if (Test-Path -LiteralPath $candidate) {
            Copy-Item -LiteralPath $candidate -Destination (Join-Path $WindowsDir $optional) -Force
        }
    }

    Write-Host "Motor de actualizacion listo desde el paquete." -ForegroundColor Green
} finally {
    if (Test-Path -LiteralPath $tmp) {
        Remove-Item -LiteralPath $tmp -Recurse -Force -ErrorAction SilentlyContinue
    }
}
