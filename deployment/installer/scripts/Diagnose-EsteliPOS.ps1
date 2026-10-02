[CmdletBinding()]
param(
    [string]$InstallRoot
)

$ErrorActionPreference = 'Continue'
$stateRoot = Join-Path $env:ProgramData 'EsteliPOS'
$reportRoot = Join-Path $stateRoot 'diagnostics'
$reportPath = Join-Path $reportRoot ("diagnostic-{0:yyyyMMdd-HHmmss}.txt" -f (Get-Date))
New-Item -ItemType Directory -Path $reportRoot -Force | Out-Null

function Add-Result([string]$Status, [string]$Check, [string]$Detail) {
    $line = "[$Status] $Check - $Detail"
    Add-Content -LiteralPath $reportPath -Value $line -Encoding UTF8
    Write-Host $line
}

function Test-TcpPort([int]$Port) {
    $client = [Net.Sockets.TcpClient]::new()
    try {
        $result = $client.BeginConnect('127.0.0.1', $Port, $null, $null)
        return $result.AsyncWaitHandle.WaitOne(2000) -and $client.Connected
    } finally {
        $client.Dispose()
    }
}

try {
    $statePath = Join-Path $stateRoot 'installation.json'
    if (-not (Test-Path -LiteralPath $statePath -PathType Leaf)) {
        throw "No existe el registro de instalacion: $statePath"
    }
    $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
    if (-not $InstallRoot) { $InstallRoot = [string] $state.installRoot }
    $InstallRoot = [IO.Path]::GetFullPath($InstallRoot).TrimEnd('\')
    Add-Result 'INFO' 'Instalacion' "$InstallRoot; version $($state.version); URL $($state.url)"

    foreach ($relativePath in @('application\artisan', 'application\.env', 'application\public\build\manifest.json', 'php\php.exe')) {
        $path = Join-Path $InstallRoot $relativePath
        if (Test-Path -LiteralPath $path -PathType Leaf) {
            Add-Result 'OK' 'Archivo' $relativePath
        } else {
            Add-Result 'ERROR' 'Archivo faltante' $path
        }
    }

    foreach ($serviceName in @($state.apacheService, $state.mysqlService)) {
        $service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
        if ($service) {
            Add-Result $(if ($service.Status -eq 'Running') { 'OK' } else { 'ERROR' }) 'Servicio' "${serviceName}: $($service.Status)"
        } else {
            Add-Result 'ERROR' 'Servicio faltante' $serviceName
        }
    }

    foreach ($port in @([int] $state.apachePort, [int] $state.mysqlPort)) {
        Add-Result $(if (Test-TcpPort $port) { 'OK' } else { 'ERROR' }) 'Puerto local' "127.0.0.1:$port"
    }

    $php = Join-Path $InstallRoot 'php\php.exe'
    if (Test-Path -LiteralPath $php -PathType Leaf) {
        $phpVersion = (& $php -v 2>&1 | Select-Object -First 1)
        Add-Result $(if ($LASTEXITCODE -eq 0) { 'OK' } else { 'ERROR' }) 'PHP' ([string] $phpVersion)
        $modules = @(& $php -m 2>&1 | ForEach-Object { $_.ToString().Trim().ToLowerInvariant() })
        $missing = @(@('curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'mysqli', 'openssl', 'pdo_mysql', 'zip') | Where-Object { $modules -notcontains $_ })
        Add-Result $(if ($missing.Count -eq 0) { 'OK' } else { 'ERROR' }) 'Extensiones PHP' $(if ($missing.Count -eq 0) { 'completas' } else { 'faltan: ' + ($missing -join ', ') })
    }

    try {
        $response = Invoke-WebRequest -Uri $state.url -UseBasicParsing -TimeoutSec 10
        Add-Result $(if ($response.StatusCode -lt 400) { 'OK' } else { 'ERROR' }) 'HTTP EsteliPOS' "codigo $($response.StatusCode)"
    } catch {
        Add-Result 'ERROR' 'HTTP EsteliPOS' $_.Exception.Message
    }

    $latestLogs = @(
        Get-ChildItem -LiteralPath (Join-Path $stateRoot 'Logs') -File -ErrorAction SilentlyContinue
        Get-ChildItem -LiteralPath (Join-Path $InstallRoot 'application\storage\logs') -File -ErrorAction SilentlyContinue
    ) | Sort-Object LastWriteTime -Descending | Select-Object -First 5
    foreach ($log in $latestLogs) {
        Add-Result 'INFO' 'Log reciente' "$($log.FullName) ($($log.LastWriteTime))"
    }
} catch {
    Add-Result 'FATAL' 'Diagnostico' $_.Exception.Message
}

Write-Host "Reporte guardado en: $reportPath"
