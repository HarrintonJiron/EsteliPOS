Unicode true
RequestExecutionLevel admin
CRCCheck on
SetCompressor /SOLID lzma
!include "MUI2.nsh"
!include "LogicLib.nsh"
!include "version.nsh"
!define PRODUCT "EsteliPOS"
Name "${PRODUCT} ${VERSION}"
OutFile "dist\EsteliPOS-Setup-${VERSION}.exe"
InstallDir "C:\EsteliPOS"
ShowInstDetails show
ShowUninstDetails show
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Spanish"

Section "Instalar EsteliPOS" SEC01
    SetOutPath "$PLUGINSDIR\payload"
    File /r "payload\*"
    SetOutPath "$PLUGINSDIR\application"
    File /r ".staging\application\*"
    SetOutPath "$PLUGINSDIR\scripts"
    File /r "scripts\*"
    DetailPrint "Preparando instalación sin conexión"
    nsExec::ExecToLog 'powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "$PLUGINSDIR\scripts\Install-EsteliPOS.ps1" -InstallRoot "$INSTDIR" -PayloadRoot "$PLUGINSDIR\payload" -ApplicationSource "$PLUGINSDIR\application" -Version "${VERSION}" -Mode Install'
    Pop $0
    ${If} $0 != 0
        MessageBox MB_ICONSTOP "La instalacion fallo. Consulte $%PROGRAMDATA%\EsteliPOS\Logs para soporte."
        Abort
    ${EndIf}
    CreateDirectory "$SMPROGRAMS\EsteliPOS"
    CreateShortCut "$SMPROGRAMS\EsteliPOS\EsteliPOS.lnk" "$INSTDIR\EsteliPOS.url"
    WriteUninstaller "$INSTDIR\Uninstall.exe"
SectionEnd

Section "Uninstall"
    nsExec::ExecToLog 'powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "$INSTDIR\installer\Uninstall-EsteliPOS.ps1" -InstallRoot "$INSTDIR"'
    RMDir /r "$INSTDIR"
    Delete "$SMPROGRAMS\EsteliPOS\EsteliPOS.lnk"
    RMDir "$SMPROGRAMS\EsteliPOS"
SectionEnd
