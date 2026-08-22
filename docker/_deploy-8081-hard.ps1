$ErrorActionPreference = 'Continue'
Write-Host '=== Reset Docker Desktop to free ghost ports (8081/8082/8787) ==='
Get-Process 'Docker Desktop','com.docker.backend' -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
wsl --shutdown
Start-Sleep -Seconds 12
$listen = netstat -ano | Select-String ':8081'
if ($listen) { Write-Host "Still listening:`n$listen" } else { Write-Host '8081 free on host' }

Start-Process "${env:ProgramFiles}\Docker\Docker\Docker Desktop.exe"
Write-Host 'Waiting for Docker daemon...'
$ready = $false
for ($i = 1; $i -le 48; $i++) {
  Start-Sleep -Seconds 5
  $out = (wsl -e bash -lc "docker info --format '{{.ServerVersion}}' 2>/dev/null").Trim()
  if ($out -match '^\d') {
    Write-Host "Docker ready: $out (attempt $i)"
    $ready = $true
    break
  }
  Write-Host "attempt $i ..."
}
if (-not $ready) { throw 'Docker daemon not ready' }
Start-Sleep -Seconds 8

Write-Host '=== Deploy stack on 8081 ==='
wsl -e bash /mnt/c/Users/marvi/Downloads/wetransfer_newuinextgen_2026-07-07_1929/newuinextgen/docker/deploy-8081.sh
if ($LASTEXITCODE -ne 0) { throw "deploy-8081.sh failed with $LASTEXITCODE" }

Write-Host '=== Verify ==='
try {
  $r = Invoke-WebRequest -Uri 'http://127.0.0.1:8081/' -UseBasicParsing -TimeoutSec 30
  Write-Host "WP HTTP $($r.StatusCode) bytes=$($r.Content.Length)"
} catch {
  Write-Host "WP probe: $($_.Exception.Message)"
  exit 1
}
