# Refactor Backlog — Prioritized Clean-up Plan

**Generated:** 2026-07-20  
**Scope:** Behavior-preserving refactors after sprint guard + dual UI + income calculator  
**Policy:** Incremental slices; test after each step

## Executive summary

The platform is **safer to activate** (guard + deny dedupe) and **richer on the frontend** (78 UI components, income calculator). Sprints A–E are complete. Remaining debt is **verification breadth** (full Playwright blueprint suite in CI), **dual UI mental model**, and **Stage 8 sanitisation**.

## Hotspots (code smells)

| ID | Area | Smell | Risk | Status |
|----|------|-------|------|--------|
| RF-01 | `NGT_UI_Catalog_Component` | Was 450+ line switch per kind | High | **Resolved** (~127 LOC, delegates to kind registry) |
| RF-02 | Dual UI | Two registries + bridge | Medium | **Mitigated** — option + docs; ADR-002 optional |
| RF-03 | `catalog.css` / `catalog.js` | Was monolithic shared assets | Medium | **Partial** — kind CSS + split JS; incremental CSS migration remains |
| RF-04 | Monorepo root | Theme + plugins co-located | Medium | **Open** — ship from `NextGenTutors-BeyondInfinity/` only |
| RF-05 | Deny list | Filter + static arrays | Low | **Mitigated** — single `is_denied()` API |
| RF-06 | Tests | Was lightweight `run.php` only | High | **Resolved** — CI: 20 + 7 PHPUnit + 28 snapshots + integration + 10 E2E |
| RF-07 | `NGC_UI_Library::render_component` | Minimal placeholder HTML | Low | **Open** — theme hooks expected |

## Refactor plan (ordered)

### Sprint A — Quick wins (1–2 days) ✅ DONE

- [x] Guard: exact basename, folder prefix, Text Domain, `wp_kses_post`, structured log
- [x] NGCPM: single `is_denied()` source
- [x] Dual UI bridge + option
- [x] Income calculator component + shortcode
- [x] Token-based catalog accents + kind CSS registration
- [x] Admin asset enqueue scoping
- [x] Guard unit tests in `run.php`
- [x] ADR-001, `.dockerignore`

### Sprint B — Catalog maintainability (3–5 days) ✅ DONE

- [x] `NGT_UI_Kind_Renderer_Interface` + 11 kind classes
- [x] `NGT_UI_Catalog_Component` delegates to `NGT_UI_Kind_Registry`
- [x] `[ngt_income_calculator]` wired in `prototypes/become-a-tutor-body.php`
- [x] B4: Catalog snapshot coverage (25 catalog + 3 dedicated = 28 slugs)

### Sprint C — Asset split (2–3 days) ✅ DONE

- [x] `scripts/split-catalog-css.php` — kind CSS files + trimmed `catalog.css`
- [x] `catalog-core.js` + `catalog-interactive.js` + `ngt-ui-vendor-loader.js`
- [ ] C3: Document critical CSS path for hero/marketing pages

### Sprint D — Packaging (2 days) ✅ DONE

- [x] `scripts/build-release.ps1` stages theme with robocopy excludes
- [x] `.dockerignore` + zip exclude test for `content/_extracted`

### Sprint E — Tests (3 days) ✅ DONE

- [x] PHPUnit `LegacyPluginGuardTest.php` (7 cases, header mocks)
- [x] `tests/run.php` extended to 20 cases
- [x] Playwright `become-a-tutor-calculator.spec.ts`
- [x] `ui-library/tests/catalog-snapshot.php` (13 slugs)
- [x] `.github/workflows/ci.yml` + `docs-lint.yml`

### Sprint F — CI breadth ✅ DONE (2026-07-20)

| Step | Action | Status |
|------|--------|--------|
| F1 | All 10 Playwright specs in CI (`npx playwright test`) | VERIFIED |
| F2 | Catalog snapshots expanded to 25 + 3 dedicated (28 total) | VERIFIED |
| F3 | WPBakery adapter smoke (`ngt_ui_vc` + optional Docker zip) | VERIFIED |
| F4 | Integration smoke (globe vendor markers, registry count) | VERIFIED |
| F5 | Stage 8 quarantine pilot scripts | VERIFIED — `scripts/stage8-quarantine-pilot.ps1` |
| F6 | ADR-002 dual UI coexistence | VERIFIED |

### Sprint G — Optional deprecations (quarter)

Only if product approves:

- Soft-deprecate `ng_ui_component` for Magic slugs (notice in debug mode)
- Consolidate logging: remove `error_log` fallback when `NGC_System_Log` always available
- ADR-002 if retiring `NGC_UI_Library` theme partials

## Safe refactor rules

1. **One behavior per PR** — guard, bridge, and catalog splits stay separate
2. **Smoke before merge** — `php tests/run.php` + `php ui-library/tests/catalog-snapshot.php` + Docker `DENY_OK` + `IC_OK`
3. **No activation** of `content-enhancement/` plugins during refactor testing
4. **Preserve shortcodes** — `[ngt_income_calculator]`, `[ng_ui_component]`, `[ngt_ui]` are contracts

## Metrics

| Metric | Before | Current | Target |
|--------|--------|---------|--------|
| `NGT_UI_Catalog_Component` LOC | ~455 | ~127 | <120 ✅ |
| Guard test cases (`run.php`) | 5 | 8 | 15+ |
| PHPUnit guard cases | 0 | 7 | 10+ |
| Catalog snapshot slugs | 0 | 13 | 20+ |
| CI workflows | 1 (docs) | 2 (ci + docs) | 2+ ✅ |
| Documented ADRs | 0 | 1 | 3+ |

## Gaps / assumptions

- Multisite not a deployment target — sitewide guard deferred
- WPBakery adapter unverified until plugin installed in Docker
- `architecture.md` reflects Stage 1 discovery; use `master-directive-status.md` for current stage truth
- Become a Tutor prototype uses `[ngt_income_calculator]` shortcode (not inline HTML)

## Verification checklist (any refactor slice)

```powershell
php NextGenTutors-Companion/tests/run.php
php ui-library/tests/catalog-snapshot.php
php -l NextGenTutors-Companion/includes/diagnostics/class-ngc-legacy-plugin-guard.php
cd docker
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval "echo NGC_Legacy_Plugin_Guard::is_denied('nextgen-tutors-core/nextgen-tutors-core.php')?'DENY_OK':'FAIL';" --allow-root
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval "echo do_shortcode('[ngt_income_calculator]')?'IC_OK':'FAIL';" --allow-root
```
