# ADR-001: Legacy plugin deny-list

## Status

Accepted (2026-07-20)

## Context

The monorepo ships archived `content-enhancement/` packages (Core, Plugin, Importer) that register conflicting `ngt/v1` REST routes and `ngt_*` database tables. NextGenTutors-Companion owns canonical domain logic under `ngc_*` and exposes a legacy REST alias to `ngt/v1`. Dual activation causes REST/DB collisions and data corruption risk.

## Decision

1. **Do not merge** Core/Plugin PHP into Companion or activate archived zips in production.
2. **Runtime guard** in Companion (`NGC_Legacy_Plugin_Guard`) strips denied plugins from `active_plugins`, force-deactivates on admin_init, and removes activate links.
3. **Plugin Manager** activation path delegates to `NGC_Legacy_Plugin_Guard::is_denied()` — no duplicate folder deny loops.
4. **Single-site only** for this deployment; multisite sitewide guards are out of scope until multisite is a target.

## Deny matching rules

| Rule | Example |
|------|---------|
| Exact basename | `nextgen-tutors-core/nextgen-tutors-core.php` |
| Folder prefix | `nextgen-tutors-importer/*` |
| Text Domain header | `nextgen-tutors-core` |
| Exact Plugin Name | `NextGen Tutors Core`, `NextGen Tutors Importer` |
| Legacy name + non-Companion domain | Name `NextGen Tutors` with TextDomain ≠ `nextgencompanion` |

**Allowed:** Companion stack (`nextgentutors-companion/*`, `nextgencompanion.php`, Plugin Manager).

## Dual UI library coexistence

`NGC_NGT_UI_Bridge` option `ngc_ui_library_mode`:

- `both` (default) — theme CMS slugs via `NGC_UI_Library`; Magic UI slugs via `NGT_UI_Renderer`; explicit `component` attribute routes to Magic UI.
- `theme_only` — `[ng_ui_component]` uses theme CMS only.
- `magic_only` — `[ng_ui_component]` resolves Magic UI registry only.

`[ngt_ui]`, `[ngt_income_calculator]`, and NGT aliases always use Magic UI.

## Consequences

- Safer production: accidental zip activation is blocked at persistence and UI layers.
- Guard drift risk reduced by single `is_denied()` API shared with Plugin Manager.
- Ops visibility via `NGC_System_Log` channel `security` instead of raw `error_log` only.
- Follow-ups if multisite becomes a target: filter `pre_update_site_option_active_sitewide_plugins`.

## References

- `content-enhancement/README.md`
- `audit-reports/content-enhancement/FINAL-REPORT.md`
- `NextGenTutors-Companion/includes/diagnostics/class-ngc-legacy-plugin-guard.php`
