# Build WordPress-ready release zips for all five NextGenTutors packages.
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
$aiSrc        = Join-Path $root 'NextGenTutors-AI-Integration'
$importerSrc  = Join-Path $root 'NextGenTutors-Html-Importer'
$managerSrc   = Join-Path $root 'NextGenTutors-Plugin-Manager'

$themeZip     = Join-Path $dist 'NextGenTutors-BeyondInfinity.zip'
$companionZip = Join-Path $dist 'NextGenTutors-Companion.zip'
$aiZip        = Join-Path $dist 'NextGenTutors-AI-Integration.zip'
$importerZip  = Join-Path $dist 'NextGenTutors-Html-Importer.zip'
$managerZip   = Join-Path $dist 'NextGenTutors-Plugin-Manager.zip'

foreach ($pair in @(
    @{ Name = 'Theme'; Path = $themeSrc },
    @{ Name = 'Companion'; Path = $companionSrc },
    @{ Name = 'AI-Integration'; Path = $aiSrc },
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

    # /XD matches directory names (not relative paths), so use _extracted not content\_extracted
    $excludeDirs = @(
        '_extracted',
        'audit-reports',
        '.git',
        'node_modules',
        '.cursor'
    )

    robocopy $Source $Destination /E /NFL /NDL /NJH /NJS /NC /NS /XD $excludeDirs /XF *.zip | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed staging theme to $Destination" }

    $extracted = Join-Path $Destination 'content\_extracted'
    if (Test-Path $extracted) { Remove-Item -Recurse -Force $extracted }
}

function Copy-PluginPackage {
    param(
        [string]$Source,
        [string]$Destination,
        [switch]$IncludeOfflinePackages
    )
    if (Test-Path $Destination) { Remove-Item -Recurse -Force $Destination }
    New-Item -ItemType Directory -Path $Destination | Out-Null
    robocopy $Source $Destination /E /NFL /NDL /NJH /NJS /NC /NS /XD vendor .git node_modules /XF *.zip | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed staging plugin to $Destination" }

    if ($IncludeOfflinePackages) {
        $offlineSource = Join-Path $Source 'offline-packages'
        $offlineDest = Join-Path $Destination 'offline-packages'
        if (-not (Test-Path $offlineSource)) { throw "Missing offline packages folder: $offlineSource" }
        if (-not (Test-Path $offlineDest)) { New-Item -ItemType Directory -Path $offlineDest | Out-Null }
        robocopy $offlineSource $offlineDest *.zip /NFL /NDL /NJH /NJS /NC /NS | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "robocopy failed staging offline packages to $offlineDest" }
    }
}

php (Join-Path $companionSrc 'scripts\validate.php')
if ($LASTEXITCODE -ne 0) { throw 'NextGenTutors-Companion validation failed.' }

$buildSrc = Join-Path $companionSrc 'build-src'
$packageJson = Join-Path $buildSrc 'package.json'
if (Test-Path $packageJson) {
    Write-Host 'Building Automation Studio bundle...'
    Push-Location $buildSrc
    try {
        # Always refresh deps so incomplete node_modules (missing vite) cannot skip install
        npm install
        if ($LASTEXITCODE -ne 0) { throw 'npm install failed for Studio build-src.' }
        npm run build:copy
        if ($LASTEXITCODE -ne 0) { throw 'Studio build:copy failed.' }
    } finally {
        Pop-Location
    }
}

php (Join-Path $managerSrc 'scripts\validate.php')
if ($LASTEXITCODE -ne 0) { throw 'NextGenTutors-Plugin-Manager validation failed.' }

Add-Type -AssemblyName System.IO.Compression.FileSystem

# Use bsdtar (ships with Windows 10+) so zip entries get forward-slash separators.
# .NET ZipFile/Compress-Archive under Windows PowerShell 5.1 write backslash entry
# names, which WordPress unzips as flat files ("theme is missing style.css").
function New-ReleaseZip {
    param(
        [string]$StageParent,
        [string]$RootFolder,
        [string]$ZipPath,
        [int]$Attempts = 8
    )
    if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }
    for ($i = 1; $i -le $Attempts; $i++) {
        tar.exe -a -cf $ZipPath -C $StageParent $RootFolder
        if ($LASTEXITCODE -eq 0) { return }
        if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }
        if ($i -eq $Attempts) { throw "tar zip failed for $(Split-Path $ZipPath -Leaf)" }
        Write-Host "Zip retry $i/$Attempts for $(Split-Path $ZipPath -Leaf)"
        Start-Sleep -Seconds (2 * $i)
    }
}

$themeStageParent = Join-Path $dist 'stage-theme'
$themeStage = Join-Path $themeStageParent 'NextGenTutors-BeyondInfinity'
Copy-ThemePackage -Source $themeSrc -Destination $themeStage
New-ReleaseZip -StageParent $themeStageParent -RootFolder 'NextGenTutors-BeyondInfinity' -ZipPath $themeZip
Remove-Item -Recurse -Force $themeStageParent

$companionStageParent = Join-Path $dist 'stage-companion'
$companionStage = Join-Path $companionStageParent 'NextGenTutors-Companion'
Copy-PluginPackage -Source $companionSrc -Destination $companionStage
New-ReleaseZip -StageParent $companionStageParent -RootFolder 'NextGenTutors-Companion' -ZipPath $companionZip
Remove-Item -Recurse -Force $companionStageParent

$aiStageParent = Join-Path $dist 'stage-ai'
$aiStage = Join-Path $aiStageParent 'NextGenTutors-AI-Integration'
Copy-PluginPackage -Source $aiSrc -Destination $aiStage
New-ReleaseZip -StageParent $aiStageParent -RootFolder 'NextGenTutors-AI-Integration' -ZipPath $aiZip
Remove-Item -Recurse -Force $aiStageParent

$importerStageParent = Join-Path $dist 'stage-importer'
$importerStage = Join-Path $importerStageParent 'NextGenTutors-Html-Importer'
Copy-PluginPackage -Source $importerSrc -Destination $importerStage
New-ReleaseZip -StageParent $importerStageParent -RootFolder 'NextGenTutors-Html-Importer' -ZipPath $importerZip
Remove-Item -Recurse -Force $importerStageParent

$managerStageParent = Join-Path $dist 'stage-manager'
$managerStage = Join-Path $managerStageParent 'NextGenTutors-Plugin-Manager'
Copy-PluginPackage -Source $managerSrc -Destination $managerStage -IncludeOfflinePackages
New-ReleaseZip -StageParent $managerStageParent -RootFolder 'NextGenTutors-Plugin-Manager' -ZipPath $managerZip
Remove-Item -Recurse -Force $managerStageParent

function Test-ZipForwardSlashes {
    param([string]$ZipPath)
    $bad = [System.IO.Compression.ZipFile]::OpenRead($ZipPath).Entries | Where-Object { $_.FullName -like '*\*' } | Select-Object -First 1
    if ($bad) { throw "Zip has backslash entry names (breaks WordPress install): $($bad.FullName) in $ZipPath" }
}

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

foreach ($zipToCheck in @($themeZip, $companionZip, $aiZip, $importerZip, $managerZip)) {
    Test-ZipForwardSlashes -ZipPath $zipToCheck
}

Test-ZipRootFolder -ZipPath $themeZip -ExpectedRoot 'NextGenTutors-BeyondInfinity'
Test-ZipContains -ZipPath $themeZip -RelativePath 'NextGenTutors-BeyondInfinity/style.css'
Test-ZipExcludes -ZipPath $themeZip -ForbiddenFragment 'content/_extracted'

Test-ZipRootFolder -ZipPath $companionZip -ExpectedRoot 'NextGenTutors-Companion'
Test-ZipContains -ZipPath $companionZip -RelativePath 'NextGenTutors-Companion/nextgencompanion.php'

Test-ZipRootFolder -ZipPath $aiZip -ExpectedRoot 'NextGenTutors-AI-Integration'
Test-ZipContains -ZipPath $aiZip -RelativePath 'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php'
Test-ZipContains -ZipPath $aiZip -RelativePath 'NextGenTutors-AI-Integration/tests/run.php'
Test-ZipExcludes -ZipPath $aiZip -ForbiddenFragment '/vendor/'
Test-ZipExcludes -ZipPath $aiZip -ForbiddenFragment '/.git/'
Test-ZipExcludes -ZipPath $aiZip -ForbiddenFragment '/node_modules/'

Test-ZipRootFolder -ZipPath $importerZip -ExpectedRoot 'NextGenTutors-Html-Importer'
Test-ZipContains -ZipPath $importerZip -RelativePath 'NextGenTutors-Html-Importer/revamp-html-importer.php'

Test-ZipRootFolder -ZipPath $managerZip -ExpectedRoot 'NextGenTutors-Plugin-Manager'
Test-ZipContains -ZipPath $managerZip -RelativePath 'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php'
foreach ($offlinePackage in @(
    'automatorwp.zip',
    'elementor.zip',
    'fluent-crm.zip',
    'fluent-smtp.zip',
    'gamipress.zip',
    'masterstudy-lms-learning-management-system.zip',
    'paygate-payweb-for-woocommerce.1.7.1.zip',
    'user-role-editor.zip',
    'woocommerce-payfast-gateway.1.7.7.zip',
    'woocommerce.zip'
)) {
    Test-ZipContains -ZipPath $managerZip -RelativePath "NextGenTutors-Plugin-Manager/offline-packages/$offlinePackage"
}

Write-Host ''
Write-Host 'Release packages created:'
Write-Host "  $themeZip"
Write-Host "  $companionZip"
Write-Host "  $aiZip"
Write-Host "  $importerZip"
Write-Host "  $managerZip"
Write-Host ''
php (Join-Path $companionSrc 'scripts\verify-versions.php')
if ($LASTEXITCODE -ne 0) { throw 'Version verification failed.' }
