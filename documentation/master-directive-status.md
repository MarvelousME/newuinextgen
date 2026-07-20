# Master Directive — Source of Truth & Stage Status

**Solution root:** `c:\Users\marvi\Downloads\wetransfer_newuinextgen_2026-07-07_1929\newuinextgen`  
**Updated:** 2026-07-20  
**Master prompt (canonical):** `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md`  
**Destructive sanitisation:** NOT STARTED (quarantine-only until Stage 8 gate)

## Exact paths

| Purpose | Path |
|--------|------|
| Solution root | `c:\Users\marvi\Downloads\wetransfer_newuinextgen_2026-07-07_1929\newuinextgen` |
| PHP page UI bodies | `...\inc\defaults\` |
| Packaged theme defaults | `...\NextGenTutors-BeyondInfinity\inc\defaults\` |
| WP page wrappers | `...\page-*.php` |
| Page registry | `...\inc\pages-registry.php` |
| UI library | `...\ui-library\` |
| UI catalog | `...\ui-library\catalog\components.php` |
| Companion bridge | `...\NextGenTutors-Companion\includes\ui-library\class-ngc-ui-library-bridge.php` |
| Dual UI bridge | `...\NextGenTutors-Companion\includes\ui-library\class-ngc-ngt-ui-bridge.php` |
| Legacy plugin guard | `...\NextGenTutors-Companion\includes\diagnostics\class-ngc-legacy-plugin-guard.php` |
| Docker compose | `...\docker\docker-compose.yml` |
| Runtime UI mount | host `ui-library` → `/var/www/html/wp-content/ngt-ui-library` |
| Magic UI zip | `...\magicui-main.zip` |
| Magic UI extracted | `...\reference-style-extraction\inventory\magicui-main\` |
| Magic UI components | `...\reference-style-extraction\inventory\magicui-main\apps\www\registry\magicui\` |
| Magic UI registry | `...\reference-style-extraction\inventory\magicui-main\apps\www\registry\registry-ui.ts` |
| BeyondInfinity | `...\NextGenTutors-BeyondInfinity\` |
| Agntix child | `...\agntix\agntix-child\` |
| Ftech child | `...\ftech-wp-package\ftech-child\` |

## Required environment

| Item | Value | Status |
|------|-------|--------|
| PHP | 8.2 (`wordpress:6.7-php8.2-apache`) | VERIFIED (compose) |
| WordPress image | 6.7 base | VERIFIED (compose) |
| Prior runtime check | WP 7.0.1 / PHP 8.2.32 | VERIFIED (2026-07-19 Docker smoke) |
| Site | http://localhost:8900 | VERIFIED |
| MySQL | 8.0 | VERIFIED (compose) |

## Latest Docker smoke (2026-07-20)

| Check | Result |
|-------|--------|
| UI mount `/wp-content/ngt-ui-library` | VERIFIED |
| Registry count | **78** (77 Magic + income-calculator) VERIFIED |
| Legacy guard `DENY_OK` | VERIFIED |
| Dual UI bridge `GUARD_BRIDGE_OK` | VERIFIED |
| `[ngt_income_calculator]` | IC_OK VERIFIED |
| Unit tests `tests/run.php` | **20** passed VERIFIED |
| PHPUnit `LegacyPluginGuardTest` | 7 cases VERIFIED |
| Catalog snapshots `ui-library/tests/catalog-snapshot.php` | **28** slugs VERIFIED |
| Integration smoke `ui-library/tests/integration-smoke.php` | VERIFIED |
| GitHub CI | `.github/workflows/ci.yml` (php-unit + docker-smoke + full Playwright) |
| WPBakery adapter `[ngt_ui_vc]` | VERIFIED (shortcode smoke; js_composer optional) |
| Stage 8 pilot | `scripts/stage8-quarantine-pilot.ps1` |
| ADR-002 | `documentation/adr/ADR-002-dual-ui-coexistence.md` |
| Shortcode markers / render failures | 0 failures VERIFIED (prior pass) |
| `wp ngt ui validate` | VERIFIED (prior pass) |
| Gutenberg block `ngt-ui/component` | registered VERIFIED |
| Elementor loaded | yes VERIFIED |
| WPBakery | adapter VERIFIED (`ngt_ui_vc`); js_composer optional via `WPBAKERY_ZIP_PATH` |
| `/`, `/find-a-tutor/`, `/ngt-ui-demo/`, `/login/`, `/student-dashboard/` | HTTP 200 VERIFIED (prior pass) |
| Demo page | http://localhost:8900/ngt-ui-demo/ |

## Sprint 2026-07-20 (VERIFIED)

| Deliverable | Status |
|-------------|--------|
| Guard hardening (single-site) | VERIFIED |
| NGCPM deny dedupe | VERIFIED |
| `NGC_NGT_UI_Bridge` + `ngc_ui_library_mode` | VERIFIED |
| `[ngt_income_calculator]` full component | VERIFIED |
| Design tokens + kind CSS handles | VERIFIED |
| ADR-001 + `.dockerignore` | VERIFIED |
| Kind extractors (Sprint B) | VERIFIED |
| Asset split CSS/JS (Sprint C) | VERIFIED |
| Release packaging excludes (Sprint D) | VERIFIED |
| PHPUnit + Playwright + catalog snapshots (Sprint E) | VERIFIED |
| CI workflow `ci.yml` | VERIFIED |
| Docs: ui-library-guide, dual-ui-stack, guard runbook, refactor-backlog | VERIFIED |

## Latest Docker smoke (2026-07-19)

| Check | Result |
|-------|--------|
| UI mount `/wp-content/ngt-ui-library` | VERIFIED |
| Registry count | **77** VERIFIED |
| Shortcode markers / render failures | 0 failures VERIFIED |
| `wp ngt ui validate` | VERIFIED |
| Gutenberg block `ngt-ui/component` | registered VERIFIED |
| Elementor loaded | yes VERIFIED |
| WPBakery | adapter VERIFIED (`ngt_ui_vc`); js_composer optional via `WPBAKERY_ZIP_PATH` |
| `/`, `/find-a-tutor/`, `/ngt-ui-demo/`, `/login/`, `/student-dashboard/` | HTTP 200 VERIFIED |
| Demo page | http://localhost:8900/ngt-ui-demo/ |

## Stage status (§27)

| Stage | Status | Evidence |
|-------|--------|----------|
| 1 Discovery | PARTIAL | `audit-reports/file-inventory.*`, `dependency-graph.*`, Magic UI matrix |
| 2 Safety baseline | PARTIAL | `documentation/stage-2-safety-baseline.md`; Docker smoke re-run **VERIFIED** 2026-07-19 |
| 3 Classification | PARTIAL | `removal-candidates.json`, `duplicate-files.json` |
| 4 Non-destructive refactor | PARTIAL | Kind extractors, asset split, bridge, guard; monorepo root still dual-role |
| 5 UI library foundation | VERIFIED | registry, contracts, renderer, assets, shortcodes |
| 6 Magic UI conversion | PARTIAL | 78 components PHP/CSS/JS; editors adapters present |
| 7 Reference style reimpl | NOT VERIFIED | extraction folder present; selective reimpl incomplete |
| 8 Sanitisation | PARTIAL | Stage 8 pilot scripts + quarantine folder; no mass deletion |
| 9 Optimisation | PARTIAL | conditional enqueue; kind CSS + lazy vendor loader (integration verified) |
| 10 Final verification | PARTIAL | CI: php-unit + integration + full Playwright + Docker smoke |
| **14 Full relational demo** | **COMPLETE WITH LIMITATIONS** | Seeder + Control Centre + CLI + journeys; report `.agent-audit/reports/demo-implementation-report.md` |

## Acceptance gaps (high priority)

| Criterion | Status |
|-----------|--------|
| Four core projects operational | PARTIAL (needs Docker re-verify) |
| Canonical UI renderer | VERIFIED |
| Shortcode + PHP API | VERIFIED |
| Gutenberg / Elementor / WPBakery | PARTIAL (block + Elementor VERIFIED; WPBakery shortcode VERIFIED, js_composer optional) |
| Admin UI Library manager | PARTIAL (registry/preview screens shipping) |
| Shared GSAP / Three runtimes | PARTIAL (lazy loader + globe vendor markers in integration smoke) |
| Destructive cleanup with restore | PARTIAL (Stage 8 pilot copy + restore scripts) |
| Full docs set (§25) | PARTIAL — see `documentation/ui-library-guide.md`, `dual-ui-stack.md`, `legacy-plugin-guard-runbook.md`, `refactor-backlog.md`, `adr/ADR-001`, `adr/ADR-002` |
| Automated tests all green | PARTIAL | 20 run.php + 7 PHPUnit + 28 snapshots + integration in CI; 10 Playwright specs in CI |

## Non-negotiable

- No file removal without dependency graph evidence.
- `reference-style-extraction/` never enqueued in production.
- All editors call `NGT_UI_Renderer` / `ngt_render_ui_component()`.
