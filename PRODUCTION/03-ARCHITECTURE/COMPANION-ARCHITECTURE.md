# Companion architecture

Bootstrap: `nextgencompanion.php` → `NGC_Loader` → `NGC_Plugin` → `NGC_Plugin_Bootstrap` modules.

- Persistence: `NGC_Database::create_tables()` (dbDelta, version option `ngc_db_version`).
- CPTs: `NGC_Post_Types`.
- REST: `NGC_Rest` namespace `ngc/v1`; `NGC_Rest_Legacy_Alias` mirrors to `ngt/v1`.
- Shortcodes: `NGC_Shortcodes` plus marketplace, matching, checkout, builder, studio.
- Integrations boot with `is_available()` gates.
- Uninstall does **not** drop tables unless `NGC_DROP_TABLES`.