$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$PidFile = Join-Path $ProjectRoot "storage\app\estelipos.pid"

if (-not (Test-Path $PidFile)) { exit 0 }

$ServerPid = [int](Get-Content $PidFile -ErrorAction SilentlyContinue)
$ServerProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $ServerPid" -ErrorAction SilentlyContinue
if ($ServerProcess -and $ServerProcess.CommandLine -match "artisan\s+serve") {
    & taskkill.exe /PID $ServerPid /T /F | Out-Null
}
Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
