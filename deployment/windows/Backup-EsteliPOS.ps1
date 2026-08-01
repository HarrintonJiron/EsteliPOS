[CmdletBinding()]
param(
    [int]$Port = 8080,
    [int]$RetentionDays = 30,
    [string]$ExternalBackupPath = ""
)

$ErrorActionPreference = "Stop"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
$BackupDirectory = Join-Path $ProjectRoot "storage\app\backups"
$LogPath = Join-Path $ProjectRoot "storage\logs\backup.log"
$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

New-Item -ItemType Directory -Force -Path $BackupDirectory | Out-Null

try {
    if (-not (Test-Path $DatabasePath)) { throw "No existe la base SQLite: $DatabasePath" }

    & (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1")
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
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "Start-EsteliPOS.ps1") -Port $Port
}
