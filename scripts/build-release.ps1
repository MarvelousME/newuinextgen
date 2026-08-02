# Build WordPress-ready release zips for all NextGenTutors packages.
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
$missionSrc   = Join-Path $root 'NextGenTutors-Mission-Control'
$uiLibrarySrc = Join-Path $root 'ui-library'

$themeZip     = Join-Path $dist 'NextGenTutors-BeyondInfinity.zip'
$companionZip = Join-Path $dist 'NextGenTutors-Companion.zip'
$aiZip        = Join-Path $dist 'NextGenTutors-AI-Integration.zip'
$importerZip  = Join-Path $dist 'NextGenTutors-Html-Importer.zip'
$managerZip   = Join-Path $dist 'NextGenTutors-Plugin-Manager.zip'
$missionZip   = Join-Path $dist 'NextGenTutors-Mission-Control.zip'
$uiLibraryZip = Join-Path $dist 'ngt-ui-library.zip'

foreach ($pair in @(
    @{ Name = 'Theme'; Path = $themeSrc },
    @{ Name = 'Companion'; Path = $companionSrc },
    @{ Name = 'AI-Integration'; Path = $aiSrc },
    @{ Name = 'Html-Importer'; Path = $importerSrc },
    @{ Name = 'Plugin-Manager'; Path = $managerSrc },
    @{ Name = 'Mission-Control'; Path = $missionSrc },
    @{ Name = 'ui-library'; Path = $uiLibrarySrc }
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

function Copy-LibraryPackage {
    param(
        [string]$Source,
        [string]$Destination
    )
    if (Test-Path $Destination) { Remove-Item -Recurse -Force $Destination }
    New-Item -ItemType Directory -Path $Destination | Out-Null
    robocopy $Source $Destination /E /NFL /NDL /NJH /NJS /NC /NS /XD .git node_modules tests /XF *.zip | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed staging library to $Destination" }
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

# WordPress/PclZip-compatible zip writer.
# Do NOT use `tar -a` here: it sets ZIP data-descriptor flags (flag_bits bit 3)
# that many hosts fail to unpack, which surfaces as:
#   "The theme is missing the style.css stylesheet."
# Do NOT use Compress-Archive / ZipFile on Windows PowerShell 5.1 either:
# those write backslash entry names and flatten the theme on extract.
function New-ReleaseZip {
    param(
        [string]$StageParent,
        [string]$RootFolder,
        [string]$ZipPath,
        [int]$Attempts = 8
    )
    if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }

    $py = @'
import os, sys, zipfile

stage_parent, root_folder, zip_path = sys.argv[1], sys.argv[2], sys.argv[3]
root_path = os.path.join(stage_parent, root_folder)
if not os.path.isdir(root_path):
    raise SystemExit(f"missing staged folder: {root_path}")

# writestr(full bytes) writes CRC/sizes in the local header (no 0x08 data descriptor).
# Streaming ZipFile.open(..., "w") reintroduces the data-descriptor flag that
# breaks WordPress/PclZip on some hosts ("missing the style.css stylesheet").
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, allowZip64=True) as zf:
    # Explicit directory entry so WordPress sees a single theme root.
    zf.writestr(root_folder.replace("\\", "/") + "/", b"")
    for dirpath, dirnames, filenames in os.walk(root_path):
        dirnames.sort()
        filenames.sort()
        for filename in filenames:
            abs_path = os.path.join(dirpath, filename)
            rel = os.path.relpath(abs_path, stage_parent)
            arcname = rel.replace("\\", "/")
            st = os.stat(abs_path)
            info = zipfile.ZipInfo(arcname, date_time=tuple(__import__("time").localtime(st.st_mtime)[:6]))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.create_system = 0  # DOS — widest unzip compatibility
            info.external_attr = 0o644 << 16
            with open(abs_path, "rb") as fh:
                zf.writestr(info, fh.read())
'@
    $pyFile = Join-Path $env:TEMP 'ngt-release-zip.py'
    Set-Content -Path $pyFile -Value $py -Encoding UTF8

    for ($i = 1; $i -le $Attempts; $i++) {
        & python $pyFile $StageParent $RootFolder $ZipPath
        if ($LASTEXITCODE -eq 0 -and (Test-Path $ZipPath)) {
            # Guard: style.css / main plugin file must not carry data-descriptor flag.
            $probe = @'
import sys, zipfile
zpath = sys.argv[1]
needle = sys.argv[2]
z = zipfile.ZipFile(zpath)
hits = [i for i in z.infolist() if i.filename.replace("\\", "/").endswith(needle)]
if not hits:
    raise SystemExit(f"missing {needle} in {zpath}")
info = hits[0]
if info.flag_bits & 0x08:
    raise SystemExit(f"data-descriptor flag set on {info.filename} (flag_bits={info.flag_bits})")
if "\\" in info.filename:
    raise SystemExit(f"backslash entry name: {info.filename!r}")
print(f"ok {info.filename} flag_bits={info.flag_bits} compress={info.compress_type}")
'@
            $probeFile = Join-Path $env:TEMP 'ngt-probe-zip.py'
            Set-Content -Path $probeFile -Value $probe -Encoding UTF8
            $needle = if ($RootFolder -like '*BeyondInfinity*') { 'style.css' } elseif ($RootFolder -like '*Companion*') { 'nextgencompanion.php' } else { "$RootFolder/" }
            & python $probeFile $ZipPath $needle
            if ($LASTEXITCODE -eq 0) { return }
        }
        if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }
        if ($i -eq $Attempts) { throw "WordPress-compatible zip failed for $(Split-Path $ZipPath -Leaf)" }
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

$missionStageParent = Join-Path $dist 'stage-mission'
$missionStage = Join-Path $missionStageParent 'NextGenTutors-Mission-Control'
Copy-PluginPackage -Source $missionSrc -Destination $missionStage
New-ReleaseZip -StageParent $missionStageParent -RootFolder 'NextGenTutors-Mission-Control' -ZipPath $missionZip
Remove-Item -Recurse -Force $missionStageParent

$uiStageParent = Join-Path $dist 'stage-ui-library'
$uiStage = Join-Path $uiStageParent 'ngt-ui-library'
Copy-LibraryPackage -Source $uiLibrarySrc -Destination $uiStage
New-ReleaseZip -StageParent $uiStageParent -RootFolder 'ngt-ui-library' -ZipPath $uiLibraryZip
Remove-Item -Recurse -Force $uiStageParent

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

foreach ($zipToCheck in @($themeZip, $companionZip, $aiZip, $importerZip, $managerZip, $missionZip, $uiLibraryZip)) {
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

Test-ZipRootFolder -ZipPath $missionZip -ExpectedRoot 'NextGenTutors-Mission-Control'
Test-ZipContains -ZipPath $missionZip -RelativePath 'NextGenTutors-Mission-Control/nextgentutors-mission-control.php'

Test-ZipRootFolder -ZipPath $uiLibraryZip -ExpectedRoot 'ngt-ui-library'
Test-ZipContains -ZipPath $uiLibraryZip -RelativePath 'ngt-ui-library/bootstrap/class-ngt-ui-bootstrap.php'

Write-Host ''
Write-Host 'Release packages created:'
Write-Host "  $themeZip"
Write-Host "  $companionZip"
Write-Host "  $aiZip"
Write-Host "  $importerZip"
Write-Host "  $managerZip"
Write-Host "  $missionZip"
Write-Host "  $uiLibraryZip"
Write-Host ''
Write-Host 'Deploy ui-library to wp-content/ngt-ui-library (extract ngt-ui-library.zip).'
Write-Host ''
php (Join-Path $companionSrc 'scripts\verify-versions.php')
if ($LASTEXITCODE -ne 0) { throw 'Version verification failed.' }

# --- Release folder: versioned copies, checksums, manifest ---
$releaseRoot = Join-Path $root 'release'
$themeVersion = '1.9.17'
$companionVersion = '1.9.5'
if (Test-Path (Join-Path $themeSrc 'style.css')) {
    $css = Get-Content (Join-Path $themeSrc 'style.css') -Raw
    if ($css -match 'Version:\s*([0-9.]+)') { $themeVersion = $Matches[1] }
}
if (Test-Path (Join-Path $companionSrc 'nextgencompanion.php')) {
    $php = Get-Content (Join-Path $companionSrc 'nextgencompanion.php') -Raw
    if ($php -match 'Version:\s*([0-9.]+)') { $companionVersion = $Matches[1] }
}

$releaseTheme = Join-Path $releaseRoot 'theme'
$releasePlugins = Join-Path $releaseRoot 'plugins'
$releaseConfig = Join-Path $releaseRoot 'config'
$releaseDocs = Join-Path $releaseRoot 'documentation'
$releaseDiagrams = Join-Path $releaseRoot 'diagrams'
$releaseEvidence = Join-Path $releaseRoot 'evidence'
foreach ($d in @($releaseTheme, $releasePlugins, $releaseConfig, $releaseDocs, $releaseDiagrams, $releaseEvidence)) {
    if (-not (Test-Path $d)) { New-Item -ItemType Directory -Path $d -Force | Out-Null }
}

$versioned = @{
    (Join-Path $releaseTheme "NextGenTutors-BeyondInfinity-v$themeVersion.zip") = $themeZip
    (Join-Path $releasePlugins "NextGenTutors-Companion-v$companionVersion.zip") = $companionZip
    (Join-Path $releasePlugins "NextGenTutors-AI-Integration-v$companionVersion.zip") = $aiZip
    (Join-Path $releasePlugins "NextGenTutors-Mission-Control-v$companionVersion.zip") = $missionZip
    (Join-Path $releasePlugins "NextGenTutors-Plugin-Manager-v$companionVersion.zip") = $managerZip
    (Join-Path $releasePlugins "NextGenTutors-Html-Importer-v$companionVersion.zip") = $importerZip
    (Join-Path $releasePlugins "ngt-ui-library-v$companionVersion.zip") = $uiLibraryZip
}
foreach ($dest in $versioned.Keys) {
    Copy-Item -Force $versioned[$dest] $dest
}

if (Test-Path (Join-Path $root 'config\nextgentutors-business-profile.json')) {
    Copy-Item -Force (Join-Path $root 'config\nextgentutors-business-profile.json') (Join-Path $releaseConfig 'nextgentutors-business-profile.json')
}

$checksumFile = Join-Path $releaseRoot 'checksums.sha256'
$checksumLines = @()
Get-ChildItem -Path $releaseTheme, $releasePlugins -Filter *.zip -File | Sort-Object FullName | ForEach-Object {
    $hash = (Get-FileHash -Algorithm SHA256 $_.FullName).Hash.ToLower()
    $rel = $_.FullName.Substring($releaseRoot.Length + 1).Replace('\', '/')
    $checksumLines += "$hash  $rel"
}
Set-Content -Path $checksumFile -Value ($checksumLines -join "`n") -Encoding ASCII

$gitSha = ''
try { $gitSha = (git -C $root rev-parse HEAD 2>$null) } catch { $gitSha = '' }
$manifest = [ordered]@{
    schema_version     = '1.0.0'
    generated_at       = (Get-Date).ToUniversalTime().ToString('o')
    git_commit         = "$gitSha"
    theme_version      = $themeVersion
    companion_version  = $companionVersion
    target_site        = 'https://www.nextgentutors.co.za'
    timezone           = 'Africa/Johannesburg'
    currency           = 'ZAR'
    packages           = @(
        @{ name = 'NextGenTutors-BeyondInfinity'; version = $themeVersion; path = "theme/NextGenTutors-BeyondInfinity-v$themeVersion.zip" }
        @{ name = 'NextGenTutors-Companion'; version = $companionVersion; path = "plugins/NextGenTutors-Companion-v$companionVersion.zip" }
        @{ name = 'NextGenTutors-AI-Integration'; version = $companionVersion; path = "plugins/NextGenTutors-AI-Integration-v$companionVersion.zip" }
        @{ name = 'NextGenTutors-Mission-Control'; version = $companionVersion; path = "plugins/NextGenTutors-Mission-Control-v$companionVersion.zip" }
        @{ name = 'NextGenTutors-Plugin-Manager'; version = $companionVersion; path = "plugins/NextGenTutors-Plugin-Manager-v$companionVersion.zip" }
        @{ name = 'NextGenTutors-Html-Importer'; version = $companionVersion; path = "plugins/NextGenTutors-Html-Importer-v$companionVersion.zip" }
        @{ name = 'ngt-ui-library'; version = $companionVersion; path = "plugins/ngt-ui-library-v$companionVersion.zip" }
    )
    checksums_file     = 'checksums.sha256'
    notes              = 'Production deployment requires explicit authorization. Secrets are never packaged.'
}
$manifestPath = Join-Path $releaseRoot 'release-manifest.json'
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($manifestPath, ($manifest | ConvertTo-Json -Depth 6), $utf8NoBom)

# Also place a copy under dist for packaging step discovery
Copy-Item -Force $manifestPath (Join-Path $dist 'release-manifest.json')
Copy-Item -Force $checksumFile (Join-Path $dist 'checksums.sha256')

Write-Host ''
Write-Host 'Release folder prepared:'
Write-Host "  $manifestPath"
Write-Host "  $checksumFile"
