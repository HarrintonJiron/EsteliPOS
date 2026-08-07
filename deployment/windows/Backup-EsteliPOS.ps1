[CmdletBinding()]
param(
    [int]$Port = 8080,
    [int]$RetentionDays = 30,
    [string]$ExternalBackupPath = "",
    [string]$HostAddress = "127.0.0.1",
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto"
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
$BackupDirectory = Join-Path $ProjectRoot "storage\app\backups"
$LogPath = Join-Path $ProjectRoot "storage\logs\backup.log"
$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null

try {
    if (-not (Test-Path $DatabasePath)) { throw "No existe la base SQLite: $DatabasePath" }

    & (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1") -ServerProfile $ServerProfile
    $Destination = Join-Path $BackupDirectory "estelipos-$Timestamp.sqlite"
    Copy-Item $DatabasePath $Destination -Force

    if (-not [string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        New-Item -ItemType Directory -Force -Path $ExternalBackupPath | Out-Null
        Copy-Item $Destination (Join-Path $ExternalBackupPath (Split-Path $Destination -Leaf)) -Force
        Get-ChildItem $ExternalBackupPath -Filter "estelipos-*.sqlite" -File |
            Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } |
            Remove-Item -Force
    }

    Get-ChildItem $BackupDirectory -Filter "estelipos-*.sqlite" -File |
        Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) } |
        Remove-Item -Force

    Add-Content $LogPath "$(Get-Date -Format s) OK $Destination"
} catch {
    Add-Content $LogPath "$(Get-Date -Format s) ERROR $($_.Exception.Message)"
    throw
} finally {
    $StartScript = Join-Path $PSScriptRoot "Start-EsteliPOS.ps1"
    $ResolvedProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile $ServerProfile
    if ($ResolvedProfile -eq "Simple") {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port -ServerProfile Simple -HostAddress $HostAddress
    } else {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port -ServerProfile IIS
    }
}
