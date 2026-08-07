[CmdletBinding()]
param(
    [int]$Port = 8080,
    [string]$AdminName = "Administrador",
    [string]$AdminEmail = "",
    [string]$ExternalBackupPath = "",
    [string]$LanAddress = ""
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
$ProjectRoot = Get-EsteliPOSProjectRoot
$PhpCommand = Get-Command php.exe -ErrorAction SilentlyContinue

function Write-Step([string]$Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Stop-WithError([string]$Message) {
    Write-Host "`nERROR: $Message" -ForegroundColor Red
    Read-Host "Presiona Enter para cerrar"
    exit 1
}

function Set-EnvValue([string]$Path, [string]$Name, [string]$Value) {
    $Lines = Get-Content $Path
    $Replacement = "$Name=$Value"
    if ($Lines -match "^$([regex]::Escape($Name))=") {
        $Lines = $Lines -replace "^$([regex]::Escape($Name))=.*$", $Replacement
    } else {
        $Lines += $Replacement
    }
    [System.IO.File]::WriteAllLines($Path, $Lines, (New-Object System.Text.UTF8Encoding($false)))
}

try {
    Write-Host "EsteliPOS - Instalacion local para Windows" -ForegroundColor Green
    Write-Host "Northlink Microsystem"

    $CurrentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $CurrentPrincipal = New-Object Security.Principal.WindowsPrincipal($CurrentIdentity)
    if (-not $CurrentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Stop-WithError "Abre PowerShell con la opcion Ejecutar como administrador y vuelve a intentarlo."
    }

    Write-Step "Comprobando PHP y el paquete"
    if (-not $PhpCommand) {
        Stop-WithError "PHP 8.4.1 o superior no esta instalado o no esta agregado al PATH."
    }

    $PhpPath = $PhpCommand.Source
    $PhpVersion = & $PhpPath -r "echo PHP_VERSION;"
    if ([version]$PhpVersion -lt [version]"8.4.1") {
        Stop-WithError "Se requiere PHP 8.4.1 o superior. Version encontrada: $PhpVersion"
    }

    $RequiredExtensions = @("ctype", "dom", "fileinfo", "gd", "mbstring", "openssl", "pdo_sqlite", "sqlite3", "tokenizer", "xml", "zip")
    $LoadedExtensions = @(& $PhpPath -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
    $MissingExtensions = @($RequiredExtensions | Where-Object { $LoadedExtensions -notcontains $_ })
    if ($MissingExtensions.Count -gt 0) {
        Stop-WithError "Faltan extensiones de PHP: $($MissingExtensions -join ', '). Habilitalas en php.ini."
    }

    if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php"))) {
        Stop-WithError "El paquete no incluye vendor. Genera el ZIP con deployment/build-release.sh."
    }
    if (-not (Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot)) {
        Stop-WithError "El paquete no contiene los recursos web compilados."
    }
    if ($Port -lt 1024 -or $Port -gt 65535) {
        Stop-WithError "El puerto debe estar entre 1024 y 65535."
    }

    $LanAddress = Get-EsteliPOSLanAddress -PreferredAddress $LanAddress
    if ([string]::IsNullOrWhiteSpace($LanAddress)) {
        Stop-WithError "No se encontro una direccion IPv4 de red local. Conecta la PC al Wi-Fi y vuelve a intentar."
    }

    $MacAddress = Get-EsteliPOSMacAddress
    $AppUrl = "http://${LanAddress}:$Port"

    $ActiveNetwork = Get-NetConnectionProfile |
        Where-Object { $_.IPv4Connectivity -ne "Disconnected" } |
        Select-Object -First 1
    if ($ActiveNetwork -and $ActiveNetwork.NetworkCategory -eq "Public") {
        Set-NetConnectionProfile -InterfaceIndex $ActiveNetwork.InterfaceIndex -NetworkCategory Private
        Write-Host "La red activa se configuro como privada para permitir el acceso de otros dispositivos."
    }

    Set-Location $ProjectRoot
    $EnvPath = Join-Path $ProjectRoot ".env"
    $EnvTemplate = Join-Path $ProjectRoot ".env.production.example"
    $DatabasePath = Join-Path $ProjectRoot "database\database.sqlite"
    $DatabaseEnvPath = $DatabasePath.Replace("\", "/")

    Write-Step "Configurando el entorno de produccion"
    if (-not (Test-Path $EnvPath)) {
        Copy-Item $EnvTemplate $EnvPath
    } else {
        $BackupEnv = "$EnvPath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Copy-Item $EnvPath $BackupEnv
        Write-Host "Se respaldo la configuracion anterior en $BackupEnv"
    }

    Set-EnvValue $EnvPath "APP_ENV" "production"
    Set-EnvValue $EnvPath "APP_DEBUG" "false"
    Set-EnvValue $EnvPath "APP_URL" $AppUrl
    Set-EnvValue $EnvPath "DB_CONNECTION" "sqlite"
    Set-EnvValue $EnvPath "DB_DATABASE" "`"$DatabaseEnvPath`""
    Set-EnvValue $EnvPath "DB_BUSY_TIMEOUT" "5000"
    Set-EnvValue $EnvPath "DB_JOURNAL_MODE" "WAL"
    Set-EnvValue $EnvPath "DB_SYNCHRONOUS" "NORMAL"
    Set-EnvValue $EnvPath "DB_TRANSACTION_MODE" "IMMEDIATE"
    Set-EnvValue $EnvPath "SESSION_DRIVER" "database"
    Set-EnvValue $EnvPath "CACHE_STORE" "database"
    Set-EnvValue $EnvPath "QUEUE_CONNECTION" "database"
    Set-EnvValue $EnvPath "SEED_DEMO_DATA" "false"

    if (-not (Test-Path $DatabasePath)) {
        New-Item -ItemType File -Path $DatabasePath | Out-Null
    }
    New-Item -ItemType Directory -Force -Path (Join-Path $ProjectRoot "storage\logs") | Out-Null
    New-Item -ItemType Directory -Force -Path (Join-Path $ProjectRoot "storage\app\backups") | Out-Null

    & $PhpPath artisan key:generate --force
    if ($LASTEXITCODE -ne 0) { Stop-WithError "No se pudo generar APP_KEY." }

    if ([string]::IsNullOrWhiteSpace($AdminEmail)) {
        $AdminEmail = Read-Host "Correo del administrador"
    }
    if ([string]::IsNullOrWhiteSpace($AdminEmail)) {
        Stop-WithError "El correo del administrador es obligatorio."
    }

    Write-Step "Instalando base de datos y administrador"
    Write-Host "El sistema solicitara una contrasena segura de 12 o mas caracteres."
    & $PhpPath artisan app:install-production "--admin-name=$AdminName" "--admin-email=$AdminEmail" --force
    if ($LASTEXITCODE -ne 0) { Stop-WithError "La instalacion de la aplicacion no termino correctamente." }

    Write-Step "Guardando configuracion de red y acceso LAN"
    Save-EsteliPOSDeploymentConfig -Config @{
        version = "1.0"
        installed_at = (Get-Date).ToString("o")
        port = $Port
        lan_address = $LanAddress
        app_url = $AppUrl
        mac_address = $MacAddress
        computer_name = $env:COMPUTERNAME
        admin_email = $AdminEmail
    }

    $NetworkPage = Write-EsteliPOSNetworkAccessPage `
        -AppUrl $AppUrl `
        -LanAddress $LanAddress `
        -Port $Port `
        -MacAddress $MacAddress `
        -ComputerName $env:COMPUTERNAME

    Write-Step "Configurando arranque automatico y respaldos"
    $StartScript = Join-Path $PSScriptRoot "Start-EsteliPOS.ps1"
    $BackupScript = Join-Path $PSScriptRoot "Backup-EsteliPOS.ps1"
    Register-EsteliPOSServerTask -StartScript $StartScript -Port $Port

    if ([string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        $ExternalBackupPath = Read-Host "Ruta opcional para una segunda copia (USB, red o nube; Enter para omitir)"
    }
    $BackupArguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$BackupScript`" -Port $Port -HostAddress 0.0.0.0"
    if (-not [string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        New-Item -ItemType Directory -Force -Path $ExternalBackupPath | Out-Null
        $BackupArguments += " -ExternalBackupPath `"$ExternalBackupPath`""
    } else {
        Write-Warning "Solo se configurara el respaldo local. Antes de cargar datos reales configura una copia externa."
    }

    $CurrentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    $Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument $BackupArguments
    $Trigger = New-ScheduledTaskTrigger -Daily -At "7:00PM"
    $Principal = New-ScheduledTaskPrincipal -UserId $CurrentUser -LogonType Interactive -RunLevel Limited
    Register-ScheduledTask -TaskName "EsteliPOS - Respaldo diario" -Action $Action -Trigger $Trigger -Principal $Principal -Description "Respaldo diario local de EsteliPOS" -Force | Out-Null

    $FirewallName = "EsteliPOS LAN - Puerto $Port"
    if (-not (Get-NetFirewallRule -DisplayName $FirewallName -ErrorAction SilentlyContinue)) {
        New-NetFirewallRule -DisplayName $FirewallName -Direction Inbound -Action Allow -Protocol TCP `
            -LocalPort $Port -Profile Private -RemoteAddress LocalSubnet | Out-Null
    }

    $BrowserCandidates = @(
        "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
        "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe"
    )
    $BrowserPath = $BrowserCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if (-not $BrowserPath) {
        Stop-WithError "No se encontro Microsoft Edge ni Google Chrome. Se requiere uno para la impresion silenciosa."
    }

    $Desktop = [Environment]::GetFolderPath("Desktop")
    $ShortcutPath = Join-Path $Desktop "EsteliPOS.lnk"
    $NetworkShortcutPath = Join-Path $Desktop "EsteliPOS - Acceso en red.lnk"
    $BrowserProfile = Join-Path $ProjectRoot "storage\app\browser-profile"
    $Shell = New-Object -ComObject WScript.Shell

    $Shortcut = $Shell.CreateShortcut($ShortcutPath)
    $Shortcut.TargetPath = $BrowserPath
    $Shortcut.Arguments = "--app=`"$AppUrl`" --kiosk-printing --user-data-dir=`"$BrowserProfile`""
    $Shortcut.WorkingDirectory = $ProjectRoot
    $Shortcut.IconLocation = "$BrowserPath,0"
    $Shortcut.Save()

    $NetworkShortcut = $Shell.CreateShortcut($NetworkShortcutPath)
    $NetworkShortcut.TargetPath = $BrowserPath
    $NetworkShortcut.Arguments = "`"file:///$($NetworkPage.Replace('\', '/'))`""
    $NetworkShortcut.WorkingDirectory = $ProjectRoot
    $NetworkShortcut.Save()

    Write-Step "Iniciando y comprobando EsteliPOS"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port -HostAddress "0.0.0.0"
    if ($LASTEXITCODE -ne 0) { Stop-WithError "El servicio local no pudo iniciar." }

    Write-Host "`nDESPLIEGUE COMPLETADO" -ForegroundColor Green
    Write-Host "Direccion para PC, tablets y otros equipos: $AppUrl"
    Write-Host "Reserva la IP $LanAddress en el router usando la MAC $MacAddress"
    Write-Host "Hoja de acceso con QR: $NetworkPage"
    Write-Host "Usuario administrador: $AdminEmail"
    Write-Host "Base de datos: $DatabasePath"
    Write-Host "Respaldos: storage\app\backups (diariamente a las 7:00 PM)"
    if (-not [string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        Write-Host "Segunda copia: $ExternalBackupPath"
    }
    Write-Host "Se crearon accesos directos en el escritorio."
    Start-Process $NetworkPage
    Start-Process $ShortcutPath
    Read-Host "Presiona Enter para finalizar"
} catch {
    Stop-WithError $_.Exception.Message
}
