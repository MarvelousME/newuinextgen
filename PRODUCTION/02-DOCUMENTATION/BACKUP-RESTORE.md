# Backup and restore

- **Database:** all customer records live in WordPress core tables plus `wp_ngc_*` (Companion). Theme switch does not drop them.
- **Deactivate Companion:** tables remain. Uninstall drops tables **only** if `NGC_DROP_TABLES` is defined true in Companion `uninstall.php`.
- **Files:** `wp-content/uploads`, theme, plugins.
- Restore: import SQL, restore uploads, re-upload the same PRODUCTION ZIPs, activate Companion before the theme.
- Docker developer backups: `docker/scripts/backup-wp.ps1` — not a substitute for host backups.