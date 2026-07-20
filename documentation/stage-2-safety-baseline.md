# Stage 2 Safety Baseline Report

**Status:** PARTIAL / VERIFIED where stated  
**Destructive actions:** none  
**Quarantine:** empty; restore dry-run succeeds with "Quarantine is empty. Nothing to restore."  

## Completed

| Item | Status | Evidence |
|------|--------|----------|
| Database backup | VERIFIED | `audit-reports/baseline/db-backup-20260717-134858.sql` (57845695 bytes) |
| Restore instructions | VERIFIED | `audit-reports/baseline/DATABASE-BACKUP-RESTORE.md` |
| Quarantine manifest | VERIFIED | `audit-reports/quarantine/manifest.json` |
| Restore tool | VERIFIED | `scripts/restore-quarantined-files.php` lint OK + dry-run OK |
| Elementor/WPBakery meta scan | VERIFIED | `audit-reports/elementor-wpbakery-asset-scan.json` |
| Docker theme mount leak fix | VERIFIED | Companion/docker/zip no longer appear under theme path |
| HTTP smoke after mount fix | VERIFIED | /, /find-a-tutor/, /student-dashboard/, /login/ all HTTP 200 |

## Docker mount refactor

Before: `..` (entire monorepo) mounted as `wp-content/themes/nextgentutors-beyondinfinity`.

After: theme-only package mounted from `../NextGenTutors-BeyondInfinity` with explicit directory overlays for source folders because Docker Desktop on Windows does not follow NTFS junction contents inside Linux containers.

Validation:

- `inc/companion.php` exists in the container — VERIFIED
- `NextGenTutors-Companion` is not inside theme path — VERIFIED
- `docker` is not inside theme path — VERIFIED
- `magicui-main.zip` is not inside theme path — VERIFIED

## Elementor / WPBakery scan highlights

| Metric | Count |
|--------|------:|
| `_elementor_data` rows | 647 |
| `_elementor_page_settings` rows | 253 |
| WPBakery custom CSS rows | 0 |
| Agntix references in builder meta | 594 |

Policy: any builder-meta referenced asset remains **UNSAFE TO REMOVE** until postmeta references are rewritten or proven irrelevant.

## Known limitation

WP-CLI `docker compose run wpcli` recreates the WordPress service when compose detects changed mounts. This is operationally acceptable after the mount fix but should be documented for scripts.

## Gate result

Stage 2 safety baseline is sufficient to proceed to **Stage 5 UI-library foundation / first Magic UI conversions**, while destructive sanitisation remains BLOCKED until full metadata reference mapping + visual regression snapshots are complete.
