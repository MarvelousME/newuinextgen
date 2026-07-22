# Mount + activate NextGen fleet plugins (Companion, Plugin Manager, HTML Importer).
$ErrorActionPreference = 'Stop'
$docker = Split-Path -Parent $PSScriptRoot
Set-Location $docker

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host 'Created .env from .env.example'
}

$repoRoot = (Resolve-Path '..').Path -replace '\\', '/'
$companionPath = "$repoRoot/NextGenTutors-Companion"

Get-Content '.env' | ForEach-Object {
    if ($_ -match '^\s*COMPANION_PLUGIN_PATH=(.+)$') {
        $custom = $Matches[1].Trim().Trim('"') -replace '\\', '/'
        if ($custom -and (Test-Path $custom)) {
            $companionPath = $custom
        }
    }
}

foreach ($path in @(
    $companionPath,
    "$repoRoot/NextGenTutors-AI-Integration",
    "$repoRoot/NextGenTutors-Plugin-Manager",
    "$repoRoot/NextGenTutors-Html-Importer"
)) {
    if (-not (Test-Path $path)) {
        throw "Required plugin folder not found: $path"
    }
}

Write-Host "Companion: $companionPath"
Write-Host 'Recreating WordPress container with plugin mounts...'
docker compose up -d --force-recreate wordpress
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Start-Sleep -Seconds 8

Write-Host 'Installing + activating fleet plugins via WP-CLI...'
docker compose --profile setup run --rm --entrypoint sh wpcli /setup/install-plugins.sh
if ($LASTEXITCODE -ne 0) { throw 'install-plugins failed' }

Write-Host ''
Write-Host 'NextGen fleet plugins are active:'
Write-Host '  - NextGenTutors-Companion (forms, dashboards, marketplace)'
Write-Host '  - NextGenTutors-AI-Integration (signed agents-api outbox bridge)'
Write-Host '  - NextGenTutors-Plugin-Manager (dependency health + installs)'
Write-Host '  - NextGenTutors-Html-Importer (webpages-content import)'
