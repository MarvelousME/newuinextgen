# Database restore (Stage 2)

Backup file: `db-backup-20260717-134858.sql`

```powershell
cd docker
Get-Content ..\audit-reports\baseline\db-backup-20260717-134858.sql -Raw |
  docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" wordpress'
```

Or:

```bash
docker compose exec -T db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" wordpress < ../audit-reports/baseline/db-backup-20260717-134858.sql
```
