# Reset site to theme defaults (run while Docker stack is up).
$ErrorActionPreference = 'Stop'
$docker = Split-Path -Parent $PSScriptRoot
Set-Location $docker

Write-Host 'Applying theme defaults via WP-CLI...'
docker compose --profile setup run --rm --entrypoint sh wpcli /setup/apply-defaults.sh
if ($LASTEXITCODE -ne 0) { throw 'apply-defaults failed' }

Write-Host ''
Write-Host 'Site loaded with defaults:'
$wpPort = '8890'
$envFile = Join-Path $docker '.env'
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*WP_PORT=(.+)$') { $wpPort = $Matches[1].Trim() }
    }
}
Write-Host "  http://localhost:$wpPort"
