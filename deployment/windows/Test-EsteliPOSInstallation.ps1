[CmdletBinding()]
param(
    [int]$Port = 0,
    [ValidateSet("Simple", "IIS", "Auto")]
    [string]$ServerProfile = "Auto",
    [switch]$SkipSlowChecks
)

$ErrorActionPreference = "Continue"
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
$Results = [ordered]@{}
$Failures = @()

function Set-Result([string]$Name, [bool]$Passed, [string]$Detail = "") {
    $Results[$Name] = @{
        passed = $Passed
        detail = $Detail
    }
    if (-not $Passed) {
        $script:Failures += "$Name`: $Detail"
    }
}

Write-Host "`nEsteliPOS - Prueba post-instalacion ($ResolvedProfile)" -ForegroundColor Cyan

Set-Result "env_file" (Test-Path (Join-Path $ProjectRoot ".env")) "Falta .env"
if (Test-Path (Join-Path $ProjectRoot ".env")) {
    $EnvContent = Get-Content (Join-Path $ProjectRoot ".env") -Raw
    Set-Result "app_debug_off" ($EnvContent -match "APP_DEBUG=false") "APP_DEBUG debe ser false"
    Set-Result "app_env_production" ($EnvContent -match "APP_ENV=production") "APP_ENV debe ser production"
    Set-Result "app_key" ($EnvContent -match "(?m)^APP_KEY=base64:\S+") "APP_KEY no esta configurada"
    Set-Result "sqlite_configured" ($EnvContent -match "DB_CONNECTION=sqlite") "DB_CONNECTION debe ser sqlite"
    Set-Result "sqlite_busy_timeout" ($EnvContent -match "DB_BUSY_TIMEOUT=5000") "DB_BUSY_TIMEOUT debe ser 5000"
    Set-Result "sqlite_wal" ($EnvContent -match "DB_JOURNAL_MODE=WAL") "DB_JOURNAL_MODE debe ser WAL"
}

$DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
Set-Result "database_exists" (Test-Path $DatabasePath) "Falta database\database.sqlite"
if (Test-Path $DatabasePath) {
    Set-Result "database_not_empty" ((Get-Item $DatabasePath).Length -gt 1024) "Base de datos vacia o corrupta"
}

Set-Result "vendor" (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php")) "Falta vendor"
Set-Result "frontend_assets" (Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot) "Faltan assets compilados"
Set-Result "web_config" (Test-Path (Join-Path $ProjectRoot "public\web.config")) "Falta web.config"
Set-Result "deployment_config" (Test-Path (Join-Path $ProjectRoot "storage\app\deployment.json")) "Falta deployment.json"
Set-Result "network_access_page" (Test-Path (Join-Path $ProjectRoot "storage\app\deployment\acceso-red.html")) "Falta la hoja de acceso LAN"

$StorageProbe = Join-Path $ProjectRoot "storage\app\install-probe.tmp"
try {
    "ok" | Set-Content -Path $StorageProbe -Encoding ASCII
    Set-Result "storage_writable" (Test-Path $StorageProbe) "storage no es escribible"
    Remove-Item $StorageProbe -Force -ErrorAction SilentlyContinue
} catch {
    Set-Result "storage_writable" $false $_.Exception.Message
}

try {
    $LoginCode = Invoke-EsteliPOSLocalHttp -Uri "http://127.0.0.1:$Port/login" -TimeoutMs 8000
    Set-Result "http_login" ($LoginCode -ge 200 -and $LoginCode -lt 500) "HTTP $LoginCode"
} catch {
    Set-Result "http_login" $false $_.Exception.Message
}

if ($ResolvedProfile -eq "IIS") {
    Import-Module WebAdministration -ErrorAction SilentlyContinue
    $SiteName = Get-EsteliPOSIISSiteName
    if (Test-Path "IIS:\Sites\$SiteName") {
        $Site = Get-Website -Name $SiteName
        Set-Result "iis_site_started" ($Site.State -eq "Started") "Estado: $($Site.State)"
    } else {
        Set-Result "iis_site_started" $false "Sitio IIS no encontrado"
    }
    Set-Result "url_rewrite" (Test-EsteliPOSUrlRewriteModule) "Modulo URL Rewrite no instalado"
} else {
    $PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"
    Set-Result "serve_pid" (Test-Path $PidFile) "Proceso PHP no registrado"
    if (Test-Path $PidFile) {
        $ServePid = [int](Get-Content $PidFile -ErrorAction SilentlyContinue)
        $ServeProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $ServePid" -ErrorAction SilentlyContinue
        $Cmd = if ($ServeProcess) { [string]$ServeProcess.CommandLine } else { "" }
        $ListensLan = $Cmd -match "--host=0\.0\.0\.0" -or $Cmd -match "-S\s+0\.0\.0\.0:$Port"
        Set-Result "serve_lan_bind" $ListensLan "PHP debe escuchar en 0.0.0.0:$Port (cmd: $Cmd)"
    }
    Set-Result "launch_script" (Test-Path (Join-Path $PSScriptRoot "Launch-EsteliPOS.ps1")) "Falta Launch-EsteliPOS.ps1"
    Set-Result "open_bat" (Test-Path (Join-Path $ProjectRoot "Abrir-EsteliPOS.bat")) "Falta Abrir-EsteliPOS.bat"
}

try {
    $PhpPath = Resolve-EsteliPOSPhpExecutable
    Set-Result "php_available" $true $PhpPath
    if (-not $SkipSlowChecks) {
        Push-Location $ProjectRoot
        $About = & $PhpPath artisan about --only=environment 2>&1 | Out-String
        Pop-Location
        Set-Result "artisan_about" ($About -match "production") "artisan about fallo"
    }
} catch {
    Set-Result "php_available" $false $_.Exception.Message
}

$FirewallName = "EsteliPOS LAN - Puerto $Port"
Set-Result "firewall_rule" ([bool](Get-NetFirewallRule -DisplayName $FirewallName -ErrorAction SilentlyContinue)) "Falta regla de firewall $FirewallName"
Set-Result "server_task" ([bool](Get-ScheduledTask -TaskName "EsteliPOS - Servidor" -ErrorAction SilentlyContinue)) "Falta tarea de arranque"
Set-Result "backup_task" ([bool](Get-ScheduledTask -TaskName "EsteliPOS - Respaldo diario" -ErrorAction SilentlyContinue)) "Falta tarea de respaldo"

if ($DeploymentConfig -and $DeploymentConfig.lan_address -and -not $SkipSlowChecks) {
    try {
        $LanResponse = Invoke-WebRequest -Uri "http://$($DeploymentConfig.lan_address):$Port/login" -UseBasicParsing -TimeoutSec 3
        Set-Result "http_lan" ($LanResponse.StatusCode -eq 200) "HTTP LAN $($LanResponse.StatusCode)"
    } catch {
        Write-Warning "Acceso LAN no verificado: $($_.Exception.Message). Abra $($DeploymentConfig.app_url) desde otra PC o tablet."
    }
}

foreach ($Entry in $Results.GetEnumerator()) {
    $Color = if ($Entry.Value.passed) { "Green" } else { "Red" }
    $Status = if ($Entry.Value.passed) { "PASS" } else { "FAIL" }
    $Detail = if ($Entry.Value.detail) { " - $($Entry.Value.detail)" } else { "" }
    Write-Host "$Status $($Entry.Key)$Detail" -ForegroundColor $Color
}

$ReportPath = Join-Path $ProjectRoot "storage\app\deployment\post-install-report.json"
$ReportDirectory = Split-Path $ReportPath -Parent
New-Item -ItemType Directory -Force -Path $ReportDirectory | Out-Null
@{
    generated_at = (Get-Date).ToString("o")
    server_profile = $ResolvedProfile.ToLowerInvariant()
    port = $Port
    passed = ($Failures.Count -eq 0)
    failures = $Failures
    checks = $Results
} | ConvertTo-Json -Depth 5 | Set-Content -Path $ReportPath -Encoding UTF8

Write-Host "`nReporte guardado en: $ReportPath"

if ($Failures.Count -gt 0) {
    Write-Host "INSTALACION INCOMPLETA: $($Failures.Count) prueba(s) fallaron." -ForegroundColor Red
    exit 1
}

Write-Host "INSTALACION VERIFICADA: todas las pruebas pasaron." -ForegroundColor Green
exit 0
