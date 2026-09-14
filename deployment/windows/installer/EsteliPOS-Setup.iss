#ifndef AppVersion
  #define AppVersion "1.0.10"
#endif

[Setup]
AppId={{D36F84A7-C350-46CB-8BD0-E57E11051070}
AppName=EsteliPOS
AppVersion={#AppVersion}
AppPublisher=Northlink Microsystem
AppPublisherURL=https://northlinkni.com
DefaultDirName={autopf}\Northlink\EsteliPOS
DisableDirPage=yes
DisableProgramGroupPage=yes
DisableReadyMemo=no
DisableFinishedPage=no
PrivilegesRequired=admin
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0.17763
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
Uninstallable=no
OutputDir=..\..
OutputBaseFilename=EsteliPOS-Setup-{#AppVersion}
SetupLogging=yes
VersionInfoVersion={#AppVersion}.0
VersionInfoCompany=Northlink Microsystem
VersionInfoDescription=Instalador gráfico de EsteliPOS
VersionInfoProductName=EsteliPOS
VersionInfoProductVersion={#AppVersion}

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Files]
Source: "..\..\produccion1.0.zip"; DestDir: "{tmp}\EsteliPOS"; Flags: deleteafterinstall
Source: "..\..\produccion1.0.zip.sha256"; DestDir: "{tmp}\EsteliPOS"; Flags: deleteafterinstall
Source: "Bootstrap-EsteliPOS-Setup.ps1"; DestDir: "{tmp}\EsteliPOS"; Flags: deleteafterinstall

[Run]
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -STA -File ""{tmp}\EsteliPOS\Bootstrap-EsteliPOS-Setup.ps1"" -PayloadZip ""{tmp}\EsteliPOS\produccion1.0.zip"" -ChecksumFile ""{tmp}\EsteliPOS\produccion1.0.zip.sha256"""; StatusMsg: "Verificando e iniciando EsteliPOS..."; Flags: waituntilterminated
