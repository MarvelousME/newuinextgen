# Verify all four NextGenTutors packages — structure, PHP lint, and cross-version sync.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/verify-solution.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$packages = @(
    @{ Label = 'BeyondInfinity';    Path = 'NextGenTutors-BeyondInfinity';    Required = @('style.css', 'functions.php') },
    @{ Label = 'Companion';         Path = 'NextGenTutors-Companion';         Required = @('nextgencompanion.php', 'includes/class-ngc-plugin.php') },
    @{ Label = 'Html-Importer';     Path = 'NextGenTutors-Html-Importer';     Required = @('revamp-html-importer.php') },
    @{ Label = 'Plugin-Manager';    Path = 'NextGenTutors-Plugin-Manager';    Required = @('NextGenTutors-Plugin-Manager.php') }
)

$retired = @(
    'beyondinfinity',
    'nextgencompanion',
    'revamp-html-importer',
    'docker\wp-content\themes\beyondinfinity'
)

$errors = 0

Write-Host 'NextGenTutors solution verification'
Write-Host ('=' * 50)

Write-Host "`n[1/5] Canonical packages"
foreach ($pkg in $packages) {
    $full = Join-Path $root $pkg.Path
    if (-not (Test-Path $full)) {
        Write-Host "  FAIL  $($pkg.Label): missing folder $($pkg.Path)" -ForegroundColor Red
        $errors++
        continue
    }
    $missing = @()
    foreach ($rel in $pkg.Required) {
        if (-not (Test-Path (Join-Path $full $rel))) { $missing += $rel }
    }
    if ($missing.Count) {
        Write-Host "  FAIL  $($pkg.Label): missing $($missing -join ', ')" -ForegroundColor Red
        $errors++
    } else {
        Write-Host "  OK    $($pkg.Label) ($($pkg.Path))"
    }
}

Write-Host "`n[2/5] Retired paths (must not be active source)"
foreach ($rel in $retired) {
    $full = Join-Path $root $rel
    if (Test-Path $full) {
        Write-Host "  WARN  Retired path still on disk: $rel (safe to delete)" -ForegroundColor Yellow
    }
}

Write-Host "`n[3/5] Docker compose mounts"
$compose = Join-Path $root 'docker\docker-compose.yml'
if (-not (Test-Path $compose)) {
    Write-Host '  FAIL  docker/docker-compose.yml missing' -ForegroundColor Red
    $errors++
} else {
    $yml = Get-Content $compose -Raw
    $expected = @(
        'NextGenTutors-BeyondInfinity',
        'NextGenTutors-Companion',
        'NextGenTutors-Html-Importer',
        'NextGenTutors-Plugin-Manager'
    )
    foreach ($mount in $expected) {
        if ($yml -notmatch [regex]::Escape($mount)) {
            Write-Host "  FAIL  compose missing mount: $mount" -ForegroundColor Red
            $errors++
        } else {
            Write-Host "  OK    mount $mount"
        }
    }
    if ($yml -match 'revamp-html-importer' -or $yml -match '\./wp-content/themes/beyondinfinity') {
        Write-Host '  FAIL  compose still references retired paths' -ForegroundColor Red
        $errors++
    }
}

Write-Host "`n[4/5] Package validators"
$validators = @(
    (Join-Path $root 'NextGenTutors-Companion\scripts\validate.php'),
    (Join-Path $root 'NextGenTutors-Plugin-Manager\scripts\validate.php'),
    (Join-Path $root 'NextGenTutors-Html-Importer\scripts\validate.php')
)
foreach ($script in $validators) {
    if (-not (Test-Path $script)) {
        Write-Host "  FAIL  Missing $script" -ForegroundColor Red
        $errors++
        continue
    }
    php $script
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  FAIL  $script exited $LASTEXITCODE" -ForegroundColor Red
        $errors++
    }
}

Write-Host "`n[5/5] Theme / companion version sync"
$verScript = Join-Path $root 'NextGenTutors-Companion\scripts\verify-versions.php'
if (Test-Path $verScript) {
    php $verScript
    if ($LASTEXITCODE -ne 0) { $errors++ }
}

Write-Host "`n$('=' * 50)"
if ($errors -gt 0) {
    Write-Host "FAILED with $errors error(s)" -ForegroundColor Red
    exit 1
}
Write-Host 'OK - all four packages verified' -ForegroundColor Green
exit 0
