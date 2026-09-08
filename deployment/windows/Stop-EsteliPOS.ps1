[CmdletBinding()]
param(
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto",
    [int]$Port = 0
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$ResolvedProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile $ServerProfile
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
if ($Port -le 0 -and $DeploymentConfig) {
    $Port = [int]$DeploymentConfig.port
}

if ($ResolvedProfile -eq "IIS") {
    Stop-EsteliPOSIISSite
    exit 0
}

if ($Port -le 0) {
    $Port = 8080
}

Stop-EsteliPOSSimpleListeners -ProjectRoot $ProjectRoot -Port $Port
exit 0
