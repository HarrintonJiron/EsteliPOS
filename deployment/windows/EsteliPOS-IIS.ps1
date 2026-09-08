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

function Test-EsteliPOSUrlRewriteInstaller {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [string]$ExpectedSha256 = ""
    )

    if (-not (Test-Path $Path)) {
        return $false
    }

    if ([string]::IsNullOrWhiteSpace($ExpectedSha256)) {
        return $true
    }

    $ActualSha256 = (Get-FileHash -Path $Path -Algorithm SHA256).Hash

    return $ActualSha256 -eq $ExpectedSha256
}

function Invoke-EsteliPOSUrlRewriteInstaller {
    param([Parameter(Mandatory = $true)][string]$InstallerPath)

    Write-Host "Instalando IIS URL Rewrite desde: $InstallerPath"

    if ([IO.Path]::GetExtension($InstallerPath) -eq ".msi") {
        $Process = Start-Process -FilePath "msiexec.exe" `
            -ArgumentList "/i", "`"$InstallerPath`"", "/qn", "/norestart" `
            -PassThru `
            -Wait
    } else {
        $Process = Start-Process -FilePath $InstallerPath `
            -ArgumentList "/install", "/quiet", "/norestart" `
            -PassThru `
            -Wait
    }

    if ($Process.ExitCode -notin @(0, 1641, 3010)) {
        throw "El instalador de IIS URL Rewrite devolvio codigo $($Process.ExitCode)."
    }

    if ($Process.ExitCode -in @(1641, 3010)) {
        Write-Warning "URL Rewrite solicito reiniciar Windows (codigo $($Process.ExitCode))."
    }

    Restart-EsteliPOSIIS
}

function Install-EsteliPOSUrlRewriteModule {
    if (Test-EsteliPOSUrlRewriteModule) {
        Write-Host "IIS URL Rewrite ya esta instalado."

        return
    }

    $ExpectedSha256 = "37342FF2F585F263F34F48E9DE59EB1051D61015A8E967DBDE4075716230A32A"
    $BundledMsi = Join-Path $PSScriptRoot "assets\rewrite_amd64_en-US.msi"
    $BundledExe = Join-Path $PSScriptRoot "assets\urlrewrite2.exe"
    $Installed = $false

    foreach ($InstallerPath in @($BundledMsi, $BundledExe)) {
        $ExpectedHash = if ($InstallerPath -eq $BundledMsi) { $ExpectedSha256 } else { "" }
        if (-not (Test-EsteliPOSUrlRewriteInstaller -Path $InstallerPath -ExpectedSha256 $ExpectedHash)) {
            continue
        }

        try {
            Invoke-EsteliPOSUrlRewriteInstaller -InstallerPath $InstallerPath
            $Installed = Wait-EsteliPOSUrlRewriteModule -MaxAttempts 10
        } catch {
            Write-Warning "No se pudo instalar URL Rewrite desde el paquete local: $($_.Exception.Message)"
        }

        if ($Installed) {
            break
        }
    }

    if (-not $Installed) {
        $InstallerPath = Join-Path $env:TEMP "rewrite_amd64_en-US.msi"
        try {
            if (-not (Test-EsteliPOSUrlRewriteInstaller -Path $InstallerPath -ExpectedSha256 $ExpectedSha256)) {
                Remove-Item $InstallerPath -Force -ErrorAction SilentlyContinue
                Write-Host "Descargando IIS URL Rewrite 2.1 x64 desde Microsoft..."
                [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
                Invoke-WebRequest `
                    -Uri "https://download.microsoft.com/download/1/2/8/128E2E22-C1B9-44A4-BE2A-5859ED1D4592/rewrite_amd64_en-US.msi" `
                    -OutFile $InstallerPath `
                    -UseBasicParsing
            }

            if (-not (Test-EsteliPOSUrlRewriteInstaller -Path $InstallerPath -ExpectedSha256 $ExpectedSha256)) {
                throw "La verificacion SHA-256 del instalador descargado no coincide."
            }

            Invoke-EsteliPOSUrlRewriteInstaller -InstallerPath $InstallerPath
            $Installed = Wait-EsteliPOSUrlRewriteModule -MaxAttempts 10
        } catch {
            Write-Warning "No se pudo descargar o instalar URL Rewrite: $($_.Exception.Message)"
        }
    }

    if (-not $Installed) {
        throw @"
No se pudo activar IIS URL Rewrite (requerido por public\web.config).
El paquete incluye rewrite_amd64_en-US.msi verificado para instalacion offline.
Opciones:
1. Reinicie Windows si IIS o URL Rewrite solicitaron reinicio.
2. Ejecute deployment\windows\assets\rewrite_amd64_en-US.msi como administrador.
3. Ejecute iisreset /restart y vuelva a abrir Instalar-EsteliPOS.bat opcion 1.
4. Si Windows es Home, use la opcion 2 (Servidor Simple).
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
        "IIS-WebServerManagementTools",
        "IIS-ManagementConsole",
        "IIS-ManagementScriptingTools"
    )

    foreach ($Feature in $Features) {
        $State = Get-WindowsOptionalFeature -Online -FeatureName $Feature -ErrorAction SilentlyContinue
        if (-not $State) {
            throw "Windows no reconoce el componente requerido: $Feature"
        }
        if ($State.State -ne "Enabled") {
            Write-Host "Activando $Feature..."
            Enable-WindowsOptionalFeature -Online -FeatureName $Feature -All -NoRestart -ErrorAction Stop | Out-Null
        }
    }

    $MissingFeatures = @($Features | Where-Object {
            $State = Get-WindowsOptionalFeature -Online -FeatureName $_ -ErrorAction SilentlyContinue
            -not $State -or $State.State -ne "Enabled"
        })
    if ($MissingFeatures.Count -gt 0) {
        throw @"
No se pudieron activar todos los componentes de IIS: $($MissingFeatures -join ', ').
Verifique que Windows sea Pro, Enterprise o Education (IIS no esta en Windows Home).
Si acaba de activar IIS, reinicie el PC y ejecute el instalador de nuevo.
Alternativa: use la opcion 2 (Simple) del instalador.
"@
    }

    Write-Host "Todos los componentes requeridos de IIS estan activos." -ForegroundColor Green
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

function Import-EsteliPOSWebAdministration {
    $Candidates = @(
        (Join-Path $env:windir "System32\WindowsPowerShell\v1.0\Modules\WebAdministration\WebAdministration.psd1"),
        "WebAdministration"
    )

    Remove-Module WebAdministration -Force -ErrorAction SilentlyContinue

    foreach ($Candidate in $Candidates) {
        if ($Candidate -ne "WebAdministration" -and -not (Test-Path $Candidate)) {
            continue
        }

        try {
            Import-Module $Candidate -Force -ErrorAction Stop | Out-Null
        } catch {
            continue
        }

        if (Get-Command New-Website -ErrorAction SilentlyContinue) {
            return
        }
    }

    throw @"
No se pudo cargar el modulo WebAdministration (cmdlets IIS).
Active 'IIS Management Scripts and Tools' (IIS-ManagementScriptingTools),
reinicie PowerShell como administrador y vuelva a instalar.
"@
}

function Register-EsteliPOSPhpHandler {
    param(
        [Parameter(Mandatory = $true)][string]$SiteName,
        [Parameter(Mandatory = $true)][string]$PhpCgiPath
    )

    # Usar appcmd siempre: en varias PCs Windows Add-WebHandler no existe
    # aunque WebAdministration cargue (New-Website/Remove-WebHandler si pueden).
    $ResolvedPhpCgiPath = (Resolve-Path $PhpCgiPath).Path
    $HandlerName = "EsteliPOS-PHP"

    Write-Host "Registrando handler PHP con appcmd..."

    $ExistingHandlers = (& (Join-Path $env:windir "system32\inetsrv\appcmd.exe") `
        "list", "config", $SiteName, "-section:system.webServer/handlers") | Out-String

    if ($ExistingHandlers -match [regex]::Escape("name=`"$HandlerName`"") -or
        $ExistingHandlers -match [regex]::Escape("name='$HandlerName'")) {
        try {
            Invoke-EsteliPOSAppCmd @(
                "set", "config", $SiteName,
                "-section:system.webServer/handlers",
                "/-`"[name='$HandlerName']`"",
                "/commit:apphost"
            ) | Out-Null
        } catch {
            # Continuar e intentar crear el handler.
        }
    }

    Invoke-EsteliPOSAppCmd @(
        "set", "config", $SiteName,
        "-section:system.webServer/handlers",
        "/+`"[name='$HandlerName',path='*.php',verb='*',modules='FastCgiModule',scriptProcessor='$ResolvedPhpCgiPath',resourceType='Either',requireAccess='Script']`"",
        "/commit:apphost"
    ) | Out-Null
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

    Import-EsteliPOSWebAdministration
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

    Import-EsteliPOSWebAdministration

    foreach ($Site in Get-Website) {
        foreach ($Binding in Get-WebBinding -Name $Site.Name) {
            if ($Binding.bindingInformation -notlike "*:${Port}:*") {
                continue
            }

            if ($Site.Name -eq $SiteName) {
                continue
            }

            throw "El puerto $Port ya pertenece al sitio IIS '$($Site.Name)'. Seleccione otro puerto o quite esa vinculacion manualmente."
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
    $EnvironmentVariables = $null
    if (-not (Test-Path $PhpCgiPath)) {
        throw "No existe php-cgi.exe en: $PhpCgiPath"
    }

    $PhpDir = Split-Path $PhpCgiPath -Parent
    $FastCgiConfiguration = Invoke-EsteliPOSAppCmd @(
        "list", "config", "-section:system.webServer/fastCgi"
    ) | Out-String
    $Existing = $FastCgiConfiguration -match [regex]::Escape($PhpCgiPath)

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

    $FastCgiConfiguration = Invoke-EsteliPOSAppCmd @(
        "list", "config", "-section:system.webServer/fastCgi"
    ) | Out-String
    $EnvironmentVariables = $FastCgiConfiguration -match "PHPRC"

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
        Import-EsteliPOSWebAdministration
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
        Register-EsteliPOSPhpHandler -SiteName $SiteName -PhpCgiPath $PhpCgiPath
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

    Import-EsteliPOSWebAdministration

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
