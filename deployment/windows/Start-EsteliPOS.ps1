[CmdletBinding()]
param([int]$Port = 8080)

$ErrorActionPreference = "Stop"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$PhpPath = (Get-Command php.exe -ErrorAction Stop).Source
$PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"
$StdOut = Join-Path $ProjectRoot "storage\logs\server-output.log"
$StdErr = Join-Path $ProjectRoot "storage\logs\server-error.log"

if (Test-Path $PidFile) {
    $ExistingPid = [int](Get-Content $PidFile -ErrorAction SilentlyContinue)
    $ExistingProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $ExistingPid" -ErrorAction SilentlyContinue
    if ($ExistingProcess -and $ExistingProcess.CommandLine -match "artisan\s+serve") {
        try {
            $ExistingResponse = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/login" -UseBasicParsing -TimeoutSec 3
            if ($ExistingResponse.StatusCode -eq 200) { exit 0 }
        } catch {
            & taskkill.exe /PID $ExistingPid /T /F | Out-Null
        }
    }
    Remove-Item $PidFile -Force
}

$Process = Start-Process -FilePath $PhpPath `
    -ArgumentList @("artisan", "serve", "--host=127.0.0.1", "--port=$Port", "--no-reload") `
    -WorkingDirectory $ProjectRoot -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput $StdOut -RedirectStandardError $StdErr
$Process.Id | Set-Content -Path $PidFile -Encoding ASCII

$Ready = $false
for ($Attempt = 1; $Attempt -le 20; $Attempt++) {
    Start-Sleep -Seconds 1
    try {
        $Response = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/login" -UseBasicParsing -TimeoutSec 2
        if ($Response.StatusCode -eq 200) {
            $Ready = $true
            break
        }
    } catch {
        if ($Process.HasExited) { break }
    }
}

if (-not $Ready) {
    if (-not $Process.HasExited) { Stop-Process -Id $Process.Id -Force }
    Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
    Write-Error "EsteliPOS no respondio. Revisa storage\logs\server-error.log."
    exit 1
}

exit 0
