[CmdletBinding()]
param(
    [string]$UpdateZip = "",
    [string]$ProjectRoot = ""
)

$ErrorActionPreference = "Stop"

function Get-EsteliPOSGuiRelaunchArgs {
    $relaunch = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-STA", "-File", $PSCommandPath)
    if (-not [string]::IsNullOrWhiteSpace($UpdateZip)) {
        $relaunch += @("-UpdateZip", $UpdateZip)
    }
    if (-not [string]::IsNullOrWhiteSpace($ProjectRoot)) {
        $relaunch += @("-ProjectRoot", $ProjectRoot)
    }
    return $relaunch
}

if ([Threading.Thread]::CurrentThread.GetApartmentState() -ne "STA") {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList (Get-EsteliPOSGuiRelaunchArgs) -Wait -PassThru
    exit $process.ExitCode
}

$CurrentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
$CurrentPrincipal = New-Object Security.Principal.WindowsPrincipal($CurrentIdentity)
if (-not $CurrentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList (Get-EsteliPOSGuiRelaunchArgs) -Verb RunAs -Wait -PassThru
    exit $(if ($process) { $process.ExitCode } else { 1 })
}

. (Join-Path $PSScriptRoot "EsteliPOS-Common.ps1")
. (Join-Path $PSScriptRoot "EsteliPOS-InstallErrors.ps1")

if (-not [string]::IsNullOrWhiteSpace($ProjectRoot)) {
    Set-EsteliPOSProjectRootOverride $ProjectRoot
}

$InstallRoot = Get-EsteliPOSProjectRoot
Set-Location $InstallRoot

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
[System.Windows.Forms.Application]::EnableVisualStyles()
[System.Windows.Forms.Application]::SetUnhandledExceptionMode([System.Windows.Forms.UnhandledExceptionMode]::CatchException)
[System.Windows.Forms.Application]::add_ThreadException({
        param($sender, $eventArgs)
        $text = "Error del asistente: $($eventArgs.Exception.Message)"
        [System.Windows.Forms.MessageBox]::Show($text, "EsteliPOS", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Error) | Out-Null
    })

function Test-EsteliPOSGuiEmail([string]$Value) {
    if ([string]::IsNullOrWhiteSpace($Value)) {
        return $false
    }
    try {
        $null = [System.Net.Mail.MailAddress]::new($Value.Trim())
        return $true
    } catch {
        return $false
    }
}

function Test-EsteliPOSGuiPassword([string]$Value) {
    if ([string]::IsNullOrWhiteSpace($Value) -or $Value.Length -lt 12) {
        return $false
    }
    if ($Value -cnotmatch "[A-Z]") {
        return $false
    }
    if ($Value -cnotmatch "[a-z]") {
        return $false
    }
    if ($Value -notmatch "\d") {
        return $false
    }
    if ($Value -notmatch "[^A-Za-z0-9]") {
        return $false
    }

    return $true
}

function New-EsteliPOSGuiLabel {
    param(
        [string]$Text,
        [int]$X,
        [int]$Y,
        [int]$Width = 640,
        [int]$Height = 22,
        [int]$Size = 10,
        [bool]$Bold = $false
    )

    $label = New-Object System.Windows.Forms.Label
    $label.Text = $Text
    $label.Location = New-Object System.Drawing.Point($X, $Y)
    $label.Size = New-Object System.Drawing.Size($Width, $Height)
    $label.Font = New-Object System.Drawing.Font("Segoe UI", $Size, $(if ($Bold) { [System.Drawing.FontStyle]::Bold } else { [System.Drawing.FontStyle]::Regular }))
    $label.ForeColor = [System.Drawing.Color]::FromArgb(15, 23, 42)
    return $label
}

function New-EsteliPOSGuiTextBox {
    param(
        [int]$X,
        [int]$Y,
        [int]$Width = 420,
        [switch]$Password
    )

    $box = New-Object System.Windows.Forms.TextBox
    $box.Location = New-Object System.Drawing.Point($X, $Y)
    $box.Size = New-Object System.Drawing.Size($Width, 28)
    $box.Font = New-Object System.Drawing.Font("Segoe UI", 10)
    if ($Password) {
        $box.UseSystemPasswordChar = $true
    }
    return $box
}

$script:Ui = @{
    Form          = $null
    Header        = $null
    StepLabel     = $null
    Content       = $null
    BackButton    = $null
    NextButton    = $null
    CancelButton  = $null
    Pages         = @{}
    Order         = @()
    Index         = 0
    ExistingData  = (Test-EsteliPOSHasProductionData -ProjectRoot $InstallRoot)
    Snapshot      = $null
    LogBox        = $null
    StatusLabel   = $null
    Result        = 0
    State         = @{
        Mode               = "install"
        Profile            = $(if (Test-EsteliPOSWindowsHomeEdition) { "Simple" } else { "IIS" })
        AdminName          = "Administrador"
        AdminEmail         = ""
        AdminPassword      = ""
        SetStaticIp        = $false
        LanAddress         = ""
        Port               = 8080
        ExternalBackupPath = ""
        SeedDemoData       = $true
        UpdateZip          = $UpdateZip
        AppUrl             = ""
        MacAddress         = ""
        Success            = $false
        ErrorMessage       = ""
        ErrorReport        = ""
        ExitCode           = 0
        LogPath            = ""
    }
}

if ($script:Ui.ExistingData) {
    $script:Ui.State.Mode = "update"
}

function Get-EsteliPOSGuiPageOrder {
    if ($script:Ui.ExistingData -and $script:Ui.State.Mode -eq "install") {
        return @("welcome", "mode", "profile", "admin", "network", "backup", "checks", "progress", "done")
    }
    if ($script:Ui.ExistingData) {
        return @("welcome", "mode", "network", "checks", "progress", "done")
    }
    if ($script:Ui.State.Mode -eq "install") {
        return @("welcome", "profile", "admin", "network", "backup", "checks", "progress", "done")
    }
    return @("welcome", "network", "checks", "progress", "done")
}

function Show-EsteliPOSGuiMessage([string]$Message) {
    [System.Windows.Forms.MessageBox]::Show(
        $script:Ui.Form,
        $Message,
        "EsteliPOS",
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Information
    ) | Out-Null
}

function Update-EsteliPOSGuiNetworkFields {
    try {
        $script:Ui.Snapshot = Get-EsteliPOSNetworkSnapshot
    } catch {
        $script:Ui.Snapshot = $null
    }

    $snap = $script:Ui.Snapshot
    $script:Ui.Pages.IpValue.Text = "IP: " + $(if ($snap -and $snap.IpAddress) { $snap.IpAddress } else { "(sin red LAN)" })
    $script:Ui.Pages.MacValue.Text = "MAC: " + $(if ($snap -and $snap.MacAddress) { $snap.MacAddress } else { "(no detectada)" })
    $script:Ui.Pages.PortValue.Text = "Puerto: " + $(if ($snap) { [string]$snap.Port } else { "8080" })
    $script:Ui.Pages.UrlValue.Text = $(if ($snap) { $snap.AppUrl } else { "http://127.0.0.1:8080" })
    $script:Ui.Pages.IpInput.Text = $(if ($snap -and $snap.IpAddress) { $snap.IpAddress } else { "" })
    $script:Ui.State.Port = if ($snap) { [int]$snap.Port } else { 8080 }
    $script:Ui.State.MacAddress = if ($snap) { [string]$snap.MacAddress } else { "" }
    $script:Ui.State.AppUrl = if ($snap) { [string]$snap.AppUrl } else { "" }
    $dhcpText = "DHCP activo: reserve esta IP en el router para que no cambie."
    if ($snap -and -not $snap.DhcpEnabled) {
        $dhcpText = "Esta PC ya tiene IP estatica. Puede dejarla o confirmarla abajo."
    }
    $script:Ui.Pages.DhcpNote.Text = $dhcpText
}

function Save-EsteliPOSGuiPage {
    $page = $script:Ui.Order[$script:Ui.Index]
    switch ($page) {
        "mode" {
            $script:Ui.State.Mode = $(if ($script:Ui.Pages.ModeUpdate.Checked) { "update" } else { "install" })
        }
        "profile" {
            $script:Ui.State.Profile = $(if ($script:Ui.Pages.ProfileIis.Checked) { "IIS" } else { "Simple" })
        }
        "admin" {
            $script:Ui.State.AdminName = $script:Ui.Pages.AdminName.Text.Trim()
            $script:Ui.State.AdminEmail = $script:Ui.Pages.AdminEmail.Text.Trim()
            $script:Ui.State.AdminPassword = $script:Ui.Pages.AdminPassword.Text
        }
        "network" {
            $script:Ui.State.SetStaticIp = [bool]$script:Ui.Pages.FixIp.Checked
            $script:Ui.State.LanAddress = $script:Ui.Pages.IpInput.Text.Trim()
        }
        "backup" {
            $script:Ui.State.ExternalBackupPath = $script:Ui.Pages.BackupPath.Text.Trim()
            if ($script:Ui.Pages.ContainsKey("SeedDemo")) {
                $script:Ui.State.SeedDemoData = [bool]$script:Ui.Pages.SeedDemo.Checked
            }
        }
    }
}

function Test-EsteliPOSGuiCurrentPage {
    $page = $script:Ui.Order[$script:Ui.Index]
    switch ($page) {
        "admin" {
            if ([string]::IsNullOrWhiteSpace($script:Ui.Pages.AdminName.Text)) {
                Show-EsteliPOSGuiMessage "Indique el nombre del administrador."
                return $false
            }
            if (-not (Test-EsteliPOSGuiEmail $script:Ui.Pages.AdminEmail.Text)) {
                Show-EsteliPOSGuiMessage "Indique un correo valido para el administrador."
                return $false
            }
            if (-not (Test-EsteliPOSGuiPassword $script:Ui.Pages.AdminPassword.Text)) {
                Show-EsteliPOSGuiMessage "La contrasena debe tener 12+ caracteres, mayuscula, minuscula, numero y simbolo."
                return $false
            }
            if ($script:Ui.Pages.AdminPassword.Text -ne $script:Ui.Pages.AdminPassword2.Text) {
                Show-EsteliPOSGuiMessage "Las contrasenas no coinciden."
                return $false
            }
        }
        "network" {
            if ($script:Ui.Pages.FixIp.Checked) {
                if (-not (Test-EsteliPOSIpv4Address $script:Ui.Pages.IpInput.Text)) {
                    Show-EsteliPOSGuiMessage "Indique una IPv4 valida para fijar en esta PC, por ejemplo 192.168.1.50."
                    return $false
                }
            } elseif ($script:Ui.Pages.IpValue.Text -like "*sin red*") {
                Show-EsteliPOSGuiMessage "Conecte esta PC a la Wi-Fi o cable de la ferreteria antes de continuar."
                return $false
            }
        }
        "checks" {
            $profile = Get-EsteliPOSGuiSelectedProfile
            $report = Get-EsteliPOSPrerequisiteReport -ServerProfile $profile -ProjectRoot $InstallRoot
            if (-not $report.Ready) {
                $fails = New-Object System.Collections.ArrayList
                foreach ($item in $report.Items) {
                    if ([string]$item.Status -eq "FAIL") {
                        [void]$fails.Add([string]$item.Detail)
                    }
                }
                Show-EsteliPOSGuiMessage ("Corrija estos fallos antes de instalar:`r`n`r`n" + [string]::Join("`r`n", $fails.ToArray()))
                return $false
            }
        }
    }

    return $true
}

function Show-EsteliPOSGuiPage {
    foreach ($name in $script:Ui.Pages.Panels.Keys) {
        $script:Ui.Pages.Panels[$name].Visible = ($name -eq $script:Ui.Order[$script:Ui.Index])
    }

    $current = $script:Ui.Order[$script:Ui.Index]
    $human = @{
        welcome  = "Bienvenida"
        mode     = "Instalar o actualizar"
        profile  = "Tipo de servidor"
        admin    = "Administrador"
        network  = "Red LAN"
        backup   = "Respaldo"
        checks   = "Dependencias"
        progress = "Instalacion"
        done     = "Listo"
    }
    $script:Ui.StepLabel.Text = "Paso $($script:Ui.Index + 1) de $($script:Ui.Order.Count): $($human[$current])"
    $script:Ui.BackButton.Enabled = ($script:Ui.Index -gt 0 -and $current -ne "progress" -and $current -ne "done")
    $script:Ui.CancelButton.Enabled = ($current -ne "progress")

    switch ($current) {
        "network" {
            Update-EsteliPOSGuiNetworkFields
            $script:Ui.NextButton.Text = "Siguiente"
            $script:Ui.NextButton.Enabled = $true
        }
        "checks" {
            Update-EsteliPOSGuiChecks
            $script:Ui.NextButton.Text = $(if ($script:Ui.State.Mode -eq "update") { "Actualizar" } else { "Instalar" })
            $script:Ui.NextButton.Enabled = $true
        }
        "progress" {
            $script:Ui.NextButton.Text = "Instalando..."
            $script:Ui.NextButton.Enabled = $false
            $script:Ui.Form.BeginInvoke([System.Windows.Forms.MethodInvoker] { Start-EsteliPOSGuiWork }) | Out-Null
        }
        "done" {
            $script:Ui.NextButton.Text = "Cerrar"
            $script:Ui.NextButton.Enabled = $true
            $script:Ui.BackButton.Enabled = $false
        }
        default {
            $isLastInput = ($script:Ui.Order[$script:Ui.Index + 1] -eq "progress")
            $script:Ui.NextButton.Text = $(if (-not $isLastInput) { "Siguiente" } elseif ($script:Ui.State.Mode -eq "update") { "Actualizar" } else { "Instalar" })
            $script:Ui.NextButton.Enabled = $true
        }
    }
}

function Get-EsteliPOSGuiSelectedProfile {
    if ($script:Ui.State.Mode -eq "install") {
        return $script:Ui.State.Profile
    }
    try {
        return Get-EsteliPOSResolvedServerProfile -ServerProfile "Auto"
    } catch {
        return "IIS"
    }
}

function Update-EsteliPOSGuiChecks {
    try {
        $profile = Get-EsteliPOSGuiSelectedProfile
        $report = Get-EsteliPOSPrerequisiteReport -ServerProfile $profile -ProjectRoot $InstallRoot
        $script:Ui.Pages.ChecksBox.Text = Format-EsteliPOSPrerequisiteReport -Report $report
        if ($report.Ready) {
            $script:Ui.Pages.ChecksNote.Text = "Perfil $profile. Puede continuar. Los avisos no detienen la instalacion."
        } else {
            $script:Ui.Pages.ChecksNote.Text = "Hay fallos. Corrijalos o cambie a perfil Simple si Windows es Home."
        }
    } catch {
        $script:Ui.Pages.ChecksBox.Text = $_.Exception.Message
        $script:Ui.Pages.ChecksNote.Text = "No se pudo completar la busqueda de dependencias."
        Show-EsteliPOSGuiMessage ("No se pudieron comprobar las dependencias.`r`n`r`n" + $_.Exception.Message)
    }
}

function Get-EsteliPOSGuiLatestLogPath {
    $latest = Get-EsteliPOSLatestInstallLog -ProjectRoot $InstallRoot
    if ($latest) {
        return $latest.FullName
    }
    return ""
}

function Add-EsteliPOSGuiLogTail {
    $path = Get-EsteliPOSGuiLatestLogPath
    if ([string]::IsNullOrWhiteSpace($path)) {
        return
    }
    $script:Ui.State.LogPath = $path
    $tail = Get-EsteliPOSInstallLogTail -LogPath $path -Lines 80
    if ($tail.Count -gt 0) {
        $script:Ui.LogBox.AppendText("`r`n----- LOG $path -----`r`n")
        $script:Ui.LogBox.AppendText(($tail -join "`r`n") + "`r`n")
        $script:Ui.LogBox.SelectionStart = $script:Ui.LogBox.Text.Length
        $script:Ui.LogBox.ScrollToCaret()
    }
}

function Copy-EsteliPOSGuiText([string]$Text, [string]$OkMessage) {
    if ([string]::IsNullOrWhiteSpace($Text)) {
        Show-EsteliPOSGuiMessage "No hay texto para copiar todavia."
        return
    }
    $ok = $false
    try {
        [System.Windows.Forms.Clipboard]::SetText($Text)
        $ok = $true
    } catch {
        $ok = Copy-EsteliPOSTextToClipboard -Text $Text
    }
    if ($ok) {
        Show-EsteliPOSGuiMessage $(if ($OkMessage) { $OkMessage } else { "Copiado. Pegue con Ctrl+V." })
    } else {
        Show-EsteliPOSGuiMessage "No se pudo copiar. Seleccione el texto y pulse Ctrl+C."
    }
}

function Invoke-EsteliPOSGuiProcess {
    param(
        [Parameter(Mandatory = $true)][string]$FilePath,
        [Parameter(Mandatory = $true)][string[]]$ArgumentList,
        [hashtable]$Environment = @{}
    )

    $info = New-Object System.Diagnostics.ProcessStartInfo
    $info.FileName = "powershell.exe"
    $quoted = @($ArgumentList | ForEach-Object {
            if ($_ -match '\s') { '"' + ($_ -replace '"', '\"') + '"' } else { $_ }
        })
    $info.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$FilePath`" $($quoted -join ' ')"
    $info.WorkingDirectory = $InstallRoot
    $info.UseShellExecute = $false
    $info.RedirectStandardOutput = $true
    $info.RedirectStandardError = $true
    $info.CreateNoWindow = $true
    $info.StandardOutputEncoding = [System.Text.Encoding]::UTF8
    $info.StandardErrorEncoding = [System.Text.Encoding]::UTF8
    foreach ($key in $Environment.Keys) {
        $info.EnvironmentVariables[$key] = [string]$Environment[$key]
    }

    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $info
    [void]$process.Start()
    while (-not $process.HasExited) {
        while ($process.StandardOutput.Peek() -gt -1) {
            $script:Ui.LogBox.AppendText([char]$process.StandardOutput.Read())
        }
        while ($process.StandardError.Peek() -gt -1) {
            $script:Ui.LogBox.AppendText([char]$process.StandardError.Read())
        }
        $script:Ui.LogBox.SelectionStart = $script:Ui.LogBox.Text.Length
        $script:Ui.LogBox.ScrollToCaret()
        [System.Windows.Forms.Application]::DoEvents()
        Start-Sleep -Milliseconds 60
    }
    $remainingOut = $process.StandardOutput.ReadToEnd()
    $remainingErr = $process.StandardError.ReadToEnd()
    if ($remainingOut) { $script:Ui.LogBox.AppendText($remainingOut) }
    if ($remainingErr) { $script:Ui.LogBox.AppendText($remainingErr) }
    $script:Ui.LogBox.SelectionStart = $script:Ui.LogBox.Text.Length
    $script:Ui.LogBox.ScrollToCaret()
    $process.WaitForExit()
    return $process.ExitCode
}

function Start-EsteliPOSGuiWork {
    $script:Ui.LogBox.Clear()
    $script:Ui.StatusLabel.Text = "Preparando..."
    $script:Ui.State.Success = $false
    $script:Ui.State.ErrorMessage = ""
    $script:Ui.State.ErrorReport = ""
    $script:Ui.State.ExitCode = 0
    $script:Ui.State.LogPath = ""

    try {
        $profile = Get-EsteliPOSGuiSelectedProfile
        $pre = Get-EsteliPOSPrerequisiteReport -ServerProfile $profile -ProjectRoot $InstallRoot
        $script:Ui.LogBox.AppendText((Format-EsteliPOSPrerequisiteReport -Report $pre) + "`r`n`r`n")

        if ($script:Ui.State.SetStaticIp -and $script:Ui.State.Mode -eq "update") {
            $script:Ui.StatusLabel.Text = "Fijando IP en esta PC..."
            $script:Ui.LogBox.AppendText("Fijando IP $($script:Ui.State.LanAddress)...`r`n")
            [void](Set-EsteliPOSStaticIpv4 -IpAddress $script:Ui.State.LanAddress)
            try { [void](Set-EsteliPOSLanProfilePrivate -TimeoutSeconds 20) } catch { }
        }

        if ($script:Ui.State.Mode -eq "update") {
            $script:Ui.StatusLabel.Text = "Actualizando sin perder datos..."
            $updateScript = Join-Path $PSScriptRoot "Update-EsteliPOS.ps1"
            $updateArgs = @()
            if (-not [string]::IsNullOrWhiteSpace($script:Ui.State.UpdateZip)) {
                $updateArgs += @("-UpdateZip", $script:Ui.State.UpdateZip)
            }
            $code = Invoke-EsteliPOSGuiProcess -FilePath $updateScript -ArgumentList $updateArgs
            if ($code -ne 0) {
                $script:Ui.State.ExitCode = $code
                throw "La actualizacion termino con codigo $code."
            }
            if ($script:Ui.State.SetStaticIp) {
                $repair = Join-Path $PSScriptRoot "Repair-EsteliPOS-LAN.ps1"
                if (Test-Path -LiteralPath $repair) {
                    $script:Ui.StatusLabel.Text = "Actualizando URL LAN..."
                    [void](Invoke-EsteliPOSGuiProcess -FilePath $repair -ArgumentList @())
                }
            }
        } else {
            $script:Ui.StatusLabel.Text = "Instalando EsteliPOS..."
            $deploy = Join-Path $PSScriptRoot "Deploy-EsteliPOS.ps1"
            $deployArgs = @(
                "-ServerProfile", $script:Ui.State.Profile,
                "-AdminName", $script:Ui.State.AdminName,
                "-AdminEmail", $script:Ui.State.AdminEmail,
                "-Port", "$($script:Ui.State.Port)",
                "-NonInteractive"
            )
            if (-not [string]::IsNullOrWhiteSpace($script:Ui.State.LanAddress)) {
                $deployArgs += @("-LanAddress", $script:Ui.State.LanAddress)
            }
            if ($script:Ui.State.SetStaticIp) {
                $deployArgs += "-SetStaticIp"
            }
            if (-not [string]::IsNullOrWhiteSpace($script:Ui.State.ExternalBackupPath)) {
                $deployArgs += @("-ExternalBackupPath", $script:Ui.State.ExternalBackupPath)
            }
            if (-not $script:Ui.State.SeedDemoData) {
                $deployArgs += "-SkipDemoData"
            }
            $envVars = @{
                INSTALL_ADMIN_PASSWORD = $script:Ui.State.AdminPassword
            }
            $deployArgs += @("-ProjectRoot", $InstallRoot)
            $code = Invoke-EsteliPOSGuiProcess -FilePath $deploy -ArgumentList $deployArgs -Environment $envVars
            if ($code -ne 0) {
                $script:Ui.State.ExitCode = $code
                throw "La instalacion termino con codigo $code."
            }
        }

        try {
            $script:Ui.Snapshot = Get-EsteliPOSNetworkSnapshot
            if ($script:Ui.Snapshot) {
                $script:Ui.State.AppUrl = $script:Ui.Snapshot.AppUrl
                $script:Ui.State.MacAddress = $script:Ui.Snapshot.MacAddress
                $script:Ui.State.LanAddress = $script:Ui.Snapshot.IpAddress
            }
        } catch {
        }

        $script:Ui.State.Success = $true
        $script:Ui.StatusLabel.Text = "Completado."
        $script:Ui.Pages.DoneTitle.Text = $(if ($script:Ui.State.Mode -eq "update") { "Actualizacion completada" } else { "Instalacion completada" })
        $script:Ui.Pages.DoneBody.Text = @"
EsteliPOS esta listo en este equipo.

URL para PC, tablets y celulares:
$($script:Ui.State.AppUrl)

IP: $($script:Ui.State.LanAddress)
MAC para el router: $($script:Ui.State.MacAddress)

Use el acceso directo del escritorio. En tablets, abra la URL o el QR de la hoja de acceso.
Si cargo datos de demostracion, ya hay productos, clientes y ventas para mostrar.
"@
        $script:Ui.Pages.CopyReport.Text = "Copiar URL"
        $script:Ui.Pages.CopyReport.Visible = $true
        $script:Ui.Pages.OpenLog.Visible = $true
    } catch {
        $script:Ui.Result = $(if ($script:Ui.State.ExitCode -gt 0) { $script:Ui.State.ExitCode } else { 1 })
        $script:Ui.State.ErrorMessage = $_.Exception.Message
        Add-EsteliPOSGuiLogTail
        $logPath = Get-EsteliPOSGuiLatestLogPath
        $script:Ui.State.LogPath = $logPath
        $exitCode = if ($script:Ui.State.ExitCode -gt 0) { $script:Ui.State.ExitCode } else { 99 }
        $script:Ui.State.ErrorReport = Format-EsteliPOSInstallErrorReport `
            -ExitCode $exitCode `
            -DetailMessage $_.Exception.Message `
            -LogPath $logPath
        $script:Ui.LogBox.AppendText("`r`n$($script:Ui.State.ErrorReport)`r`n")
        $script:Ui.StatusLabel.Text = "No se completo."
        $script:Ui.Pages.DoneTitle.Text = "No se completo la operacion"
        $script:Ui.Pages.DoneBody.Text = @"
$($_.Exception.Message)

Pulse Copiar informe y envielo a soporte.
Log: $logPath

El recuadro de instalacion tambien se puede seleccionar y copiar con Ctrl+C.
"@
        $script:Ui.Pages.CopyReport.Text = "Copiar informe"
        $script:Ui.Pages.CopyReport.Visible = $true
        $script:Ui.Pages.OpenLog.Visible = $true
        $script:Ui.LogBox.AppendText("`r`nERROR: $($_.Exception.Message)`r`n")
    } finally {
        $script:Ui.State.AdminPassword = ""
        $script:Ui.Pages.AdminPassword.Text = ""
        $script:Ui.Pages.AdminPassword2.Text = ""
        $script:Ui.Index = $script:Ui.Order.Count - 1
        Show-EsteliPOSGuiPage
    }
}

$form = New-Object System.Windows.Forms.Form
$form.Text = "EsteliPOS - Asistente de instalacion"
$form.Size = New-Object System.Drawing.Size(760, 680)
$form.StartPosition = "CenterScreen"
$form.FormBorderStyle = "FixedDialog"
$form.MaximizeBox = $false
$form.MinimizeBox = $true
$form.BackColor = [System.Drawing.Color]::FromArgb(241, 245, 249)
$form.Font = New-Object System.Drawing.Font("Segoe UI", 10)

$header = New-Object System.Windows.Forms.Panel
$header.Location = New-Object System.Drawing.Point(0, 0)
$header.Size = New-Object System.Drawing.Size(760, 78)
$header.BackColor = [System.Drawing.Color]::FromArgb(67, 56, 202)
$form.Controls.Add($header)

$title = New-EsteliPOSGuiLabel -Text "EsteliPOS" -X 24 -Y 12 -Width 700 -Height 28 -Size 16 -Bold $true
$title.ForeColor = [System.Drawing.Color]::White
$header.Controls.Add($title)

$stepLabel = New-EsteliPOSGuiLabel -Text "Paso 1" -X 24 -Y 44 -Width 700 -Height 22 -Size 10
$stepLabel.ForeColor = [System.Drawing.Color]::FromArgb(199, 210, 254)
$header.Controls.Add($stepLabel)

$content = New-Object System.Windows.Forms.Panel
$content.Location = New-Object System.Drawing.Point(16, 94)
$content.Size = New-Object System.Drawing.Size(712, 470)
$content.BackColor = [System.Drawing.Color]::White
$form.Controls.Add($content)

$footer = New-Object System.Windows.Forms.Panel
$footer.Location = New-Object System.Drawing.Point(0, 580)
$footer.Size = New-Object System.Drawing.Size(760, 56)
$footer.BackColor = [System.Drawing.Color]::FromArgb(226, 232, 240)
$form.Controls.Add($footer)

$back = New-Object System.Windows.Forms.Button
$back.Text = "Atras"
$back.Location = New-Object System.Drawing.Point(24, 12)
$back.Size = New-Object System.Drawing.Size(110, 32)
$footer.Controls.Add($back)

$cancel = New-Object System.Windows.Forms.Button
$cancel.Text = "Cancelar"
$cancel.Location = New-Object System.Drawing.Point(430, 12)
$cancel.Size = New-Object System.Drawing.Size(110, 32)
$footer.Controls.Add($cancel)

$next = New-Object System.Windows.Forms.Button
$next.Text = "Siguiente"
$next.Location = New-Object System.Drawing.Point(552, 12)
$next.Size = New-Object System.Drawing.Size(180, 32)
$next.BackColor = [System.Drawing.Color]::FromArgb(67, 56, 202)
$next.ForeColor = [System.Drawing.Color]::White
$next.FlatStyle = "Flat"
$footer.Controls.Add($next)

$panels = @{}

function Add-EsteliPOSGuiPage([string]$Name) {
    $panel = New-Object System.Windows.Forms.Panel
    $panel.Location = New-Object System.Drawing.Point(0, 0)
    $panel.Size = New-Object System.Drawing.Size(712, 470)
    $panel.BackColor = [System.Drawing.Color]::White
    $panel.Visible = $false
    $content.Controls.Add($panel)
    $panels[$Name] = $panel
    return $panel
}

$welcome = Add-EsteliPOSGuiPage "welcome"
$welcome.Controls.Add((New-EsteliPOSGuiLabel -Text "Asistente de instalacion" -X 28 -Y 24 -Width 650 -Height 32 -Size 16 -Bold $true))
$welcome.Controls.Add((New-EsteliPOSGuiLabel -Text "Este asistente deja EsteliPOS listo en esta PC para la ferreteria (LAN local, puerto 8080)." -X 28 -Y 70 -Width 650 -Height 48))
$welcomeBody = New-EsteliPOSGuiLabel -Text @"
Carpeta: $InstallRoot

El siguiente paso le pedira:
- Instalar nuevo o actualizar sin perder ventas
- IIS (recomendado) o Simple
- Usuario administrador
- IP y MAC de esta PC, con opcion de fijar la IP
- Datos de demostracion para mostrar a clientes (recomendado)
- Revision automatica de PHP, Visual C++, paquete e IIS
"@ -X 28 -Y 130 -Width 650 -Height 160
$welcome.Controls.Add($welcomeBody)

$mode = Add-EsteliPOSGuiPage "mode"
$mode.Controls.Add((New-EsteliPOSGuiLabel -Text "Ya hay datos en esta carpeta" -X 28 -Y 24 -Width 650 -Height 32 -Size 16 -Bold $true))
$mode.Controls.Add((New-EsteliPOSGuiLabel -Text "Se encontro una base de datos. Elija con cuidado:" -X 28 -Y 70 -Width 650 -Height 28))
$modeUpdate = New-Object System.Windows.Forms.RadioButton
$modeUpdate.Text = "Actualizar sin perder ventas, inventario ni clientes (recomendado)"
$modeUpdate.Location = New-Object System.Drawing.Point(32, 120)
$modeUpdate.Size = New-Object System.Drawing.Size(640, 36)
$modeUpdate.Checked = $true
$mode.Controls.Add($modeUpdate)
$modeInstall = New-Object System.Windows.Forms.RadioButton
$modeInstall.Text = "Reinstalar servicios IIS/Simple (conserva la base de datos)"
$modeInstall.Location = New-Object System.Drawing.Point(32, 168)
$modeInstall.Size = New-Object System.Drawing.Size(640, 36)
$mode.Controls.Add($modeInstall)

$profile = Add-EsteliPOSGuiPage "profile"
$profile.Controls.Add((New-EsteliPOSGuiLabel -Text "Tipo de servidor" -X 28 -Y 24 -Width 650 -Height 32 -Size 16 -Bold $true))
$profileIis = New-Object System.Windows.Forms.RadioButton
$profileIis.Text = "IIS + PHP (recomendado para ferreteria / varias cajas o tablets)"
$profileIis.Location = New-Object System.Drawing.Point(32, 90)
$profileIis.Size = New-Object System.Drawing.Size(640, 36)
$profile.Controls.Add($profileIis)
$profileSimple = New-Object System.Windows.Forms.RadioButton
$profileSimple.Text = "Simple (1 caja o Windows Home; PHP embebido)"
$profileSimple.Location = New-Object System.Drawing.Point(32, 138)
$profileSimple.Size = New-Object System.Drawing.Size(640, 36)
$profile.Controls.Add($profileSimple)
if (Test-EsteliPOSWindowsHomeEdition) {
    $profileIis.Enabled = $false
    $profileIis.Text = "IIS (no disponible en Windows Home)"
    $profileSimple.Checked = $true
    $script:Ui.State.Profile = "Simple"
} else {
    $profileIis.Checked = $true
}
$profile.Controls.Add((New-EsteliPOSGuiLabel -Text "IIS requiere Windows 10/11 Pro, Enterprise o Education. En Windows Home use Simple." -X 28 -Y 200 -Width 650 -Height 48))

$admin = Add-EsteliPOSGuiPage "admin"
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "Usuario administrador" -X 28 -Y 24 -Width 650 -Height 32 -Size 16 -Bold $true))
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "Nombre" -X 28 -Y 80 -Width 200 -Height 22 -Bold $true))
$adminName = New-EsteliPOSGuiTextBox -X 28 -Y 104
$adminName.Text = "Administrador"
$admin.Controls.Add($adminName)
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "Correo" -X 28 -Y 146 -Width 200 -Height 22 -Bold $true))
$adminEmail = New-EsteliPOSGuiTextBox -X 28 -Y 170
$admin.Controls.Add($adminEmail)
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "Contrasena (12+ caracteres, mayuscula, minuscula, numero y simbolo)" -X 28 -Y 212 -Width 640 -Height 22 -Bold $true))
$adminPassword = New-EsteliPOSGuiTextBox -X 28 -Y 236 -Password
$admin.Controls.Add($adminPassword)
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "Confirmar contrasena" -X 28 -Y 278 -Width 300 -Height 22 -Bold $true))
$adminPassword2 = New-EsteliPOSGuiTextBox -X 28 -Y 302 -Password
$admin.Controls.Add($adminPassword2)
$admin.Controls.Add((New-EsteliPOSGuiLabel -Text "El administrador debera cambiar esta contrasena en su primer ingreso." -X 28 -Y 350 -Width 650 -Height 40))

$network = Add-EsteliPOSGuiPage "network"
$network.Controls.Add((New-EsteliPOSGuiLabel -Text "Red local (LAN)" -X 28 -Y 18 -Width 650 -Height 28 -Size 16 -Bold $true))
$network.Controls.Add((New-EsteliPOSGuiLabel -Text "Tablets y otras PCs usaran esta direccion. El instalador no puede reservar la IP en el router." -X 28 -Y 50 -Width 660 -Height 36))
$urlValue = New-EsteliPOSGuiLabel -Text "http://127.0.0.1:8080" -X 28 -Y 92 -Width 650 -Height 28 -Size 12 -Bold $true
$urlValue.ForeColor = [System.Drawing.Color]::FromArgb(67, 56, 202)
$network.Controls.Add($urlValue)
$ipValue = New-EsteliPOSGuiLabel -Text "IP: " -X 28 -Y 128 -Width 320 -Height 22
$macValue = New-EsteliPOSGuiLabel -Text "MAC: " -X 360 -Y 128 -Width 320 -Height 22
$portValue = New-EsteliPOSGuiLabel -Text "Puerto: 8080" -X 28 -Y 152 -Width 320 -Height 22
$network.Controls.Add($ipValue)
$network.Controls.Add($macValue)
$network.Controls.Add($portValue)
$dhcpNote = New-EsteliPOSGuiLabel -Text "" -X 28 -Y 182 -Width 660 -Height 40
$network.Controls.Add($dhcpNote)
$fixIp = New-Object System.Windows.Forms.CheckBox
$fixIp.Text = "Fijar esta IP en Windows (desactiva DHCP en esta PC)"
$fixIp.Location = New-Object System.Drawing.Point(32, 228)
$fixIp.Size = New-Object System.Drawing.Size(640, 28)
$network.Controls.Add($fixIp)
$network.Controls.Add((New-EsteliPOSGuiLabel -Text "IP a fijar" -X 28 -Y 262 -Width 200 -Height 22 -Bold $true))
$ipInput = New-EsteliPOSGuiTextBox -X 28 -Y 286 -Width 260
$network.Controls.Add($ipInput)
$network.Controls.Add((New-EsteliPOSGuiLabel -Text "Recomendado tambien: en el router, Reserva DHCP / IP estatica con la MAC de arriba." -X 28 -Y 330 -Width 660 -Height 50))

$backup = Add-EsteliPOSGuiPage "backup"
$backup.Controls.Add((New-EsteliPOSGuiLabel -Text "Datos de demostracion y respaldo" -X 28 -Y 18 -Width 650 -Height 28 -Size 16 -Bold $true))
$seedDemo = New-Object System.Windows.Forms.CheckBox
$seedDemo.Text = "Incluir datos de prueba (productos, clientes, compras y ventas) para mostrar a clientes"
$seedDemo.Location = New-Object System.Drawing.Point(32, 56)
$seedDemo.Size = New-Object System.Drawing.Size(640, 40)
$seedDemo.Checked = $true
$backup.Controls.Add($seedDemo)
$backup.Controls.Add((New-EsteliPOSGuiLabel -Text "Desmarque solo si esta ferreteria va a trabajar con su inventario real desde el dia uno." -X 28 -Y 98 -Width 650 -Height 36))
$backup.Controls.Add((New-EsteliPOSGuiLabel -Text "Siempre se crea un respaldo diario local a las 7:00 PM. Puede indicar una carpeta USB o de red para una segunda copia." -X 28 -Y 140 -Width 650 -Height 48))
$backup.Controls.Add((New-EsteliPOSGuiLabel -Text "Carpeta externa (opcional)" -X 28 -Y 196 -Width 400 -Height 22 -Bold $true))
$backupPath = New-EsteliPOSGuiTextBox -X 28 -Y 222 -Width 500
$backup.Controls.Add($backupPath)
$browse = New-Object System.Windows.Forms.Button
$browse.Text = "Examinar..."
$browse.Location = New-Object System.Drawing.Point(540, 220)
$browse.Size = New-Object System.Drawing.Size(130, 28)
$backup.Controls.Add($browse)
$browse.Add_Click({
        $dialog = New-Object System.Windows.Forms.FolderBrowserDialog
        $dialog.Description = "Carpeta para segunda copia de EsteliPOS"
        if ($dialog.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) {
            $script:Ui.Pages.BackupPath.Text = $dialog.SelectedPath
        }
    })

$checks = Add-EsteliPOSGuiPage "checks"
$checks.Controls.Add((New-EsteliPOSGuiLabel -Text "Dependencias de este equipo" -X 28 -Y 18 -Width 650 -Height 28 -Size 16 -Bold $true))
$checksNote = New-EsteliPOSGuiLabel -Text "Buscando PHP, Visual C++, paquete e IIS..." -X 28 -Y 50 -Width 660 -Height 36
$checks.Controls.Add($checksNote)
$checksBox = New-Object System.Windows.Forms.TextBox
$checksBox.Multiline = $true
$checksBox.ScrollBars = "Vertical"
$checksBox.ReadOnly = $true
$checksBox.Location = New-Object System.Drawing.Point(28, 92)
$checksBox.Size = New-Object System.Drawing.Size(656, 330)
$checksBox.Font = New-Object System.Drawing.Font("Consolas", 8.5)
$checksBox.BackColor = [System.Drawing.Color]::FromArgb(248, 250, 252)
$checks.Controls.Add($checksBox)
$refreshChecks = New-Object System.Windows.Forms.Button
$refreshChecks.Text = "Volver a buscar"
$refreshChecks.Location = New-Object System.Drawing.Point(28, 432)
$refreshChecks.Size = New-Object System.Drawing.Size(160, 28)
$checks.Controls.Add($refreshChecks)
$refreshChecks.Add_Click({
        try {
            Update-EsteliPOSGuiChecks
        } catch {
            Show-EsteliPOSGuiMessage $_.Exception.Message
        }
    })

$progress = Add-EsteliPOSGuiPage "progress"
$statusLabel = New-EsteliPOSGuiLabel -Text "Instalando..." -X 28 -Y 12 -Width 650 -Height 24 -Size 12 -Bold $true
$progress.Controls.Add($statusLabel)
$logBox = New-Object System.Windows.Forms.TextBox
$logBox.Multiline = $true
$logBox.ScrollBars = "Vertical"
$logBox.ReadOnly = $true
$logBox.Location = New-Object System.Drawing.Point(28, 42)
$logBox.Size = New-Object System.Drawing.Size(656, 350)
$logBox.Font = New-Object System.Drawing.Font("Consolas", 8.5)
$logBox.BackColor = [System.Drawing.Color]::FromArgb(15, 23, 42)
$logBox.ForeColor = [System.Drawing.Color]::FromArgb(226, 232, 240)
$progress.Controls.Add($logBox)
$copyLog = New-Object System.Windows.Forms.Button
$copyLog.Text = "Copiar log"
$copyLog.Location = New-Object System.Drawing.Point(28, 402)
$copyLog.Size = New-Object System.Drawing.Size(140, 28)
$progress.Controls.Add($copyLog)
$openLogs = New-Object System.Windows.Forms.Button
$openLogs.Text = "Abrir carpeta de logs"
$openLogs.Location = New-Object System.Drawing.Point(178, 402)
$openLogs.Size = New-Object System.Drawing.Size(180, 28)
$progress.Controls.Add($openLogs)
$copyLog.Add_Click({
        $text = $script:Ui.LogBox.Text
        if ([string]::IsNullOrWhiteSpace($text) -and $script:Ui.State.LogPath -and (Test-Path -LiteralPath $script:Ui.State.LogPath)) {
            $text = Get-Content -LiteralPath $script:Ui.State.LogPath -Raw -ErrorAction SilentlyContinue
        }
        Copy-EsteliPOSGuiText $text "Log copiado. Pegue con Ctrl+V en un correo o chat."
    })
$openLogs.Add_Click({
        $dir = Join-Path $InstallRoot "storage\logs"
        if (-not (Test-Path -LiteralPath $dir)) {
            New-Item -ItemType Directory -Force -Path $dir | Out-Null
        }
        Start-Process explorer.exe $dir
    })

$done = Add-EsteliPOSGuiPage "done"
$doneTitle = New-EsteliPOSGuiLabel -Text "Listo" -X 28 -Y 18 -Width 650 -Height 32 -Size 16 -Bold $true
$done.Controls.Add($doneTitle)
$doneBody = New-EsteliPOSGuiLabel -Text "" -X 28 -Y 60 -Width 650 -Height 280
$done.Controls.Add($doneBody)
$copyReport = New-Object System.Windows.Forms.Button
$copyReport.Text = "Copiar informe"
$copyReport.Location = New-Object System.Drawing.Point(28, 360)
$copyReport.Size = New-Object System.Drawing.Size(160, 32)
$copyReport.Visible = $false
$done.Controls.Add($copyReport)
$openLog = New-Object System.Windows.Forms.Button
$openLog.Text = "Abrir log"
$openLog.Location = New-Object System.Drawing.Point(198, 360)
$openLog.Size = New-Object System.Drawing.Size(140, 32)
$openLog.Visible = $false
$done.Controls.Add($openLog)
$copyReport.Add_Click({
        if ($script:Ui.State.Success) {
            Copy-EsteliPOSGuiText $script:Ui.State.AppUrl "URL copiada."
            return
        }
        $text = $script:Ui.State.ErrorReport
        if ([string]::IsNullOrWhiteSpace($text)) {
            $text = $script:Ui.LogBox.Text
        }
        Copy-EsteliPOSGuiText $text "Informe copiado. Pegue con Ctrl+V para enviarlo a soporte."
    })
$openLog.Add_Click({
        $path = $script:Ui.State.LogPath
        if ([string]::IsNullOrWhiteSpace($path)) {
            $path = Get-EsteliPOSGuiLatestLogPath
        }
        if ($path -and (Test-Path -LiteralPath $path)) {
            Start-Process notepad.exe $path
        } else {
            $dir = Join-Path $InstallRoot "storage\logs"
            if (Test-Path -LiteralPath $dir) {
                Start-Process explorer.exe $dir
            } else {
                Show-EsteliPOSGuiMessage "Todavia no hay un log de instalacion."
            }
        }
    })

$script:Ui.Form = $form
$script:Ui.Header = $header
$script:Ui.StepLabel = $stepLabel
$script:Ui.Content = $content
$script:Ui.BackButton = $back
$script:Ui.NextButton = $next
$script:Ui.CancelButton = $cancel
$script:Ui.LogBox = $logBox
$script:Ui.StatusLabel = $statusLabel
$script:Ui.Pages = @{
    Panels          = $panels
    ModeUpdate      = $modeUpdate
    ModeInstall     = $modeInstall
    ProfileIis      = $profileIis
    ProfileSimple   = $profileSimple
    AdminName       = $adminName
    AdminEmail      = $adminEmail
    AdminPassword   = $adminPassword
    AdminPassword2  = $adminPassword2
    UrlValue        = $urlValue
    IpValue         = $ipValue
    MacValue        = $macValue
    PortValue       = $portValue
    DhcpNote        = $dhcpNote
    FixIp           = $fixIp
    IpInput         = $ipInput
    BackupPath      = $backupPath
    SeedDemo        = $seedDemo
    ChecksBox       = $checksBox
    ChecksNote      = $checksNote
    CopyReport      = $copyReport
    OpenLog         = $openLog
    DoneTitle       = $doneTitle
    DoneBody        = $doneBody
}

$back.Add_Click({
        try {
            if ($script:Ui.Index -le 0) { return }
            Save-EsteliPOSGuiPage
            $script:Ui.Index--
            $script:Ui.Order = Get-EsteliPOSGuiPageOrder
            if ($script:Ui.Index -ge $script:Ui.Order.Count) {
                $script:Ui.Index = $script:Ui.Order.Count - 1
            }
            Show-EsteliPOSGuiPage
        } catch {
            Show-EsteliPOSGuiMessage $_.Exception.Message
        }
    })

$next.Add_Click({
        try {
            $current = $script:Ui.Order[$script:Ui.Index]
            if ($current -eq "done") {
                $form.Close()
                return
            }
            if ($current -eq "progress") {
                return
            }
            if (-not (Test-EsteliPOSGuiCurrentPage)) {
                return
            }
            Save-EsteliPOSGuiPage
            $script:Ui.Order = Get-EsteliPOSGuiPageOrder
            if ($script:Ui.Index -lt ($script:Ui.Order.Count - 1)) {
                $script:Ui.Index++
            }
            Show-EsteliPOSGuiPage
        } catch {
            Show-EsteliPOSGuiMessage $_.Exception.Message
        }
    })

$cancel.Add_Click({
        if ([System.Windows.Forms.MessageBox]::Show(
                $form,
                "Cancelar el asistente?",
                "EsteliPOS",
                [System.Windows.Forms.MessageBoxButtons]::YesNo,
                [System.Windows.Forms.MessageBoxIcon]::Question
            ) -eq [System.Windows.Forms.DialogResult]::Yes) {
            $script:Ui.Result = 0
            $form.Close()
        }
    })

$form.Add_FormClosing({
        if ($script:Ui.Order.Count -gt 0 -and $script:Ui.Order[$script:Ui.Index] -eq "progress" -and -not $script:Ui.State.Success) {
            $_.Cancel = $true
        }
    })

$script:Ui.Order = Get-EsteliPOSGuiPageOrder
$script:Ui.Index = 0
Show-EsteliPOSGuiPage
[void]$form.ShowDialog()
exit $(if ($script:Ui.State.Success) { 0 } else { $script:Ui.Result })
