[CmdletBinding()]
param(
    [ValidateSet("Simple", "IIS")]
    [string]$ServerProfile = "IIS"
)

$ErrorActionPreference = "Continue"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$RequiredExtensions = @("ctype", "dom", "fileinfo", "gd", "mbstring", "openssl", "pdo_sqlite", "sqlite3", "tokenizer", "xml", "zip")
$Failures = @()
$Warnings = @()

function Add-Failure([string]$Message) {
    script:Failures += $Message
    Write-Host "FALLO: $Message" -ForegroundColor Red
}

function Add-Warning([string]$Message) {
    script:Warnings += $Message
    Write-Host "AVISO: $Message" -ForegroundColor Yellow
}

function Add-Pass([string]$Message) {
    Write-Host "OK: $Message" -ForegroundColor Green
}

Write-Host "`nEsteliPOS - Verificacion previa de PHP ($ServerProfile)" -ForegroundColor Cyan
Write-Host "============================================================`n"

$Php = Get-Command php.exe -ErrorAction SilentlyContinue
if (-not $Php) {
    Add-Failure "PHP no esta en el PATH. El instalador intentara instalarlo automaticamente (requiere internet o php-ts.zip en assets)."
} else {
    Add-Pass "PHP encontrado en $($Php.Source)"
    $Version = & $Php.Source -r "echo PHP_VERSION;"
    if ([version]$Version -lt [version]"8.4.1") {
        Add-Failure "Se requiere PHP 8.4.1 o superior. Version actual: $Version"
    } else {
        Add-Pass "Version PHP $Version"
    }

    $Loaded = @(& $Php.Source -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
    foreach ($Extension in $RequiredExtensions) {
        if ($Loaded -contains $Extension) {
            Add-Pass "Extension $Extension"
        } else {
            Add-Failure "Falta extension PHP: $Extension"
        }
    }

    if ($ServerProfile -eq "IIS") {
        $PhpCgi = Join-Path (Split-Path $Php.Source -Parent) "php-cgi.exe"
        if (Test-Path $PhpCgi) {
            Add-Pass "php-cgi.exe encontrado (PHP Thread Safe)"
        } else {
            Add-Failure "No existe php-cgi.exe. Instale PHP Thread Safe (TS), no Non-Thread Safe (NTS)."
        }
    }
}

$ProjectRoot = Get-EsteliPOSProjectRoot
if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php"))) {
    Add-Failure "Falta vendor\autoload.php. Use el ZIP generado con deployment/build-release.sh."
} else {
    Add-Pass "Dependencias PHP (vendor) presentes"
}

if (-not (Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot)) {
    Add-Failure "Faltan assets compilados en public/build o public/css/app-ui.css."
} else {
    Add-Pass "Assets frontend compilados"
}

if (-not (Test-Path (Join-Path $ProjectRoot "public\web.config"))) {
    Add-Failure "Falta public\web.config (requerido para IIS)."
} else {
    Add-Pass "public\web.config presente"
}

$Edge = @(
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if ($Edge) {
    Add-Pass "Navegador compatible encontrado"
} else {
    Add-Failure "Instale Microsoft Edge o Google Chrome."
}

$Identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$Principal = New-Object Security.Principal.WindowsPrincipal($Identity)
if ($Principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Add-Pass "PowerShell ejecutado como administrador"
} else {
    Add-Warning "No se detectaron privilegios de administrador. El instalador los requiere."
}

if ($ServerProfile -eq "IIS") {
    $IisFeature = Get-WindowsOptionalFeature -Online -FeatureName IIS-WebServerRole -ErrorAction SilentlyContinue
    if ($IisFeature -and $IisFeature.State -eq "Enabled") {
        Add-Pass "IIS ya esta activado"
    } else {
        Add-Pass "IIS no esta activo; el instalador lo instalara automaticamente (opcion 1)."
    }
}

Write-Host "`n============================================================"
Write-Host "Resumen: $($Failures.Count) fallo(s), $($Warnings.Count) aviso(s)" -ForegroundColor $(if ($Failures.Count -eq 0) { "Green" } else { "Red" })

if ($Failures.Count -gt 0) {
    Write-Host "`nCorrija los fallos antes de instalar." -ForegroundColor Red
    Write-Host "Ejecute el instalador y elija opcion 3 (solo verificar) o:" -ForegroundColor Yellow
    Write-Host "  powershell -File deployment\windows\Show-InstallError.ps1 -ExitCode 2" -ForegroundColor Gray
    exit 2
}

Write-Host "`nEntorno listo para Deploy-EsteliPOS.ps1 -ServerProfile $ServerProfile" -ForegroundColor Green
exit 0
