function Get-EsteliPOSIISSiteName {
    return "EsteliPOS"
}

function Get-EsteliPOSIISAppPoolName {
    return "EsteliPOS"
}

function Get-EsteliPOSPhpCgiPath {
    $PhpExe = (Get-Command php.exe -ErrorAction Stop).Source
    $PhpCgiPath = Join-Path (Split-Path $PhpExe -Parent) "php-cgi.exe"

    if (-not (Test-Path $PhpCgiPath)) {
        throw "No se encontro php-cgi.exe junto a PHP. Use la distribucion Thread Safe de PHP para Windows."
    }

    return $PhpCgiPath
}

function Test-EsteliPOSUrlRewriteModule {
    Import-Module WebAdministration -ErrorAction SilentlyContinue

    if (Get-WebGlobalModule -Name "RewriteModule" -ErrorAction SilentlyContinue) {
        return $true
    }

    $RewriteDll = Join-Path $env:windir "System32\inetsrv\rewrite.dll"
    if (Test-Path $RewriteDll) {
        return $true
    }

    return Test-Path "HKLM:\SOFTWARE\Microsoft\IIS Extensions\URL Rewrite"
}

function Restart-EsteliPOSIIS {
    Write-Host "Reiniciando IIS para aplicar modulos y configuracion..."
    & iisreset.exe /restart 2>&1 | Out-Null
    Start-Sleep -Seconds 4
    Start-EsteliPOSIISService
}

function Wait-EsteliPOSUrlRewriteModule {
    param([int]$MaxAttempts = 6)

    for ($Attempt = 1; $Attempt -le $MaxAttempts; $Attempt++) {
        if (Test-EsteliPOSUrlRewriteModule) {
            return $true
        }

        Write-Host "Esperando modulo URL Rewrite (intento $Attempt/$MaxAttempts)..."
        Start-Sleep -Seconds 3
    }

    return $false
}

function Install-EsteliPOSUrlRewriteModule {
    if (Test-EsteliPOSUrlRewriteModule) {
        Write-Host "IIS URL Rewrite ya esta instalado."

        return
    }

    $BundledInstaller = Join-Path $PSScriptRoot "assets\urlrewrite2.exe"
    $InstallerPath = $BundledInstaller
    $Installed = $false

    if (Test-Path $InstallerPath) {
        Write-Host "Instalando IIS URL Rewrite desde paquete local..."
        $Process = Start-Process -FilePath $InstallerPath -ArgumentList "/install", "/quiet", "/norestart" -PassThru -Wait
        if ($Process.ExitCode -ne 0) {
            Write-Warning "urlrewrite2.exe devolvio codigo $($Process.ExitCode)."
        }

        Restart-EsteliPOSIIS
        $Installed = Wait-EsteliPOSUrlRewriteModule
    }

    if (-not $Installed) {
        $InstallerPath = Join-Path $env:TEMP "urlrewrite2.exe"
        try {
            if (-not (Test-Path $InstallerPath)) {
                Write-Host "Descargando IIS URL Rewrite..."
                Invoke-WebRequest `
                    -Uri "https://download.microsoft.com/download/1/2/8/128E2E0C-1125-48B4-AB5E-829F25AC6235/urlrewrite2.exe" `
                    -OutFile $InstallerPath `
                    -UseBasicParsing
            }

            $Process = Start-Process -FilePath $InstallerPath -ArgumentList "/install", "/quiet", "/norestart" -PassThru -Wait
            if ($Process.ExitCode -ne 0) {
                Write-Warning "urlrewrite2.exe devolvio codigo $($Process.ExitCode)."
            }

            Restart-EsteliPOSIIS
            $Installed = Wait-EsteliPOSUrlRewriteModule
        } catch {
            Write-Warning "No se pudo descargar URL Rewrite: $($_.Exception.Message)"
        }
    }

    if (-not $Installed) {
        throw @"
No se pudo instalar IIS URL Rewrite (requerido por public\web.config).
Opciones:
1. Copie urlrewrite2.exe en deployment\windows\assets\ e instale de nuevo.
2. Descarguelo desde https://www.iis.net/downloads/microsoft/url-rewrite
3. Instale el .exe manualmente como administrador y reinicie IIS (iisreset /restart).
4. Vuelva a ejecutar Instalar-EsteliPOS.bat opcion 1.
"@
    }
}

function Test-EsteliPOSIISSupported {
    $Edition = (Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion" -ErrorAction SilentlyContinue).EditionID

    if ($Edition -match "Core|Home|Starter") {
        return $false
    }

    return $true
}

function Test-EsteliPOSIISRoleEnabled {
    $Role = Get-WindowsOptionalFeature -Online -FeatureName IIS-WebServerRole -ErrorAction SilentlyContinue

    return [bool]($Role -and $Role.State -eq "Enabled")
}

function Enable-EsteliPOSIISFeatures {
    if (Test-EsteliPOSIISRoleEnabled) {
        Write-Host "IIS ya esta instalado en Windows."

        return
    }

    Write-Host "Activando rol IIS y componentes necesarios (puede tardar varios minutos)..."

    $Features = @(
        "IIS-WebServerRole",
        "IIS-WebServer",
        "IIS-CommonHttpFeatures",
        "IIS-StaticContent",
        "IIS-DefaultDocument",
        "IIS-HttpErrors",
        "IIS-ApplicationDevelopment",
        "IIS-CGI",
        "IIS-ISAPIExtensions",
        "IIS-ISAPIFilter",
        "IIS-HttpLogging",
        "IIS-RequestFiltering",
        "IIS-Performance",
        "IIS-HttpCompressionStatic",
        "IIS-ManagementConsole"
    )

    $DismOutput = & dism.exe /online /enable-feature /featurename:IIS-WebServerRole /all /NoRestart 2>&1
    foreach ($Line in $DismOutput) {
        if ($Line -match "Error|failed|denegado|denied") {
            Write-Warning $Line
        }
    }

    if (-not (Test-EsteliPOSIISRoleEnabled)) {
        foreach ($Feature in $Features) {
            $State = Get-WindowsOptionalFeature -Online -FeatureName $Feature -ErrorAction SilentlyContinue
            if ($State -and $State.State -ne "Enabled") {
                Write-Host "Activando $Feature..."
                Enable-WindowsOptionalFeature -Online -FeatureName $Feature -All -NoRestart | Out-Null
            }
        }
    }

    if (-not (Test-EsteliPOSIISRoleEnabled)) {
        throw @"
No se pudo instalar IIS automaticamente.
Verifique que Windows sea Pro, Enterprise o Education (IIS no esta en Windows Home).
Si acaba de activar IIS, reinicie el PC y ejecute el instalador de nuevo.
Alternativa: use la opcion 2 (Simple) del instalador.
"@
    }

    $Role = Get-WindowsOptionalFeature -Online -FeatureName IIS-WebServerRole -ErrorAction SilentlyContinue
    if ($Role.RestartNeeded) {
        Write-Warning "Windows puede requerir reinicio para completar IIS. Si la instalacion falla despues, reinicie y vuelva a ejecutar."
    }
}

function Start-EsteliPOSIISService {
    $Service = Get-Service W3SVC -ErrorAction SilentlyContinue
    if (-not $Service) {
        throw "El servicio IIS (W3SVC) no existe. IIS no se instalo correctamente en este equipo."
    }

    if ($Service.StartType -eq "Disabled") {
        Set-Service W3SVC -StartupType Automatic
    }

    if ($Service.Status -ne "Running") {
        Write-Host "Iniciando servicio World Wide Web Publishing (W3SVC)..."
        Start-Service W3SVC -ErrorAction Stop
    }
}

function Install-EsteliPOSIISPlatform {
    if (-not (Test-EsteliPOSIISSupported)) {
        throw @"
IIS no esta disponible en Windows Home o ediciones basicas.
Use la opcion 2 (Simple) del instalador o actualice a Windows Pro/Enterprise.
"@
    }

    Write-Host ""
    Write-Host "=== Instalacion automatica de IIS ===" -ForegroundColor Cyan
    Enable-EsteliPOSIISFeatures
    Start-EsteliPOSIISService

    Import-Module WebAdministration -ErrorAction Stop | Out-Null
    Write-Host "IIS instalado y servicio W3SVC en ejecucion." -ForegroundColor Green
    Write-Host ""
}

function Invoke-EsteliPOSAppCmd {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)

    $AppCmd = Join-Path $env:windir "system32\inetsrv\appcmd.exe"
    $Output = & $AppCmd @Arguments 2>&1
    $ExitCode = $LASTEXITCODE

    if ($ExitCode -ne 0) {
        $Detail = ($Output | Out-String).Trim()
        throw "appcmd fallo (codigo $ExitCode): $Detail"
    }

    return $Output
}

function Resolve-EsteliPOSIISPortBinding {
    param(
        [Parameter(Mandatory = $true)][int]$Port,
        [Parameter(Mandatory = $true)][string]$SiteName
    )

    Import-Module WebAdministration -ErrorAction Stop

    foreach ($Site in Get-Website) {
        foreach ($Binding in Get-WebBinding -Name $Site.Name) {
            if ($Binding.bindingInformation -notlike "*:${Port}:*") {
                continue
            }

            if ($Site.Name -eq $SiteName) {
                continue
            }

            Write-Host "Liberando puerto $Port del sitio IIS '$($Site.Name)'..."
            Remove-WebBinding -Name $Site.Name -BindingInformation $Binding.bindingInformation -Protocol $Binding.protocol -ErrorAction Stop
        }
    }
}

function Test-EsteliPOSHttpPortInUse {
    param([Parameter(Mandatory = $true)][int]$Port)

    $Netstat = & netstat.exe -ano -p tcp 2>$null |
        Where-Object { $_ -match ":$Port\s" -and $_ -match "LISTENING" }

    return [bool]$Netstat
}

function Register-EsteliPOSPhpFastCgi {
    param(
        [Parameter(Mandatory = $true)][string]$PhpCgiPath,
        [int]$MaxInstances = 4
    )

    $PhpCgiPath = (Resolve-Path $PhpCgiPath).Path
    if (-not (Test-Path $PhpCgiPath)) {
        throw "No existe php-cgi.exe en: $PhpCgiPath"
    }

    $PhpDir = Split-Path $PhpCgiPath -Parent
    $Existing = Invoke-EsteliPOSAppCmd @(
        "list", "config", "-section:system.webServer/fastCgi", "/text:fullPath"
    ) | Where-Object { $_ -eq $PhpCgiPath }

    if (-not $Existing) {
        Invoke-EsteliPOSAppCmd @(
            "set", "config", "-section:system.webServer/fastCgi",
            "/+`"[fullPath='$PhpCgiPath',maxInstances='$MaxInstances',instanceMaxRequests='10000',activityTimeout='600',requestTimeout='600']`"",
            "/commit:apphost"
        ) | Out-Null
    } else {
        Invoke-EsteliPOSAppCmd @(
            "set", "config", "-section:system.webServer/fastCgi",
            "/`"[fullPath='$PhpCgiPath'].maxInstances:$MaxInstances`"",
            "/commit:apphost"
        ) | Out-Null
    }

    if (-not $EnvironmentVariables) {
        $AppCmd = Join-Path $env:windir "system32\inetsrv\appcmd.exe"
        $EnvironmentVariables = & $AppCmd list config -section:system.webServer/fastCgi `
            "/`"[fullPath='$PhpCgiPath'].environmentVariables.[name='PHPRC']`"" /text:value 2>$null
    }

    if (-not $EnvironmentVariables) {
        Invoke-EsteliPOSAppCmd @(
            "set", "config", "-section:system.webServer/fastCgi",
            "/+`"[fullPath='$PhpCgiPath'].environmentVariables.[name='PHPRC',value='$PhpDir']`"",
            "/commit:apphost"
        ) | Out-Null
    }
}

function Set-EsteliPOSIISPermissions {
    param([Parameter(Mandatory = $true)][string]$ProjectRoot)

    $PoolName = Get-EsteliPOSIISAppPoolName
    $Paths = @(
        (Join-Path $ProjectRoot "storage"),
        (Join-Path $ProjectRoot "bootstrap\cache"),
        (Join-Path $ProjectRoot "database")
    )

    foreach ($Path in $Paths) {
        if (-not (Test-Path $Path)) {
            continue
        }

        & icacls.exe $Path /grant "IIS_IUSRS:(OI)(CI)M" /T /Q | Out-Null
        & icacls.exe $Path /grant "IIS AppPool\${PoolName}:(OI)(CI)M" /T /Q | Out-Null
    }
}

function Install-EsteliPOSIISSite {
    param(
        [Parameter(Mandatory = $true)][string]$ProjectRoot,
        [Parameter(Mandatory = $true)][string]$PhpCgiPath,
        [Parameter(Mandatory = $true)][int]$Port,
        [int]$FastCgiMaxInstances = 4,
        [switch]$SkipPlatformInstall
    )

    $SiteName = Get-EsteliPOSIISSiteName
    $PoolName = Get-EsteliPOSIISAppPoolName
    $PublicPath = Join-Path $ProjectRoot "public"
    $WebConfigPath = Join-Path $PublicPath "web.config"

    if (-not (Test-Path $WebConfigPath)) {
        throw "Falta public\web.config en el paquete de EsteliPOS."
    }

    if (-not $SkipPlatformInstall) {
        Install-EsteliPOSIISPlatform
    } else {
        Import-Module WebAdministration -ErrorAction Stop
    }

    try {
        Write-Host "Paso 1/6: IIS URL Rewrite..."
        Install-EsteliPOSUrlRewriteModule
    } catch {
        throw "URL Rewrite: $($_.Exception.Message)"
    }

    try {
        Write-Host "Paso 2/6: Registro PHP FastCGI..."
        Register-EsteliPOSPhpFastCgi -PhpCgiPath $PhpCgiPath -MaxInstances $FastCgiMaxInstances
    } catch {
        throw "FastCGI PHP: $($_.Exception.Message)"
    }

    try {
        Write-Host "Paso 3/6: Preparando puerto $Port..."

        if (Test-Path "IIS:\Sites\$SiteName") {
            Stop-Website -Name $SiteName -ErrorAction SilentlyContinue
            Remove-Website -Name $SiteName -ErrorAction SilentlyContinue
        }

        Resolve-EsteliPOSIISPortBinding -Port $Port -SiteName $SiteName

        if (Test-EsteliPOSHttpPortInUse -Port $Port) {
            $BlockingProcess = & netstat.exe -ano -p tcp 2>$null |
                Where-Object { $_ -match ":$Port\s" -and $_ -match "LISTENING" } |
                ForEach-Object {
                    if ($_ -match "\s+(\d+)\s*$") {
                        return $Matches[1]
                    }
                } | Select-Object -First 1

            throw @"
El puerto $Port ya esta en uso (PID $BlockingProcess).
Cierre el programa que lo usa o ejecute: netstat -ano | findstr :$Port
"@
        }
    } catch {
        throw "Puerto ${Port}: $($_.Exception.Message)"
    }

    try {
        Write-Host "Paso 4/6: Application pool y sitio IIS..."

        if (Test-Path "IIS:\AppPools\$PoolName") {
            Stop-WebAppPool -Name $PoolName -ErrorAction SilentlyContinue
            Remove-WebAppPool -Name $PoolName -ErrorAction Stop
        }

        New-WebAppPool -Name $PoolName -ErrorAction Stop | Out-Null
        Set-ItemProperty "IIS:\AppPools\$PoolName" -Name managedRuntimeVersion -Value "" -ErrorAction Stop
        Set-ItemProperty "IIS:\AppPools\$PoolName" -Name startMode -Value "AlwaysRunning" -ErrorAction Stop

        if (-not (Test-Path $PublicPath)) {
            throw "No existe la carpeta public en: $PublicPath"
        }

        New-Website -Name $SiteName -Port $Port -PhysicalPath $PublicPath -ApplicationPool $PoolName -Force -ErrorAction Stop | Out-Null
    } catch {
        throw "Sitio IIS: $($_.Exception.Message)"
    }

    try {
        Write-Host "Paso 5/6: Handler PHP..."
        $SitePath = "IIS:\Sites\$SiteName"
        $ResolvedPhpCgiPath = (Resolve-Path $PhpCgiPath).Path

        Clear-WebConfiguration -Filter "system.webServer/handlers" -PSPath $SitePath -ErrorAction SilentlyContinue
        Remove-WebHandler -Name "EsteliPOS-PHP" -PSPath $SitePath -ErrorAction SilentlyContinue

        Add-WebHandler `
            -Name "EsteliPOS-PHP" `
            -Path "*.php" `
            -Verb "*" `
            -Modules "FastCgiModule" `
            -ScriptProcessor $ResolvedPhpCgiPath `
            -ResourceType "Either" `
            -PSPath $SitePath `
            -ErrorAction Stop

        Set-EsteliPOSIISPermissions -ProjectRoot $ProjectRoot
    } catch {
        throw "Handler PHP: $($_.Exception.Message)"
    }

    try {
        Write-Host "Paso 6/6: Iniciando sitio..."
        Start-WebAppPool -Name $PoolName -ErrorAction Stop
        Start-Website -Name $SiteName -ErrorAction Stop
    } catch {
        throw "Inicio del sitio: $($_.Exception.Message). Si menciona 'rewrite', instale URL Rewrite y ejecute iisreset /restart."
    }

    Write-Host "Sitio IIS '$SiteName' activo en http://127.0.0.1:$Port" -ForegroundColor Green
}

function Start-EsteliPOSIISSite {
    param([int]$Port = 0)

    $SiteName = Get-EsteliPOSIISSiteName
    $PoolName = Get-EsteliPOSIISAppPoolName

    Import-Module WebAdministration -ErrorAction Stop

    $Service = Get-Service W3SVC -ErrorAction SilentlyContinue
    if ($Service -and $Service.Status -ne "Running") {
        Start-Service W3SVC
    }

    if (-not (Test-Path "IIS:\Sites\$SiteName")) {
        throw "El sitio IIS '$SiteName' no existe. Ejecute Deploy-EsteliPOS.ps1 con -ServerProfile IIS."
    }

    if ($Port -gt 0) {
        $Binding = Get-WebBinding -Name $SiteName -Protocol "http" -ErrorAction SilentlyContinue
        if ($Binding -and $Binding.bindingInformation -notlike "*:${Port}:*") {
            Write-Warning "El sitio IIS usa el puerto $($Binding.bindingInformation), no $Port."
        }
    }

    if ((Get-WebAppPoolState -Name $PoolName).Value -ne "Started") {
        Start-WebAppPool -Name $PoolName
    }

    if ((Get-Website -Name $SiteName).State -ne "Started") {
        Start-Website -Name $SiteName
    }
}

function Stop-EsteliPOSIISSite {
    $SiteName = Get-EsteliPOSIISSiteName
    $PoolName = Get-EsteliPOSIISAppPoolName

    Import-Module WebAdministration -ErrorAction SilentlyContinue

    if (Test-Path "IIS:\Sites\$SiteName") {
        Stop-Website -Name $SiteName -ErrorAction SilentlyContinue
    }

    if (Test-Path "IIS:\AppPools\$PoolName") {
        Stop-WebAppPool -Name $PoolName -ErrorAction SilentlyContinue
    }
}

function Test-EsteliPOSIISSite {
    return Test-Path "IIS:\Sites\$(Get-EsteliPOSIISSiteName)"
}

function Get-EsteliPOSResolvedServerProfile {
    param(
        [ValidateSet("Simple", "IIS", "Auto")]
        [string]$ServerProfile = "Auto"
    )

    if ($ServerProfile -ne "Auto") {
        return $ServerProfile
    }

    $DeploymentConfig = Get-EsteliPOSDeploymentConfig
    if ($DeploymentConfig -and $DeploymentConfig.server_profile) {
        $SavedProfile = [string]$DeploymentConfig.server_profile
        if ($SavedProfile -ieq "iis") {
            return "IIS"
        }

        return "Simple"
    }

    if (Test-EsteliPOSIISSite) {
        return "IIS"
    }

    return "Simple"
}
