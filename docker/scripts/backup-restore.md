# Docker backup / restore (Platform Kernel)

## Targets

- **RPO:** ≤ 24h with daily dump; ≤ 15m if cron dump hourly
- **RTO:** ≤ 60m for single-site Coolify/Docker WordPress volume restore

## Backup

```powershell
# From repo root
./docker/scripts/backup-wp.ps1
```

Creates `docker/backups/ngt-YYYYMMDD-HHMMSS/` with:

- `db.sql.gz` — MySQL dump of WordPress DB
- `uploads.tgz` — `wp-content/uploads` (includes `ngc-worm/`)
- `MANIFEST.sha256` — checksums

## Restore

```powershell
./docker/scripts/restore-wp.ps1 -BackupDir docker/backups/ngt-YYYYMMDD-HHMMSS
```

## Health check

```bash
curl -s http://localhost:8900/wp-json/ngc/v1/platform/queue/stats
wp ngc audit verify
wp ngc queue stats
```

## Notes

- Stop write traffic before restore when possible.
- After restore, run `wp ngc queue work` once to drain delayed jobs.
- WORM exports under `uploads/ngc-worm/` are evidence — retain per legal hold.
