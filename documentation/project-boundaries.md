# Project Boundaries

## Ownership rules (target)

| Concern | Owner | Must not live in |
|---------|-------|------------------|
| Templates, theme tokens, theme chrome, Woo/Amelia/MasterStudy theme adapters | BeyondInfinity theme | Companion / Plugin Manager |
| CPTs, REST, bookings, marketplace data, shortcodes business logic | Companion | Theme (except thin render adapters) |
| Dependency install/activate, diagnostics, repair, quarantine ops | Plugin Manager | Theme |
| HTML parse/import/idempotent page creation | Html Importer | Theme |
| Canonical component render + editor adapters | `ui-library` (loaded by Companion) | Duplicated per editor |
| Reference theme / Magic UI source trees | `reference-style-extraction` | Production enqueue |

## Current violations (VERIFIED / PARTIAL)

1. **VERIFIED** — Entire monorepo mounted as BeyondInfinity theme in `docker/docker-compose.yml`.
2. **VERIFIED** — Agntix child loads selected BeyondInfinity `inc/*` modules in place (`BI_DIR` bridge) — intentional functional reuse; visual chrome stays Agntix.
3. **VERIFIED** — `to-discard/NextGenTutors-BeyondInfinity` mirrors theme code (DUPLICATE / REDUNDANT candidate).
4. **PARTIAL** — Companion already hosts some UI/admin CSS/JS that may belong in `ui-library` after consolidation.
5. **PARTIAL** — Plugin Manager packages/zips inflate inventory byte counts (archives counted as assets).

## Coupling notes

- Companion probes `BI_VERSION`, `bi_*` helpers, and shortcodes — theme/plugin contract must remain stable during extraction.
- Theme switcher for Agntix ⇄ BeyondInfinity lives in Companion (`ngc_switch_theme`) so it survives theme changes — keep this in the plugin.
