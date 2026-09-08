[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$PayloadZip,
    [Parameter(Mandatory = $true)][string]$ChecksumFile
)

$ErrorActionPreference = "Stop"
$LogRoot = Join-Path $env:ProgramData "EsteliPOS\Logs"
$LogPath = Join-Path $LogRoot ("setup-exe-{0}.log" -f (Get-Date -Format "yyyyMMdd-HHmmss"))
$WorkRoot = Join-Path $env:TEMP ("EsteliPOS-Setup-{0}" -f [guid]::NewGuid().ToString("N"))

function Write-SetupLog([string]$Message) {
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -LiteralPath $LogPath -Value $line -Encoding UTF8
}

function Show-SetupFailure([string]$Message) {
    Add-Type -AssemblyName System.Windows.Forms
    $detail = "$Message`r`n`r`nRegistro técnico:`r`n$LogPath"
    [System.Windows.Forms.MessageBox]::Show($detail, "EsteliPOS - No se pudo instalar", "OK", "Error") | Out-Null
    Start-Process -FilePath "notepad.exe" -ArgumentList @($LogPath) -ErrorAction SilentlyContinue
}

try {
    New-Item -ItemType Directory -Path $LogRoot -Force | Out-Null
    New-Item -ItemType Directory -Path $WorkRoot -Force | Out-Null
    Write-SetupLog "Inicio del instalador EXE. Equipo=$env:COMPUTERNAME Usuario=$env:USERNAME"

    if (-not (Test-Path -LiteralPath $PayloadZip)) {
        throw "El instalador no contiene el paquete de la aplicación."
    }
    if (-not (Test-Path -LiteralPath $ChecksumFile)) {
        throw "El instalador no contiene la firma SHA-256 del paquete."
    }

    $expected = ((Get-Content -LiteralPath $ChecksumFile -Raw).Trim() -split '\s+')[0].ToLowerInvariant()
    $actual = (Get-FileHash -LiteralPath $PayloadZip -Algorithm SHA256).Hash.ToLowerInvariant()
    Write-SetupLog "SHA256 esperado=$expected actual=$actual"
    if ($expected -notmatch '^[a-f0-9]{64}$' -or $actual -ne $expected) {
        throw "La verificación de integridad falló. Descargue nuevamente el instalador; no se modificó la instalación existente."
    }

    Expand-Archive -LiteralPath $PayloadZip -DestinationPath $WorkRoot -Force
    $launcher = Join-Path $WorkRoot "INSTALAR.bat"
    if (-not (Test-Path -LiteralPath $launcher)) {
        throw "El paquete verificado no contiene INSTALAR.bat."
    }

    Write-SetupLog "Paquete verificado y extraído. Iniciando asistente gráfico."
    $process = Start-Process -FilePath $launcher -WorkingDirectory $WorkRoot -Wait -PassThru
    Write-SetupLog "El asistente terminó con código $($process.ExitCode)."
    if ($process.ExitCode -ne 0) {
        throw "El asistente de EsteliPOS terminó con el código $($process.ExitCode). Revise el informe que se abrirá a continuación."
    }

    Write-SetupLog "Instalación finalizada correctamente."
    exit 0
} catch {
    try { Write-SetupLog ("ERROR: " + $_.Exception.Message + "`r`n" + $_.ScriptStackTrace) } catch {}
    Show-SetupFailure $_.Exception.Message
    exit 1
} finally {
    Remove-Item -LiteralPath $WorkRoot -Recurse -Force -ErrorAction SilentlyContinue
}
