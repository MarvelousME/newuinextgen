# Upgrade guide

1. Backup DB + files.
2. Replace plugin/theme ZIPs via WP upload (or overwrite folders keeping `wp-content/uploads`).
3. Activate Companion first so `NGC_Database::create_tables()` / dbDelta can run (idempotent).
4. Visit wp-admin once, then flush permalinks.
5. Do not delete `wp_ngc_*` tables between versions.
6. Html Importer is not an upgrade tool.
7. Version headers in this train: theme 1.9.29, Companion 1.9.19. Ignore older `release/` ZIPs tagged 1.9.5 / 1.9.17 / 1.9.34.