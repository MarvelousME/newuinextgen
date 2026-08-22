$ErrorActionPreference = 'Continue'
Write-Host 'Hard reset: stop Docker + WSL shutdown'
Get-Process 'Docker Desktop','com.docker.backend','com.docker.service' -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
wsl --shutdown
Start-Sleep -Seconds 12
$listen = netstat -ano | Select-String ':8890'
if ($listen) { Write-Host $listen } else { Write-Host '8890 free on host' }

Start-Process "${env:ProgramFiles}\Docker\Docker\Docker Desktop.exe"
Write-Host 'Waiting for fresh Docker...'
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
if (-not $ready) { throw 'Docker not ready' }
Start-Sleep -Seconds 8

$script = '/mnt/c/Users/marvi/Downloads/wetransfer_newuinextgen_2026-07-07_1929/newuinextgen/docker/_restart-8890.sh'
wsl -e bash $script
Write-Host "compose exit=$LASTEXITCODE"

try {
  $r = Invoke-WebRequest -Uri 'http://127.0.0.1:8890/' -UseBasicParsing -TimeoutSec 30
  Write-Host ("WP status {0} bytes={1}" -f $r.StatusCode, $r.Content.Length)
  exit 0
} catch {
  Write-Host ("WP probe fail: {0}" -f $_.Exception.Message)
  exit 1
}
