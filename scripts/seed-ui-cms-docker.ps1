# Seed homepage CMS research copy inside Docker WordPress
param(
    [switch]$Force
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$container = (docker ps --format '{{.Names}}' | Select-String -Pattern 'wordpress' | Select-Object -First 1).ToString().Trim()
if (-not $container) {
    Write-Error 'No running WordPress container found. Start docker stack first: cd docker; .\start.ps1'
}

$forceFlag = if ($Force) { ' --force' } else { '' }
Write-Host "Seeding CMS via container: $container"
docker exec $container php /var/www/html/wp-cli.phar ngc ui_seed_cms$forceFlag --allow-root
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "Verifying UI library..."
docker exec $container php /var/www/html/wp-cli.phar ngc ui_verify --allow-root
exit $LASTEXITCODE
