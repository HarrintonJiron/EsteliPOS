[CmdletBinding()]
param(
    [int]$Port = 0,
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto",
    [switch]$PreferLocalhost,
    [switch]$SkipBrowser
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
if ($Port -le 0 -and $DeploymentConfig) {
    $Port = [int]$DeploymentConfig.port
}
if ($Port -le 0) {
    $Port = 8080
}

$ResolvedProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile $ServerProfile
$StartScript = Join-Path $PSScriptRoot "Start-EsteliPOS.ps1"
$LauncherLog = Join-Path $ProjectRoot "storage\logs\launcher.log"

function Write-LauncherLog([string]$Message) {
    New-Item -ItemType Directory -Force -Path (Split-Path $LauncherLog -Parent) | Out-Null
    Add-Content -Path $LauncherLog -Value "$(Get-Date -Format o) LAUNCH $Message" -Encoding UTF8 -ErrorAction SilentlyContinue
}

Write-Host "Iniciando EsteliPOS..." -ForegroundColor Cyan
Write-LauncherLog "Launch profile=$ResolvedProfile port=$Port"

$StartArguments = @(
    "-NoProfile",
    "-ExecutionPolicy", "Bypass",
    "-File", $StartScript,
    "-Port", "$Port",
    "-ServerProfile", $ResolvedProfile
)
if ($ResolvedProfile -eq "Simple") {
    $StartArguments += @("-HostAddress", "0.0.0.0")
}

& powershell.exe @StartArguments
if ($LASTEXITCODE -ne 0) {
    Write-LauncherLog "ERROR Start-EsteliPOS exit=$LASTEXITCODE"
    Write-Host ""
    Write-Host "No se pudo iniciar EsteliPOS." -ForegroundColor Red
    Write-Host "Revise:" -ForegroundColor Yellow
    Write-Host "  $LauncherLog"
    Write-Host "  $(Join-Path $ProjectRoot 'storage\logs\server-error.log')"
    if (-not $SkipBrowser) {
        Read-Host "Presione Enter para cerrar"
    }
    exit 1
}

if (-not (Wait-EsteliPOSHttpReady -Port $Port -Attempts 10)) {
    Write-LauncherLog "ERROR HTTP no listo tras Start"
    Write-Host "El servidor arranco pero aun no responde en el puerto $Port." -ForegroundColor Red
    if (-not $SkipBrowser) {
        Read-Host "Presione Enter para cerrar"
    }
    exit 1
}

$AppUrl = Get-EsteliPOSAppUrl -Port $Port -PreferLocalhost:$PreferLocalhost
Write-LauncherLog "OK abriendo $AppUrl"
Write-Host "Listo: $AppUrl" -ForegroundColor Green

if ($SkipBrowser) {
    exit 0
}

$BrowserCandidates = @(
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe"
)
$BrowserPath = $BrowserCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
$BrowserProfile = Join-Path $ProjectRoot "storage\app\browser-profile"

if ($BrowserPath) {
    Start-Process -FilePath $BrowserPath -ArgumentList @(
        "--app=`"$AppUrl`"",
        "--kiosk-printing",
        "--user-data-dir=`"$BrowserProfile`""
    )
} else {
    Start-Process $AppUrl
}

exit 0
