[CmdletBinding()]
param(
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto"
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$ResolvedProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile $ServerProfile

if ($ResolvedProfile -eq "IIS") {
    Stop-EsteliPOSIISSite
    exit 0
}

$PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"

if (-not (Test-Path $PidFile)) { exit 0 }

$ServerPid = [int](Get-Content $PidFile -ErrorAction SilentlyContinue)
$ServerProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $ServerPid" -ErrorAction SilentlyContinue
if ($ServerProcess -and $ServerProcess.CommandLine -match "artisan\s+serve") {
    & taskkill.exe /PID $ServerPid /T /F | Out-Null
}
Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
