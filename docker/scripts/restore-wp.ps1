# Restore WordPress MySQL + uploads from backup-wp.ps1 output.
param(
  [Parameter(Mandatory = $true)][string]$BackupDir,
  [string]$ComposeFile = "docker/docker-compose.yml"
)

$ErrorActionPreference = "Stop"
if (-not (Test-Path (Join-Path $BackupDir "db.sql.gz"))) {
  throw "Missing db.sql.gz in $BackupDir"
}

Write-Host "Restoring DB from $BackupDir\db.sql.gz"
Get-Content (Join-Path $BackupDir "db.sql.gz") -AsByteStream | docker compose -f $ComposeFile exec -T db sh -c "gunzip | mysql -uwordpress -pwordpress wordpress"

if (Test-Path (Join-Path $BackupDir "uploads.tgz")) {
  Write-Host "Restoring uploads"
  Get-Content (Join-Path $BackupDir "uploads.tgz") -AsByteStream | docker compose -f $ComposeFile exec -T wordpress sh -c "cd /var/www/html/wp-content && tar xzf -"
}

Write-Host "Restore complete. Run: wp ngc audit verify && wp ngc queue work"
