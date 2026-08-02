# Run Playwright workflow E2E against Docker WordPress.
# Usage:
#   powershell -File scripts/run-playwright.ps1
#   powershell -File scripts/run-playwright.ps1 -Headed
#   powershell -File scripts/run-playwright.ps1 -System -Headed
#   powershell -File scripts/run-playwright.ps1 -Phase14 -Headed

param(
    [switch]$Headed,
    [switch]$System,
    [switch]$Phase14
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$e2e = Join-Path $root 'e2e'

if (-not $env:BASE_URL) {
    $env:BASE_URL = 'http://localhost:8900'
}

$mode = if ($Headed) { 'HEADED (visible Chrome)' } else { 'headless' }
$suite = if ($System) { 'system-verification' } elseif ($Phase14) { 'phase14-demo-walkthrough' } else { 'all workflows' }
Write-Host "NextGen Playwright E2E - $($env:BASE_URL) [$mode] [$suite]"
Write-Host ('=' * 50)

Push-Location $e2e
try {
    if (-not (Test-Path 'node_modules')) {
        Write-Host 'Installing Playwright dependencies...'
        $env:PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD = '1'
        npm install
    }

    Write-Host 'Running workflow tests...'
    $pwArgs = @('playwright', 'test', '--workers=1')
    if ($Headed) {
        $pwArgs += '--headed'
    }
    if ($System) {
        $pwArgs += 'workflows/system-verification.spec.ts'
    } elseif ($Phase14) {
        $pwArgs += 'workflows/phase14-demo-walkthrough.spec.ts'
    }
    npx @pwArgs
    $code = $LASTEXITCODE

    node (Join-Path $root 'scripts\summarize-playwright-report.mjs')
    $sumCode = $LASTEXITCODE

    if ($code -ne 0) { exit $code }
    if ($sumCode -ne 0) { exit $sumCode }

    Write-Host ''
    Write-Host 'Reports:'
    Write-Host '  HTML:    e2e/reports/html/index.html'
    Write-Host '  Summary: e2e/reports/SUMMARY.md'
    if ($System) {
        Write-Host '  Evidence: .agent-audit/evidence/runtime/system-verification-latest.json'
    }
} finally {
    Pop-Location
}
