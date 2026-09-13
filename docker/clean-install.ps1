# Clean-room install from PRODUCTION ZIPs (no monorepo mounts).
# Isolated Compose project: ngt-clean-install (does not touch docker/.env COMPOSE_PROJECT_NAME).
$ErrorActionPreference = 'Continue'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$prodPkgs = Join-Path $here '..\PRODUCTION\01-INSTALLABLE-PACKAGES'
Set-Location $here

if (-not (Get-ChildItem $prodPkgs -Filter 'NextGenTutors-Companion-v*.zip' -ErrorAction SilentlyContinue)) {
    throw "Missing PRODUCTION packages. Run scripts/build-production-release.ps1 first."
}

$compose = @('-p', 'ngt-clean-install', '-f', 'docker-compose.clean-install.yml')

Write-Host 'Starting clean-install stack (http://localhost:8891)...'
docker compose @compose down -v --remove-orphans
docker compose @compose up -d
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Host 'Waiting for WordPress (40s)...'
Start-Sleep -Seconds 40

Write-Host 'Installing ZIPs via WP-CLI...'
docker compose @compose --profile setup run --rm wpcli
$code = $LASTEXITCODE

$report = Join-Path $here '..\PRODUCTION\04-VALIDATION\INSTALLATION-TEST.md'
$stamp = (Get-Date).ToUniversalTime().ToString('o')
Add-Content -Path $report -Value "`n`n## Latest clean-install run`n- UTC: $stamp`n- WP-CLI exit: $code`n- URL: http://localhost:8891`n"

if ($code -ne 0) { throw 'Clean-install WP-CLI failed - do not label PRODUCTION PASS.' }

Write-Host ''
Write-Host 'Clean install WP-CLI succeeded.'
Write-Host '  WordPress: http://localhost:8891'
Write-Host '  Admin:     http://localhost:8891/wp-admin'
Write-Host 'Visit Home, Find a Tutor, Login with JS on/off before signing RELEASE-ACCEPTANCE.md.'
