# Install Hello Elementor (parent) and optionally activate the child theme.
#   powershell -ExecutionPolicy Bypass -File docker/scripts/install-themes.ps1

param(
    [switch]$SkipRecreate
)

$ErrorActionPreference = 'Stop'
$docker = Split-Path -Parent $PSScriptRoot
$themeDir = Join-Path $docker 'hello-elementor'
$zip = Join-Path $docker 'hello-elementor.zip'

if (-not (Test-Path (Join-Path $themeDir 'style.css'))) {
    Write-Host 'Downloading Hello Elementor theme...'
    if (-not (Test-Path $themeDir)) { New-Item -ItemType Directory -Path $themeDir | Out-Null }
    Invoke-WebRequest -Uri 'https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip' -OutFile $zip -UseBasicParsing -TimeoutSec 600
    $tmp = Join-Path $docker '_hello_extract'
    if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
    Expand-Archive -Path $zip -DestinationPath $tmp -Force
    $inner = Get-ChildItem $tmp -Directory | Select-Object -First 1
    if (-not $inner) { throw 'Hello Elementor zip extraction failed' }
    Get-ChildItem $inner.FullName | ForEach-Object {
        Copy-Item $_.FullName -Destination $themeDir -Recurse -Force
    }
    Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item $zip -Force -ErrorAction SilentlyContinue
    Write-Host 'Hello Elementor extracted to docker/hello-elementor'
}

if ($SkipRecreate) { return }

Set-Location $docker
Write-Host 'Recreating WordPress container (hello-elementor bind mount)...'
docker compose up -d --force-recreate wordpress
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Host 'Waiting for WordPress (10s)...'
Start-Sleep -Seconds 10

Write-Host 'Activating nextgentutors-beyondinfinity theme...'
docker compose --profile setup run --rm --entrypoint wp wpcli theme activate nextgentutors-beyondinfinity --path=/var/www/html --allow-root
if ($LASTEXITCODE -ne 0) { throw 'Theme activation failed' }

Write-Host 'Done. Active theme: NextGenTutors-BeyondInfinity (child of Hello Elementor).'
