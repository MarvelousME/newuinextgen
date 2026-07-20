# Build WordPress-ready release zips for all four NextGenTutors packages.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dist = Join-Path $root 'dist'

if (-not (Test-Path $dist)) {
    New-Item -ItemType Directory -Path $dist | Out-Null
}

Get-ChildItem -Path $root -Filter '*.zip' -File -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem -Path $dist -Filter '*.zip' -File -ErrorAction SilentlyContinue | Remove-Item -Force

$themeSrc     = Join-Path $root 'NextGenTutors-BeyondInfinity'
$companionSrc = Join-Path $root 'NextGenTutors-Companion'
$importerSrc  = Join-Path $root 'NextGenTutors-Html-Importer'
$managerSrc   = Join-Path $root 'NextGenTutors-Plugin-Manager'

$themeZip     = Join-Path $dist 'NextGenTutors-BeyondInfinity.zip'
$companionZip = Join-Path $dist 'NextGenTutors-Companion.zip'
$importerZip  = Join-Path $dist 'NextGenTutors-Html-Importer.zip'
$managerZip   = Join-Path $dist 'NextGenTutors-Plugin-Manager.zip'

foreach ($pair in @(
    @{ Name = 'Theme'; Path = $themeSrc },
    @{ Name = 'Companion'; Path = $companionSrc },
    @{ Name = 'Html-Importer'; Path = $importerSrc },
    @{ Name = 'Plugin-Manager'; Path = $managerSrc }
)) {
    if (-not (Test-Path $pair.Path)) { throw "Missing $($pair.Name) folder: $($pair.Path)" }
}

function Copy-ThemePackage {
    param(
        [string]$Source,
        [string]$Destination
    )
    if (Test-Path $Destination) { Remove-Item -Recurse -Force $Destination }
    New-Item -ItemType Directory -Path $Destination | Out-Null

    $excludeDirs = @(
        'content\_extracted',
        'audit-reports',
        '.git',
        'node_modules',
        '.cursor'
    )

    robocopy $Source $Destination /E /NFL /NDL /NJH /NJS /NC /NS /XD $excludeDirs /XF *.zip | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed staging theme to $Destination" }
}

php (Join-Path $companionSrc 'scripts\validate.php')
if ($LASTEXITCODE -ne 0) { throw 'NextGenTutors-Companion validation failed.' }

$buildSrc = Join-Path $companionSrc 'build-src'
$packageJson = Join-Path $buildSrc 'package.json'
if (Test-Path $packageJson) {
    Write-Host 'Building Automation Studio bundle...'
    Push-Location $buildSrc
    try {
        if (-not (Test-Path (Join-Path $buildSrc 'node_modules'))) {
            npm install
            if ($LASTEXITCODE -ne 0) { throw 'npm install failed for Studio build-src.' }
        }
        npm run build:copy
        if ($LASTEXITCODE -ne 0) { throw 'Studio build:copy failed.' }
    } finally {
        Pop-Location
    }
}

php (Join-Path $managerSrc 'scripts\validate.php')
if ($LASTEXITCODE -ne 0) { throw 'NextGenTutors-Plugin-Manager validation failed.' }

$themeStage = Join-Path $dist 'stage-theme\NextGenTutors-BeyondInfinity'
Copy-ThemePackage -Source $themeSrc -Destination $themeStage

Compress-Archive -Path $themeStage -DestinationPath $themeZip -CompressionLevel Optimal -Force
Remove-Item -Recurse -Force (Split-Path $themeStage -Parent)
Compress-Archive -Path $companionSrc -DestinationPath $companionZip -CompressionLevel Optimal -Force
Compress-Archive -Path $importerSrc -DestinationPath $importerZip -CompressionLevel Optimal -Force
Compress-Archive -Path $managerSrc -DestinationPath $managerZip -CompressionLevel Optimal -Force

Add-Type -AssemblyName System.IO.Compression.FileSystem

function Test-ZipRootFolder {
    param([string]$ZipPath, [string]$ExpectedRoot)
    $entries = [System.IO.Compression.ZipFile]::OpenRead($ZipPath).Entries | ForEach-Object { $_.FullName.Replace('\', '/') }
    $hasRoot = $entries | Where-Object { $_ -like "$ExpectedRoot/*" -or $_ -eq "$ExpectedRoot/" } | Select-Object -First 1
    if (-not $hasRoot) { throw "Zip root folder missing: $ExpectedRoot in $ZipPath" }
}

function Test-ZipContains {
    param([string]$ZipPath, [string]$RelativePath)
    $normalized = $RelativePath.Replace('\', '/')
    $entries = [System.IO.Compression.ZipFile]::OpenRead($ZipPath).Entries | ForEach-Object { $_.FullName.Replace('\', '/') }
    if (-not ($entries -contains $normalized)) { throw "Zip missing: $normalized in $ZipPath" }
}

function Test-ZipExcludes {
    param([string]$ZipPath, [string]$ForbiddenFragment)
    $entries = [System.IO.Compression.ZipFile]::OpenRead($ZipPath).Entries | ForEach-Object { $_.FullName.Replace('\', '/') }
    $hit = $entries | Where-Object { $_ -like "*$ForbiddenFragment*" } | Select-Object -First 1
    if ($hit) { throw "Zip should exclude $ForbiddenFragment but found: $hit" }
}

Test-ZipRootFolder -ZipPath $themeZip -ExpectedRoot 'NextGenTutors-BeyondInfinity'
Test-ZipContains -ZipPath $themeZip -RelativePath 'NextGenTutors-BeyondInfinity/style.css'
Test-ZipExcludes -ZipPath $themeZip -ForbiddenFragment 'content/_extracted'

Test-ZipRootFolder -ZipPath $companionZip -ExpectedRoot 'NextGenTutors-Companion'
Test-ZipContains -ZipPath $companionZip -RelativePath 'NextGenTutors-Companion/nextgencompanion.php'

Test-ZipRootFolder -ZipPath $importerZip -ExpectedRoot 'NextGenTutors-Html-Importer'
Test-ZipContains -ZipPath $importerZip -RelativePath 'NextGenTutors-Html-Importer/revamp-html-importer.php'

Test-ZipRootFolder -ZipPath $managerZip -ExpectedRoot 'NextGenTutors-Plugin-Manager'
Test-ZipContains -ZipPath $managerZip -RelativePath 'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php'

Write-Host ''
Write-Host 'Release packages created:'
Write-Host "  $themeZip"
Write-Host "  $companionZip"
Write-Host "  $importerZip"
Write-Host "  $managerZip"
Write-Host ''
php (Join-Path $companionSrc 'scripts\verify-versions.php')
if ($LASTEXITCODE -ne 0) { throw 'Version verification failed.' }
