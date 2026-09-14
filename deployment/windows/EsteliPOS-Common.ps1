. (Join-Path $PSScriptRoot "EsteliPOS-IIS.ps1")

if ($null -eq $script:EsteliPOSCommonLoaded) {
    $script:EsteliPOSProjectRootOverride = $null
    $script:EsteliPOSCommonLoaded = $true
}

function Set-EsteliPOSProjectRootOverride([string]$Path) {
    if ([string]::IsNullOrWhiteSpace($Path)) {
        $script:EsteliPOSProjectRootOverride = $null
        return
    }

    $script:EsteliPOSProjectRootOverride = (Resolve-Path -LiteralPath $Path -ErrorAction Stop).Path
}

function Test-EsteliPOSProjectRoot([string]$Path) {
    if ([string]::IsNullOrWhiteSpace($Path) -or -not (Test-Path -LiteralPath $Path)) {
        return $false
    }

    return (Test-Path -LiteralPath (Join-Path $Path "artisan")) -and (
        (Test-Path -LiteralPath (Join-Path $Path "composer.json")) -or
        (Test-Path -LiteralPath (Join-Path $Path ".env")) -or
        (Test-Path -LiteralPath (Join-Path $Path "database"))
    )
}

function Get-EsteliPOSProjectRoot {
    if (-not [string]::IsNullOrWhiteSpace($script:EsteliPOSProjectRootOverride)) {
        $override = $script:EsteliPOSProjectRootOverride
        if (Test-EsteliPOSProjectRoot $override) {
            return (Resolve-Path -LiteralPath $override).Path
        }
    }

    $candidates = @(
        (Join-Path $PSScriptRoot "..\.."),
        $PSScriptRoot,
        (Join-Path $PSScriptRoot ".."),
        (Get-Location).Path
    )

    foreach ($candidate in $candidates) {
        try {
            $resolved = (Resolve-Path -LiteralPath $candidate -ErrorAction Stop).Path
        } catch {
            continue
        }

        if (Test-EsteliPOSProjectRoot $resolved) {
            return $resolved
        }
    }

    $cursor = $PSScriptRoot
    for ($i = 0; $i -lt 6; $i++) {
        if (Test-EsteliPOSProjectRoot $cursor) {
            return (Resolve-Path -LiteralPath $cursor).Path
        }
        $parent = Split-Path -Parent $cursor
        if ([string]::IsNullOrWhiteSpace($parent) -or $parent -eq $cursor) {
            break
        }
        $cursor = $parent
    }

    throw "No se encontro la carpeta de instalacion de EsteliPOS (artisan/composer.json). Ejecute el actualizador desde C:\Northlink\EsteliPOS."
}

function Get-EsteliPOSWindowsScriptsDir {
    $projectRoot = Get-EsteliPOSProjectRoot
    $preferred = Join-Path $projectRoot "deployment\windows"
    if (Test-Path -LiteralPath (Join-Path $preferred "EsteliPOS-Common.ps1")) {
        return $preferred
    }
    if (Test-Path -LiteralPath (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")) {
        return $PSScriptRoot
    }
    throw "No se encontro deployment\windows\EsteliPOS-Common.ps1 dentro de la instalacion."
}

function Get-EsteliPOSDeploymentConfigPath {
    return Join-Path (Get-EsteliPOSProjectRoot) "storage\app\deployment.json"
}

function Get-EsteliPOSDeploymentConfig {
    $ConfigPath = Get-EsteliPOSDeploymentConfigPath
    if (-not (Test-Path $ConfigPath)) {
        return $null
    }

    return Get-Content $ConfigPath -Raw | ConvertFrom-Json
}

function Save-EsteliPOSDeploymentConfig {
    param(
        [Parameter(Mandatory = $true)]
        [hashtable]$Config
    )

    $ConfigPath = Get-EsteliPOSDeploymentConfigPath
    $Directory = Split-Path $ConfigPath -Parent
    New-Item -ItemType Directory -Force -Path $Directory | Out-Null
    ($Config | ConvertTo-Json -Depth 4) | Set-Content -Path $ConfigPath -Encoding UTF8
}

function Get-EsteliPOSLanAddress {
    param([string]$PreferredAddress = "")

    if (-not [string]::IsNullOrWhiteSpace($PreferredAddress)) {
        return $PreferredAddress.Trim()
    }

    $SavedConfig = Get-EsteliPOSDeploymentConfig
    if ($SavedConfig -and -not [string]::IsNullOrWhiteSpace($SavedConfig.lan_address)) {
        return [string]$SavedConfig.lan_address
    }

    return Get-NetIPConfiguration |
        Where-Object { $_.IPv4DefaultGateway -and $_.IPv4Address } |
        ForEach-Object { $_.IPv4Address.IPAddress } |
        Where-Object { $_ -notlike "169.254.*" } |
        Select-Object -First 1
}

function Get-EsteliPOSMacAddress {
    $LanAddress = Get-EsteliPOSLanAddress
    if ([string]::IsNullOrWhiteSpace($LanAddress)) {
        return ""
    }

    $Interface = Get-NetIPConfiguration |
        Where-Object { $_.IPv4Address.IPAddress -contains $LanAddress } |
        Select-Object -First 1

    if (-not $Interface -or -not $Interface.InterfaceIndex) {
        return ""
    }

    return (Get-NetAdapter -InterfaceIndex $Interface.InterfaceIndex -ErrorAction SilentlyContinue).MacAddress
}

function Test-EsteliPOSPortBindable {
    param([Parameter(Mandatory = $true)][int]$Port)

    $Listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Any, $Port)
    try {
        $Listener.Start()

        return $true
    } catch {
        return $false
    } finally {
        $Listener.Stop()
    }
}

function Get-EsteliPOSAvailablePort {
    param(
        [Parameter(Mandatory = $true)][int]$PreferredPort,
        [int]$MaximumAttempts = 20
    )

    for ($Offset = 0; $Offset -lt $MaximumAttempts; $Offset++) {
        $Candidate = $PreferredPort + $Offset
        if ($Candidate -gt 65535) {
            break
        }
        if (Test-EsteliPOSPortBindable -Port $Candidate) {
            return $Candidate
        }
    }

    return 0
}

function Test-EsteliPOSFrontendAssets {
    param([string]$ProjectRoot)

    return (Test-Path (Join-Path $ProjectRoot "public\build\manifest.json")) -and
        (Test-Path (Join-Path $ProjectRoot "public\css\app-ui.css"))
}

function Write-EsteliPOSNetworkAccessPage {
    param(
        [Parameter(Mandatory = $true)][string]$AppUrl,
        [Parameter(Mandatory = $true)][string]$LanAddress,
        [Parameter(Mandatory = $true)][int]$Port,
        [string]$MacAddress = "",
        [string]$ComputerName = $env:COMPUTERNAME
    )

    $ProjectRoot = Get-EsteliPOSProjectRoot
    $OutputDirectory = Join-Path $ProjectRoot "storage\app\deployment"
    $AssetsDirectory = Join-Path $OutputDirectory "assets"
    $TemplatePath = Join-Path $PSScriptRoot "templates\acceso-red.html"
    $AssetSource = Join-Path $PSScriptRoot "assets\qrcode.min.js"

    New-Item -ItemType Directory -Force -Path $AssetsDirectory | Out-Null
    Copy-Item $AssetSource (Join-Path $AssetsDirectory "qrcode.min.js") -Force

    $Html = Get-Content $TemplatePath -Raw
    $Html = $Html.Replace("__APP_URL__", $AppUrl)
    $Html = $Html.Replace("__LAN_ADDRESS__", $LanAddress)
    $Html = $Html.Replace("__PORT__", "$Port")
    $Html = $Html.Replace("__MAC_ADDRESS__", $(if ($MacAddress) { $MacAddress } else { "No detectada" }))
    $Html = $Html.Replace("__COMPUTER_NAME__", $ComputerName)
    $Html = $Html.Replace("__GENERATED_AT__", (Get-Date -Format "dd/MM/yyyy HH:mm"))

    $OutputPath = Join-Path $OutputDirectory "acceso-red.html"
    [System.IO.File]::WriteAllText($OutputPath, $Html, (New-Object System.Text.UTF8Encoding($false)))

    return $OutputPath
}

function Resolve-EsteliPOSPhpExecutable {
    param([string]$PreferredPath = "")

    $candidates = @()

    if (-not [string]::IsNullOrWhiteSpace($PreferredPath)) {
        $candidates += $PreferredPath.Trim()
    }

    $SavedConfig = Get-EsteliPOSDeploymentConfig
    if ($SavedConfig -and -not [string]::IsNullOrWhiteSpace($SavedConfig.php_directory)) {
        $candidates += (Join-Path ([string]$SavedConfig.php_directory) "php.exe")
    }

    $candidates += @(
        "C:\EsteliPOS\PHP\php.exe",
        "C:\php\php.exe",
        "C:\PHP\php.exe",
        "C:\Northlink\PHP\php.exe",
        (Join-Path $env:ProgramFiles "PHP\php.exe"),
        (Join-Path ${env:ProgramFiles(x86)} "PHP\php.exe")
    )

    $PhpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($PhpCommand) {
        $candidates += $PhpCommand.Source
    }

    foreach ($candidate in $candidates) {
        if (-not [string]::IsNullOrWhiteSpace($candidate) -and (Test-Path -LiteralPath $candidate)) {
            return (Resolve-Path -LiteralPath $candidate).Path
        }
    }

    throw "No se encontro php.exe. Instale PHP en C:\EsteliPOS\PHP o C:\PHP, o agreguelo al PATH."
}

function Get-EsteliPOSSimpleListenHost {
    param([string]$HostAddress = "")

    if (-not [string]::IsNullOrWhiteSpace($HostAddress)) {
        return $HostAddress.Trim()
    }

    # Siempre escuchar en todas las interfaces para acceso LAN (tablets/otras PCs).
    return "0.0.0.0"
}

function Register-EsteliPOSServerTask {
    param(
        [Parameter(Mandatory = $true)][string]$StartScript,
        [Parameter(Mandatory = $true)][int]$Port,
        [ValidateSet("Simple", "IIS")]
        [string]$ServerProfile = "IIS"
    )

    $TaskName = "EsteliPOS - Servidor"
    $Arguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$StartScript`" -Port $Port -ServerProfile $ServerProfile"
    if ($ServerProfile -eq "Simple") {
        $Arguments += " -HostAddress 0.0.0.0"
    }
    $Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument $Arguments
    $LogonTrigger = New-ScheduledTaskTrigger -AtLogOn
    $BootTrigger = New-ScheduledTaskTrigger -AtStartup
    $BootTrigger.Delay = "PT2M"
    $Settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -RestartCount 3 `
        -RestartInterval (New-TimeSpan -Minutes 1)
    $Principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger @($LogonTrigger, $BootTrigger) -Settings $Settings -Principal $Principal -Description "Inicia el servidor local de EsteliPOS para acceso LAN" -Force | Out-Null
}

function Register-EsteliPOSFirewallRule {
    param(
        [Parameter(Mandatory = $true)][int]$Port
    )

    $Names = @(
        "EsteliPOS LAN - Puerto $Port",
        "EsteliPOS PHP $Port"
    )

    foreach ($FirewallName in $Names) {
        if (-not (Get-NetFirewallRule -DisplayName $FirewallName -ErrorAction SilentlyContinue)) {
            New-NetFirewallRule -DisplayName $FirewallName -Direction Inbound -Action Allow -Protocol TCP `
                -LocalPort $Port -Profile Private,Domain -RemoteAddress LocalSubnet | Out-Null
        }
    }
}

function Stop-EsteliPOSSimpleListeners {
    param(
        [string]$ProjectRoot = "",
        [int]$Port = 0
    )

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        $ProjectRoot = Get-EsteliPOSProjectRoot
    }

    $PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"
    if (Test-Path -LiteralPath $PidFile) {
        $ExistingPid = 0
        try { $ExistingPid = [int](Get-Content -LiteralPath $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1) } catch { }
        if ($ExistingPid -gt 0) {
            & taskkill.exe /PID $ExistingPid /T /F 2>$null | Out-Null
        }
        Remove-Item -LiteralPath $PidFile -Force -ErrorAction SilentlyContinue
    }

    $normalizedRoot = $ProjectRoot.TrimEnd('\')
    $phpProcs = @(Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue)
    foreach ($proc in $phpProcs) {
        $cmd = [string]$proc.CommandLine
        if ([string]::IsNullOrWhiteSpace($cmd)) {
            continue
        }
        $isServe = ($cmd -match "artisan\s+serve") -or ($cmd -match "-S\s+")
        if (-not $isServe) {
            continue
        }
        $isOurs = ($cmd -like "*$normalizedRoot*") -or ($cmd -match "EsteliPOS")
        $portMatch = ($Port -le 0) -or ($cmd -match [regex]::Escape(":$Port"))
        if ($isOurs -and $portMatch) {
            & taskkill.exe /PID $proc.ProcessId /T /F 2>$null | Out-Null
        }
    }

    if ($Port -gt 0) {
        $listeners = @(Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
        foreach ($listener in $listeners) {
            $owner = Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue
            if ($owner -and ($owner.ProcessName -ieq "php")) {
                & taskkill.exe /PID $owner.Id /T /F 2>$null | Out-Null
            }
        }
    }
}

function Test-EsteliPOSTcpOpen {
    param(
        [string]$Address = "127.0.0.1",
        [Parameter(Mandatory = $true)][int]$Port,
        [int]$TimeoutMs = 800
    )

    $client = $null
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $async = $client.BeginConnect($Address, $Port, $null, $null)
        if (-not $async.AsyncWaitHandle.WaitOne($TimeoutMs, $false)) {
            return $false
        }
        $client.EndConnect($async)
        return $true
    } catch {
        return $false
    } finally {
        if ($client) {
            $client.Close()
        }
    }
}

function Invoke-EsteliPOSLocalHttp {
    param(
        [Parameter(Mandatory = $true)][string]$Uri,
        [int]$TimeoutMs = 8000
    )

    $request = [System.Net.HttpWebRequest]::Create($Uri)
    $request.Method = "GET"
    $request.Timeout = $TimeoutMs
    $request.ReadWriteTimeout = $TimeoutMs
    $request.AllowAutoRedirect = $false
    $request.KeepAlive = $false
    $request.Proxy = New-Object System.Net.WebProxy
    $request.UserAgent = "EsteliPOS"

    try {
        $response = $request.GetResponse()
        $code = [int]$response.StatusCode
        $response.Close()
        return $code
    } catch [System.Net.WebException] {
        $webResponse = $_.Exception.Response
        if ($webResponse) {
            $code = [int]$webResponse.StatusCode
            $webResponse.Close()
            return $code
        }
        throw
    }
}

function Wait-EsteliPOSHttpReady {
    param(
        [Parameter(Mandatory = $true)][int]$Port,
        [string]$Path = "/up",
        [int]$Attempts = 40,
        [int]$DelaySeconds = 1,
        [string[]]$Addresses = @()
    )

    $hosts = New-Object System.Collections.ArrayList
    [void]$hosts.Add("127.0.0.1")
    [void]$hosts.Add("localhost")
    foreach ($extra in @($Addresses)) {
        if (-not [string]::IsNullOrWhiteSpace($extra) -and -not $hosts.Contains($extra)) {
            [void]$hosts.Add($extra)
        }
    }

    $paths = @($Path)
    if ($Path -ne "/up") {
        $paths = @("/up", $Path)
    }

    for ($Attempt = 1; $Attempt -le $Attempts; $Attempt++) {
        foreach ($addr in $hosts) {
            foreach ($probePath in $paths) {
                try {
                    $code = Invoke-EsteliPOSLocalHttp -Uri "http://${addr}:${Port}${probePath}" -TimeoutMs 8000
                    if ($code -ge 200 -and $code -lt 500) {
                        return $true
                    }
                } catch {
                }
            }
        }

        Start-Sleep -Seconds $DelaySeconds
    }

    return $false
}

function Get-EsteliPOSAppUrl {
    param(
        [int]$Port = 0,
        [switch]$PreferLocalhost
    )

    $DeploymentConfig = Get-EsteliPOSDeploymentConfig
    if ($Port -le 0 -and $DeploymentConfig -and $DeploymentConfig.port) {
        $Port = [int]$DeploymentConfig.port
    }
    if ($Port -le 0) {
        $Port = 8080
    }

    if ($PreferLocalhost) {
        return "http://127.0.0.1:$Port"
    }

    if ($DeploymentConfig -and -not [string]::IsNullOrWhiteSpace($DeploymentConfig.app_url)) {
        return ([string]$DeploymentConfig.app_url).TrimEnd("/")
    }

    $LanAddress = Get-EsteliPOSLanAddress
    if (-not [string]::IsNullOrWhiteSpace($LanAddress)) {
        return "http://${LanAddress}:$Port"
    }

    return "http://127.0.0.1:$Port"
}

function Test-EsteliPOSIpv4Address([string]$Value) {
    if ([string]::IsNullOrWhiteSpace($Value)) {
        return $false
    }

    $parsed = $null
    if (-not [System.Net.IPAddress]::TryParse($Value.Trim(), [ref]$parsed)) {
        return $false
    }

    return $parsed.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork
}

function ConvertTo-EsteliPOSSubnetMask([int]$PrefixLength) {
    if ($PrefixLength -lt 0) {
        $PrefixLength = 0
    }
    if ($PrefixLength -gt 32) {
        $PrefixLength = 32
    }

    $binary = ("1" * $PrefixLength).PadRight(32, "0")
    $octets = 0..3 | ForEach-Object { [Convert]::ToInt32($binary.Substring(($_ * 8), 8), 2) }

    return ($octets -join ".")
}

function Test-EsteliPOSHasProductionData {
    param([string]$ProjectRoot = "")

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        try {
            $ProjectRoot = Get-EsteliPOSProjectRoot
        } catch {
            return $false
        }
    }

    $databasePath = Join-Path $ProjectRoot "database\database.sqlite"
    $envPath = Join-Path $ProjectRoot ".env"
    if (-not (Test-Path -LiteralPath $databasePath) -or -not (Test-Path -LiteralPath $envPath)) {
        return $false
    }

    return ((Get-Item -LiteralPath $databasePath).Length -gt 1024)
}

function Get-EsteliPOSLanInterface {
    param([string]$PreferredAddress = "")

    $lanAddress = if ([string]::IsNullOrWhiteSpace($PreferredAddress)) {
        Get-NetIPConfiguration |
            Where-Object { $_.IPv4DefaultGateway -and $_.IPv4Address } |
            ForEach-Object { $_.IPv4Address.IPAddress } |
            Where-Object { $_ -and ($_ -notlike "169.254.*") } |
            Select-Object -First 1
    } else {
        $PreferredAddress.Trim()
    }

    if ([string]::IsNullOrWhiteSpace($lanAddress)) {
        throw "No hay adaptador de red con IPv4 en la LAN."
    }

    $config = Get-NetIPConfiguration | Where-Object {
        $addresses = @($_.IPv4Address | ForEach-Object { $_.IPAddress })
        $addresses -contains $lanAddress
    } | Select-Object -First 1

    if (-not $config) {
        throw "No se encontro el adaptador de red para $lanAddress."
    }

    $adapter = Get-NetAdapter -InterfaceIndex $config.InterfaceIndex -ErrorAction Stop
    $ip = Get-NetIPAddress -InterfaceIndex $config.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object { $_.IPAddress -eq $lanAddress } |
        Select-Object -First 1
    $dns = Get-DnsClientServerAddress -InterfaceIndex $config.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue
    $ipInterface = Get-NetIPInterface -InterfaceIndex $config.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue

    $prefixLength = if ($ip -and $ip.PrefixLength) { [int]$ip.PrefixLength } else { 24 }
    $gateway = if ($config.IPv4DefaultGateway) { [string]$config.IPv4DefaultGateway.NextHop } else { "" }
    $dnsServers = @()
    if ($dns -and $dns.ServerAddresses) {
        $dnsServers = @($dns.ServerAddresses | Where-Object { $_ -and ($_ -notlike "fec0:*") })
    }
    if ($dnsServers.Count -eq 0 -and -not [string]::IsNullOrWhiteSpace($gateway)) {
        $dnsServers = @($gateway)
    }

    return [pscustomobject]@{
        InterfaceAlias = $adapter.Name
        InterfaceIndex = [int]$config.InterfaceIndex
        IpAddress      = $lanAddress
        PrefixLength   = $prefixLength
        Gateway        = $gateway
        DnsServers     = $dnsServers
        MacAddress     = [string]$adapter.MacAddress
        DhcpEnabled    = ($ipInterface -and ($ipInterface.Dhcp -eq "Enabled"))
    }
}

function Get-EsteliPOSNetworkSnapshot {
    $info = $null
    try {
        $info = Get-EsteliPOSLanInterface
    } catch {
        $info = $null
    }

    $port = 8080
    $saved = $null
    try {
        $saved = Get-EsteliPOSDeploymentConfig
    } catch {
        $saved = $null
    }
    if ($saved -and $saved.port) {
        $port = [int]$saved.port
    }

    $ip = if ($info) { [string]$info.IpAddress } else { "" }
    $mac = if ($info) { [string]$info.MacAddress } else { "" }
    $appUrl = if (-not [string]::IsNullOrWhiteSpace($ip)) { "http://${ip}:$port" } else { "http://127.0.0.1:$port" }

    return [pscustomobject]@{
        IpAddress       = $ip
        MacAddress      = $mac
        Port            = $port
        Gateway         = if ($info) { [string]$info.Gateway } else { "" }
        PrefixLength    = if ($info) { [int]$info.PrefixLength } else { 24 }
        DnsServers      = if ($info) { @($info.DnsServers) } else { @() }
        InterfaceAlias  = if ($info) { [string]$info.InterfaceAlias } else { "" }
        DhcpEnabled     = if ($info) { [bool]$info.DhcpEnabled } else { $true }
        AppUrl          = $appUrl
        ComputerName    = $env:COMPUTERNAME
    }
}

function Set-EsteliPOSStaticIpv4 {
    param(
        [string]$IpAddress = "",
        [int]$PrefixLength = 0,
        [string]$Gateway = "",
        [string[]]$DnsServers = @()
    )

    $current = Get-EsteliPOSLanInterface
    $targetIp = if ([string]::IsNullOrWhiteSpace($IpAddress)) { $current.IpAddress } else { $IpAddress.Trim() }
    if (-not (Test-EsteliPOSIpv4Address $targetIp)) {
        throw "La direccion IPv4 no es valida: $targetIp"
    }

    $prefix = if ($PrefixLength -gt 0) { $PrefixLength } else { [int]$current.PrefixLength }
    if ($prefix -lt 1 -or $prefix -gt 32) {
        $prefix = 24
    }
    $gatewayAddress = if ([string]::IsNullOrWhiteSpace($Gateway)) { [string]$current.Gateway } else { $Gateway.Trim() }
    $dnsList = if ($DnsServers -and $DnsServers.Count -gt 0) { @($DnsServers) } else { @($current.DnsServers) }
    $alias = $current.InterfaceAlias
    $index = $current.InterfaceIndex

    Write-Host "Fijando IP $targetIp/$prefix en adaptador $alias (DHCP desactivado)."

    try {
        Set-NetIPInterface -InterfaceIndex $index -AddressFamily IPv4 -Dhcp Disabled -ErrorAction Stop

        $existing = @(Get-NetIPAddress -InterfaceIndex $index -AddressFamily IPv4 -ErrorAction SilentlyContinue)
        $alreadyHasTarget = $existing | Where-Object { $_.IPAddress -eq $targetIp } | Select-Object -First 1

        if (-not $alreadyHasTarget) {
            foreach ($addr in $existing) {
                Remove-NetIPAddress -InterfaceIndex $index -IPAddress $addr.IPAddress -Confirm:$false -ErrorAction SilentlyContinue
            }

            if (-not [string]::IsNullOrWhiteSpace($gatewayAddress)) {
                Get-NetRoute -InterfaceIndex $index -DestinationPrefix "0.0.0.0/0" -ErrorAction SilentlyContinue |
                    Remove-NetRoute -Confirm:$false -ErrorAction SilentlyContinue
            }

            $newParams = @{
                InterfaceIndex = $index
                IPAddress      = $targetIp
                PrefixLength   = $prefix
                AddressFamily  = "IPv4"
                ErrorAction    = "Stop"
            }
            if (-not [string]::IsNullOrWhiteSpace($gatewayAddress)) {
                $newParams.DefaultGateway = $gatewayAddress
            }
            New-NetIPAddress @newParams | Out-Null
        } elseif (-not [string]::IsNullOrWhiteSpace($gatewayAddress)) {
            $defaultRoute = Get-NetRoute -InterfaceIndex $index -DestinationPrefix "0.0.0.0/0" -ErrorAction SilentlyContinue
            if (-not $defaultRoute) {
                New-NetRoute -InterfaceIndex $index -DestinationPrefix "0.0.0.0/0" -NextHop $gatewayAddress -ErrorAction SilentlyContinue | Out-Null
            }
        }

        if ($dnsList.Count -gt 0) {
            Set-DnsClientServerAddress -InterfaceIndex $index -ServerAddresses $dnsList -ErrorAction Stop
        }
    } catch {
        $mask = ConvertTo-EsteliPOSSubnetMask -PrefixLength $prefix
        $netshAddress = @(
            "interface", "ipv4", "set", "address",
            "name=$alias",
            "source=static",
            "address=$targetIp",
            "mask=$mask"
        )
        if (-not [string]::IsNullOrWhiteSpace($gatewayAddress)) {
            $netshAddress += "gateway=$gatewayAddress"
        }
        $addressResult = & netsh.exe @netshAddress
        if ($LASTEXITCODE -ne 0) {
            throw "No se pudo fijar la IP $targetIp en $alias. $($_.Exception.Message) $addressResult"
        }

        if ($dnsList.Count -gt 0) {
            $dnsResult = & netsh.exe interface ipv4 set dnsservers "name=$alias" source=static address=$($dnsList[0]) register=primary validate=no
            if ($LASTEXITCODE -ne 0) {
                Write-Warning "IP fijada, pero no se pudieron guardar los DNS: $dnsResult"
            }
        }
    }

    return Wait-EsteliPOSLanReady -PreferredAddress $targetIp -TimeoutSeconds 25
}

function Wait-EsteliPOSLanReady {
    param(
        [string]$PreferredAddress = "",
        [int]$TimeoutSeconds = 25
    )

    $deadline = (Get-Date).AddSeconds([Math]::Max(2, $TimeoutSeconds))
    $lastError = $null
    while ((Get-Date) -lt $deadline) {
        try {
            return Get-EsteliPOSLanInterface -PreferredAddress $PreferredAddress
        } catch {
            $lastError = $_
            Start-Sleep -Seconds 2
        }
    }

    if ($lastError) {
        throw $lastError
    }

    throw "La red local no volvio a estar lista despues de fijar la IP."
}

function Set-EsteliPOSLanProfilePrivate {
    param([int]$TimeoutSeconds = 30)

    Write-Host "Comprobando perfil de red de Windows (Privada permite tablets en LAN)..."
    $deadline = (Get-Date).AddSeconds([Math]::Max(4, $TimeoutSeconds))
    $lastError = ""

    while ((Get-Date) -lt $deadline) {
        $profiles = @()
        try {
            $profiles = @(Get-NetConnectionProfile -ErrorAction Stop |
                Where-Object { $_.IPv4Connectivity -ne "Disconnected" })
        } catch {
            $lastError = $_.Exception.Message
            Start-Sleep -Seconds 2
            continue
        }

        $publicProfiles = @($profiles | Where-Object { $_.NetworkCategory -eq "Public" })
        if ($publicProfiles.Count -eq 0) {
            $summary = @($profiles | ForEach-Object { "$($_.InterfaceAlias)=$($_.NetworkCategory)" })
            if ($summary.Count -gt 0) {
                Write-Host "Perfil de red: $($summary -join ', ')"
            }
            return $true
        }

        $changed = $true
        foreach ($profile in $publicProfiles) {
            try {
                Set-NetConnectionProfile -InterfaceIndex $profile.InterfaceIndex -NetworkCategory Private -ErrorAction Stop
            } catch {
                $changed = $false
                $lastError = $_.Exception.Message
            }
        }

        if ($changed) {
            Write-Host "La red activa se configuro como privada para permitir el acceso de otros dispositivos."
            return $true
        }

        if ($lastError -and ($lastError -notmatch "Identifying")) {
            Write-Warning "No se pudo marcar la red como privada: $lastError. EsteliPOS se instalara igual."
            return $false
        }

        Start-Sleep -Seconds 2
    }

    Write-Warning "Windows sigue identificando la red. EsteliPOS se instalara igual."
    if ($lastError) {
        Write-Warning $lastError
    }
    Write-Warning "Si tablets no entran, en Configuracion de Windows marque esta red como Privada."
    return $false
}

function Get-EsteliPOSBrowserPath {
    $candidates = @(
        "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
        "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
        "$env:LOCALAPPDATA\Microsoft\Edge\Application\msedge.exe",
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
    )

    return $candidates | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -First 1
}

function Test-EsteliPOSVcRedistributableInstalled {
    $key = Get-ItemProperty -Path "HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64" -ErrorAction SilentlyContinue
    if ($key -and [int]$key.Installed -eq 1) {
        return $true
    }

    $wow = Get-ItemProperty -Path "HKLM:\SOFTWARE\WOW6432Node\Microsoft\VisualStudio\14.0\VC\Runtimes\x64" -ErrorAction SilentlyContinue
    return ($wow -and [int]$wow.Installed -eq 1)
}

function Test-EsteliPOSWindowsHomeEdition {
    try {
        $edition = [string](Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion" -ErrorAction Stop).EditionID
        return ($edition -match "Core|Home")
    } catch {
        return $false
    }
}

function Get-EsteliPOSLatestInstallLog {
    param([string]$ProjectRoot = "")

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        $ProjectRoot = Get-EsteliPOSProjectRoot
    }
    $logDir = Join-Path $ProjectRoot "storage\logs"
    if (-not (Test-Path -LiteralPath $logDir)) {
        return $null
    }

    return Get-ChildItem -Path $logDir -Filter "install-*.log" -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1
}

function Copy-EsteliPOSTextToClipboard {
    param([Parameter(Mandatory = $true)][string]$Text)

    if ([string]::IsNullOrWhiteSpace($Text)) {
        return $false
    }

    try {
        Set-Clipboard -Value $Text
        return $true
    } catch {
        return $false
    }
}

function Get-EsteliPOSPrerequisiteReport {
    param(
        [ValidateSet("Simple", "IIS")]
        [string]$ServerProfile = "IIS",
        [string]$ProjectRoot = ""
    )

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        $ProjectRoot = Get-EsteliPOSProjectRoot
    }

    $items = New-Object System.Collections.ArrayList
    function Add-PrereqItem([string]$Name, [string]$Status, [string]$Detail) {
        [void]$items.Add((New-Object psobject -Property @{
                    Name   = $Name
                    Status = $Status
                    Detail = $Detail
                }))
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if ($principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Add-PrereqItem "Administrador" "OK" "El instalador corre con privilegios elevados."
    } else {
        Add-PrereqItem "Administrador" "FAIL" "Ejecute Instalar-EsteliPOS-Grafico.bat como administrador."
    }

    $os = (Get-CimInstance Win32_OperatingSystem -ErrorAction SilentlyContinue)
    $osName = if ($os) { [string]$os.Caption } else { "Windows" }
    if (Test-EsteliPOSWindowsHomeEdition) {
        if ($ServerProfile -eq "IIS") {
            Add-PrereqItem "Windows" "FAIL" "$osName (Home) no incluye IIS. Elija perfil Simple."
        } else {
            Add-PrereqItem "Windows" "OK" "$osName Home: use perfil Simple."
        }
    } else {
        Add-PrereqItem "Windows" "OK" $osName
    }

    $phpPath = $null
    try {
        $phpPath = Resolve-EsteliPOSPhpExecutable
        $version = (& $phpPath -n -r "echo PHP_VERSION;" 2>$null)
        if ($version) {
            Add-PrereqItem "PHP" "OK" "$version en $phpPath"
        } else {
            Add-PrereqItem "PHP" "WARN" "Encontrado en $phpPath, pero no arranca (falta Visual C++?)."
        }
        $cgi = Join-Path (Split-Path $phpPath -Parent) "php-cgi.exe"
        if ($ServerProfile -eq "IIS") {
            if (Test-Path -LiteralPath $cgi) {
                Add-PrereqItem "php-cgi" "OK" $cgi
            } else {
                Add-PrereqItem "php-cgi" "WARN" "No hay php-cgi.exe; el instalador extraera PHP Thread Safe del paquete."
            }
        }
    } catch {
        $bundledPhp = Join-Path $PSScriptRoot "assets\php-ts.zip"
        if (Test-Path -LiteralPath $bundledPhp) {
            Add-PrereqItem "PHP" "WARN" "Aun no esta instalado. Se extraera del paquete (php-ts.zip)."
        } else {
            Add-PrereqItem "PHP" "FAIL" "No hay PHP ni php-ts.zip en el paquete."
        }
    }

    if (Test-EsteliPOSVcRedistributableInstalled) {
        Add-PrereqItem "Visual C++" "OK" "Redistributable x64 instalado."
    } else {
        $vc = Join-Path $PSScriptRoot "assets\vc_redist.x64.exe"
        if (Test-Path -LiteralPath $vc) {
            Add-PrereqItem "Visual C++" "WARN" "No instalado. El instalador usara vc_redist.x64.exe incluido."
        } else {
            Add-PrereqItem "Visual C++" "FAIL" "Falta Visual C++ y no viene vc_redist.x64.exe."
        }
    }

    $browser = Get-EsteliPOSBrowserPath
    if ($browser) {
        Add-PrereqItem "Navegador" "OK" $browser
    } else {
        Add-PrereqItem "Navegador" "WARN" "No se encontro Edge ni Chrome. La instalacion seguira; instale uno para abrir EsteliPOS."
    }

    if (Test-Path -LiteralPath (Join-Path $ProjectRoot "vendor\autoload.php")) {
        Add-PrereqItem "Paquete vendor" "OK" "Dependencias PHP incluidas."
    } else {
        Add-PrereqItem "Paquete vendor" "FAIL" "Falta vendor. Use el ZIP de produccion, no el codigo fuente."
    }

    if (Test-EsteliPOSFrontendAssets -ProjectRoot $ProjectRoot) {
        Add-PrereqItem "Frontend" "OK" "public/build y CSS presentes."
    } else {
        Add-PrereqItem "Frontend" "FAIL" "Faltan recursos web compilados."
    }

    if (Test-Path -LiteralPath (Join-Path $ProjectRoot "public\web.config")) {
        Add-PrereqItem "web.config" "OK" "Listo para IIS."
    } else {
        Add-PrereqItem "web.config" "FAIL" "Falta public\web.config."
    }

    $rewrite = Join-Path $PSScriptRoot "assets\rewrite_amd64_en-US.msi"
    if ($ServerProfile -eq "IIS") {
        if (Test-Path -LiteralPath $rewrite) {
            Add-PrereqItem "URL Rewrite" "OK" "MSI incluido en el paquete."
        } else {
            Add-PrereqItem "URL Rewrite" "WARN" "No esta el MSI; se intentara instalar IIS igual."
        }
    }

    try {
        $lan = Get-EsteliPOSLanInterface
        $dhcpText = "IP estatica"
        if ($true -eq $lan.DhcpEnabled) {
            $dhcpText = "DHCP"
        }
        Add-PrereqItem "Red LAN" "OK" ("{0} ({1}) MAC {2}" -f [string]$lan.IpAddress, $dhcpText, [string]$lan.MacAddress)
    } catch {
        Add-PrereqItem "Red LAN" "FAIL" "Conecte Wi-Fi o cable de la ferreteria (IPv4)."
    }

    $failCount = 0
    foreach ($entry in $items) {
        if ([string]$entry.Status -eq "FAIL") {
            $failCount++
        }
    }

    $snapshot = New-Object object[] $items.Count
    if ($items.Count -gt 0) {
        $items.CopyTo($snapshot)
    }

    return (New-Object psobject -Property @{
            Items         = $snapshot
            Ready         = ($failCount -eq 0)
            ServerProfile = $ServerProfile
            ProjectRoot   = $ProjectRoot
        })
}

function Format-EsteliPOSPrerequisiteReport {
    param($Report)

    if ($null -eq $Report) {
        return "No se pudo generar el reporte de dependencias."
    }

    $lines = New-Object System.Collections.ArrayList
    foreach ($item in $Report.Items) {
        $status = [string]$item.Status
        if ($status -eq "OK") {
            $mark = "[OK]  "
        } elseif ($status -eq "WARN") {
            $mark = "[AVISO]"
        } elseif ($status -eq "FAIL") {
            $mark = "[FALLO]"
        } else {
            $mark = "[INFO]"
        }
        [void]$lines.Add(("{0} {1}: {2}" -f $mark, [string]$item.Name, [string]$item.Detail))
    }

    [void]$lines.Add("")
    if ($Report.Ready) {
        [void]$lines.Add("Listo para instalar.")
    } else {
        [void]$lines.Add("Hay fallos que hay que corregir antes de instalar.")
    }

    return [string]::Join([Environment]::NewLine, $lines.ToArray())
}
