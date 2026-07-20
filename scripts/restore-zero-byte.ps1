$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Restore-ZipEntry {
    param([string]$ZipPath, [string]$EntrySuffix, [string]$DestPath)
    $zip = [IO.Compression.ZipFile]::OpenRead($ZipPath)
    try {
        $entry = $zip.Entries | Where-Object { $_.FullName -replace '\\','/' -like "*$EntrySuffix" } | Select-Object -First 1
        if (-not $entry) { Write-Warning "Not found: $EntrySuffix in $ZipPath"; return }
        $dir = Split-Path $DestPath -Parent
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        [IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $DestPath, $true)
        Write-Host "OK $($entry.FullName) -> $DestPath"
    } finally {
        $zip.Dispose()
    }
}

$root = 'c:\Users\marvi\Music\REVAMP'
$themeZip = Join-Path $root 'dist\NextGenTutors-BeyondInfinity.zip'
$compZip  = Join-Path $root 'dist\NextGenTutors-Companion.zip'
$theme    = Join-Path $root 'NextGenTutors-BeyondInfinity'

Restore-ZipEntry $themeZip 'functions.php' (Join-Path $theme 'functions.php')
Restore-ZipEntry $themeZip 'style.css' (Join-Path $theme 'style.css')
Restore-ZipEntry $themeZip 'assets/css/bi-3d.css' (Join-Path $theme 'assets\css\bi-3d.css')
Restore-ZipEntry $themeZip 'assets/js/tutors-carousel.js' (Join-Path $theme 'assets\js\tutors-carousel.js')
Restore-ZipEntry $themeZip 'assets/css/kinetic-home.css' (Join-Path $theme 'assets\css\kinetic-home.css')
Restore-ZipEntry $compZip 'nextgencompanion.php' (Join-Path $root 'NextGenTutors-Companion\nextgencompanion.php')
Restore-ZipEntry $themeZip 'inc/defaults/home.php' (Join-Path $theme 'inc\defaults\home.php')
