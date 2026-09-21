# Clean-room install from PRODUCTION ZIPs (no monorepo mounts).
# Isolated Compose project: ngt-clean-install (does not touch docker/.env COMPOSE_PROJECT_NAME).
# Prefer host-network compose when bridge networking is blocked (nested/cloud VMs).
$ErrorActionPreference = 'Continue'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$prodPkgs = Join-Path $here '..\PRODUCTION\01-INSTALLABLE-PACKAGES'
Set-Location $here

if (-not (Get-ChildItem $prodPkgs -Filter 'NextGenTutors-Companion-v*.zip' -ErrorAction SilentlyContinue)) {
    throw "Missing PRODUCTION packages. Run: python3 scripts/build-production-release.py"
}

$composeFile = if (Test-Path (Join-Path $here 'docker-compose.clean-install.host.yml')) {
    'docker-compose.clean-install.host.yml'
} else {
    'docker-compose.clean-install.yml'
}
$compose = @('-p', 'ngt-clean-install', '-f', $composeFile)

Write-Host "Starting clean-install stack ($composeFile) at http://127.0.0.1:8999..."
docker compose @compose down -v --remove-orphans
docker compose @compose up -d
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Host 'Waiting for WordPress (45s)...'
Start-Sleep -Seconds 45

Write-Host 'Installing ZIPs via WP-CLI...'
docker compose @compose --profile setup run --rm wpcli
$code = $LASTEXITCODE

$report = Join-Path $here '..\PRODUCTION\04-VALIDATION\INSTALLATION-TEST.md'
$stamp = (Get-Date).ToUniversalTime().ToString('o')
Add-Content -Path $report -Value "`n`n## Latest clean-install run`n- UTC: $stamp`n- WP-CLI exit: $code`n- URL: http://127.0.0.1:8999`n- Compose: $composeFile`n"

if ($code -ne 0) { throw 'Clean-install WP-CLI failed - do not label PRODUCTION PASS.' }

Write-Host ''
Write-Host 'Clean install WP-CLI succeeded.'
Write-Host '  WordPress: http://127.0.0.1:8999'
Write-Host '  Admin:     http://127.0.0.1:8999/wp-admin'
Write-Host 'Visit Home, Find a Tutor, Login with JS on/off before signing RELEASE-ACCEPTANCE.md.'
