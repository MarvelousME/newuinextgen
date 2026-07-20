# PayFast sandbox E2E — configure gateway + run ITN/checkout checks in Docker.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/run-payfast-e2e.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$docker = Join-Path $root 'docker'
$wpSkip = @('--skip-plugins=elementor,ameliabooking')

Write-Host 'NextGen PayFast E2E' -ForegroundColor Cyan
Write-Host ('=' * 50)

Set-Location $docker

$running = docker ps --format '{{.Names}}' 2>$null | Select-String 'nextgentutors-wordpress-1'
if (-not $running) {
    throw 'Docker WordPress container not running. Start with: cd docker; docker compose up -d'
}

Write-Host '=== Configure PayFast sandbox ===' -ForegroundColor Cyan
& (Join-Path $docker 'scripts\configure-payfast-sandbox.ps1')

Write-Host ''
Write-Host '=== Ensure WooCommerce active ===' -ForegroundColor Cyan
docker compose --profile setup run --rm --entrypoint wp wpcli plugin is-active woocommerce @($wpSkip + @('--path=/var/www/html', '--allow-root')) 2>&1 | Out-Host
if ($LASTEXITCODE -ne 0) {
    docker compose --profile setup run --rm --entrypoint wp wpcli plugin activate woocommerce @($wpSkip + @('--path=/var/www/html', '--allow-root'))
}

Write-Host ''
Write-Host '=== Run PayFast E2E (checkout + ITN) ===' -ForegroundColor Cyan
$e2e = '/var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/payfast-e2e-docker.php'
docker compose --profile setup run --rm --entrypoint wp wpcli eval-file $e2e @($wpSkip + @('--path=/var/www/html', '--allow-root'))
$code = $LASTEXITCODE

Write-Host ''
if ($code -eq 0) {
    Write-Host 'PayFast E2E: PASS' -ForegroundColor Green
} else {
    Write-Host "PayFast E2E: FAIL (exit $code)" -ForegroundColor Red
}
exit $code
