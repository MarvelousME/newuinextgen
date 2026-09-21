# Start WordPress + ecosystem platform (Control Center on :8790)
$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $here

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from .env.example — set ODOO_ADMIN_PASSWORD and ODOO_MASTER_PASSWORD before ecosystem overlay.'
}

Write-Host 'Starting WordPress + ecosystem platform overlay...'
docker compose -f docker-compose.yml -f docker-compose.ecosystem.yml up -d --build wordpress ecosystem-api
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

$wpPort = '8890'
$apiPort = '8790'
Get-Content '.env' | ForEach-Object {
    if ($_ -match '^\s*WP_PORT=(.+)$') { $wpPort = $Matches[1].Trim() }
    if ($_ -match '^\s*ECOSYSTEM_API_PORT=(.+)$') { $apiPort = $Matches[1].Trim() }
}

Write-Host ''
Write-Host 'Ready:'
Write-Host "  WordPress (kinetic theme):  http://localhost:$wpPort"
Write-Host "  Control Center (brand kit): http://localhost:$apiPort"
Write-Host "  WP Admin:                   http://localhost:$wpPort/wp-admin"
Write-Host ''
Write-Host 'Control Center API token: value of ECOSYSTEM_API_TOKEN in docker/.env (session prompt on first use).'
