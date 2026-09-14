# CODEBASE UNUSED FILE ANALYSIS

Date: 2026-03-26  
Method: static require/include/get_template_part analysis, WordPress template hierarchy, Template Name headers, hooks, enqueues, shortcodes, REST/AJAX, autoload, dynamic path construction, page-builder paths, and post-migration recheck.

## Verified unused (moved to NOT-USED/)

- All `prototypes/*-body.php` (19) — content migrated to `template-parts/pages/`; blend default OFF; runtime no longer includes these paths
- Nested package copies under `NextGenTutors-BeyondInfinity/inc/defaults/*.php` (24) — duplicate one-line routers
- Root `inc/defaults/*.php` (24) — one-line routers superseded by flat pages

See `NOT-USED/NOT-USED-MANIFEST.md`.

## Likely unused

- Competing `page-templates/{about,pricing,contact,...}.php` duplicates that call missing `template-parts/head|nav|footer` — broken if assigned
- `page-templates/home.php` monolithic kinetic alternate (live home forced to `front-page.php` when kinetic)

## Potentially unused (NOT moved — Template Name / assignable)

| File | Why retained |
|---|---|
| `page-templates/template-app.php` | Assignable app shell |
| `page-templates/template-full-width.php` | Assignable full width |
| `page-templates/template-home.php` | Assignable marketing home |
| `page-templates/home.php` | Assignable “NextGen Kinetic Homepage” |
| Duplicate `page-templates/{slug}.php` with Template Name | May be assigned in WP DB — no environment proof of non-assignment |

## Duplicates

- Repo-root theme files vs `NextGenTutors-BeyondInfinity/` package: Docker mounts nested theme and overlays root `inc/`, `template-parts/`, `prototypes/`, etc. Nested `prototypes` is a junction to root. Not classified VERIFIED_UNUSED as a tree.

## Deprecated but referenced

- `bi_render_page_template()` — compatibility facade → canonical helpers
- `bi_render_generic_page()` — facade
- `bi_home_default_path()` — points at canonical home body first
- `inc/prototype-blend.php` / `inc/prototype-bodies.php` — retained; blend default false; includes resolve to canonical bodies

## Generated

- `e2e/reports/**`, Playwright artifacts — regenerable (not moved)
- `architecture/reports/gate-report.json` — gate output

## Vendor

- `docker/hello-elementor/`, `node_modules/`, `vendor/` — not moved

## Retained due to uncertainty

- Entire `NextGenTutors-BeyondInfinity/` tree (active Docker theme root)
- `inc/defaults-production/*` — still fallback via `bi_render_production_default()`
- Assignable `page-templates/*` listed above
- Plugin packages, Companion, AI-Integration, etc.

## Dead code candidates (functions — not deleted)

See `DEAD_CODE_CANDIDATES.md`.
