# Restore Stage 8 pilot quarantine copies from manifest (does not remove originals).

param(
    [string]$ManifestPath = "content/_quarantine/stage8-pilot/manifest.json"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$manifestFull = Join-Path $root $ManifestPath

if (-not (Test-Path $manifestFull)) {
    Write-Error "Manifest not found: $manifestFull — run scripts/stage8-quarantine-pilot.ps1 first."
}

$manifest = Get-Content $manifestFull -Raw | ConvertFrom-Json
$restored = 0

foreach ($entry in $manifest.entries) {
    $src = Join-Path $root $entry.quarantine_path
    $rel = ($entry.relative_path -replace '^to-discard/', '')
    $dest = Join-Path $root "to-discard/$rel"

    if (-not (Test-Path $src)) {
        Write-Warning "SKIP missing quarantine copy: $($entry.quarantine_path)"
        continue
    }

    $destDir = Split-Path $dest -Parent
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    Copy-Item -Path $src -Destination $dest -Force
    $restored++
    Write-Host "RESTORED $rel"
}

Write-Host "Restored $restored file(s) from pilot quarantine."
