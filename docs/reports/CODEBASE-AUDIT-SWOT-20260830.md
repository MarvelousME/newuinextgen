# Codebase Deep Audit & SWOT — vs CODEBASE-UPDATE-25082026.md

**Audit date:** 30 August 2026  
**Reference:** `CODEBASE-UPDATE-25082026.md` (25 Aug 2026)  
**Method:** On-disk verification, automated tests, RAD gate, git status, cross-check of doc claims

---

## Executive verdict

| Dimension | Assessment |
|-----------|------------|
| **Architecture intent** | **VERIFIED** — six sacred WP packages + Companion domain + RAD governance |
| **Production readiness** | **NOT READY** — theme packaging, WP/fleet version friction, demo limitations, ecosystem scaffold only STAGING |
| **Doc accuracy (25 Aug)** | **Mostly accurate at time of writing** — several gaps **closed since** (see delta below) |
| **Recommended demo claim** | Phase 14: **COMPLETE WITH LIMITATIONS** only |
| **Recommended platform claim** | Ecosystem-platform: **STAGING READY** (not production) |

---

## Automated evidence (this audit run)

| Check | Result |
|-------|--------|
| `node rad-platform/cli/gate.mjs` | **PASS** — 9 manifests, 43 capabilities, 0 errors |
| `ecosystem-platform` unit tests | **4/4 PASS** — blueprint, tenant, provision, isolation |
| `NextGenTutors-Plugin-Manager/tests/run.php` | **PASS** |
| `publish-worker-restart.php` | **5/5 PASS** |
| `docs/kinetic-ui/` | **MISSING** |
| Docker `wordpress` image | **6.7-php8.2-apache** (doc concern still valid) |

---

## Inventory vs document claims

| Metric | CODEBASE-UPDATE (25 Aug) | Verified 30 Aug |
|--------|--------------------------|-----------------|
| Root `inc/` PHP files | ~109 | **109** ✓ |
| BI package `inc/` PHP | ~28 | **28** ✓ |
| Companion `includes/` PHP | ~307 | **313** (+6) |
| `docs/kinetic-ui/` | Missing | **Still missing** ✓ |
| Phase 14 status | COMPLETE WITH LIMITATIONS | **Unchanged** ✓ |
| Session orchestrator / classroom | Missing | **Now present** (minimal) ⚠️ delta |
| Subjects CMS / product provisioner / publish worker | Missing | **Now present** ⚠️ delta |
| `ecosystem-platform/` | Not in doc | **37 source files** — new tier ⚠️ delta |

---

## Tier-by-tier audit

### Tier 0 — Product intent

**VERIFIED.** ZA tutoring marketplace thesis is consistent across `ARCHITECTURE.md`, Companion domain (matching → booking → payments), and theme marketing surfaces.

### Tier 1 — Presentation

| Claim | Status | Evidence |
|-------|--------|----------|
| Dual-root theme model | **VERIFIED CRITICAL RISK** | BI `functions.php` requires 40+ `inc/*.php`; **81 files git-deleted** under `NextGenTutors-BeyondInfinity/inc/`; `security.php`, `loader.php`, `page-composer.php` **False** in BI package alone |
| Root overlays are source of truth | **VERIFIED** | Docker bind-mounts root `inc/`, `assets/`, templates into theme path |
| Kinetic / motion / page composer | **VERIFIED** at root | `inc/kinetic-*`, `inc/motion.php`, `inc/page-composer.php` on root only |
| `ui-library/` package | **VERIFIED** | Present; Elementor/Gutenberg integrations |
| `docs/kinetic-ui/` | **MISSING** | Doc correct |
| Sticky UI scaffold | **VERIFIED** | `sticky-ui/` prototype only |

**Severity:** P0 if anyone deploys BI package without overlays.

### Tier 2 — Domain (Companion)

| Claim | Status | Evidence |
|-------|--------|----------|
| Deep domain heart (~300+ PHP) | **VERIFIED** | 313 files in `includes/` |
| Matching, bookings, PayFast, workflows, studio, AI | **VERIFIED** | Classes and REST namespaces present |
| Session classroom / orchestrator **missing** | **PARTIAL — DOC STALE** | `NGC_Session_Orchestrator`, `NGC_Session_Classroom` exist; thin (hooks + shortcode), not full virtual classroom |
| Product provisioner / subjects CMS / publish worker **missing** | **VERIFIED FIXED** | Classes on disk; publish worker tests pass |
| Memory / talent noop without sidecars | **VERIFIED** | No change expected |
| FluentCRM / LMS **PARTIAL** | **VERIFIED** | Adapter pattern; env-dependent |
| Phase 14 demo | **VERIFIED LIMITED** | Checkpoint file unchanged |

### Tier 3 — Sacred satellites

| Package | Doc maturity | Audit |
|---------|--------------|-------|
| Plugin-Manager | Live | **VERIFIED** — tests pass, zip validation added in recent work |
| AI-Integration | Live | **VERIFIED** — manifest in gate |
| Html-Importer | Live | **VERIFIED** |
| Beyond Measure | Partial SPA | **VERIFIED** — control-plane PHP; webpack ship soft |

### Tier 4 — Ops / platform

| Piece | Status | Notes |
|-------|--------|-------|
| Mission Control | Live | Present, Docker-mounted |
| Docker stack | Live | WP 6.7 + mounts + agent-gateway |
| Agent gateway | Live | `:8787`, tests in package |
| RAD platform | Live | Gate PASS |
| e2e Playwright | Live | Specs present; not re-run in this audit |
| `.agent-audit` | Living | Phase 14 limitations documented |
| **ecosystem-platform** (new) | STAGING | Tenant IAM, blueprints, Odoo adapter (memory), API `:8790`, gate manifest registered |

### Tier 5 — Parallel / legacy

| Piece | Status |
|-------|--------|
| Automation Hub | **VERIFIED overlap** — still mounted in docker-compose |
| content-enhancement | **VERIFIED** — do not activate |
| automations/ JSON | **VERIFIED legacy** |

---

## Integrity risks (re-scored)

| ID | Risk | Severity | Status |
|----|------|----------|--------|
| R1 | BI package alone fatals (deleted `inc/`) | **P0** | **OPEN** — git shows mass `D` under BI/inc |
| R2 | WP 6.7 vs WooCommerce/PayFast 6.8–6.9 | **P1** | **OPEN** — compose still `wordpress:6.7-php8.2-apache` |
| R3 | Missing bootstrap classes | **P2** | **MOSTLY CLOSED** — classes added; session layer still thin |
| R4 | Phase 14 over-claim | **P1** | **OPEN** — must keep WITH LIMITATIONS |
| R5 | Automation Hub dual domain | **P2** | **OPEN** |
| R6 | Ecosystem-platform not production | **P1** | **NEW** — Odoo live CRUD, backup/restore, Postgres store missing |
| R7 | Root README outdated | **P3** | **OPEN** |

---

## SWOT analysis

### Strengths

- **Mature Companion domain** — real matching, booking, finance, workflows, studio, agent policy stack (~313 PHP files).
- **Clear sacred-package boundaries** — `ARCHITECTURE.md` + RAD manifests enforce contracts.
- **Governance tooling** — RAD gate PASS, agent-audit directive, Phase 14 honesty checkpoint.
- **Rich presentation layer** — kinetic UI, motion registry, ui-library, page composer (on root overlays).
- **Ops depth** — Plugin Manager, Mission Control, agent-gateway, Docker dev stack, e2e harness.
- **Recent remediation** — publish worker, subjects CMS, session stubs, ecosystem-platform scaffold.

### Weaknesses

- **Theme packaging fragility** — dual-root; BI `inc/` deleted in working tree; fatal without Docker binds.
- **Documentation drift** — root README, missing `docs/kinetic-ui/`, CODEBASE-UPDATE stale on Companion gaps.
- **Integration brittleness** — FluentCRM/LMS/Amelia PARTIAL without full stack active.
- **Demo evidence** — journeys defined but export/live persona matrix incomplete.
- **Ecosystem-platform immature** — JSON tenant store, memory Odoo adapter, static control UI.
- **Version skew** — Docker WP 6.7 vs fleet plugin requirements.

### Opportunities

- **Strangler to Odoo** — ecosystem-platform provider contracts without replacing tutoring vertical in Companion.
- **Consolidate control plane** — Beyond Measure + Mission Control + ecosystem Control Center convergence.
- **Fix theme packaging** — restore BI/inc junction or sync script as release gate.
- **Complete kinetic-ui docs** — align with implemented motion tokens.
- **Pin WP 6.9 + Plugin Manager fleet** — remove activation friction.
- **Phase 14 evidence export** — unlock evaluator-ready demo on one command.

### Threats

- **Wrong deploy path** → white screen (BI without overlays).
- **Dual domain authority** — Automation Hub vs Companion confusion for agents/developers.
- **Over-claiming production** — large codebase invites false confidence.
- **Disk/ops constraints** — historical low disk blocked Docker sync and package repair.
- **Security surface** — agent/MCP tooling without full tenant isolation E2E on WordPress layer.
- **Odoo lock-in risk** — if UI bypasses provider contracts (currently architecture forbids this).

---

## Gap analysis (prioritized)

### P0 — Critical

1. **BI theme not self-contained** — reconcile git-deleted `inc/` with `functions.php` requires (sync, junction, or slim functions.php for package-only mode).
2. **Production path undefined** — no single verified “install zip and run” without Docker overlay knowledge.

### P1 — High

3. **WordPress 6.7 vs WooCommerce/PayFast** — bump compose image or pin older WC in Plugin Manager defaults.
4. **Phase 14 evidence** — run export/journey on demo host; store JSON in `.agent-audit/evidence/demo/`.
5. **Ecosystem-platform production gates** — live Odoo provision, Postgres persistence, backup/restore.
6. **Automation Hub boundary** — document + enforce Companion as domain SOtT.

### P2 — Medium

7. **Session “classroom”** — doc promised orchestrator; current code is reminder/meeting/join-url layer, not full classroom product.
8. **Beyond Measure SPA** — ship real webpack bundle vs fallback.js.
9. **Memory/talent sidecars** — configure or clearly disable in production profile.
10. **Update CODEBASE-UPDATE-25082026.md** — reflect closed Companion gaps + ecosystem-platform tier.

### P3 — Low

11. Root `README.md` alignment with `ARCHITECTURE.md`.
12. `docs/kinetic-ui/` documentation pass.
13. Sticky UI — integrate or archive prototype.

---

## Requirements traceability (doc → reality)

| CODEBASE-UPDATE claim | Current verdict |
|----------------------|-----------------|
| Real large monorepo, not a toy | **VERIFIED** |
| Not uniformly production complete | **VERIFIED** |
| Companion is domain heart | **VERIFIED** |
| Theme dual-root fragile | **VERIFIED** |
| Several bootstrap features missing | **PARTIALLY STALE** — most now shipped (thin) |
| Phase 14 COMPLETE WITH LIMITATIONS | **VERIFIED** |
| Biggest product gap: live session classroom | **STILL VALID** — stubs ≠ product |
| Demo claim COMPLETE WITH LIMITATIONS only | **VERIFIED** |

---

## Recommendations (next 30 days)

1. Run `scripts/sync-beyondinfinity-theme.ps1` (or equivalent) and add CI check: BI `inc/security.php` must exist OR gate fails.
2. Update `docker-compose.yml` to `wordpress:6.9-php8.2-apache` and re-validate Plugin Manager fleet.
3. Refresh `CODEBASE-UPDATE-25082026.md` or supersede with this audit.
4. Execute one Phase 14 evidence export run and attach to `.agent-audit/evidence/demo/`.
5. Ecosystem-platform: Odoo DB create on provision + one headed E2E (Super Admin → customer in Odoo).

---

*This audit verifies structure and automated tests; it does not replace runtime UAT on a live Docker stack unless explicitly run.*
