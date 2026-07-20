# NextGen Tutors — Architecture (live summary)

**Status:** VERIFIED (2026-07-20)  
**Historical Stage 1 inventory:** see git history of this file; detailed scans remain in `audit-reports/`.

For the full living architecture reference (domain, demo, privacy, metrics), see **[platform-architecture.md](./platform-architecture.md)**.

For day-to-day status, use **[master-directive-status.md](./master-directive-status.md)** as the live source of truth.

## Stack overview

```text
BeyondInfinity (theme)          NextGenTutors-Companion
  inc/defaults/*.php              ngc_* domain, REST, workflows
  prototypes/*.php                NGC_Legacy_Plugin_Guard
                                  NGC_UI_Library (theme CMS)
                                  NGC_NGT_UI_Bridge (option-centric)
                                        │
                                        ▼
                              ui-library / NGT_UI (Magic UI)
                                kind renderers + catalog
                                [ngt_income_calculator]
NextGenTutors-Plugin-Manager      NextGenTutors-Html-Importer
  NGCPM → is_denied()             import only (no CE plugin activation)
```

## UI rendering paths

| Entry | Resolves to |
|-------|-------------|
| `[ng_ui_component slug="hero"]` | Theme CMS (`NGC_UI_Library`) when mode allows |
| `[ng_ui_component component="magic-card"]` | Magic UI via bridge |
| `[ngt_ui component="…"]` | Always `NGT_UI_Renderer` |
| `[ngt_income_calculator]` | `NGT_UI_Income_Calculator` |

Catalog components delegate to **kind strategies** (`NGT_UI_Kind_*`) — see `ui-library/rendering/kinds/`.

## Assets (Sprint C)

- **CSS:** `catalog.css` (base shell) + `assets/css/kinds/kind-*.css` per kind
- **JS:** `catalog-core.js` + `catalog-interactive.js` + lazy `ngt-ui-vendor-loader.js`

## Packaging (Sprint D)

- Ship theme from **`NextGenTutors-BeyondInfinity/`** only (`scripts/build-release.ps1`)
- Excludes: `content/_extracted`, `audit-reports`, zips
- Monorepo root remains dev workspace; Docker mounts theme package + plugins separately

## Security

- Legacy CE plugins blocked — [legacy-plugin-guard-runbook.md](./legacy-plugin-guard-runbook.md)
- ADR: [adr/ADR-001-legacy-plugin-deny.md](./adr/ADR-001-legacy-plugin-deny.md)

## Tests

| Layer | Location |
|-------|----------|
| Lightweight PHP | `NextGenTutors-Companion/tests/run.php` |
| PHPUnit guard | `NextGenTutors-Companion/tests/phpunit/LegacyPluginGuardTest.php` |
| Playwright | `e2e/workflows/become-a-tutor-calculator.spec.ts` |

## Editor adapters

| Editor | Status |
|--------|--------|
| Gutenberg | VERIFIED |
| Elementor | VERIFIED when active |
| WPBakery | PARTIAL — adapter shipped; **not installed in Docker** (see [wpbakery-guide.md](./wpbakery-guide.md)) |

## Related docs

- [ui-library-guide.md](./ui-library-guide.md)
- [dual-ui-stack.md](./dual-ui-stack.md)
- [refactor-backlog.md](./refactor-backlog.md)
