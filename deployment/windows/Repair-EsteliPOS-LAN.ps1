[CmdletBinding()]
param(
    [int]$Port = 0
)

<#
.SYNOPSIS
    Repara una instalacion Simple que solo escucha en 127.0.0.1
    y recrea tarea, firewall y acceso directo correctos.
#>

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

$ResolvedProfile = Get-EsteliPOSResolvedServerProfile -ServerProfile Auto
Write-Host "Reparando arranque EsteliPOS en $ProjectRoot (perfil $ResolvedProfile, puerto $Port)" -ForegroundColor Cyan

& (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1") -ServerProfile $ResolvedProfile

# Matar php -S 127.0.0.1 huerfanos del proyecto
$Orphans = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
    Where-Object {
        $_.CommandLine -and
        $_.CommandLine -match "-S\s+127\.0\.0\.1:$Port" -and
        $_.CommandLine -match [regex]::Escape($ProjectRoot)
    }
foreach ($Orphan in $Orphans) {
    Write-Host "Deteniendo php localhost-only PID $($Orphan.ProcessId)" -ForegroundColor Yellow
    & taskkill.exe /PID $Orphan.ProcessId /T /F | Out-Null
}

$LanAddress = Get-EsteliPOSLanAddress
if ([string]::IsNullOrWhiteSpace($LanAddress)) {
    throw "No se pudo detectar la IPv4 LAN. Conecte el equipo a la red e intente de nuevo."
}

$AppUrl = "http://${LanAddress}:$Port"
$MacAddress = Get-EsteliPOSMacAddress

if ($DeploymentConfig) {
    $ConfigHash = @{}
    $DeploymentConfig.PSObject.Properties | ForEach-Object { $ConfigHash[$_.Name] = $_.Value }
    $ConfigHash["port"] = $Port
    $ConfigHash["lan_address"] = $LanAddress
    $ConfigHash["app_url"] = $AppUrl
    $ConfigHash["server_profile"] = $ResolvedProfile
    Save-EsteliPOSDeploymentConfig -Config $ConfigHash
} else {
    Save-EsteliPOSDeploymentConfig -Config @{
        port = $Port
        lan_address = $LanAddress
        app_url = $AppUrl
        server_profile = $ResolvedProfile
        installed_at = (Get-Date -Format o)
    }
}

# Actualizar APP_URL en .env
$EnvPath = Join-Path $ProjectRoot ".env"
if (Test-Path $EnvPath) {
    $EnvText = Get-Content $EnvPath -Raw
    if ($EnvText -match "(?m)^APP_URL=.*$") {
        $EnvText = [regex]::Replace($EnvText, "(?m)^APP_URL=.*$", "APP_URL=$AppUrl")
    } else {
        $EnvText = $EnvText.TrimEnd() + "`r`nAPP_URL=$AppUrl`r`n"
    }
    [System.IO.File]::WriteAllText($EnvPath, $EnvText, (New-Object System.Text.UTF8Encoding($false)))
}

Register-EsteliPOSFirewallRule -Port $Port
$StartScript = Join-Path $PSScriptRoot "Start-EsteliPOS.ps1"
Register-EsteliPOSServerTask -StartScript $StartScript -Port $Port -ServerProfile $ResolvedProfile

$NetworkPage = Write-EsteliPOSNetworkAccessPage -AppUrl $AppUrl -LanAddress $LanAddress -Port $Port -MacAddress $MacAddress

$BrowserCandidates = @(
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe"
)
$BrowserPath = $BrowserCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
$Desktop = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $Desktop "EsteliPOS.lnk"
$OpenBat = Join-Path $ProjectRoot "Abrir-EsteliPOS.bat"
$LaunchScript = Join-Path $PSScriptRoot "Launch-EsteliPOS.ps1"
$Shell = New-Object -ComObject WScript.Shell
$Shortcut = $Shell.CreateShortcut($ShortcutPath)
if (Test-Path $OpenBat) {
    $Shortcut.TargetPath = $OpenBat
    $Shortcut.Arguments = ""
} else {
    $Shortcut.TargetPath = "powershell.exe"
    $Shortcut.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$LaunchScript`" -Port $Port -ServerProfile $ResolvedProfile"
}
$Shortcut.WorkingDirectory = $ProjectRoot
if ($BrowserPath) {
    $Shortcut.IconLocation = "$BrowserPath,0"
}
$Shortcut.Description = "Inicia EsteliPOS y abre $AppUrl"
$Shortcut.Save()

if ($ResolvedProfile -eq "Simple") {
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port -ServerProfile Simple -HostAddress "0.0.0.0"
} else {
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port -ServerProfile IIS
}

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo iniciar el servidor tras la reparacion. Revise storage\logs\launcher.log"
}

Write-Host ""
Write-Host "REPARACION COMPLETADA" -ForegroundColor Green
Write-Host "URL LAN: $AppUrl"
Write-Host "Hoja de acceso: $NetworkPage"
Write-Host "Acceso directo: $ShortcutPath"
Write-Host "Firewall + tarea de arranque actualizados."
Write-Host "Abra EsteliPOS desde el acceso directo (no desde un Chrome App aislado)."
