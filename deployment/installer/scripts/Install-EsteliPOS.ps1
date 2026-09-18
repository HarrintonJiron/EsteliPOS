[CmdletBinding()]
param(
    [Parameter(Mandatory)][string]$InstallRoot,
    [Parameter(Mandatory)][string]$PayloadRoot,
    [Parameter(Mandatory)][string]$ApplicationSource,
    [Parameter(Mandatory)][string]$Version,
    [ValidateSet('Install', 'Update', 'Repair')][string]$Mode = 'Install'
)

$ErrorActionPreference = 'Stop'
$InstallRoot = [IO.Path]::GetFullPath($InstallRoot).TrimEnd('\')
$PayloadRoot = [IO.Path]::GetFullPath($PayloadRoot).TrimEnd('\')
$ApplicationSource = [IO.Path]::GetFullPath($ApplicationSource).TrimEnd('\')
if ($InstallRoot -eq [IO.Path]::GetPathRoot($InstallRoot).TrimEnd('\')) {
    throw 'La raiz de una unidad no es una ruta de instalacion valida.'
}

$stateRoot = Join-Path $env:ProgramData 'EsteliPOS'
$logRoot = Join-Path $stateRoot 'Logs'
$serviceLogRoot = Join-Path $logRoot 'services'
$logPath = Join-Path $logRoot ("installer-{0:yyyyMMdd-HHmmss}.log" -f (Get-Date))
$appRoot = Join-Path $InstallRoot 'application'
$apacheContainer = Join-Path $InstallRoot 'apache'
$phpRoot = Join-Path $InstallRoot 'php'
$mysqlContainer = Join-Path $InstallRoot 'mysql'
$installerScriptsRoot = Join-Path $InstallRoot 'installer'
$backupRoot = Join-Path $stateRoot ('backups\' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
$createdPaths = [Collections.Generic.List[string]]::new()
$movedPaths = [Collections.Generic.List[object]]::new()
$registeredServices = [Collections.Generic.List[string]]::new()
$previousServices = [Collections.Generic.List[string]]::new()
$existingServices = @{}
$freshDatabase = $false
$freshEnvironment = $false
$removeInstanceOnRollback = $false
$previousState = $null

New-Item -ItemType Directory -Path $logRoot, $serviceLogRoot -Force | Out-Null

function Write-InstallLog([string]$Message) {
    $line = "{0} {1}" -f (Get-Date -Format 's'), $Message
    Add-Content -LiteralPath $logPath -Value $line -Encoding UTF8
}

function Stop-WithError([string]$Code, [string]$Phase, [string]$Detail) {
    Write-InstallLog "[ERROR $Code] $Phase - $Detail"
    throw "$Code|$Phase|$Detail|$logPath"
}

function Convert-ToConfigPath([string]$Path) {
    return $Path.Replace('\', '/')
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

function Get-FreePort([int]$Start) {
    foreach ($port in $Start..($Start + 50)) {
        $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, $port)
        try {
            $listener.Start()
            return $port
        } catch {
        } finally {
            $listener.Stop()
        }
    }
    Stop-WithError 'E021' 'Puertos' "No hay puerto disponible desde $Start."
}

function Invoke-Hidden {
    param(
        [Parameter(Mandatory)][string]$File,
        [string]$Arguments = '',
        [Parameter(Mandatory)][string]$Phase,
        [string]$WorkingDirectory = $InstallRoot,
        [int[]]$AllowedExitCodes = @(0)
    )

    Write-InstallLog "[PASO] $Phase"
    $slug = ($Phase -replace '[^a-zA-Z0-9_-]', '-').Trim('-')
    $stdoutPath = "$logPath.$slug.out"
    $stderrPath = "$logPath.$slug.err"
    $process = Start-Process -FilePath $File -ArgumentList $Arguments -WorkingDirectory $WorkingDirectory -Wait -PassThru -WindowStyle Hidden -RedirectStandardOutput $stdoutPath -RedirectStandardError $stderrPath
    if ($AllowedExitCodes -notcontains $process.ExitCode) {
        $detail = (Get-Content -LiteralPath $stderrPath -Raw -ErrorAction SilentlyContinue).Trim()
        if (-not $detail) {
            $detail = (Get-Content -LiteralPath $stdoutPath -Raw -ErrorAction SilentlyContinue).Trim()
        }
        Stop-WithError 'E030' $Phase "Codigo $($process.ExitCode). $detail"
    }
    Write-InstallLog "[OK] $Phase"
}

function New-Secret([int]$Length = 32) {
    $bytes = [byte[]]::new($Length)
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $rng.GetBytes($bytes)
        return ([Convert]::ToBase64String($bytes) -replace '[^a-zA-Z0-9]', '').Substring(0, $Length)
    } finally {
        $rng.Dispose()
    }
}

function Wait-ForService([string]$Name, [int]$TimeoutSeconds = 45) {
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        $service = Get-Service -Name $Name -ErrorAction SilentlyContinue
        if ($service -and $service.Status -eq 'Running') {
            return
        }
        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)
    Stop-WithError 'E041' 'Servicios' "El servicio $Name no inicio dentro de $TimeoutSeconds segundos."
}

function Remove-ServiceIfPresent([string]$Name) {
    $service = Get-Service -Name $Name -ErrorAction SilentlyContinue
    if (-not $service) {
        return
    }
    if ($service.Status -ne 'Stopped') {
        Stop-Service -Name $Name -Force -ErrorAction SilentlyContinue
        $service.WaitForStatus('Stopped', [TimeSpan]::FromSeconds(20))
    }
    & sc.exe delete $Name | Out-Null
    $deadline = (Get-Date).AddSeconds(20)
    while ((Get-Service -Name $Name -ErrorAction SilentlyContinue) -and (Get-Date) -lt $deadline) {
        Start-Sleep -Milliseconds 500
    }
    if (Get-Service -Name $Name -ErrorAction SilentlyContinue) {
        Stop-WithError 'E042' 'Servicios' "No se pudo reemplazar el servicio $Name."
    }
}

function Backup-Component([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }
    New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
    $destination = Join-Path $backupRoot (Split-Path -Leaf $Path)
    Move-Item -LiteralPath $Path -Destination $destination -Force
    $movedPaths.Add([pscustomobject]@{ Original = $Path; Backup = $destination })
    Write-InstallLog "[OK] Respaldo creado: $destination"
}

function Restore-PreviousInstallation {
    foreach ($serviceName in $registeredServices) {
        Remove-ServiceIfPresent $serviceName
    }

    foreach ($path in $createdPaths) {
        if (Test-Path -LiteralPath $path) {
            Remove-Item -LiteralPath $path -Recurse -Force -ErrorAction SilentlyContinue
        }
    }

    foreach ($entry in $movedPaths) {
        if (Test-Path -LiteralPath $entry.Backup) {
            Move-Item -LiteralPath $entry.Backup -Destination $entry.Original -Force
        }
    }

    if ($removeInstanceOnRollback -and $instanceRoot -and (Test-Path -LiteralPath $instanceRoot)) {
        Remove-Item -LiteralPath $instanceRoot -Recurse -Force -ErrorAction SilentlyContinue
    }

    foreach ($serviceName in $previousServices) {
        if (Get-Service -Name $serviceName -ErrorAction SilentlyContinue) {
            Start-Service -Name $serviceName -ErrorAction SilentlyContinue
        }
    }
}

try {
    Write-InstallLog "[PASO] Inicio $Mode EsteliPOS $Version"
    if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Stop-WithError 'E001' 'Privilegios' 'Se requieren privilegios de administrador.'
    }

    foreach ($requiredPath in @($PayloadRoot, $ApplicationSource)) {
        if (-not (Test-Path -LiteralPath $requiredPath -PathType Container)) {
            Stop-WithError 'E002' 'Prevalidacion' "No existe el directorio requerido: $requiredPath"
        }
    }
    foreach ($requiredFile in @('artisan', 'public\index.php', 'vendor\autoload.php', 'public\build\manifest.json')) {
        if (-not (Test-Path -LiteralPath (Join-Path $ApplicationSource $requiredFile) -PathType Leaf)) {
            Stop-WithError 'E003' 'Prevalidacion' "La aplicacion empaquetada no contiene $requiredFile."
        }
    }

    $manifestPath = Join-Path $PayloadRoot 'manifest.json'
    $components = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
    foreach ($component in $components.components) {
        $path = Join-Path $PayloadRoot $component.file
        if (-not (Test-Path -LiteralPath $path -PathType Leaf) -or (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant() -ne $component.sha256) {
            Stop-WithError 'E010' 'Integridad' "Checksum invalido: $($component.name)."
        }
    }

    $suffix = Get-InstallSuffix $InstallRoot
    $apacheService = "EsteliPOSApache_$suffix"
    $mysqlService = "EsteliPOSMySQL_$suffix"
    $statePath = Join-Path $stateRoot "installation-$suffix.json"
    $instanceRoot = Join-Path $stateRoot "instances\$suffix"
    $mysqlData = Join-Path $instanceRoot 'mysql\data'
    $mysqlConfig = Join-Path $instanceRoot 'mysql\my.ini'

    if (Test-Path -LiteralPath $statePath -PathType Leaf) {
        $previousState = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
        if ($previousState.installRoot -ne $InstallRoot) {
            Stop-WithError 'E011' 'Estado' "El estado $statePath pertenece a otra ruta."
        }
        $apachePort = [int]$previousState.apachePort
        $mysqlPort = [int]$previousState.mysqlPort
        if ($previousState.apacheService) { $apacheService = [string]$previousState.apacheService }
        if ($previousState.mysqlService) { $mysqlService = [string]$previousState.mysqlService }
        if ($previousState.instanceRoot) {
            $instanceRoot = [string]$previousState.instanceRoot
            $mysqlData = Join-Path $instanceRoot 'mysql\data'
            $mysqlConfig = Join-Path $instanceRoot 'mysql\my.ini'
        }
    } else {
        $apachePort = Get-FreePort 8080
        $mysqlPort = Get-FreePort 3307
        if (-not (Test-Path -LiteralPath $instanceRoot)) {
            $removeInstanceOnRollback = $true
        }
    }
    Write-InstallLog "[OK] Apache 127.0.0.1:$apachePort; MySQL 127.0.0.1:$mysqlPort; instancia $suffix"

    foreach ($serviceName in @($apacheService, $mysqlService)) {
        $service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
        if ($service) {
            $existingServices[$serviceName] = $true
            $previousServices.Add($serviceName)
            if ($service.Status -ne 'Stopped') {
                Stop-Service -Name $serviceName -Force
                $service.WaitForStatus('Stopped', [TimeSpan]::FromSeconds(30))
            }
        }
    }

    New-Item -ItemType Directory -Path $InstallRoot, $stateRoot -Force | Out-Null
    foreach ($componentPath in @($appRoot, $apacheContainer, $phpRoot, $mysqlContainer, $installerScriptsRoot)) {
        Backup-Component $componentPath
    }

    foreach ($componentPath in @($appRoot, $apacheContainer, $phpRoot, $mysqlContainer, $installerScriptsRoot)) {
        New-Item -ItemType Directory -Path $componentPath -Force | Out-Null
        $createdPaths.Add($componentPath)
    }

    Get-ChildItem -LiteralPath $ApplicationSource -Force | Copy-Item -Destination $appRoot -Recurse -Force
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Install-EsteliPOS.ps1') -Destination $installerScriptsRoot -Force
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'Uninstall-EsteliPOS.ps1') -Destination $installerScriptsRoot -Force
    Write-InstallLog '[OK] Aplicacion copiada con estructura validada'

    Expand-Archive -LiteralPath (Join-Path $PayloadRoot 'apache-win64.zip') -DestinationPath $apacheContainer -Force
    Expand-Archive -LiteralPath (Join-Path $PayloadRoot 'php-8.5.10-Win32-vs17-x64.zip') -DestinationPath $phpRoot -Force
    Expand-Archive -LiteralPath (Join-Path $PayloadRoot 'mysql-8.0.21-winx64.zip') -DestinationPath $mysqlContainer -Force

    $apacheExe = Get-ChildItem -LiteralPath $apacheContainer -Filter 'httpd.exe' -Recurse -File | Select-Object -First 1
    $mysqlExe = Get-ChildItem -LiteralPath $mysqlContainer -Filter 'mysql.exe' -Recurse -File | Select-Object -First 1
    $mysqldExe = Get-ChildItem -LiteralPath $mysqlContainer -Filter 'mysqld.exe' -Recurse -File | Select-Object -First 1
    if (-not $apacheExe -or -not $mysqlExe -or -not $mysqldExe) {
        Stop-WithError 'E012' 'Runtime' 'No se encontraron los ejecutables de Apache o MySQL en los archivos distribuidos.'
    }
    $apacheRoot = Split-Path -Parent (Split-Path -Parent $apacheExe.FullName)
    $mysqlRoot = Split-Path -Parent (Split-Path -Parent $mysqldExe.FullName)
    $phpExe = Join-Path $phpRoot 'php.exe'
    if (-not (Test-Path -LiteralPath $phpExe -PathType Leaf) -or -not (Test-Path -LiteralPath (Join-Path $phpRoot 'php8apache2_4.dll') -PathType Leaf)) {
        Stop-WithError 'E013' 'Runtime' 'PHP no incluye php.exe o el modulo compatible con Apache.'
    }

    Invoke-Hidden -File (Join-Path $PayloadRoot 'vc_redist.x64.exe') -Arguments '/install /quiet /norestart' -Phase 'Instalando runtime de Visual C++' -AllowedExitCodes @(0, 1638, 3010)

    $phpIni = Join-Path $phpRoot 'php.ini'
    Copy-Item -LiteralPath (Join-Path $phpRoot 'php.ini-production') -Destination $phpIni -Force
    $phpExtensionPath = Convert-ToConfigPath (Join-Path $phpRoot 'ext')
    @"

extension_dir="$phpExtensionPath"
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
date.timezone=America/Managua
"@ | Add-Content -LiteralPath $phpIni -Encoding ASCII
    # PHPIniDir is ignored by some Windows Apache builds during module startup.
    # Apache also searches its own root for php.ini, so keep a synchronized copy there.
    Copy-Item -LiteralPath $phpIni -Destination (Join-Path $apacheRoot 'php.ini') -Force

    $oldEnv = Join-Path $backupRoot 'application\.env'
    $envFile = Join-Path $appRoot '.env'
    if (Test-Path -LiteralPath $oldEnv -PathType Leaf) {
        Copy-Item -LiteralPath $oldEnv -Destination $envFile -Force
        $oldStorage = Join-Path $backupRoot 'application\storage\app'
        if (Test-Path -LiteralPath $oldStorage -PathType Container) {
            Copy-Item -LiteralPath $oldStorage -Destination (Join-Path $appRoot 'storage') -Recurse -Force
        }
    } else {
        $freshEnvironment = $true
        $mysqlPassword = New-Secret
        @"
APP_NAME="EsteliPOS"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://127.0.0.1:$apachePort
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_NI
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=$mysqlPort
DB_DATABASE=estelipos
DB_USERNAME=estelipos
DB_PASSWORD=$mysqlPassword
SESSION_DRIVER=file
SESSION_LIFETIME=120
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file
MAIL_MAILER=log
"@ | Set-Content -LiteralPath $envFile -Encoding ASCII
    }

    foreach ($directory in @(
        (Join-Path $appRoot 'bootstrap\cache'),
        (Join-Path $appRoot 'storage\app\public'),
        (Join-Path $appRoot 'storage\framework\cache\data'),
        (Join-Path $appRoot 'storage\framework\sessions'),
        (Join-Path $appRoot 'storage\framework\views'),
        (Join-Path $appRoot 'storage\logs')
    )) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    $mysqlDataWasInitialized = Test-Path -LiteralPath (Join-Path $mysqlData 'mysql') -PathType Container
    New-Item -ItemType Directory -Path (Split-Path -Parent $mysqlConfig), $mysqlData -Force | Out-Null
    $mysqlRootConfig = Convert-ToConfigPath $mysqlRoot
    $mysqlDataConfig = Convert-ToConfigPath $mysqlData
    $mysqlErrorLog = Convert-ToConfigPath (Join-Path $serviceLogRoot "mysql-$suffix-error.log")
    @"
[mysqld]
basedir="$mysqlRootConfig"
datadir="$mysqlDataConfig"
port=$mysqlPort
bind-address=127.0.0.1
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
default-authentication-plugin=mysql_native_password
log-error="$mysqlErrorLog"

[client]
host=127.0.0.1
port=$mysqlPort
default-character-set=utf8mb4
"@ | Set-Content -LiteralPath $mysqlConfig -Encoding ASCII

    if (-not $mysqlDataWasInitialized) {
        $freshDatabase = $true
        Invoke-Hidden -File $mysqldExe.FullName -Arguments "--defaults-file=`"$mysqlConfig`" --initialize-insecure --console" -Phase 'Inicializando MySQL' -WorkingDirectory $mysqlRoot
    }
    if (-not $existingServices.ContainsKey($mysqlService)) {
        Invoke-Hidden -File $mysqldExe.FullName -Arguments "--install $mysqlService --defaults-file=`"$mysqlConfig`"" -Phase 'Registrando servicio MySQL' -WorkingDirectory $mysqlRoot
        $registeredServices.Add($mysqlService)
    } else {
        Write-InstallLog "[OK] Servicio MySQL reutilizado: $mysqlService"
    }
    Start-Service -Name $mysqlService
    Wait-ForService $mysqlService

    if ($freshDatabase) {
        $mysqlPasswordLine = Get-Content -LiteralPath $envFile | Where-Object { $_ -like 'DB_PASSWORD=*' } | Select-Object -First 1
        $mysqlPassword = $mysqlPasswordLine.Substring('DB_PASSWORD='.Length)
        $sql = "CREATE DATABASE IF NOT EXISTS estelipos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'estelipos'@'127.0.0.1' IDENTIFIED BY '$mysqlPassword'; ALTER USER 'estelipos'@'127.0.0.1' IDENTIFIED BY '$mysqlPassword'; GRANT ALL PRIVILEGES ON estelipos.* TO 'estelipos'@'127.0.0.1'; FLUSH PRIVILEGES;"
        Invoke-Hidden -File $mysqlExe.FullName -Arguments "--protocol=TCP --host=127.0.0.1 --port=$mysqlPort --user=root --execute=`"$sql`"" -Phase 'Creando base de datos' -WorkingDirectory $mysqlRoot
    }

    if ($freshEnvironment) {
        Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" key:generate --force --no-interaction" -Phase 'Generando clave de la aplicacion' -WorkingDirectory $appRoot
    }
    Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" migrate --force --no-interaction" -Phase 'Aplicando migraciones' -WorkingDirectory $appRoot
    if ($freshDatabase) {
        Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" db:seed --class=UserSeeder --force --no-interaction" -Phase 'Creando usuarios iniciales' -WorkingDirectory $appRoot
    }
    Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" db:seed --force --no-interaction" -Phase 'Sincronizando catalogos' -WorkingDirectory $appRoot
    Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" optimize --no-interaction" -Phase 'Optimizando Laravel' -WorkingDirectory $appRoot
    Invoke-Hidden -File $phpExe -Arguments "`"$appRoot\artisan`" storage:link --no-interaction" -Phase 'Enlazando archivos publicos' -WorkingDirectory $appRoot -AllowedExitCodes @(0, 1)

    $apacheConfig = Join-Path $apacheRoot 'conf\httpd.conf'
    $apacheConfigContent = Get-Content -LiteralPath $apacheConfig -Raw
    $apacheRootConfig = Convert-ToConfigPath $apacheRoot
    $publicRootConfig = Convert-ToConfigPath (Join-Path $appRoot 'public')
    $phpRootConfig = Convert-ToConfigPath $phpRoot
    $apacheErrorLog = Convert-ToConfigPath (Join-Path $serviceLogRoot "apache-$suffix-error.log")
    $apacheAccessLog = Convert-ToConfigPath (Join-Path $serviceLogRoot "apache-$suffix-access.log")
    $apacheConfigContent = $apacheConfigContent -replace 'Define SRVROOT ".*?"', "Define SRVROOT `"$apacheRootConfig`""
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^Listen\s+\d+\s*$', "Listen 127.0.0.1:$apachePort"
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^#\s*LoadModule rewrite_module', 'LoadModule rewrite_module'
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^#ServerName www\.example\.com:80\s*$', "ServerName 127.0.0.1:$apachePort"
    $apacheConfigContent = $apacheConfigContent -replace 'DocumentRoot "\$\{SRVROOT\}/htdocs"', "DocumentRoot `"$publicRootConfig`""
    $apacheConfigContent = $apacheConfigContent -replace '<Directory "\$\{SRVROOT\}/htdocs">', "<Directory `"$publicRootConfig`">"
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^\s*DirectoryIndex\s+index\.html\s*$', '    DirectoryIndex index.php index.html'
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^ErrorLog\s+"logs/error\.log"\s*$', "ErrorLog `"$apacheErrorLog`""
    $apacheConfigContent = $apacheConfigContent -replace '(?m)^\s*CustomLog\s+"logs/access\.log"\s+common\s*$', "    CustomLog `"$apacheAccessLog`" common"
    $apacheConfigContent += @"

# EsteliPOS PHP and Laravel configuration
LoadModule php_module "$phpRootConfig/php8apache2_4.dll"
PHPIniDir "$phpRootConfig/"
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
<Directory "$publicRootConfig">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
"@
    Set-Content -LiteralPath $apacheConfig -Value $apacheConfigContent -Encoding ASCII

    Invoke-Hidden -File $apacheExe.FullName -Arguments "-t -f `"$apacheConfig`"" -Phase 'Validando Apache' -WorkingDirectory $apacheRoot
    if (-not $existingServices.ContainsKey($apacheService)) {
        Invoke-Hidden -File $apacheExe.FullName -Arguments "-k install -n `"$apacheService`" -f `"$apacheConfig`"" -Phase 'Registrando servicio Apache' -WorkingDirectory $apacheRoot
        $registeredServices.Add($apacheService)
    } else {
        Write-InstallLog "[OK] Servicio Apache reutilizado: $apacheService"
    }
    Start-Service -Name $apacheService
    Wait-ForService $apacheService

    $healthUrl = "http://127.0.0.1:$apachePort/login"
    $deadline = (Get-Date).AddSeconds(45)
    $healthy = $false
    $lastHealthError = $null
    do {
        try {
            $response = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 5
            if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400) {
                $healthy = $true
                break
            }
        } catch {
            if ($_.Exception.Response) {
                $lastHealthError = "HTTP $([int]$_.Exception.Response.StatusCode)"
            } else {
                $lastHealthError = $_.Exception.Message
            }
        }
        Start-Sleep -Seconds 1
    } while ((Get-Date) -lt $deadline)
    if (-not $healthy) {
        Write-InstallLog "[DIAGNOSTICO] Ultimo resultado HTTP: $lastHealthError"
        $laravelLog = Get-ChildItem -LiteralPath (Join-Path $appRoot 'storage\logs') -File -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
        if ($laravelLog) {
            $laravelTail = (Get-Content -LiteralPath $laravelLog.FullName -Tail 30 -ErrorAction SilentlyContinue) -join ' | '
            Write-InstallLog "[DIAGNOSTICO] Laravel: $laravelTail"
        }
        Stop-WithError 'E050' 'Validacion' "EsteliPOS no respondio correctamente en $healthUrl ($lastHealthError)."
    }

    $urlShortcut = Join-Path $InstallRoot 'EsteliPOS.url'
    @"
[InternetShortcut]
URL=http://127.0.0.1:$apachePort/
"@ | Set-Content -LiteralPath $urlShortcut -Encoding ASCII
    $createdPaths.Add($urlShortcut)

    $state = [ordered]@{
        version = $Version
        installRoot = $InstallRoot
        instanceRoot = $instanceRoot
        apachePort = $apachePort
        mysqlPort = $mysqlPort
        apacheService = $apacheService
        mysqlService = $mysqlService
        url = "http://127.0.0.1:$apachePort/"
        installedAt = (Get-Date).ToString('o')
        backupRoot = if (Test-Path -LiteralPath $backupRoot) { $backupRoot } else { $null }
    }
    $stateJson = $state | ConvertTo-Json -Depth 4
    Set-Content -LiteralPath $statePath -Value $stateJson -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $stateRoot 'installation.json') -Value $stateJson -Encoding UTF8
    Write-InstallLog "[OK] Instalacion finalizada: $healthUrl"
} catch {
    $message = $_.Exception.Message
    Write-InstallLog "[ERROR E099] Instalacion - $message"
    if ($_.ScriptStackTrace) {
        Write-InstallLog "[DETALLE] $($_.ScriptStackTrace -replace "`r?`n", ' | ')"
    }
    try {
        Restore-PreviousInstallation
        Write-InstallLog '[OK] Rollback completado'
    } catch {
        Write-InstallLog "[ERROR E098] Rollback incompleto - $($_.Exception.Message)"
    }
    throw
}
