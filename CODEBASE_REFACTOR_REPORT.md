# CODEBASE REFACTOR REPORT

Date: 2026-03-26  
Status: **COMPLETE WITH LIMITATIONS**

## Executive summary

The WordPress theme page stack was flattened from a multi-layer router (`page-*.php` → `bi_render_page_template` → `inc/defaults/*` → production/prototype blend) into a WordPress-native contract: **one obvious page entry** + **`get_header()` / body / `get_footer()`**, with page bodies in `template-parts/pages/`. Navigation now resolves to **NextGen Primary**. Verified-unused prototype bodies and defaults routers were quarantined under `NOT-USED/` with path preservation.

## Original architecture

```
page-{slug}.php
  → bi_render_page_template()
    → get_header()
    → bi_render_theme_default()
      → bi_page_open()
      → inc/defaults/{slug}.php
        → bi_render_page_default()
          → prototype blend (*-body.php) [default ON]
          → OR inc/defaults-production/{slug}.php
      → bi_page_close()
    → get_footer()
```

Problems: unclear ownership, nested indirection, competing bodies (prototype vs production), duplicate `page-templates/*`, menu named “NextGen Primary Grouped”.

## Target architecture

```
WordPress hierarchy
  → front-page.php | page-{slug}.php | page.php
       → get_header()  [header.php → templates/header/* → NextGen Primary]
       → template-parts/pages/{slug}.php
       → get_footer()  [footer.php → templates/footer/*]
```

Compatibility: `bi_render_page_template()` remains as a deprecated facade for legacy callers.

## Dependency-analysis methodology

Static PHP includes, `get_template_part`, template hierarchy, Template Name, hooks, shortcodes, enqueues, dynamic path builders, Docker mount map, and post-move reference recheck (no live refs into `NOT-USED/`).

## Primary page inventory

See `PAGE_ARCHITECTURE_MAP.md` (24 canonical bodies).

## Header consolidation

- Single entry: `header.php`
- Variants retained only as style partials under `templates/header/` (transparent/default/minimal)
- Ordinary pages call `get_header()` only

## Footer consolidation

- Single entry: `footer.php`
- Variants: `templates/footer/default|minimal`
- Ordinary pages call `get_footer()` only

## Navigation consolidation

- Menu name standardized to **NextGen Primary**
- Legacy **NextGen Primary Grouped** renamed/fallback via `bi_resolve_nextgen_primary_menu()`
- Schema bump forces rebuild: `2026-03-26-nextgen-primary-v1`
- Location label: “Primary Navigation (NextGen Primary)”

## *-body.php migration

| Source | Target |
|---|---|
| Marketing prototypes (`about-body`, `pricing-body`, …) | `template-parts/pages/{slug}.php` |
| Kinetic home / auth / dashboards / find-a-tutor | from `inc/defaults-production/` |
| Scripts in prototype HTML | stripped (enqueue path retained) |

Obsolete bodies moved to `NOT-USED/prototypes/`.

## Reusable components retained

- `template-parts/components/*`, UI library cards
- `bi_hero`, brand story, kinetic helpers, 3D tutors
- Companion shortcodes for forms/dashboards/marketplace

## Routers / loaders removed / reduced

- Quarantined: `inc/defaults/*.php` routers
- Quarantined: `prototypes/*-body.php`
- Blend default flipped to **OFF**
- `bi_render_page_template` → thin facade

## Asset changes

No global “load everything” shortcut. Kinetic/home assets still gated by existing enqueue logic; page bodies preserve prior CSS/JS selectors.

## Files moved

67 files → `NOT-USED/` (see manifest).

## Files modified (high signal)

- All root + nested `page-*.php`, `front-page.php`, `home.php`, `page.php`, `admin-dashboard.php`
- `functions.php` (+ nested)
- `inc/canonical-page.php` (new)
- `inc/page-wrapper.php`, `inc/nav-menu.php`, `inc/production-content.php`, `inc/prototype-blend.php`, `inc/prototype-bodies.php`, `inc/pages-registry.php`, `inc/template-tags.php`

## Files created

- `inc/canonical-page.php`
- `template-parts/pages/*.php` (24)
- `NOT-USED/` + `.htaccess` + `index.php` + manifest
- `BACKUPS/20260326-page-flatten/`
- Reports: this file, `PAGE_ARCHITECTURE_MAP.md`, `CODEBASE_UNUSED_FILE_ANALYSIS.md`, `DEAD_CODE_CANDIDATES.md`

## Tests

| Check | Result |
|---|---|
| `php -l` on changed templates/bodies/helpers | PASS (0 errors) |
| Live refs into `NOT-USED/` | PASS (none) |
| `node rad-platform/cli/gate.mjs` | PASS |
| HTTP smoke localhost:8890 | BLOCKED (service timeout — WP not running) |
| E2E / responsive | NOT RUN (Playwright browsers pruned for disk; WP down) |

## Regressions detected/fixed

- Disk full blocked early writes → freed ~1.9GB regenerable caches/artifacts
- Nested `front-page.php` / `home.php` file locks → retried / .NET write
- Mojibake em-dashes in migrated UTF-8 bodies → normalized

## Before / after complexity metrics

| Metric | Before | After |
|---|---|---|
| Include depth (typical marketing page) | 6–8 | 2–3 |
| Active `*-body.php` | 19 | 0 |
| Active `inc/defaults/*.php` routers | 24 | 0 |
| Canonical page bodies | scattered | 24 in `template-parts/pages/` |
| Competing page render modes | production + blend + prototype flag | canonical first |
| Primary menu name | NextGen Primary Grouped | NextGen Primary |

## Known limitations

1. Full browser/E2E/responsive matrix not executed (WP/Docker not reachable; Playwright browsers removed for disk).
2. Assignable `page-templates/*` retained as POTENTIALLY_UNUSED pending DB template-assignment proof.
3. `inc/defaults-production/` kept as fallback (duplicate of many canonical bodies) — safe, not yet quarantined.
4. Nested theme package vs monorepo root dual layout remains (brownfield Docker mount model).
5. Dashboard pages intentionally use minimal chrome (documented exception).

## Readiness verdict

**COMPLETE WITH LIMITATIONS** — architecture flatten, body migration, nav standardization, quarantine, PHP validation, and gate are done. Runtime UI regression pending a live WordPress environment.
