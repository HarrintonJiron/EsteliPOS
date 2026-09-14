$Script:EsteliPOSRequiredPhpExtensions = @(
    "ctype", "curl", "dom", "fileinfo", "gd", "mbstring", "openssl", "pdo_sqlite", "sqlite3", "tokenizer", "xml", "zip"
)

$Script:EsteliPOSMinimumPhpVersion = [version]"8.4.1"
$Script:EsteliPOSDefaultPhpDirectory = "C:\EsteliPOS\PHP"
$Script:EsteliPOSPhpReleasesJsonUrl = "https://windows.php.net/downloads/releases/releases.json"
$Script:EsteliPOSPhpDownloadBaseUrl = "https://windows.php.net/downloads/releases/"
$Script:EsteliPOSVcRedistUrl = "https://aka.ms/vs/17/release/vc_redist.x64.exe"
$Script:EsteliPOSBundledPhpSha256 = "7b57fc9840273ab153834d0e2bd06e0bcf4fead36e381182b4b8fe9cedff3174"
$Script:EsteliPOSBundledVcRedistSha256 = "cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b"

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

function Get-EsteliPOSBundledPhpZipPath {
    $AssetsDirectory = Join-Path $PSScriptRoot "assets"
    $Exact = Join-Path $AssetsDirectory "php-ts.zip"
    if (Test-Path $Exact) {
        return $Exact
    }

    return Get-ChildItem -Path $AssetsDirectory -Filter "php-*-Win32-*-x64.zip" -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -notmatch "-nts-" } |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1 -ExpandProperty FullName
}

function Update-EsteliPOSCurrentProcessPath {
    param([string]$Directory)

    if ([string]::IsNullOrWhiteSpace($Directory)) {
        return
    }

    $MachinePath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    $UserPath = [Environment]::GetEnvironmentVariable("Path", "User")
    $env:Path = "$Directory;$MachinePath;$UserPath"
}

function Add-EsteliPOSDirectoryToMachinePath {
    param([Parameter(Mandatory = $true)][string]$Directory)

    if (-not (Test-Path $Directory)) {
        throw "No existe el directorio para agregar al PATH: $Directory"
    }

    $NormalizedDirectory = (Resolve-Path $Directory).Path.TrimEnd('\')
    $MachinePath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    $Segments = @($MachinePath -split ";" | Where-Object { -not [string]::IsNullOrWhiteSpace($_) })

    $Filtered = @($Segments | Where-Object {
            $_.TrimEnd('\').ToLowerInvariant() -ne $NormalizedDirectory.ToLowerInvariant()
        })

    $NewPath = ($NormalizedDirectory, ($Filtered -join ";")) -join ";"
    [Environment]::SetEnvironmentVariable("Path", $NewPath, "Machine")
    Update-EsteliPOSCurrentProcessPath -Directory $NormalizedDirectory
}

function Get-EsteliPOSPhpDownloadCandidate {
    param(
        [string]$ReleasesJsonUrl = $Script:EsteliPOSPhpReleasesJsonUrl
    )

    $ReleaseData = Invoke-RestMethod -Uri $ReleasesJsonUrl -UseBasicParsing
    $PreferredBranches = @("8.4", "8.5")
    $ThreadSafeKeys = @("ts-vs17-x64", "ts-vs16-x64")

    foreach ($Branch in $PreferredBranches) {
        if (-not $ReleaseData.PSObject.Properties.Name.Contains($Branch)) {
            continue
        }

        $BranchData = $ReleaseData.$Branch
        foreach ($Key in $ThreadSafeKeys) {
            if ($BranchData.PSObject.Properties.Name.Contains($Key) -and $BranchData.$Key.zip.path) {
                return [pscustomobject]@{
                    Branch = $Branch
                    Version = [string]$BranchData.version
                    FileName = [string]$BranchData.$Key.zip.path
                    Sha256 = [string]$BranchData.$Key.zip.sha256
                    Url = "$Script:EsteliPOSPhpDownloadBaseUrl$($BranchData.$Key.zip.path)"
                }
            }
        }
    }

    throw "No se encontro PHP 8.4+ Thread Safe x64 en releases.json de windows.php.net."
}

function Install-EsteliPOSVcRedistributable {
    $Bundled = Join-Path $PSScriptRoot "assets\vc_redist.x64.exe"
    $UsesBundledInstaller = Test-Path $Bundled
    $InstallerPath = if ($UsesBundledInstaller) { $Bundled } else { Join-Path $env:TEMP "vc_redist.x64.exe" }

    if ($UsesBundledInstaller) {
        $Hash = (Get-FileHash -Path $InstallerPath -Algorithm SHA256).Hash.ToLowerInvariant()
        if ($Hash -ne $Script:EsteliPOSBundledVcRedistSha256) {
            throw "El instalador incluido de Visual C++ no coincide con el SHA256 esperado."
        }
        Write-Host "Instalando Microsoft Visual C++ Redistributable incluido..."
    } elseif (-not (Test-Path $InstallerPath)) {
        Write-Host "Descargando Microsoft Visual C++ Redistributable (requerido por PHP)..."
        Invoke-WebRequest -Uri $Script:EsteliPOSVcRedistUrl -OutFile $InstallerPath -UseBasicParsing
    } else {
        Write-Host "Instalando Microsoft Visual C++ Redistributable..."
    }

    $Signature = Get-AuthenticodeSignature -FilePath $InstallerPath
    if ($Signature.Status -ne "Valid" -or $Signature.SignerCertificate.Subject -notmatch "Microsoft Corporation") {
        throw "El instalador de Visual C++ no tiene una firma digital valida de Microsoft."
    }

    $Process = Start-Process -FilePath $InstallerPath -ArgumentList "/install", "/quiet", "/norestart" -PassThru -Wait
    if ($Process.ExitCode -notin @(0, 1638, 1641, 3010)) {
        throw "Visual C++ Redistributable termino con codigo $($Process.ExitCode)."
    }
    if ($Process.ExitCode -in @(1641, 3010)) {
        Write-Warning "Visual C++ Redistributable requiere reiniciar Windows. Termine la instalacion y reinicie el equipo."
    }
}

function Set-EsteliPOSPhpIni {
    param(
        [Parameter(Mandatory = $true)][string]$PhpDirectory,
        [Parameter(Mandatory = $true)][string]$ProjectRoot
    )

    $IniPath = Join-Path $PhpDirectory "php.ini"
    $ProductionIni = Join-Path $PhpDirectory "php.ini-production"
    $SnippetPath = Join-Path $PSScriptRoot "templates\php-production.ini.snippet"

    if (-not (Test-Path $IniPath)) {
        if (-not (Test-Path $ProductionIni)) {
            throw "No se encontro php.ini-production en $PhpDirectory"
        }
        Copy-Item $ProductionIni $IniPath -Force
    }

    $ExtDirectory = Join-Path $PhpDirectory "ext"
    $Lines = Get-Content $IniPath
    $SnippetLines = Get-Content $SnippetPath | Where-Object {
        $_ -notmatch "^\s*;" -and -not [string]::IsNullOrWhiteSpace($_)
    }

    foreach ($SnippetLine in $SnippetLines) {
        $Name = ($SnippetLine -split "=", 2)[0].Trim()
        if ($Name -eq "extension_dir") {
            $Replacement = "extension_dir = `"$ExtDirectory`""
        } elseif ($Name -eq "extension") {
            $ExtensionName = ($SnippetLine -split "=", 2)[1].Trim().Trim('"')
            $ExtensionPattern = "^\s*;?\s*extension\s*=\s*`"?(?:php_)?$([regex]::Escape($ExtensionName))(?:\.dll)?`"?\s*$"
            if ($Lines -match $ExtensionPattern) {
                $Lines = $Lines -replace $ExtensionPattern, "extension=$ExtensionName"
            } else {
                $Lines += "extension=$ExtensionName"
            }
            continue
        } else {
            $Replacement = $SnippetLine
        }

        if ($Lines -match "^$([regex]::Escape($Name))\s*=") {
            $Lines = $Lines -replace "^$([regex]::Escape($Name))\s*=.*$", $Replacement
        } else {
            $Lines += $Replacement
        }
    }

    $SeenExtensions = @{}
    $NormalizedLines = foreach ($Line in $Lines) {
        if ($Line -match "^\s*extension\s*=\s*`"?([^`"]+)`"?\s*$") {
            $ExtensionKey = $Matches[1].Trim().ToLowerInvariant()
            if ($SeenExtensions.ContainsKey($ExtensionKey)) {
                continue
            }
            $SeenExtensions[$ExtensionKey] = $true
        }
        $Line
    }

    [System.IO.File]::WriteAllLines($IniPath, $NormalizedLines, (New-Object System.Text.UTF8Encoding($false)))
}

function Test-EsteliPOSPhpInstallation {
    param(
        [ValidateSet("Simple", "IIS")]
        [string]$ServerProfile = "IIS",
        [string]$PhpPath = ""
    )

    $Result = [ordered]@{
        Ready = $false
        Issues = New-Object System.Collections.Generic.List[string]
        PhpPath = $null
        PhpCgiPath = $null
        Version = $null
        MissingExtensions = @()
    }

    if ([string]::IsNullOrWhiteSpace($PhpPath)) {
        try {
            $PhpPath = Resolve-EsteliPOSPhpExecutable
        } catch {
            $Result.Issues.Add("PHP no esta en el PATH ni en C:\EsteliPOS\PHP.")
            return [pscustomobject]$Result
        }
    }

    if (-not (Test-Path -LiteralPath $PhpPath)) {
        $Result.Issues.Add("No existe php.exe en $PhpPath")
        return [pscustomobject]$Result
    }

    $Result.PhpPath = $PhpPath
    try {
        $VersionOutput = @(& $PhpPath -n -r "echo PHP_VERSION;" 2>&1)
        if ($LASTEXITCODE -ne 0) {
            throw "php.exe termino con codigo $LASTEXITCODE`: $($VersionOutput -join ' ')"
        }
        $Result.Version = ($VersionOutput | Select-Object -Last 1).ToString().Trim()
    } catch {
        $Result.Issues.Add("PHP no pudo ejecutarse: $($_.Exception.Message)")
        return [pscustomobject]$Result
    }

    try {
        $DetectedVersion = [version]$Result.Version
    } catch {
        $Result.Issues.Add("PHP devolvio una version no valida: '$($Result.Version)'.")
        return [pscustomobject]$Result
    }

    if ($DetectedVersion -lt $Script:EsteliPOSMinimumPhpVersion) {
        $Result.Issues.Add("Se requiere PHP $($Script:EsteliPOSMinimumPhpVersion) o superior. Version actual: $($Result.Version).")
    }

    $Loaded = @(& $PhpPath -m 2>$null | ForEach-Object { $_.Trim().ToLowerInvariant() })
    $Result.MissingExtensions = @($Script:EsteliPOSRequiredPhpExtensions | Where-Object { $Loaded -notcontains $_ })
    foreach ($Extension in $Result.MissingExtensions) {
        $Result.Issues.Add("Falta extension PHP: $Extension")
    }

    if ($ServerProfile -eq "IIS") {
        $PhpCgiPath = Join-Path (Split-Path $PhpPath -Parent) "php-cgi.exe"
        if (Test-Path $PhpCgiPath) {
            $Result.PhpCgiPath = $PhpCgiPath
        } else {
            $Result.Issues.Add("No existe php-cgi.exe. Se requiere PHP Thread Safe (TS) para IIS.")
        }
    }

    $Result.Ready = ($Result.Issues.Count -eq 0)
    return [pscustomobject]$Result
}

function Expand-EsteliPOSPhpArchive {
    param(
        [Parameter(Mandatory = $true)][string]$ArchivePath,
        [Parameter(Mandatory = $true)][string]$DestinationDirectory
    )

    if (Test-Path $DestinationDirectory) {
        $BackupDirectory = "$DestinationDirectory.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Write-Host "Respaldando PHP anterior en $BackupDirectory"
        Move-Item $DestinationDirectory $BackupDirectory
    }

    New-Item -ItemType Directory -Force -Path $DestinationDirectory | Out-Null
    Expand-Archive -Path $ArchivePath -DestinationPath $DestinationDirectory -Force

    $NestedPhp = Get-ChildItem -Path $DestinationDirectory -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if ($NestedPhp -and $NestedPhp.DirectoryName -ne $DestinationDirectory) {
        Get-ChildItem -Path $NestedPhp.DirectoryName | Move-Item -Destination $DestinationDirectory -Force
    }
}

function Install-EsteliPOSPhpThreadSafe {
    param(
        [string]$InstallDirectory = $Script:EsteliPOSDefaultPhpDirectory,
        [string]$ProjectRoot = "",
        [switch]$SkipVcRedist
    )

    if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
        . (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1") | Out-Null
        $ProjectRoot = Get-EsteliPOSProjectRoot
    }

    $BundledZip = Get-EsteliPOSBundledPhpZipPath
    $DownloadArchive = Join-Path $env:TEMP "estelipos-php-ts.zip"
    $ArchivePath = $null

    if ($BundledZip) {
        Write-Host "Usando PHP Thread Safe incluido en el paquete: $BundledZip"
        $BundledHash = (Get-FileHash -Path $BundledZip -Algorithm SHA256).Hash.ToLowerInvariant()
        if ($BundledHash -ne $Script:EsteliPOSBundledPhpSha256) {
            throw "El paquete PHP incluido no coincide con el SHA256 esperado."
        }
        $ArchivePath = $BundledZip
    } else {
        $Candidate = Get-EsteliPOSPhpDownloadCandidate
        Write-Host "Descargando PHP $($Candidate.Version) Thread Safe x64..."
        Write-Host "Origen: $($Candidate.Url)"
        Invoke-WebRequest -Uri $Candidate.Url -OutFile $DownloadArchive -UseBasicParsing

        if ($Candidate.Sha256) {
            $Hash = (Get-FileHash -Path $DownloadArchive -Algorithm SHA256).Hash.ToLowerInvariant()
            if ($Hash -ne $Candidate.Sha256.ToLowerInvariant()) {
                throw "La descarga de PHP no coincide con el SHA256 esperado."
            }
        }

        $ArchivePath = $DownloadArchive
    }

    if (-not $SkipVcRedist) {
        if (Test-EsteliPOSVcRedistributableInstalled) {
            Write-Host "Visual C++ Redistributable x64 ya esta instalado."
        } else {
            try {
                Install-EsteliPOSVcRedistributable
            } catch {
                Write-Warning "No se pudo instalar VC++ Redistributable: $($_.Exception.Message)"
            }
        }
    }

    Write-Host "Instalando PHP en $InstallDirectory ..."
    Expand-EsteliPOSPhpArchive -ArchivePath $ArchivePath -DestinationDirectory $InstallDirectory

    if (-not (Test-Path (Join-Path $InstallDirectory "php.exe"))) {
        throw "La extraccion de PHP no dejo php.exe en $InstallDirectory"
    }
    if (-not (Test-Path (Join-Path $InstallDirectory "php-cgi.exe"))) {
        throw "El archivo descargado no incluye php-cgi.exe. Verifique que sea Thread Safe (TS), no NTS."
    }

    Set-EsteliPOSPhpIni -PhpDirectory $InstallDirectory -ProjectRoot $ProjectRoot
    Add-EsteliPOSDirectoryToMachinePath -Directory $InstallDirectory

    $InstalledPhpPath = Join-Path $InstallDirectory "php.exe"
    $VersionOutput = @(& $InstalledPhpPath -n -r "echo PHP_VERSION;" 2>&1)
    if ($LASTEXITCODE -ne 0) {
        throw @"
PHP fue extraido, pero php.exe no pudo iniciar (codigo $LASTEXITCODE).
Detalle: $($VersionOutput -join ' ')
Reinicie Windows y vuelva a ejecutar el instalador. Si persiste, reinstale Microsoft Visual C++ Redistributable x64.
"@
    }
    $Version = ($VersionOutput | Select-Object -Last 1).ToString().Trim()
    Write-Host "PHP Thread Safe $Version instalado en $InstallDirectory" -ForegroundColor Green

    return [pscustomobject]@{
        InstallDirectory = $InstallDirectory
        PhpPath = Join-Path $InstallDirectory "php.exe"
        PhpCgiPath = Join-Path $InstallDirectory "php-cgi.exe"
        Version = $Version
    }
}

function Ensure-EsteliPOSPhp {
    param(
        [ValidateSet("Simple", "IIS")]
        [string]$ServerProfile = "IIS",
        [string]$InstallDirectory = $Script:EsteliPOSDefaultPhpDirectory,
        [switch]$ForceReinstall
    )

    . (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1") | Out-Null
    $ProjectRoot = Get-EsteliPOSProjectRoot

    $Status = Test-EsteliPOSPhpInstallation -ServerProfile $ServerProfile
    if ($Status.Ready -and -not $ForceReinstall) {
        Write-Host "PHP listo: $($Status.Version) en $($Status.PhpPath)" -ForegroundColor Green
        return $Status
    }

    $HasSupportedVersion = $false
    if (-not [string]::IsNullOrWhiteSpace($Status.Version)) {
        try {
            $HasSupportedVersion = [version]$Status.Version -ge $Script:EsteliPOSMinimumPhpVersion
        } catch {
            $HasSupportedVersion = $false
        }
    }

    $NeedsThreadSafe = $ServerProfile -eq "IIS" -and (
        -not $Status.PhpPath -or
        ($Status.Issues | Where-Object { $_ -match "php-cgi" })
    )
    $NeedsInstall = -not $Status.PhpPath -or
        (-not $HasSupportedVersion) -or
        ($Status.MissingExtensions.Count -gt 0) -or
        $NeedsThreadSafe -or
        $ForceReinstall

    if (-not $NeedsInstall) {
        return $Status
    }

    if ($ServerProfile -eq "Simple" -and $Status.PhpPath -and -not $NeedsThreadSafe -and $Status.MissingExtensions.Count -eq 0 -and $HasSupportedVersion) {
        Write-Host "PHP actual es suficiente para perfil Simple." -ForegroundColor Green
        return $Status
    }

    Write-Host ""
    Write-Host "PHP no cumple los requisitos. Instalando PHP Thread Safe automaticamente..." -ForegroundColor Yellow
    foreach ($Issue in $Status.Issues) {
        Write-Host "  - $Issue" -ForegroundColor DarkYellow
    }
    Write-Host ""

    $ExistingManaged = Test-Path (Join-Path $InstallDirectory "php.exe")
    $ExistingManagedCgi = Test-Path (Join-Path $InstallDirectory "php-cgi.exe")
    if ($ExistingManaged -and $ExistingManagedCgi -and $Status.MissingExtensions.Count -gt 0 -and -not $NeedsThreadSafe) {
        Write-Host "Reconfigurando php.ini en $InstallDirectory ..."
        Set-EsteliPOSPhpIni -PhpDirectory $InstallDirectory -ProjectRoot $ProjectRoot
        Add-EsteliPOSDirectoryToMachinePath -Directory $InstallDirectory
        $ManagedPhpPath = Join-Path $InstallDirectory "php.exe"
        $Status = Test-EsteliPOSPhpInstallation -ServerProfile $ServerProfile -PhpPath $ManagedPhpPath
        if ($Status.Ready) {
            return $Status
        }
    }

    Install-EsteliPOSPhpThreadSafe -InstallDirectory $InstallDirectory -ProjectRoot $ProjectRoot | Out-Null

    $ManagedPhpPath = Join-Path $InstallDirectory "php.exe"
    $FinalStatus = Test-EsteliPOSPhpInstallation -ServerProfile $ServerProfile -PhpPath $ManagedPhpPath
    if (-not $FinalStatus.Ready) {
        throw "PHP se instalo pero la verificacion sigue fallando:`n- $($FinalStatus.Issues -join "`n- ")"
    }

    return $FinalStatus
}
