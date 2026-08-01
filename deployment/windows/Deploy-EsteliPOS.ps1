[CmdletBinding()]
param(
    [int]$Port = 8080,
    [string]$AdminName = "Administrador",
    [string]$AdminEmail = "",
    [string]$ExternalBackupPath = ""
)

$ErrorActionPreference = "Stop"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
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
    Write-Host "EsteliPOS - Despliegue local para Windows" -ForegroundColor Green
    Write-Host "Northlink Microsystem"

    $CurrentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $CurrentPrincipal = New-Object Security.Principal.WindowsPrincipal($CurrentIdentity)
    if (-not $CurrentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Stop-WithError "Abre PowerShell con la opcion Ejecutar como administrador y vuelve a intentarlo."
    }

    Write-Step "Comprobando el paquete"
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
    if (-not (Test-Path (Join-Path $ProjectRoot "public\build\manifest.json"))) {
        Stop-WithError "El paquete no contiene los recursos web compilados."
    }
    if ($Port -lt 1024 -or $Port -gt 65535) {
        Stop-WithError "El puerto debe estar entre 1024 y 65535."
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
    Set-EnvValue $EnvPath "APP_URL" "http://127.0.0.1:$Port"
    Set-EnvValue $EnvPath "DB_CONNECTION" "sqlite"
    Set-EnvValue $EnvPath "DB_DATABASE" "`"$DatabaseEnvPath`""
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

    Write-Step "Configurando arranque automatico"
    $StartScript = Join-Path $PSScriptRoot "Start-EsteliPOS.ps1"
    $BackupScript = Join-Path $PSScriptRoot "Backup-EsteliPOS.ps1"
    $RunCommand = "powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$StartScript`" -Port $Port"
    New-Item -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run" -Force | Out-Null
    Set-ItemProperty -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run" -Name "EsteliPOS" -Value $RunCommand

    if ([string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        $ExternalBackupPath = Read-Host "Ruta opcional para una segunda copia (USB, red o nube; Enter para omitir)"
    }
    $BackupArguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$BackupScript`" -Port $Port"
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

    $Desktop = [Environment]::GetFolderPath("Desktop")
    $ShortcutPath = Join-Path $Desktop "EsteliPOS.url"
    @("[InternetShortcut]", "URL=http://127.0.0.1:$Port", "IconFile=$env:SystemRoot\System32\SHELL32.dll", "IconIndex=220") |
        Set-Content -Path $ShortcutPath -Encoding ASCII

    Write-Step "Iniciando y comprobando EsteliPOS"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $StartScript -Port $Port
    if ($LASTEXITCODE -ne 0) { Stop-WithError "El servicio local no pudo iniciar." }

    Write-Host "`nDESPLIEGUE COMPLETADO" -ForegroundColor Green
    Write-Host "Direccion: http://127.0.0.1:$Port"
    Write-Host "Usuario administrador: $AdminEmail"
    Write-Host "Base de datos: $DatabasePath"
    Write-Host "Respaldos: storage\app\backups (diariamente a las 7:00 PM)"
    if (-not [string]::IsNullOrWhiteSpace($ExternalBackupPath)) {
        Write-Host "Segunda copia: $ExternalBackupPath"
    }
    Write-Host "Se creo el acceso directo EsteliPOS en el escritorio."
    Start-Process "http://127.0.0.1:$Port"
    Read-Host "Presiona Enter para finalizar"
} catch {
    Stop-WithError $_.Exception.Message
}
