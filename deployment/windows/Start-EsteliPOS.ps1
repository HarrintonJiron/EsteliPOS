[CmdletBinding()]
param(
    [int]$Port = 0,
    [string]$HostAddress = "",
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto"
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
$LauncherLog = Join-Path $ProjectRoot "storage\logs\launcher.log"

function Write-LauncherLog([string]$Message) {
    $Line = "$(Get-Date -Format o) $Message"
    Add-Content -Path $LauncherLog -Value $Line -Encoding UTF8 -ErrorAction SilentlyContinue
}

New-Item -ItemType Directory -Force -Path (Split-Path $LauncherLog -Parent) | Out-Null

if ($ResolvedProfile -eq "IIS") {
    try {
        Start-EsteliPOSIISSite -Port $Port
    } catch {
        Write-LauncherLog "ERROR IIS: $($_.Exception.Message)"
        Write-Error $_.Exception.Message
        exit 1
    }

    if (-not (Wait-EsteliPOSHttpReady -Port $Port -Attempts 25)) {
        Write-LauncherLog "ERROR IIS no respondio en http://127.0.0.1:$Port/up"
        Write-Error "EsteliPOS (IIS) no respondio en http://127.0.0.1:$Port/up"
        exit 1
    }

    Write-LauncherLog "OK IIS listo en puerto $Port"
    exit 0
}

$ListenHost = Get-EsteliPOSSimpleListenHost -HostAddress $HostAddress
$PhpPath = Resolve-EsteliPOSPhpExecutable
$PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"
$StdOut = Join-Path $ProjectRoot "storage\logs\server-output.log"
$StdErr = Join-Path $ProjectRoot "storage\logs\server-error.log"
$PublicDir = Join-Path $ProjectRoot "public"
$Router = Join-Path $ProjectRoot "vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
$LanAddress = ""
if ($DeploymentConfig -and $DeploymentConfig.lan_address) {
    $LanAddress = [string]$DeploymentConfig.lan_address
}

if (-not (Test-Path -LiteralPath $Router)) {
    Write-Error "Falta el enrutador PHP de Laravel: $Router"
    exit 1
}

Write-LauncherLog "Iniciando Simple: `"$PhpPath`" -S ${ListenHost}:$Port (cwd=$PublicDir)"

Stop-EsteliPOSSimpleListeners -ProjectRoot $ProjectRoot -Port $Port
Start-Sleep -Seconds 1

if (Test-Path $StdOut) { Clear-Content $StdOut -ErrorAction SilentlyContinue }
if (Test-Path $StdErr) { Clear-Content $StdErr -ErrorAction SilentlyContinue }

$Process = Start-Process -FilePath $PhpPath `
    -ArgumentList @("-S", "${ListenHost}:${Port}", $Router) `
    -WorkingDirectory $PublicDir -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput $StdOut -RedirectStandardError $StdErr
$Process.Id | Set-Content -Path $PidFile -Encoding ASCII
Write-LauncherLog "Proceso iniciado PID $($Process.Id)"

Start-Sleep -Seconds 1
if ($Process.HasExited) {
    $ErrTail = ""
    if (Test-Path $StdErr) {
        $ErrTail = (Get-Content $StdErr -Raw -ErrorAction SilentlyContinue)
    }
    Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
    Write-LauncherLog "ERROR php -S salio de inmediato. stderr=$ErrTail"
    Write-Error "PHP no pudo escuchar en ${ListenHost}:$Port. $ErrTail"
    exit 1
}

$Ready = Wait-EsteliPOSHttpReady -Port $Port -Attempts 40 -Addresses @($LanAddress)
if (-not $Ready -and (Test-EsteliPOSTcpOpen -Port $Port) -and -not $Process.HasExited) {
    Write-LauncherLog "AVISO HTTP no confirmo /up, pero el puerto $Port esta en escucha. Se continua."
    $Ready = $true
}

if (-not $Ready) {
    $ErrTail = ""
    if (Test-Path $StdErr) {
        $ErrTail = (Get-Content $StdErr -Raw -ErrorAction SilentlyContinue)
    }
    if (-not $Process.HasExited) { Stop-Process -Id $Process.Id -Force -ErrorAction SilentlyContinue }
    Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
    Write-LauncherLog "ERROR no respondio. stderr=$ErrTail"
    Write-Error "EsteliPOS no respondio en http://127.0.0.1:$Port/up. Revise storage\logs\server-error.log y storage\logs\launcher.log. $ErrTail"
    exit 1
}

Write-LauncherLog "OK listo en http://0.0.0.0:$Port (comprobado via 127.0.0.1)"
exit 0
