# Build the PRODUCTION distribution (PclZip-safe ZIPs, honest versions, no secrets).
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-production-release.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$prod = Join-Path $root 'PRODUCTION'
$pkgDir = Join-Path $prod '01-INSTALLABLE-PACKAGES'
$optDir = Join-Path $pkgDir 'optional'
$dropDir = Join-Path $pkgDir 'drop-ins'
$valDir = Join-Path $prod '04-VALIDATION'
$stageRoot = Join-Path $env:TEMP ('ngt-prod-' + [guid]::NewGuid().ToString('N').Substring(0, 8))

foreach ($d in @($prod, $pkgDir, $optDir, $dropDir, $valDir, $stageRoot)) {
    New-Item -ItemType Directory -Force -Path $d | Out-Null
}

function Get-WpHeaderVersion([string]$Path, [string]$Fallback) {
    if (-not (Test-Path $Path)) { return $Fallback }
    $raw = Get-Content -LiteralPath $Path -Raw -ErrorAction SilentlyContinue
    if ($raw -match '(?m)^\s*\*?\s*Version:\s*([0-9.]+)') { return $Matches[1] }
    return $Fallback
}

function Copy-Filtered {
    param(
        [string]$Source,
        [string]$Destination,
        [string[]]$ExcludeDirs
    )
    if (Test-Path $Destination) { Remove-Item -Recurse -Force $Destination }
    New-Item -ItemType Directory -Path $Destination | Out-Null
    $xd = @()
    foreach ($d in $ExcludeDirs) { $xd += @('/XD', $d) }
    & robocopy $Source $Destination /E /NFL /NDL /NJH /NJS /NC /NS @xd /XF *.zip *.map .env .env.* | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed: $Source -> $Destination" }
}

function New-ReleaseZip {
    param(
        [string]$StageParent,
        [string]$RootFolder,
        [string]$ZipPath
    )
    if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }

    $py = @'
import os, sys, zipfile, time
stage_parent, root_folder, zip_path = sys.argv[1], sys.argv[2], sys.argv[3]
root_path = os.path.join(stage_parent, root_folder)
if not os.path.isdir(root_path):
    raise SystemExit(f"missing staged folder: {root_path}")
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, allowZip64=True) as zf:
    zf.writestr(root_folder.replace("\\", "/") + "/", b"")
    for dirpath, dirnames, filenames in os.walk(root_path):
        dirnames[:] = [d for d in dirnames if d not in ('.git', 'node_modules', 'tests', 'vendor', 'build-src', 'offline-packages', '__pycache__')]
        dirnames.sort()
        filenames.sort()
        for filename in filenames:
            if filename.endswith('.zip') or filename in ('.env', '.DS_Store'):
                continue
            abs_path = os.path.join(dirpath, filename)
            rel = os.path.relpath(abs_path, stage_parent).replace("\\", "/")
            st = os.stat(abs_path)
            info = zipfile.ZipInfo(rel, date_time=tuple(time.localtime(st.st_mtime)[:6]))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.create_system = 0
            info.external_attr = 0o644 << 16
            with open(abs_path, "rb") as fh:
                zf.writestr(info, fh.read())
'@
    $pyFile = Join-Path $env:TEMP 'ngt-prod-zip.py'
    Set-Content -Path $pyFile -Value $py -Encoding UTF8
    & python $pyFile $StageParent $RootFolder $ZipPath
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $ZipPath)) {
        throw "WordPress-compatible zip failed for $ZipPath"
    }

    $probe = @'
import sys, zipfile
zpath, needle = sys.argv[1], sys.argv[2]
z = zipfile.ZipFile(zpath)
hits = [i for i in z.infolist() if i.filename.replace("\\", "/").endswith(needle)]
if not hits:
    raise SystemExit(f"missing {needle} in {zpath}")
info = hits[0]
if info.flag_bits & 0x08:
    raise SystemExit(f"data-descriptor flag set on {info.filename}")
if "\\" in info.filename:
    raise SystemExit(f"backslash entry name: {info.filename!r}")
print(f"ok {info.filename} flag_bits={info.flag_bits}")
'@
    $probeFile = Join-Path $env:TEMP 'ngt-prod-probe.py'
    Set-Content -Path $probeFile -Value $probe -Encoding UTF8
    $needle = if ($RootFolder -eq 'NextGenTutors-BeyondInfinity' -or $RootFolder -eq 'hello-elementor') { 'style.css' } else { "$RootFolder/" }
    & python $probeFile $ZipPath $needle
    if ($LASTEXITCODE -ne 0) { throw "Zip probe failed for $ZipPath" }
}

$pluginExclude = @('.git', 'node_modules', 'vendor', 'build-src', 'tests', '.cursor', 'offline-packages', '__pycache__')

# --- Materialize theme from monorepo root (copy, not junction) ---
$themeStageParent = Join-Path $stageRoot 'theme'
$themeStage = Join-Path $themeStageParent 'NextGenTutors-BeyondInfinity'
New-Item -ItemType Directory -Force -Path $themeStage | Out-Null

$themeDirs = @('assets', 'inc', 'templates', 'template-parts', 'page-templates', 'prototypes', 'content')
foreach ($d in $themeDirs) {
    $src = Join-Path $root $d
    if (Test-Path $src) {
        Copy-Filtered -Source $src -Destination (Join-Path $themeStage $d) -ExcludeDirs @('_extracted', '.git', 'node_modules', 'tests')
    }
}

$uiSrc = Join-Path $root 'ui-library'
if (Test-Path $uiSrc) {
    Copy-Filtered -Source $uiSrc -Destination (Join-Path $themeStage 'ui-library') -ExcludeDirs @('.git', 'node_modules', 'tests')
}

$pageFiles = Get-ChildItem -Path $root -Filter 'page-*.php' -File -ErrorAction SilentlyContinue
$rootFiles = @(
    'style.css', 'functions.php', 'header.php', 'footer.php', 'index.php',
    'front-page.php', 'home.php', 'page.php', 'single.php', 'single-tutors.php',
    'archive.php', 'archive-tutors.php', 'searchform.php', 'comments.php',
    '404.php', 'admin-dashboard.php', 'screenshot.png'
) + @($pageFiles | ForEach-Object { $_.Name })
foreach ($f in ($rootFiles | Select-Object -Unique)) {
    $src = Join-Path $root $f
    if (Test-Path -LiteralPath $src) {
        Copy-Item -LiteralPath $src -Destination (Join-Path $themeStage $f) -Force
    }
}

$extracted = Join-Path $themeStage 'content\_extracted'
if (Test-Path $extracted) { Remove-Item -Recurse -Force $extracted }

$themeVersion = Get-WpHeaderVersion (Join-Path $themeStage 'style.css') '1.9.29'
$themeZip = Join-Path $pkgDir "NextGenTutors-BeyondInfinity-v$themeVersion.zip"
New-ReleaseZip -StageParent $themeStageParent -RootFolder 'NextGenTutors-BeyondInfinity' -ZipPath $themeZip
Write-Host "Built theme $themeZip"

# --- Hello Elementor parent ---
$helloSrc = Join-Path $root 'docker\hello-elementor'
if (Test-Path $helloSrc) {
    $helloParent = Join-Path $stageRoot 'hello'
    $helloStage = Join-Path $helloParent 'hello-elementor'
    Copy-Filtered -Source $helloSrc -Destination $helloStage -ExcludeDirs @('.git', 'node_modules')
    $helloVer = Get-WpHeaderVersion (Join-Path $helloStage 'style.css') '3.5.1'
    $helloZip = Join-Path $pkgDir "Hello-Elementor-v$helloVer.zip"
    New-ReleaseZip -StageParent $helloParent -RootFolder 'hello-elementor' -ZipPath $helloZip
    Write-Host "Built parent $helloZip"
}

function Build-PluginZip {
    param(
        [string]$Name,
        [string]$SrcRel,
        [string]$Entry,
        [string]$DestDir,
        [string]$ZipNameOverride = ''
    )
    $src = Join-Path $root $SrcRel
    if (-not (Test-Path $src)) { throw "Missing plugin source $src" }
    $parent = Join-Path $stageRoot $Name
    $stage = Join-Path $parent $Name
    Copy-Filtered -Source $src -Destination $stage -ExcludeDirs $pluginExclude
    $ver = Get-WpHeaderVersion (Join-Path $stage $Entry) '0.0.0'
    $zipName = if ($ZipNameOverride) { $ZipNameOverride } else { "$Name-v$ver.zip" }
    $zipPath = Join-Path $DestDir $zipName
    New-ReleaseZip -StageParent $parent -RootFolder $Name -ZipPath $zipPath
    Write-Host "Built $zipPath"
    return @{ Name = $Name; Version = $ver; Path = $zipPath; Entry = $Entry }
}

$built = @()
$built += Build-PluginZip -Name 'NextGenTutors-Companion' -SrcRel 'NextGenTutors-Companion' -Entry 'nextgencompanion.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'NextGenTutors-Plugin-Manager' -SrcRel 'NextGenTutors-Plugin-Manager' -Entry 'NextGenTutors-Plugin-Manager.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'NextGenTutors-Mission-Control' -SrcRel 'NextGenTutors-Mission-Control' -Entry 'nextgentutors-mission-control.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'nextgen-3d-scroll-manager' -SrcRel 'nextgen-3d-scroll-manager' -Entry 'nextgen-3d-scroll-manager.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'nextgen-3d-filmstrip' -SrcRel 'nextgen-3d-filmstrip' -Entry 'nextgen-3d-filmstrip.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'nextgen-subjects-widget' -SrcRel 'nextgen-subjects-widget' -Entry 'nextgen-subjects-widget.php' -DestDir $pkgDir
$built += Build-PluginZip -Name 'NextGenTutors-Html-Importer' -SrcRel 'NextGenTutors-Html-Importer' -Entry 'revamp-html-importer.php' -DestDir $pkgDir

# Optional first-party plugins
$built += Build-PluginZip -Name 'NextGenTutors-AI-Integration' -SrcRel 'NextGenTutors-AI-Integration' -Entry 'nextgentutors-ai-integration.php' -DestDir $optDir
$built += Build-PluginZip -Name 'NextGenTutors-BeyondMeasure' -SrcRel 'NextGenTutors-BeyondMeasure' -Entry 'nextgentutors-beyond-measure.php' -DestDir $optDir
$built += Build-PluginZip -Name 'nextgen-automation-hub' -SrcRel 'nextgen-automation-hub' -Entry 'nextgen-automation-hub.php' -DestDir $optDir

# UI library drop-in (not a WP plugin)
$uiParent = Join-Path $stageRoot 'ui-drop'
$uiStage = Join-Path $uiParent 'ngt-ui-library'
Copy-Filtered -Source (Join-Path $root 'ui-library') -Destination $uiStage -ExcludeDirs @('.git', 'node_modules', 'tests')
$uiZip = Join-Path $dropDir 'ngt-ui-library.zip'
New-ReleaseZip -StageParent $uiParent -RootFolder 'ngt-ui-library' -ZipPath $uiZip
Write-Host "Built drop-in $uiZip (copy to wp-content/ngt-ui-library)"

# --- Checksums ---
$checksumFile = Join-Path $prod 'CHECKSUMS.sha256'
$checksumLines = @()
Get-ChildItem -Path $pkgDir -Filter *.zip -Recurse -File | Sort-Object FullName | ForEach-Object {
    $hash = (Get-FileHash -Algorithm SHA256 $_.FullName).Hash.ToLower()
    $rel = $_.FullName.Substring($prod.Length + 1).Replace('\', '/')
    $checksumLines += "$hash  $rel"
}
Set-Content -Path $checksumFile -Value ($checksumLines -join "`n") -Encoding ASCII

$gitSha = ''
try { $gitSha = (git -C $root rev-parse HEAD 2>$null) } catch { $gitSha = '' }

$manifest = [ordered]@{
    schema_version    = '1.0.0'
    release_train     = '2026.09.12'
    generated_at      = (Get-Date).ToUniversalTime().ToString('o')
    git_commit        = "$gitSha"
    theme_version     = $themeVersion
    recommendation    = 'CLEAN-INSTALL CANDIDATE - not production-ready until 04-VALIDATION/RELEASE-ACCEPTANCE.md is signed'
    checksums_file    = 'CHECKSUMS.sha256'
    notes             = 'Versions are plugin/theme headers. Secrets are never packaged. Plugin Manager ZIP is slim (no offline-packages). Companion excludes build-src/tests.'
}
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText((Join-Path $prod 'release-manifest.json'), ($manifest | ConvertTo-Json -Depth 6), $utf8NoBom)

# Inventory from live source (matches staged content)
php (Join-Path $root 'scripts\production-inventory.php') $valDir
if ($LASTEXITCODE -ne 0) { throw 'production-inventory.php failed' }

php (Join-Path $root 'scripts\production-docs.php')
if ($LASTEXITCODE -ne 0) { throw 'production-docs.php failed' }

Remove-Item -Recurse -Force $stageRoot -ErrorAction SilentlyContinue
Write-Host "PRODUCTION packages written to $prod"
