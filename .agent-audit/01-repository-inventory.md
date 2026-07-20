# 01 — Repository Inventory

**Evidence date:** 2026-07-20  
**Method:** filesystem listing + explore agent path walk + compose ps

## Top-level packages

| File or directory | Type | Purpose | Build system | Entry point | Risk | Status |
| ----------------- | ---- | ------- | ------------ | ----------- | ---- | ------ |
| `NextGenTutors-Companion/` | Plugin | Business logic, REST, payments, workflows, AI | Composer + Vite build-src | `nextgencompanion.php` | HIGH (finance/PII) | PARTIAL |
| `nextgen-automation-hub/` | Plugin | Dashboards, RTM, hub workflows, matching, payouts | None | `nextgen-automation-hub.php` | HIGH (parallel finance) | PARTIAL |
| `NextGenTutors-BeyondInfinity/` + root theme files | Theme | UI, prototypes, theme workflows | None | `functions.php` | MEDIUM | PARTIAL |
| `NextGenTutors-Plugin-Manager/` | Plugin | Install/activate/diagnostics | None | `NextGenTutors-Plugin-Manager.php` | MEDIUM | PARTIAL |
| `NextGenTutors-Html-Importer/` | Plugin | HTML import | None | `revamp-html-importer.php` | LOW | PARTIAL |
| `ui-library/` | Shared lib | Magic UI catalog | PHP tests | `bootstrap/class-ngt-ui-bootstrap.php` | LOW | VERIFIED (smoke) |
| `docker/` | Infra | Local WP stack | Compose | `docker-compose.yml` | MEDIUM (phpMyAdmin exposed) | VERIFIED |
| `e2e/` | Tests | Playwright | npm | `playwright.config.ts` | LOW | PARTIAL |
| `documentation/` | Docs | ADRs, guides | — | README | LOW | PARTIAL |
| `to-discard/`, `content-enhancement/` | Quarantine/legacy | Denied by guard | — | — | MEDIUM if activated | DEPRECATED |
| `agntix/`, `reference-style-extraction/` | Reference | Non-production | — | — | LOW | NOT IMPLEMENTED (prod) |

## Baseline tests (host)

| Suite | Result | Evidence |
|-------|--------|----------|
| Companion `tests/run.php` | 20 passed | VERIFIED 2026-07-20 |
| UI library integration smoke | passed | VERIFIED 2026-07-20 |
| Full PHPUnit / Playwright / Docker smoke this run | NOT VERIFIED | deferred — Kirki WP-CLI fatal; CI exists |

## VCS

| Item | Status |
|------|--------|
| Git repository | NOT VERIFIED — absent |
| Uncommitted changes | N/A |
| Safe working branch | BLOCKED — cannot create branch without git init (not performed; would invent history) |
