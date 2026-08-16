# Start NextGen Tutors Docker stack (newuinextgen workspace)
$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $here

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from .env.example'
}

Write-Host 'Downloading Hello Elementor parent theme (if needed)...'
& (Join-Path $here 'scripts\install-themes.ps1') -SkipRecreate

Write-Host 'Starting containers...'
docker compose up -d
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Host 'Waiting for WordPress to initialize (25s)...'
Start-Sleep -Seconds 25

Write-Host 'Running WordPress setup (wpcli)...'
docker compose --profile setup run --rm wpcli
if ($LASTEXITCODE -ne 0) { throw 'Setup failed' }

Write-Host 'Applying theme defaults...'
docker compose --profile setup run --rm --entrypoint sh wpcli /setup/apply-defaults.sh
if ($LASTEXITCODE -ne 0) { Write-Host 'WARN: apply-defaults reported issues' }

$envFile = Join-Path $here '.env'
$wpPort = '8890'
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*WP_PORT=(.+)$') { $wpPort = $Matches[1].Trim() }
    }
}

Write-Host ''
Write-Host 'NextGen Tutors Docker is ready:'
Write-Host "  WordPress:  http://localhost:$wpPort"
Write-Host "  Admin:      http://localhost:$wpPort/wp-admin"
Write-Host '  phpMyAdmin: http://localhost:8082'
Write-Host '  User:       admin'
Write-Host '  Password:   NextGenAdmin!2026  (or value in .env)'
