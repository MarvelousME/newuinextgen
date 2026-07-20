# Deployment Documentation

## Environments

| Environment | Purpose | Required Components | Status |
|---|---|---|---|
| Local | development | WP + theme + companion | VERIFIED |
| Development | integration testing | above + sample data | PARTIAL |
| Testing/UAT | role/workflow validation | above + external plugins | PARTIAL |
| Staging | production rehearsal | full dependency stack | PARTIAL |
| Production | live operations | full stack + backups/monitoring | NOT VERIFIED |

## Deployment Topology

- Web runtime: PHP WordPress hosting/VPS.
- DB runtime: MySQL/MariaDB.
- Deploy artifacts:
  - `beyondinfinity` theme
  - `nextgencompanion` plugin
- Optional plugin dependencies:
  - WooCommerce
  - FluentCRM
  - Amelia
  - MasterStudy

## Deployment Procedure

1. Deploy theme to `wp-content/themes/beyondinfinity`.
2. Deploy plugin to `wp-content/plugins/nextgencompanion`.
3. Activate plugin and theme in WP admin.
4. Confirm schema migration/roles via Health screens.
5. Configure integration keys/options.
6. Run verification checklist and smoke tests.

## Migrations and Rollback

- Schema changes run via `NGC_Database::create_tables()`.
- Rollback strategy:
  - restore DB backup
  - redeploy previous plugin/theme version
  - invalidate caches and rerun verification

## Backups and Recovery

- Daily DB backup recommended.
- Pre-deploy snapshot mandatory for staging/production.
- Recovery validated through health/verification screens post-restore.

## Monitoring

- Application-level: workflow logs, admin health checks, audit logs.
- Infrastructure-level: host monitoring not defined in repository (`NOT VERIFIED`).

## Docker/VPS

- VPS deployment is feasible and documented conceptually.
- Repository contains no canonical Docker stack files for production (`NOT VERIFIED`).

