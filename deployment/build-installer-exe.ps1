[CmdletBinding()]
param([string]$Version = "")

$ErrorActionPreference = "Stop"
$DeploymentRoot = Split-Path -Parent $PSCommandPath
$ProjectRoot = Split-Path -Parent $DeploymentRoot
$VersionFile = Join-Path $ProjectRoot "VERSION"
$IssPath = Join-Path $DeploymentRoot "windows\installer\EsteliPOS-Setup.iss"
$Payload = Join-Path $DeploymentRoot "produccion1.0.zip"
$Checksum = "$Payload.sha256"

if ([string]::IsNullOrWhiteSpace($Version)) {
    $Version = (Get-Content -LiteralPath $VersionFile -Raw).Trim()
}
if ($Version -notmatch '^\d+\.\d+\.\d+([.-][A-Za-z0-9]+)*$') {
    throw "VERSION inválida: $Version"
}
foreach ($required in @($IssPath, $Payload, $Checksum)) {
    if (-not (Test-Path -LiteralPath $required)) { throw "Falta el archivo requerido: $required" }
}

$expected = ((Get-Content -LiteralPath $Checksum -Raw).Trim() -split '\s+')[0].ToLowerInvariant()
$actual = (Get-FileHash -LiteralPath $Payload -Algorithm SHA256).Hash.ToLowerInvariant()
if ($expected -ne $actual) { throw "El checksum de produccion1.0.zip no coincide. Reconstruya el release antes del EXE." }

$candidates = @(
    "$env:ProgramFiles(x86)\Inno Setup 6\ISCC.exe",
    "$env:ProgramFiles\Inno Setup 6\ISCC.exe"
)
$iscc = $candidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
if (-not $iscc) {
    $command = Get-Command ISCC.exe -ErrorAction SilentlyContinue
    if ($command) { $iscc = $command.Source }
}
if (-not $iscc) {
    throw "No se encontró Inno Setup 6. Instálelo desde https://jrsoftware.org/isdl.php y vuelva a ejecutar este script."
}

& $iscc "/DAppVersion=$Version" $IssPath
if ($LASTEXITCODE -ne 0) { throw "Inno Setup terminó con código $LASTEXITCODE." }

$output = Join-Path $DeploymentRoot "EsteliPOS-Setup-$Version.exe"
if (-not (Test-Path -LiteralPath $output)) { throw "No se generó el EXE esperado: $output" }
$hash = (Get-FileHash -LiteralPath $output -Algorithm SHA256).Hash.ToLowerInvariant()
Set-Content -LiteralPath "$output.sha256" -Value "$hash  $(Split-Path -Leaf $output)" -Encoding ASCII
Write-Host "Instalador generado: $output"
Write-Host "SHA-256: $hash"
