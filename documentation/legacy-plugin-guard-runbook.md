# Legacy Plugin Guard — Operations Runbook

**Status:** VERIFIED (single-site)  
**Class:** `NGC_Legacy_Plugin_Guard`  
**ADR:** [ADR-001](./adr/ADR-001-legacy-plugin-deny.md)

## Purpose

Prevent co-activation of archived `content-enhancement/` plugins that collide with Companion on:

- REST namespace `ngt/v1`
- Database tables `ngt_*` vs canonical `ngc_*`

## Denied packages (summary)

| Package | Typical path |
|---------|--------------|
| NextGen Tutors Core | `nextgen-tutors-core/nextgen-tutors-core.php` |
| NextGen Tutors (legacy) | `nextgen-tutors-plugin/nextgen-tutors.php` |
| NextGen Tutors Importer | `nextgen-tutors-importer/nextgen-tutors-importer.php` |

**Allowed:** `NextGenTutors-Companion`, `NextGenTutors-Plugin-Manager`, standard third-party plugins.

## Matching rules (`is_denied()`)

1. Exact basename match against deny list
2. Folder prefix (`nextgen-tutors-core/*`, etc.)
3. Plugin header **Text Domain** (`nextgen-tutors`, `nextgen-tutors-core`, …)
4. Exact **Plugin Name** (`NextGen Tutors Core`, `NextGen Tutors Importer`)
5. Name `NextGen Tutors` when TextDomain ≠ `nextgencompanion`

Extend list: `add_filter( 'ngc_denied_legacy_plugins', … )`

## Enforcement layers

| Layer | When | Action |
|-------|------|--------|
| `pre_update_option_active_plugins` | Plugin activate | Strip denied paths before save |
| `admin_init` (priority 1) | Admin load | Force-deactivate if still active |
| `plugin_action_links` | Plugins screen | Remove Activate; show blocked label |
| `NGCPM_Discovery::activate_plugin_file` | Plugin Manager | Return error via `is_denied()` |

## Logging

Blocked events write to `NGC_System_Log`:

- **level:** `warning`
- **source:** `legacy_plugin_guard`
- **channel:** `security`
- **context:** `{ plugins: [...], forced: bool }`

Fallback: `error_log` if system log table unavailable.

## Admin notice

After a block, a one-time `notice-error` shows denied plugin paths (wrapped with `wp_kses_post`).

## Verification commands

```powershell
cd docker

# Deny legacy core
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval `
  "echo NGC_Legacy_Plugin_Guard::is_denied('nextgen-tutors-core/nextgen-tutors-core.php') ? 'DENY_OK' : 'FAIL';" --allow-root

# Allow Companion
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval `
  "echo NGC_Legacy_Plugin_Guard::is_denied('NextGenTutors-Companion/nextgencompanion.php') ? 'FAIL' : 'ALLOW_OK';" --allow-root
```

## Unit tests

```bash
php NextGenTutors-Companion/tests/run.php
```

Guard matrix: 5 assertions in `run.php` (basename, folder, companion allow, unrelated allow).

## Out of scope (this deployment)

- Multisite `active_sitewide_plugins` filter
- mu-plugin early deny (Companion guard sufficient for single-site)

## Incident response

1. Check **NextGen → System Log** for `legacy_plugin_guard` entries
2. Confirm plugin not in `wp_options.active_plugins`
3. Review `content-enhancement/README.md` — **do not activate** archived zips
4. If false positive: open issue with plugin basename, Name, TextDomain headers

## Related audit artifacts

- `content-enhancement/README.md`
- `audit-reports/content-enhancement/FINAL-REPORT.md`
- `audit-reports/content-enhancement/consolidation-decisions.json`
