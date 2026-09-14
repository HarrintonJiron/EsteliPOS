[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [int]$ExitCode,

    [string]$DetailMessage = "",

    [switch]$NoWait
)

. (Join-Path $PSScriptRoot "EsteliPOS-InstallErrors.ps1")

. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
$ProjectRoot = Get-EsteliPOSProjectRoot
$LogDir = Join-Path $ProjectRoot "storage\logs"
$LatestLog = Get-ChildItem -Path $LogDir -Filter "install-*.log" -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending |
    Select-Object -First 1
$LogPath = if ($LatestLog) { $LatestLog.FullName } else { "" }

Show-EsteliPOSInstallError -ExitCode $ExitCode -DetailMessage $DetailMessage -LogPath $LogPath

if (-not $NoWait) {
    Read-Host "Presione Enter para cerrar"
}
