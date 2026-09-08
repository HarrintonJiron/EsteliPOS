. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")

$ProjectRoot = Get-EsteliPOSProjectRoot
$DeploymentConfig = Get-EsteliPOSDeploymentConfig
$NetworkPage = Join-Path $ProjectRoot "storage\app\deployment\acceso-red.html"

if (-not (Test-Path $NetworkPage) -and $DeploymentConfig) {
    $NetworkPage = Write-EsteliPOSNetworkAccessPage `
        -AppUrl $DeploymentConfig.app_url `
        -LanAddress $DeploymentConfig.lan_address `
        -Port ([int]$DeploymentConfig.port) `
        -MacAddress ([string]$DeploymentConfig.mac_address) `
        -ComputerName ([string]$DeploymentConfig.computer_name)
}

if (-not (Test-Path $NetworkPage)) {
    Write-Error "No se encontro la hoja de acceso. Ejecuta primero Deploy-EsteliPOS.ps1."
    exit 1
}

Start-Process $NetworkPage
