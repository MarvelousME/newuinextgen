#!/usr/bin/env pwsh
<#
.SYNOPSIS
  Export a Stage 2 MySQL dump from the local Docker WordPress stack.
#>
param(
  [string]$ComposeFile = (Join-Path $PSScriptRoot '..\docker\docker-compose.yml'),
  [string]$OutDir = (Join-Path $PSScriptRoot '..\audit-reports\baseline')
)

$ErrorActionPreference = 'Stop'
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$outSql = Join-Path $OutDir "db-backup-$stamp.sql"

Push-Location (Split-Path $ComposeFile)
try {
  docker compose -f $ComposeFile exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers wordpress' |
    Set-Content -Path $outSql -Encoding UTF8
} finally {
  Pop-Location
}

if (-not (Test-Path $outSql) -or (Get-Item $outSql).Length -lt 1000) {
  Write-Error "Backup failed or too small: $outSql"
}
Write-Host "WROTE $outSql size=$((Get-Item $outSql).Length)"

@"
# Database restore (Stage 2)

Backup file: ``$([IO.Path]::GetFileName($outSql))``

``````powershell
cd docker
Get-Content ..\audit-reports\baseline\$([IO.Path]::GetFileName($outSql)) -Raw |
  docker compose exec -T db sh -c 'mysql -uroot -p"`$MYSQL_ROOT_PASSWORD" wordpress'
``````

Or:

``````bash
docker compose exec -T db mysql -uroot -p"`$MYSQL_ROOT_PASSWORD" wordpress < ../audit-reports/baseline/$([IO.Path]::GetFileName($outSql))
``````
"@ | Set-Content (Join-Path $OutDir 'DATABASE-BACKUP-RESTORE.md') -Encoding UTF8
