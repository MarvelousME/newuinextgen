# Verify all five NextGenTutors packages — structure, PHP lint, tests, and release safety.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/verify-solution.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$packages = @(
    @{ Label = 'BeyondInfinity'; Path = 'NextGenTutors-BeyondInfinity'; Required = @('style.css', 'functions.php') },
    @{ Label = 'Companion'; Path = 'NextGenTutors-Companion'; Required = @('nextgencompanion.php', 'includes/class-ngc-plugin.php') },
    @{ Label = 'AI-Integration'; Path = 'NextGenTutors-AI-Integration'; Required = @(
        'nextgentutors-ai-integration.php',
        'uninstall.php',
        'config/event-schemas.php',
        'config/payload-allowlists.php',
        'config/capabilities.php',
        'includes/class-ngtai-signature.php',
        'includes/class-ngtai-nonce-store.php',
        'includes/class-ngtai-migrator.php',
        'includes/class-ngtai-policy-gate.php',
        'includes/class-ngtai-delivery-repository.php',
        'includes/class-ngtai-callback-controller.php',
        'includes/rest/class-ngtai-rest-callbacks.php',
        'includes/admin/class-ngtai-approvals-page.php',
        'tests/run.php',
        'docs/SECURITY.md'
    ) },
    @{ Label = 'Html-Importer'; Path = 'NextGenTutors-Html-Importer'; Required = @('revamp-html-importer.php') },
    @{ Label = 'Plugin-Manager'; Path = 'NextGenTutors-Plugin-Manager'; Required = @('NextGenTutors-Plugin-Manager.php') }
)

$retired = @(
    'beyondinfinity',
    'nextgencompanion',
    'revamp-html-importer',
    'docker\wp-content\themes\beyondinfinity'
)

$errors = 0

function Add-Failure {
    param([string]$Message)
    Write-Host "  FAIL  $Message" -ForegroundColor Red
    $script:errors++
}

Write-Host 'NextGenTutors solution verification'
Write-Host ('=' * 64)

Write-Host "`n[1/7] Five canonical packages and bootstraps"
foreach ($pkg in $packages) {
    $full = Join-Path $root $pkg.Path
    if (-not (Test-Path $full)) {
        Add-Failure "$($pkg.Label): missing folder $($pkg.Path)"
        continue
    }
    $missing = @()
    foreach ($rel in $pkg.Required) {
        if (-not (Test-Path (Join-Path $full $rel))) { $missing += $rel }
    }
    if ($missing.Count) {
        Add-Failure "$($pkg.Label): missing $($missing -join ', ')"
    } else {
        Write-Host "  OK    $($pkg.Label) ($($pkg.Path))"
    }
}

Write-Host "`n[2/7] AI-Integration contract surface"
$aiRoot = Join-Path $root 'NextGenTutors-AI-Integration'
$aiBootstrap = Join-Path $aiRoot 'nextgentutors-ai-integration.php'
if (Test-Path $aiBootstrap) {
    $bootstrapSource = Get-Content $aiBootstrap -Raw
    if ($bootstrapSource -notmatch "define\s*\(\s*['""]NGTAI_VERSION['""]\s*,") {
        Add-Failure 'AI-Integration bootstrap does not define NGTAI_VERSION'
    } else {
        Write-Host '  OK    NGTAI_VERSION declared'
    }
}

$schemaFile = Join-Path $aiRoot 'config\event-schemas.php'
if (-not (Test-Path $schemaFile)) {
    Add-Failure 'AI-Integration config/event-schemas.php missing'
}

$restRoot = Join-Path $aiRoot 'includes\rest'
$routeFiles = @()
if (Test-Path $restRoot) {
    $routeFiles = @(Get-ChildItem -Path $restRoot -Filter '*.php' -File -Recurse | Where-Object {
        (Get-Content $_.FullName -Raw) -match 'register_rest_route\s*\('
    })
}
if ($routeFiles.Count -lt 1) {
    Add-Failure 'AI-Integration has no REST route source under includes/rest'
} else {
    Write-Host "  OK    REST route source ($($routeFiles.Count) file(s))"
}

Write-Host "`n[3/7] Retired paths (must not be active source)"
foreach ($rel in $retired) {
    $full = Join-Path $root $rel
    if (Test-Path $full) {
        Write-Host "  WARN  Retired path still on disk: $rel (safe to delete)" -ForegroundColor Yellow
    }
}

Write-Host "`n[4/7] Docker compose mounts"
$compose = Join-Path $root 'docker\docker-compose.yml'
if (-not (Test-Path $compose)) {
    Add-Failure 'docker/docker-compose.yml missing'
} else {
    $yml = Get-Content $compose -Raw
    $expected = @(
        'NextGenTutors-BeyondInfinity',
        'NextGenTutors-Companion',
        'NextGenTutors-AI-Integration',
        'NextGenTutors-Html-Importer',
        'NextGenTutors-Plugin-Manager'
    )
    foreach ($mount in $expected) {
        if ($yml -notmatch [regex]::Escape($mount)) {
            Add-Failure "compose missing mount: $mount"
        } else {
            Write-Host "  OK    mount $mount"
        }
    }
    if ($yml -match 'revamp-html-importer' -or $yml -match '\./wp-content/themes/beyondinfinity') {
        Write-Host '  FAIL  compose still references retired paths' -ForegroundColor Red
        $errors++
    }
}

Write-Host "`n[5/7] Recursive PHP syntax"
foreach ($pkg in $packages) {
    $packagePath = Join-Path $root $pkg.Path
    if (-not (Test-Path $packagePath)) { continue }
    $phpFiles = @(Get-ChildItem -Path $packagePath -Filter '*.php' -File -Recurse | Where-Object {
        $_.FullName -notmatch '[\\/](vendor|node_modules|\.git)[\\/]'
    })
    foreach ($file in $phpFiles) {
        $lintOutput = & php -l $file.FullName 2>&1
        if ($LASTEXITCODE -ne 0) {
            Add-Failure "PHP syntax: $($file.FullName.Substring($root.Length + 1)): $($lintOutput -join ' ')"
        }
    }
    Write-Host "  OK    $($pkg.Label): linted $($phpFiles.Count) PHP file(s)"
}

Write-Host "`n[6/7] Package validators and tests"
$validators = @(
    (Join-Path $root 'NextGenTutors-Companion\scripts\validate.php'),
    (Join-Path $root 'NextGenTutors-Plugin-Manager\scripts\validate.php'),
    (Join-Path $root 'NextGenTutors-Html-Importer\scripts\validate.php')
)
foreach ($script in $validators) {
    if (-not (Test-Path $script)) {
        Add-Failure "Missing $script"
        continue
    }
    php $script
    if ($LASTEXITCODE -ne 0) {
        Add-Failure "$script exited $LASTEXITCODE"
    }
}

$testRunners = @(
    (Join-Path $root 'NextGenTutors-Companion\tests\run.php'),
    (Join-Path $root 'NextGenTutors-AI-Integration\tests\run.php'),
    (Join-Path $root 'NextGenTutors-Html-Importer\tests\run.php'),
    (Join-Path $root 'NextGenTutors-Plugin-Manager\tests\run.php')
)
foreach ($test in $testRunners) {
    if (-not (Test-Path $test)) {
        Add-Failure "Missing test runner $test"
        continue
    }
    & php $test
    if ($LASTEXITCODE -ne 0) { Add-Failure "$test exited $LASTEXITCODE" }
}

$verScript = Join-Path $root 'NextGenTutors-Companion\scripts\verify-versions.php'
if (Test-Path $verScript) {
    & php $verScript
    if ($LASTEXITCODE -ne 0) { Add-Failure 'Theme / Companion version sync failed' }
}

Write-Host "`n[7/7] AI-Integration likely-secret scan"
if (Test-Path $aiRoot) {
    $secretPattern = '(?i)(api[_-]?key|client[_-]?secret|access[_-]?token|private[_-]?key|password)\s*[''"]?\s*(?:=>|=|:)\s*[''"]([^''"]{8,})[''"]'
    $placeholderPattern = '(?i)(example|placeholder|changeme|replace[_ -]?me|your[_ -]?(key|secret|token)|test[_ -]?only|dummy|not-a-real|<[^>]+>|\$\{[^}]+\})'
    $scanFiles = @(Get-ChildItem -Path $aiRoot -File -Recurse | Where-Object {
        $_.Extension -in @('.php', '.json', '.yml', '.yaml', '.env') -and
        $_.FullName -notmatch '[\\/](vendor|node_modules|\.git|tests)[\\/]'
    })
    foreach ($file in $scanFiles) {
        $lineNumber = 0
        foreach ($line in Get-Content $file.FullName) {
            $lineNumber++
            if ($line -match $secretPattern -and $Matches[2] -notmatch $placeholderPattern) {
                Add-Failure "likely committed secret in $($file.FullName.Substring($root.Length + 1)):$lineNumber"
            }
        }
    }
    Write-Host "  OK    scanned $($scanFiles.Count) non-document source/config file(s)"
}

Write-Host "`n$('=' * 64)"
if ($errors -gt 0) {
    Write-Host "FAILED with $errors error(s)" -ForegroundColor Red
    exit 1
}
Write-Host 'OK - all five packages verified' -ForegroundColor Green
exit 0
