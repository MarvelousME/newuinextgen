# Database migrations

Companion uses versioned dbDelta (`ngc_db_version`, `ngc_platform_db_version`). Re-activation is safe. There is no destructive default uninstall.

3D Scroll Manager has its own schema version (`NGT3D_SCHEMA_VERSION`).

AI Integration uses `NGTAI_Migrator` on its own `wp_ngtai_*` tables (optional plugin).