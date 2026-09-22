[CmdletBinding()]
param(
    [Parameter(Mandatory)][string]$InstallRoot
)

$ErrorActionPreference = 'Stop'
$InstallRoot = [IO.Path]::GetFullPath($InstallRoot).TrimEnd('\')
$stateRoot = Join-Path $env:ProgramData 'EsteliPOS'
$logRoot = Join-Path $stateRoot 'Logs'
$logPath = Join-Path $logRoot ("uninstaller-{0:yyyyMMdd-HHmmss}.log" -f (Get-Date))
New-Item -ItemType Directory -Path $logRoot -Force | Out-Null

function Write-UninstallLog([string]$Message) {
    Add-Content -LiteralPath $logPath -Value ("{0} {1}" -f (Get-Date -Format 's'), $Message) -Encoding UTF8
}

function Get-InstallSuffix([string]$Path) {
    $normalized = [IO.Path]::GetFullPath($Path).TrimEnd('\').ToLowerInvariant()
    $sha = [Security.Cryptography.SHA256]::Create()
    try {
        $hash = $sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($normalized))
        return ([BitConverter]::ToString($hash) -replace '-', '').Substring(0, 8)
    } finally {
        $sha.Dispose()
    }
}

try {
    $suffix = Get-InstallSuffix $InstallRoot
    $statePath = Join-Path $stateRoot "installation-$suffix.json"
    $serviceNames = @("EsteliPOSApache_$suffix", "EsteliPOSMySQL_$suffix")
    if (Test-Path -LiteralPath $statePath -PathType Leaf) {
        $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
        if ($state.installRoot -eq $InstallRoot) {
            $serviceNames = @($state.apacheService, $state.mysqlService) | Where-Object { $_ }
        }
    }

    foreach ($serviceName in $serviceNames) {
        $service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
        if ($service) {
            if ($service.Status -ne 'Stopped') {
                Stop-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
                $service.WaitForStatus('Stopped', [TimeSpan]::FromSeconds(20))
            }
            & sc.exe delete $serviceName | Out-Null
            Write-UninstallLog "[OK] Servicio eliminado: $serviceName"
        }
    }

    if (Test-Path -LiteralPath $statePath -PathType Leaf) {
        $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
        $state | Add-Member -NotePropertyName uninstalledAt -NotePropertyValue (Get-Date).ToString('o') -Force
        $state | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $statePath -Encoding UTF8
    }
    Write-UninstallLog '[OK] Servicios detenidos. Los datos persistentes se conservaron en ProgramData.'
} catch {
    Write-UninstallLog "[ERROR] $($_.Exception.Message)"
    throw
}
