# Run Playwright workflow E2E against Docker WordPress.
# Usage:
#   powershell -File scripts/run-playwright.ps1
#   powershell -File scripts/run-playwright.ps1 -Headed

param(
    [switch]$Headed
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$e2e = Join-Path $root 'e2e'

if (-not $env:BASE_URL) {
    $env:BASE_URL = 'http://127.0.0.1:8899'
}

$mode = if ($Headed) { 'HEADED (visible Chrome)' } else { 'headless' }
Write-Host "NextGen Playwright E2E - $($env:BASE_URL) [$mode]"
Write-Host ('=' * 50)

Push-Location $e2e
try {
    if (-not (Test-Path 'node_modules')) {
        Write-Host 'Installing Playwright dependencies...'
        $env:PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD = '1'
        npm install
    }

    Write-Host 'Running workflow tests...'
    $args = @('playwright', 'test')
    if ($Headed) {
        $args += '--headed'
    }
    npx @args
    $code = $LASTEXITCODE

    node (Join-Path $root 'scripts\summarize-playwright-report.mjs')
    $sumCode = $LASTEXITCODE

    if ($code -ne 0) { exit $code }
    if ($sumCode -ne 0) { exit $sumCode }

    Write-Host ''
    Write-Host 'Reports:'
    Write-Host '  HTML:    e2e/reports/html/index.html'
    Write-Host '  Summary: e2e/reports/SUMMARY.md'
} finally {
    Pop-Location
}
