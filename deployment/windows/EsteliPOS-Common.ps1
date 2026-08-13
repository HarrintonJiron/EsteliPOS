. (Join-Path $PSScriptRoot "EsteliPOS-IIS.ps1")

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
