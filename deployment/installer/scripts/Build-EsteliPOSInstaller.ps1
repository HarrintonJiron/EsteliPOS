[CmdletBinding()]
param(
    [string]$ApacheArchive = (Join-Path $PSScriptRoot '..\payload\apache-win64.zip'),
    [string]$NsisCompiler = ${env:MAKENSIS},
    [string]$OutputDirectory = (Join-Path $env:USERPROFILE 'Downloads'),
    [switch]$SkipCompile,
    [switch]$SkipPublish
)

$ErrorActionPreference = 'Stop'
$installerRoot = Split-Path -Parent $PSScriptRoot
$projectRoot = Split-Path -Parent (Split-Path -Parent $installerRoot)
$payloadRoot = Join-Path $installerRoot 'payload'
$stagingRoot = Join-Path $installerRoot '.staging\application'
$manifestPath = Join-Path $payloadRoot 'manifest.json'
$versionIncludePath = Join-Path $installerRoot 'version.nsh'
$version = (Get-Content (Join-Path $projectRoot 'VERSION') -Raw).Trim()

function Require-File([string]$Path, [string]$Label) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "$Label no está disponible: $Path"
    }
}

function Get-ManifestComponent([string]$Name, [string]$Path) {
    $item = Get-Item -LiteralPath $Path
    [ordered]@{
        name = $Name
        file = $item.Name
        size = $item.Length
        sha256 = (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash.ToLowerInvariant()
    }
}

function Assert-ChildPath([string]$Path, [string]$Parent) {
    $resolvedPath = [IO.Path]::GetFullPath($Path).TrimEnd('\')
    $resolvedParent = [IO.Path]::GetFullPath($Parent).TrimEnd('\') + '\'
    if (-not $resolvedPath.StartsWith($resolvedParent, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Ruta fuera del directorio permitido: $resolvedPath"
    }
}

Require-File (Join-Path $payloadRoot 'php-8.5.10-Win32-vs17-x64.zip') 'PHP Thread Safe x64'
Require-File (Join-Path $payloadRoot 'mysql-8.0.21-winx64.zip') 'MySQL 8.x'
Require-File (Join-Path $payloadRoot 'vc_redist.x64.exe') 'Visual C++ Redistributable'
Require-File $ApacheArchive 'Apache HTTP Server x64'
Require-File (Join-Path $projectRoot 'vendor\autoload.php') 'Dependencias PHP de producción'
Require-File (Join-Path $projectRoot 'public\build\manifest.json') 'Assets frontend compilados'

Assert-ChildPath $stagingRoot $installerRoot
Remove-Item -LiteralPath $stagingRoot -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $stagingRoot -Force | Out-Null

$excludeDirectories = @('.git', '.github', '.vscode', '.cursor', 'node_modules', 'deployment', 'storage', 'backups', 'bootstrap\cache') |
    ForEach-Object { Join-Path $projectRoot $_ }
$excludeFiles = @('.env', '.financial-query.php', 'phpunit.xml', 'compose.yaml') |
    ForEach-Object { Join-Path $projectRoot $_ }
$robocopyArguments = @($projectRoot, $stagingRoot, '/E', '/XJ', '/NFL', '/NDL', '/NJH', '/NJS', '/XD') + $excludeDirectories + @('/XF') + $excludeFiles
& robocopy @robocopyArguments | Out-Null
if ($LASTEXITCODE -gt 7) {
    throw "No fue posible preparar application.zip. Robocopy devolvió $LASTEXITCODE."
}

Remove-Item -LiteralPath (Join-Path $stagingRoot '.env') -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath (Join-Path $stagingRoot 'database\database.sqlite') -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath (Join-Path $stagingRoot 'public\storage') -Recurse -Force -ErrorAction SilentlyContinue

New-Item -ItemType Directory -Path (Join-Path $stagingRoot 'storage\app\public') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $stagingRoot 'storage\framework\cache\data') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $stagingRoot 'storage\framework\sessions') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $stagingRoot 'storage\framework\views') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $stagingRoot 'storage\logs') -Force | Out-Null

Set-Content -LiteralPath $versionIncludePath -Value "!define VERSION `"$version`"" -Encoding ASCII

$manifest = [ordered]@{
    name = 'EsteliPOS'
    version = $version
    architecture = 'x64'
    generatedAt = (Get-Date).ToUniversalTime().ToString('o')
    components = @(
        Get-ManifestComponent 'Apache HTTP Server' $ApacheArchive
        Get-ManifestComponent 'PHP 8.5.10 Thread Safe' (Join-Path $payloadRoot 'php-8.5.10-Win32-vs17-x64.zip')
        Get-ManifestComponent 'MySQL 8.0.21' (Join-Path $payloadRoot 'mysql-8.0.21-winx64.zip')
        Get-ManifestComponent 'Visual C++ Redistributable x64' (Join-Path $payloadRoot 'vc_redist.x64.exe')
    )
}
$manifest | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $manifestPath -Encoding UTF8

& (Join-Path $PSScriptRoot 'Test-EsteliPOSInstaller.ps1') -InstallerRoot $installerRoot -ApplicationSource $stagingRoot

if ($SkipCompile) {
    Write-Host "Payload creado y verificado: $manifestPath"
    exit 0
}

if (-not $NsisCompiler) {
    $candidates = @(
        (Join-Path $installerRoot 'tools\nsis-portable\nsis-3.11\Bin\makensis.exe'),
        "$env:ProgramFiles(x86)\NSIS\makensis.exe",
        "$env:ProgramFiles\NSIS\makensis.exe"
    )
    $NsisCompiler = $candidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
}
Require-File $NsisCompiler 'Compilador NSIS makensis.exe'
New-Item -ItemType Directory -Path (Join-Path $installerRoot 'dist') -Force | Out-Null
& $NsisCompiler (Join-Path $installerRoot 'EsteliPOS.nsi')
if ($LASTEXITCODE -ne 0) {
    throw "La compilación NSIS falló con código $LASTEXITCODE."
}

$installer = Join-Path $installerRoot "dist\EsteliPOS-Setup-$version.exe"
Require-File $installer 'Instalador NSIS compilado'
if (-not $SkipPublish) {
    New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
    $publishedInstaller = Join-Path $OutputDirectory (Split-Path -Leaf $installer)
    Copy-Item -LiteralPath $installer -Destination $publishedInstaller -Force
    Write-Host "Instalador publicado: $publishedInstaller"
}
