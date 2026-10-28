param(
    [string]$Version,
    [string]$SourceDir = $PSScriptRoot,
    [string]$OutputDir = ""
)

if (-not $Version) {
    Write-Host "Errore: Devi specificare la versione. Esempio: .\package_release.ps1 -Version 2.7.17" -ForegroundColor Red
    exit 1
}

$resolvedSourceDir = (Resolve-Path $SourceDir).Path.TrimEnd('\')
if (-not $OutputDir) {
    $resolvedOutputDir = $resolvedSourceDir
} else {
    if (-not (Test-Path $OutputDir)) {
        New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
    }
    $resolvedOutputDir = (Resolve-Path $OutputDir).Path.TrimEnd('\')
}

# 1. Housekeeping: sposta i vecchi ZIP in archive/ (se si pacchettizza la root)
if ($resolvedOutputDir -eq (Resolve-Path $PSScriptRoot).Path.TrimEnd('\')) {
    Write-Host "Spostamento vecchi file ZIP in archive/..." -ForegroundColor Cyan
    if (!(Test-Path "$PSScriptRoot\archive")) {
        New-Item -ItemType Directory -Path "$PSScriptRoot\archive" -Force | Out-Null
    }
    Get-ChildItem -Path "$PSScriptRoot\*.zip" | Move-Item -Destination "$PSScriptRoot\archive\" -Force
}

# 2. Creazione Nuovi ZIP con System.IO.Compression usando forward slashes per WordPress
Write-Host "Caricamento librerie .NET System.IO.Compression..." -ForegroundColor Cyan
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

function Zip-Folder {
    param([string]$source, [string]$destination)
    if (Test-Path $destination) { Remove-Item $destination -Force }
    
    $resolvedSource = (Resolve-Path $source).Path.TrimEnd('\')
    $folderName = (Get-Item $resolvedSource).Name
    $zip = [System.IO.Compression.ZipFile]::Open($destination, [System.IO.Compression.ZipArchiveMode]::Create)
    
    Get-ChildItem -Path $resolvedSource -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Substring($resolvedSource.Length + 1).Replace('\', '/')
        $entryName = "$folderName/$relativePath"
        $null = [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal)
    }
    
    $zip.Dispose()
    Write-Host "Creazione pacchetto completata: $destination (con forward slashes sicuri)" -ForegroundColor Green
}

# Parent Theme
Write-Host "Creazione ZIP Parent Theme in corso..." -ForegroundColor Yellow
Zip-Folder -source "$resolvedSourceDir\NTN_Journal_Theme_Reloaded\ntn-journal" -destination "$resolvedOutputDir\ntn-journal-parent-$Version.zip"

# Child Theme
Write-Host "Creazione ZIP Child Theme in corso..." -ForegroundColor Yellow
Zip-Folder -source "$resolvedSourceDir\NTN_Journal_Theme_Reloaded\ntn-journal-reloaded" -destination "$resolvedOutputDir\ntn-journal-child-reloaded-$Version.zip"

# Core Plugin
Write-Host "Creazione ZIP Core Plugin in corso..." -ForegroundColor Yellow
Zip-Folder -source "$resolvedSourceDir\NTN_Journal_Core_Plugin\ntn-journal-core" -destination "$resolvedOutputDir\ntn-journal-core-$Version.zip"

Write-Host "Pacchettizzazione di tutte le risorse completata con successo!" -ForegroundColor Green

