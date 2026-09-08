[CmdletBinding()]
param(
    [int]$Port = 8080,
    [ValidateSet("Simple", "IIS")]
    [string]$ServerProfile = "IIS",
    [int]$FastCgiMaxInstances = 2,
    [string]$AdminName = "Administrador",
    [string]$AdminEmail = "",
    [string]$AdminPassword = "",
    [string]$ExternalBackupPath = "",
    [string]$LanAddress = "",
    [string]$ProjectRoot = "",
    [switch]$SetStaticIp,
    [switch]$SkipDemoData,
    [switch]$NonInteractive,
    [switch]$AutoInstallPhp = $true,
    [string]$PhpInstallDirectory = "C:\EsteliPOS\PHP"
)

$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
. (Join-Path $PSScriptRoot "EsteliPOS-InstallErrors.ps1")

if (-not [string]::IsNullOrWhiteSpace($ProjectRoot)) {
    Set-EsteliPOSProjectRootOverride $ProjectRoot
}

$ProjectRoot = Get-EsteliPOSProjectRoot
if ([string]::IsNullOrWhiteSpace($AdminPassword) -and -not [string]::IsNullOrWhiteSpace($env:INSTALL_ADMIN_PASSWORD)) {
    $AdminPassword = [string]$env:INSTALL_ADMIN_PASSWORD
}
$PhpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
$PhpPath = if ($PhpCommand) { $PhpCommand.Source } else { "" }
$InstallLogPath = Get-EsteliPOSInstallLogPath

function Write-Step([string]$Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
    Write-InstallLogLine -LogPath $InstallLogPath -Line "[PASO] $Message"
}

function Stop-WithError {
    param(
        [string]$Message,
        [int]$ExitCode = 99,
        [string[]]$ExtraSolutions = @()
    )

    Stop-EsteliPOSInstall -ExitCode $ExitCode -Message $Message -ExtraSolutions $ExtraSolutions -LogPath $InstallLogPath
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
    Write-Host "Perfil de servidor: $ServerProfile"
    Write-InstallLogLine -LogPath $InstallLogPath -Line "=== Inicio instalacion EsteliPOS $(Get-Date -Format o) ==="
    Write-InstallLogLine -LogPath $InstallLogPath -Line "Perfil: $ServerProfile | Puerto: $Port | Equipo: $env:COMPUTERNAME"

    $CurrentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $CurrentPrincipal = New-Object Security.Principal.WindowsPrincipal($CurrentIdentity)
    if (-not $CurrentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Stop-WithError "Ejecute el instalador como administrador." -ExitCode 1
    }

    Write-Step "Buscando dependencias en este equipo"
    $PrerequisiteReport = Get-EsteliPOSPrerequisiteReport -ServerProfile $ServerProfile -ProjectRoot $ProjectRoot
    $PrerequisiteText = Format-EsteliPOSPrerequisiteReport -Report $PrerequisiteReport
    Write-Host $PrerequisiteText
    Write-InstallLogLine -LogPath $InstallLogPath -Line $PrerequisiteText
    if (-not $PrerequisiteReport.Ready) {
        $failDetails = New-Object System.Collections.ArrayList
        foreach ($item in $PrerequisiteReport.Items) {
            if ([string]$item.Status -eq "FAIL") {
                [void]$failDetails.Add(("{0}: {1}" -f [string]$item.Name, [string]$item.Detail))
            }
        }
        Stop-WithError ([string]::Join("; ", $failDetails.ToArray())) -ExitCode 2
    }

    if ($AutoInstallPhp) {
        Write-Step "Comprobando o instalando PHP Thread Safe"
        try {
            . (Join-Path $PSScriptRoot "EsteliPOS-PHP.ps1")
            $PhpStatus = Ensure-EsteliPOSPhp -ServerProfile $ServerProfile -InstallDirectory $PhpInstallDirectory
            Write-InstallLogLine -LogPath $InstallLogPath -Line "PHP: $($PhpStatus.Version) en $($PhpStatus.PhpPath)"
            $PhpPath = $PhpStatus.PhpPath
        } catch {
            Stop-WithError $_.Exception.Message -ExitCode 19
        }
    }

    Write-Step "Comprobando PHP y el paquete"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "Verify-PHP-EsteliPOS.ps1") -ServerProfile $ServerProfile
    if ($LASTEXITCODE -ne 0) {
        Stop-WithError "La verificacion previa detecto problemas. Revise los mensajes FALLO arriba." -ExitCode 2
    }

    if ([string]::IsNullOrWhiteSpace($PhpPath) -or -not (Test-Path $PhpPath)) {
        try {
            $PhpPath = Resolve-EsteliPOSPhpExecutable
        } catch {
            Stop-WithError "php.exe no esta en el PATH ni en C:\EsteliPOS\PHP." -ExitCode 3
        }
    }

    $PhpVersion = & $PhpPath -r "echo PHP_VERSION;"
    if ([version]$PhpVersion -lt [version]"8.4.1") {
        Stop-WithError "Version encontrada: $PhpVersion" -ExitCode 4
    }

    $RequiredExtensions = @("ctype", "curl", "dom", "fileinfo", "gd", "mbstring", "openssl", "pdo_sqlite", "sqlite3", "tokenizer", "xml", "zip")
    $LoadedExtensions = @(& $PhpPath -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
    $MissingExtensions = @($RequiredExtensions | Where-Object { $LoadedExtensions -notcontains $_ })
    if ($MissingExtensions.Count -gt 0) {
        Stop-WithError "Extensiones faltantes: $($MissingExtensions -join ', ')" -ExitCode 5
    }

    if ($ServerProfile -eq "IIS") {
        try {
            $PhpCgiPath = Get-EsteliPOSPhpCgiPath
        } catch {
            Stop-WithError $_.Exception.Message -ExitCode 6
        }
    }

    if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php"))) {
        Stop-WithError "No existe vendor\autoload.php en el paquete extraido." -ExitCode 7
    }
    if (-not (Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot)) {
        Stop-WithError "Faltan archivos en public\build o public\css\app-ui.css." -ExitCode 8
    }
    if (-not (Test-Path (Join-Path $ProjectRoot "public\web.config"))) {
        Stop-WithError "No se encontro public\web.config." -ExitCode 9
    }
    if ($Port -lt 1024 -or $Port -gt 65535) {
        Stop-WithError "Puerto indicado: $Port" -ExitCode 10
    }

    if ($ServerProfile -eq "Simple") {
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1") -ServerProfile Simple -Port $Port
        Start-Sleep -Seconds 1
        $RequestedPort = $Port
        $Port = Get-EsteliPOSAvailablePort -PreferredPort $RequestedPort
        if ($Port -eq 0) {
            Stop-WithError "Windows bloqueo los puertos $RequestedPort a $($RequestedPort + 19)." -ExitCode 10
        }
        if ($Port -ne $RequestedPort) {
            Write-Warning "Windows no permite usar el puerto $RequestedPort. EsteliPOS utilizara automaticamente el puerto $Port."
            Write-InstallLogLine -LogPath $InstallLogPath -Line "Puerto $RequestedPort no enlazable; se selecciono $Port."
        }
    }

    $LanInfo = $null
    if ($SetStaticIp) {
        Write-Step "Fijando direccion IPv4 en esta PC"
        try {
            $LanInfo = Set-EsteliPOSStaticIpv4 -IpAddress $LanAddress
            $LanAddress = $LanInfo.IpAddress
            $MacAddress = $LanInfo.MacAddress
            Write-InstallLogLine -LogPath $InstallLogPath -Line "IP fija: $LanAddress MAC: $MacAddress DHCP desactivado"
        } catch {
            Stop-WithError "No se pudo fijar la IP en esta PC. $($_.Exception.Message)" -ExitCode 21
        }
    } else {
        $LanAddress = Get-EsteliPOSLanAddress -PreferredAddress $LanAddress
        if ([string]::IsNullOrWhiteSpace($LanAddress)) {
            Stop-WithError "No hay adaptador de red con IPv4 en la LAN." -ExitCode 11
        }
        $MacAddress = Get-EsteliPOSMacAddress
    }

    if ([string]::IsNullOrWhiteSpace($LanAddress)) {
        Stop-WithError "No hay adaptador de red con IPv4 en la LAN." -ExitCode 11
    }

    $AppUrl = "http://${LanAddress}:$Port"

    Write-Step "Esperando perfil de red de Windows"
    $privateOk = $false
    try {
        $privateOk = Set-EsteliPOSLanProfilePrivate -TimeoutSeconds 8
    } catch {
        Write-Warning "No se pudo consultar el perfil de red: $($_.Exception.Message). EsteliPOS se instalara igual."
        Write-InstallLogLine -LogPath $InstallLogPath -Line "AVISO perfil de red: $($_.Exception.Message)"
        $privateOk = $false
    }
    if ($privateOk) {
        Write-InstallLogLine -LogPath $InstallLogPath -Line "Red marcada como privada o ya no era Public."
    } else {
        Write-InstallLogLine -LogPath $InstallLogPath -Line "AVISO: no se marco la red como Privada (Identifying u otro). La instalacion continua."
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
    Set-EnvValue $EnvPath "SEED_DEMO_DATA" $(if ($SkipDemoData) { "false" } else { "true" })

    if (-not (Test-Path $DatabasePath)) {
        New-Item -ItemType File -Path $DatabasePath | Out-Null
    }
    New-Item -ItemType Directory -Force -Path (Join-Path $ProjectRoot "storage\logs") | Out-Null
    New-Item -ItemType Directory -Force -Path (Join-Path $ProjectRoot "storage\app\backups") | Out-Null

    $AppKeyLine = Get-Content $EnvPath | Where-Object { $_ -match "^APP_KEY=" } | Select-Object -First 1
    $ExistingAppKey = if ($AppKeyLine) { ($AppKeyLine -split "=", 2)[1].Trim() } else { "" }
    if ([string]::IsNullOrWhiteSpace($ExistingAppKey)) {
        & $PhpPath artisan key:generate --force
        if ($LASTEXITCODE -ne 0) { Stop-WithError "artisan key:generate devolvio codigo $LASTEXITCODE." -ExitCode 12 }
    } else {
        Write-Host "Se conserva la clave APP_KEY existente."
    }

    if ([string]::IsNullOrWhiteSpace($AdminEmail)) {
        if ($NonInteractive) {
            Stop-WithError "En modo no interactivo debe indicar -AdminEmail." -ExitCode 13
        }
        $AdminEmail = Read-Host "Correo del administrador"
    }
    if ([string]::IsNullOrWhiteSpace($AdminEmail)) {
        Stop-WithError "Debe ingresar un correo para el usuario administrador." -ExitCode 13
    }
    if ($NonInteractive -and [string]::IsNullOrWhiteSpace($AdminPassword)) {
        Stop-WithError "En modo no interactivo debe indicar -AdminPassword o INSTALL_ADMIN_PASSWORD." -ExitCode 13
    }

    Write-Step "Instalando base de datos y administrador"
    $PreviousInstallPassword = $env:INSTALL_ADMIN_PASSWORD
    try {
        if (-not [string]::IsNullOrWhiteSpace($AdminPassword)) {
            $env:INSTALL_ADMIN_PASSWORD = $AdminPassword
        } else {
            Write-Host "El sistema solicitara una contrasena segura de 12 o mas caracteres."
        }
        $InstallArguments = @(
            "app:install-production",
            "--admin-name=$AdminName",
            "--admin-email=$AdminEmail",
            "--force"
        )
        if (-not [string]::IsNullOrWhiteSpace($AdminPassword)) {
            $InstallArguments += "--admin-password=$AdminPassword"
        }
        if (-not $SkipDemoData) {
            $InstallArguments += "--demo"
            Write-Host "Se cargaran productos, clientes y ventas de demostracion para mostrar a clientes."
        } else {
            Write-Host "Instalacion vacia: sin productos ni ventas de demostracion."
        }
        & $PhpPath artisan @InstallArguments
        if ($LASTEXITCODE -ne 0) {
            Stop-WithError "app:install-production devolvio codigo $LASTEXITCODE." -ExitCode 14
        }
    } finally {
        if ($null -eq $PreviousInstallPassword) {
            Remove-Item Env:INSTALL_ADMIN_PASSWORD -ErrorAction SilentlyContinue
        } else {
            $env:INSTALL_ADMIN_PASSWORD = $PreviousInstallPassword
        }
    }

    if ($ServerProfile -eq "IIS") {
        Write-Step "Instalando IIS (Internet Information Services)"
        try {
            Install-EsteliPOSIISPlatform
        } catch {
            Stop-WithError $_.Exception.Message -ExitCode 20
        }

        Write-Step "Configurando sitio EsteliPOS + PHP FastCGI + URL Rewrite"
        try {
            & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "Stop-EsteliPOS.ps1") -ServerProfile Simple
            Install-EsteliPOSIISSite `
                -ProjectRoot $ProjectRoot `
                -PhpCgiPath $PhpCgiPath `
                -Port $Port `
                -FastCgiMaxInstances $FastCgiMaxInstances `
                -SkipPlatformInstall
            Write-Host "Workers PHP FastCGI: $FastCgiMaxInstances"
        } catch {
            Stop-WithError $_.Exception.Message -ExitCode 15
        }
    }

    Write-Step "Guardando configuracion de red y acceso LAN"
    Save-EsteliPOSDeploymentConfig -Config @{
        version = "1.1"
        installed_at = (Get-Date).ToString("o")
        server_profile = $ServerProfile.ToLowerInvariant()
        fastcgi_max_instances = $FastCgiMaxInstances
        iis_site_name = (Get-EsteliPOSIISSiteName)
        port = $Port
        lan_address = $LanAddress
        app_url = $AppUrl
        mac_address = $MacAddress
        computer_name = $env:COMPUTERNAME
        admin_email = $AdminEmail
        php_directory = (Split-Path $PhpPath -Parent)
        ip_mode = $(if ($SetStaticIp) { "static" } else { "dhcp" })
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
    Register-EsteliPOSServerTask -StartScript $StartScript -Port $Port -ServerProfile $ServerProfile

    if ([string]::IsNullOrWhiteSpace($ExternalBackupPath) -and -not $NonInteractive) {
        $ExternalBackupPath = Read-Host "Ruta opcional para una segunda copia (USB, red o nube; Enter para omitir)"
    }
    $BackupArguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$BackupScript`" -Port $Port -ServerProfile $ServerProfile"
    if ($ServerProfile -eq "Simple") {
        $BackupArguments += " -HostAddress 0.0.0.0"
    }
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

    Register-EsteliPOSFirewallRule -Port $Port

    $BrowserPath = Get-EsteliPOSBrowserPath
    if (-not $BrowserPath) {
        Write-Warning "No se encontro Microsoft Edge ni Google Chrome. Se creara el acceso directo igual."
        Write-InstallLogLine -LogPath $InstallLogPath -Line "AVISO: sin Edge/Chrome. Instale un navegador para abrir EsteliPOS."
    }

    $Desktop = [Environment]::GetFolderPath("Desktop")
    $ShortcutPath = Join-Path $Desktop "EsteliPOS.lnk"
    $NetworkShortcutPath = Join-Path $Desktop "EsteliPOS - Acceso en red.lnk"
    $LaunchScript = Join-Path $PSScriptRoot "Launch-EsteliPOS.ps1"
    $OpenBat = Join-Path $ProjectRoot "Abrir-EsteliPOS.bat"
    $Shell = New-Object -ComObject WScript.Shell

    # El acceso directo DEBE iniciar Laravel (si hace falta) y esperar HTTP listo
    # antes de abrir el navegador. No apuntar solo a Chrome/PWA.
    $Shortcut = $Shell.CreateShortcut($ShortcutPath)
    if (Test-Path $OpenBat) {
        $Shortcut.TargetPath = $OpenBat
        $Shortcut.Arguments = ""
    } else {
        $Shortcut.TargetPath = "powershell.exe"
        $Shortcut.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$LaunchScript`" -Port $Port -ServerProfile $ServerProfile"
    }
    $Shortcut.WorkingDirectory = $ProjectRoot
    if ($BrowserPath) {
        $Shortcut.IconLocation = "$BrowserPath,0"
    }
    $Shortcut.Description = "Inicia EsteliPOS y abre la interfaz ($AppUrl)"
    $Shortcut.Save()

    $NetworkShortcut = $Shell.CreateShortcut($NetworkShortcutPath)
    if ($BrowserPath) {
        $NetworkShortcut.TargetPath = $BrowserPath
        $NetworkShortcut.Arguments = "`"file:///$($NetworkPage.Replace('\', '/'))`""
    } else {
        $NetworkShortcut.TargetPath = $NetworkPage
        $NetworkShortcut.Arguments = ""
    }
    $NetworkShortcut.WorkingDirectory = $ProjectRoot
    $NetworkShortcut.Save()

    Write-Step "Iniciando y comprobando EsteliPOS"
    $StartArguments = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-File", $StartScript, "-Port", $Port, "-ServerProfile", $ServerProfile)
    if ($ServerProfile -eq "Simple") {
        $StartArguments += @("-HostAddress", "0.0.0.0")
    }
    & powershell.exe @StartArguments
    if ($LASTEXITCODE -ne 0) {
        Stop-WithError "Start-EsteliPOS.ps1 devolvio codigo $LASTEXITCODE." -ExitCode 17
    }

    Write-Step "Verificando instalacion completa"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "Test-EsteliPOSInstallation.ps1") -Port $Port -ServerProfile $ServerProfile -SkipSlowChecks
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Algunas comprobaciones locales fallaron. EsteliPOS ya esta instalado; revise storage\app\deployment\post-install-report.json"
        Write-InstallLogLine -LogPath $InstallLogPath -Line "AVISO: Test-EsteliPOSInstallation codigo $LASTEXITCODE (no aborta)."
    }

    Write-InstallLogLine -LogPath $InstallLogPath -Line "=== Instalacion completada OK $(Get-Date -Format o) ==="
    Write-Host "`nDESPLIEGUE COMPLETADO" -ForegroundColor Green
    Write-Host "Log de instalacion: $InstallLogPath" -ForegroundColor DarkGray
    Write-Host "Perfil de servidor: $ServerProfile"
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
    Write-Host "Abra EsteliPOS desde el acceso directo del escritorio cuando termine este asistente."
    if (-not $NonInteractive) {
        Read-Host "Presiona Enter para finalizar"
    }
} catch {
    Write-InstallLogLine -LogPath $InstallLogPath -Line "EXCEPCION: $($_.Exception.Message)"
    Write-InstallLogLine -LogPath $InstallLogPath -Line "STACK: $($_.ScriptStackTrace)"
    Stop-WithError $_.Exception.Message -ExitCode 99
}
