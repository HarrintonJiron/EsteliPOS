[CmdletBinding()]
param(
    [Parameter(Mandatory)][string]$InstallerRoot,
    [Parameter(Mandatory)][string]$ApplicationSource
)

$ErrorActionPreference = 'Stop'
$InstallerRoot = [IO.Path]::GetFullPath($InstallerRoot).TrimEnd('\')
$ApplicationSource = [IO.Path]::GetFullPath($ApplicationSource).TrimEnd('\')
$payloadRoot = Join-Path $InstallerRoot 'payload'
$scriptRoot = Join-Path $InstallerRoot 'scripts'

function Require-File([string]$Path, [string]$Label) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "$Label no esta disponible: $Path"
    }
}

function Assert-PowerShellSyntax([string]$Path) {
    $tokens = $null
    $errors = $null
    [Management.Automation.Language.Parser]::ParseFile($Path, [ref]$tokens, [ref]$errors) | Out-Null
    if ($errors.Count -gt 0) {
        $details = ($errors | ForEach-Object { "linea $($_.Extent.StartLineNumber): $($_.Message)" }) -join '; '
        throw "Sintaxis invalida en $Path`: $details"
    }
}

function Assert-ZipEntry([string]$Archive, [string]$Pattern, [string]$Label) {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [IO.Compression.ZipFile]::OpenRead($Archive)
    try {
        $match = $zip.Entries | Where-Object { $_.FullName -match $Pattern } | Select-Object -First 1
        if (-not $match) {
            throw "$Label no se encontro en $Archive"
        }
    } finally {
        $zip.Dispose()
    }
}

foreach ($script in @('Install-EsteliPOS.ps1', 'Uninstall-EsteliPOS.ps1', 'Build-EsteliPOSInstaller.ps1')) {
    $path = Join-Path $scriptRoot $script
    Require-File $path $script
    Assert-PowerShellSyntax $path
}

$installScriptText = Get-Content -LiteralPath (Join-Path $scriptRoot 'Install-EsteliPOS.ps1') -Raw
if ($installScriptText -notmatch "\(\?m\)\^#\\s\*LoadModule rewrite_module") {
    throw 'La configuracion no habilita mod_rewrite tolerando espacios despues de #.'
}
if ($installScriptText -notmatch 'Join-Path \$apacheRoot ''php\.ini''') {
    throw 'La configuracion no copia php.ini al directorio que Apache inspecciona al iniciar.'
}

foreach ($requiredFile in @('artisan', 'public\index.php', 'vendor\autoload.php', 'public\build\manifest.json')) {
    Require-File (Join-Path $ApplicationSource $requiredFile) "Aplicacion/$requiredFile"
}
if (Test-Path -LiteralPath (Join-Path $ApplicationSource '.env')) {
    throw 'El paquete de la aplicacion no debe incluir .env.'
}
if (Test-Path -LiteralPath (Join-Path $ApplicationSource 'database\database.sqlite')) {
    throw 'El paquete de la aplicacion no debe incluir database.sqlite.'
}

Assert-ZipEntry (Join-Path $payloadRoot 'apache-win64.zip') '(^|/)bin/httpd\.exe$' 'Apache httpd.exe'
Assert-ZipEntry (Join-Path $payloadRoot 'php-8.5.10-Win32-vs17-x64.zip') '^php8apache2_4\.dll$' 'Modulo PHP para Apache'
Assert-ZipEntry (Join-Path $payloadRoot 'mysql-8.0.21-winx64.zip') '(^|/)bin/mysqld\.exe$' 'MySQL mysqld.exe'

$tempRoot = Join-Path ([IO.Path]::GetTempPath()) ("EsteliPOS-installer-layout-" + [guid]::NewGuid().ToString('N'))
$source = Join-Path $tempRoot 'source'
$destination = Join-Path $tempRoot 'application'
try {
    New-Item -ItemType Directory -Path (Join-Path $source 'app\Http'), (Join-Path $source 'public'), $destination -Force | Out-Null
    Set-Content -LiteralPath (Join-Path $source 'artisan') -Value 'artisan' -Encoding ASCII
    Set-Content -LiteralPath (Join-Path $source 'app\Http\Controller.php') -Value 'controller' -Encoding ASCII
    Set-Content -LiteralPath (Join-Path $source 'public\index.php') -Value 'index' -Encoding ASCII
    Get-ChildItem -LiteralPath $source -Force | Copy-Item -Destination $destination -Recurse -Force
    Require-File (Join-Path $destination 'artisan') 'Prueba de copia/artisan'
    Require-File (Join-Path $destination 'app\Http\Controller.php') 'Prueba de copia/app'
    Require-File (Join-Path $destination 'public\index.php') 'Prueba de copia/public'
} finally {
    $resolvedTemp = [IO.Path]::GetFullPath($tempRoot)
    $allowedTemp = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
    if ($resolvedTemp.StartsWith($allowedTemp, [StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedTemp)) {
        Remove-Item -LiteralPath $resolvedTemp -Recurse -Force
    }
}

Write-Host 'Verificacion del instalador completada correctamente.'
