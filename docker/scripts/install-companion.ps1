# Mount + activate NextGenTutors-Companion in the Docker stack.
$ErrorActionPreference = 'Stop'
$docker = Split-Path -Parent $PSScriptRoot
Set-Location $docker

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from .env.example — set COMPANION_PLUGIN_PATH if needed.'
}

$companionPath = $null
Get-Content '.env' | ForEach-Object {
    if ($_ -match '^\s*COMPANION_PLUGIN_PATH=(.+)$') {
        $companionPath = $Matches[1].Trim().Trim('"')
    }
}
if (-not $companionPath) {
    $companionPath = 'C:/Users/marvi/Music/REVAMP/NextGenTutors-Companion'
}
$companionPath = $companionPath -replace '\\', '/'
if (-not (Test-Path $companionPath)) {
    throw "Companion plugin not found at: $companionPath`nSet COMPANION_PLUGIN_PATH in docker/.env"
}

Write-Host "Using companion plugin: $companionPath"
Write-Host 'Recreating WordPress container with plugin mount...'
docker compose up -d --force-recreate wordpress
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Start-Sleep -Seconds 8

Write-Host 'Installing + activating fleet plugins via WP-CLI...'
docker compose --profile setup run --rm --entrypoint sh wpcli /setup/install-plugins.sh
if ($LASTEXITCODE -ne 0) { throw 'install-companion failed' }

Write-Host ''
Write-Host 'NextGen Companion is active.'
Write-Host '  Forms, dashboards, smart matching, and tutor marketplace are enabled.'
Write-Host '  Verify: Appearance -> Sync Launch Pages (all ngc_* shortcodes registered)'
