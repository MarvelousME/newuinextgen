# Backup WordPress MySQL + uploads for local Docker stack.
param(
  [string]$ComposeFile = "docker/docker-compose.yml",
  [string]$OutRoot = "docker/backups"
)

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$dest = Join-Path $OutRoot "ngt-$stamp"
New-Item -ItemType Directory -Force -Path $dest | Out-Null

Write-Host "Backing up DB → $dest\db.sql.gz"
docker compose -f $ComposeFile exec -T db sh -c "mysqldump -uwordpress -pwordpress wordpress" | gzip > (Join-Path $dest "db.sql.gz")

Write-Host "Backing up uploads → $dest\uploads.tgz"
docker compose -f $ComposeFile exec -T wordpress sh -c "cd /var/www/html/wp-content && tar czf - uploads" > (Join-Path $dest "uploads.tgz")

Push-Location $dest
Get-FileHash -Algorithm SHA256 db.sql.gz, uploads.tgz | ForEach-Object { "$($_.Hash.ToLower())  $($_.Path | Split-Path -Leaf)" } | Set-Content MANIFEST.sha256
Pop-Location

Write-Host "Backup complete: $dest"
Write-Host "RPO note: schedule this hourly/daily via Task Scheduler or Coolify cron."
