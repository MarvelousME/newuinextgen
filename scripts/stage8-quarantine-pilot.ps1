# Stage 8 quarantine pilot - copy removal candidates to isolated folder (no deletion).

param(
    [int]$Limit = 10,
    [switch]$DryRun,
    [string]$ManifestPath = "content/_quarantine/stage8-pilot/manifest.json"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot

$candidatesFile = Join-Path $root "audit-reports/removal-candidates.json"
$quarantineRoot = Join-Path $root "content/_quarantine/stage8-pilot"
$manifestFull = Join-Path $root $ManifestPath

if (-not (Test-Path $candidatesFile)) {
    Write-Error "Missing $candidatesFile"
}

$data = Get-Content $candidatesFile -Raw | ConvertFrom-Json
$items = @($data.candidates | Where-Object { $_.proposed_action -eq "Quarantine-candidate" } | Select-Object -First $Limit)

if ($items.Count -eq 0) {
    Write-Error "No quarantine candidates found."
}

$manifest = @{
    generated_at = (Get-Date).ToString("o")
    policy       = "Stage 8 pilot - copy only; originals retained until sign-off"
    limit        = $Limit
    entries      = @()
}

foreach ($item in $items) {
    $rel = ($item.relative_path -replace '^to-discard/', '')
    $src = Join-Path $root "to-discard/$rel"
    if (-not (Test-Path $src)) {
        $src = Join-Path $root $item.relative_path
    }
    if (-not (Test-Path $src)) {
        Write-Warning "SKIP missing: $($item.relative_path)"
        continue
    }

    $dest = Join-Path $quarantineRoot $rel
    $destDir = Split-Path $dest -Parent
    if (-not $DryRun) {
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        Copy-Item -Path $src -Destination $dest -Force
    }

    $manifest.entries += @{
        relative_path   = $item.relative_path
        quarantine_path = "content/_quarantine/stage8-pilot/$rel"
        sha256          = $item.sha256
        status          = $item.status
        copied_at       = (Get-Date).ToString("o")
    }
    $action = if ($DryRun) { 'DRY' } else { 'COPY' }
    Write-Host "$action $($item.relative_path)"
}

if (-not $DryRun) {
    $manifestDir = Split-Path $manifestFull -Parent
    if (-not (Test-Path $manifestDir)) {
        New-Item -ItemType Directory -Path $manifestDir -Force | Out-Null
    }
    $manifest | ConvertTo-Json -Depth 6 | Set-Content -Path $manifestFull -Encoding UTF8
    $entryCount = $manifest.entries.Count
    Write-Host "Manifest: $manifestFull ($entryCount entries)"
} else {
    $entryCount = $manifest.entries.Count
    Write-Host "Dry run complete - $entryCount file(s) would be copied."
}
