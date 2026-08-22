# Install registry plugins from local zips and configure NextGenTutors stack.
$ErrorActionPreference = "Stop"
$composeDir = Split-Path $PSScriptRoot -Parent

Push-Location $composeDir
try {
    $running = docker compose ps -q wordpress 2>$null | Select-Object -First 1
    if (-not $running) {
        Write-Host "WordPress container not running. Start with: cd docker; docker compose up -d"
        exit 1
    }

    Write-Host "Installing registry plugins via WP-CLI..."

    # Recover broken Amelia state (active but missing DB tables) before full WP bootstrap.
    docker compose --profile setup run --rm --entrypoint wp wpcli `
        plugin deactivate ameliabooking --path=/var/www/html --allow-root --skip-plugins 2>$null

    docker compose --profile setup run --rm --entrypoint wp wpcli `
        eval-file /var/www/html/wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php `
        --path=/var/www/html --allow-root

    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    $wpPort = '8890'
    if (Test-Path (Join-Path $composeDir '.env')) {
        Get-Content (Join-Path $composeDir '.env') | ForEach-Object {
            if ($_ -match '^\s*WP_PORT=(.+)$') { $wpPort = $Matches[1].Trim() }
        }
    }
    Write-Host "`nDone. Site: http://localhost:$wpPort"
}
finally {
    Pop-Location
}
