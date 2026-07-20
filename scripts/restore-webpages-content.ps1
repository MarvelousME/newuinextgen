# Restore webpages-content from BeyondInfinity preview HTML (Phase 1).
# Maps preview filenames to Html-Importer expected names.
#
# Usage: powershell -File scripts/restore-webpages-content.ps1

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
$src  = Join-Path $repo 'NextGenTutors-BeyondInfinity\previews\pages'
$dest = Join-Path $repo 'webpages-content'

if (-not (Test-Path $src)) {
    Write-Error "Source not found: $src"
}

$renameMap = @{
    'home.html'              = 'index.html'
    'privacy-policy.html'    = 'privacy.html'
    'student-dashboard.html' = 'dashboard.html'
}

New-Item -ItemType Directory -Force -Path $dest | Out-Null

$copied = 0
Get-ChildItem -Path $src -Filter '*.html' | ForEach-Object {
    $outName = $renameMap[$_.Name]
    if (-not $outName) { $outName = $_.Name }
    $target = Join-Path $dest $outName
    Copy-Item -Path $_.FullName -Destination $target -Force
    Write-Host "OK $($_.Name) -> $outName"
    $copied++
}

$readme = @"
# webpages-content

Static HTML sources for **NextGenTutors-Html-Importer**.

Restored from ``NextGenTutors-BeyondInfinity/previews/pages/`` via ``scripts/restore-webpages-content.ps1``.

| Preview file | Importer file |
|--------------|---------------|
| home.html | index.html |
| privacy-policy.html | privacy.html |
| student-dashboard.html | dashboard.html |

Run importer: WordPress admin → Revamp HTML Importer, or ``wp rhi import``.
"@
Set-Content -Path (Join-Path $dest 'README.md') -Value $readme -Encoding UTF8

Write-Host "Restored $copied HTML file(s) to webpages-content/"
