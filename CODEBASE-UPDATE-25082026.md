# NextGenTutors — Honest Codebase Update

**Original date:** 25 August 2026  
**Refreshed:** 30 August 2026 (post-audit remediation pass)  
**File:** `CODEBASE-UPDATE-25082026.md`  
**Canonical architecture:** `ARCHITECTURE.md` (ignore root `README.md` — it still describes retired `react-to-wp-theme/`)  
**Latest audit:** `docs/reports/CODEBASE-AUDIT-SWOT-20260830.md`

---

## Honest verdict (30 Aug 2026)

This is a **real, large tutoring platform monorepo** with a clear six-package architecture plus an **ecosystem-platform** control-plane scaffold — not a toy. It is **not** uniformly “production complete.”

- **Companion** is deep and is the domain heart (~313 PHP files in `includes/`).
- **Theme packaging remediated** — `node scripts/sync-beyondinfinity-theme.mjs` copies root overlays into `NextGenTutors-BeyondInfinity/`; RAD gate enforces `ARCH-BI-PACKAGE`.
- **Bootstrap gaps mostly closed** — session orchestrator/classroom, subjects CMS, product provisioner, publish worker now ship (session layer still thin).
- **Docker WordPress bumped to 6.9** — aligns with WooCommerce / PayFast fleet expectations.
- **Ecosystem-platform** — STAGING READY (tenant IAM, Odoo DB provision, API `:8790`, Control Center shell).
- Phase 14 demo remains **COMPLETE WITH LIMITATIONS**; automation evidence exported to `.agent-audit/evidence/demo/audit-automation-20260830/`.

Almost all intended product tiers exist as substantial code. Remaining gaps: **full virtual classroom product**, **sidecar-backed intelligence**, **ecosystem-platform production gates** (Postgres tenant store, backup/restore E2E), and **full persona Playwright matrix**.

---

## What changed since 25 Aug 2026

| Item | 25 Aug status | 30 Aug status |
|------|---------------|---------------|
| BI `inc/` self-contained | ~28 files, fatal without overlays | **109 files synced** via `scripts/sync-beyondinfinity-theme.mjs`; gate checks `ARCH-BI-PACKAGE` |
| Session orchestrator / classroom | Missing | **Present** (hooks, reminders, join URL — not full classroom) |
| Subjects CMS / product provisioner / publish worker | Missing | **Present**; publish worker tests pass |
| Docker WordPress image | 6.7 | **6.9-php8.2-apache** |
| `ecosystem-platform/` | Not in doc | **37+ source files** — STAGING READY |
| Phase 14 evidence | Scaffold only | **Automation export** `audit-automation-20260830` PASS |
| Odoo DB per tenant | Not implemented | **Provisioner** + `npm run test:e2e` (memory; live via `ODOO_E2E=1`) |

---

## What this product is

ZA tutoring marketplace:

1. Find / match tutors  
2. Book lessons  
3. Pay (WooCommerce / PayFast)  
4. Lessons + CRM / LMS side-effects  
5. Role dashboards  
6. Ops / AI / admin  

One product expressed as **six sacred WordPress packages** plus **ecosystem-platform** (multi-tenant control plane) and ops consoles.

### Sacred packages (`ARCHITECTURE.md`)

| Package | Folder | Job |
|---------|--------|-----|
| **BeyondInfinity** | `NextGenTutors-BeyondInfinity/` + root overlays | Presentation: templates, tokens, page defaults |
| **Companion** | `NextGenTutors-Companion/` | Domain: data, CPTs, `[ngc_*]`, `ngc/v1`, matching, bookings, AI suite, integrations |
| **Beyond Measure** | `NextGenTutors-BeyondMeasure/` | Control-plane admin OS (React SPA in wp-admin) |
| **AI-Integration** | `NextGenTutors-AI-Integration/` | Governed AI transport only (no LLM runtime, no domain ownership) |
| **Html-Importer** | `NextGenTutors-Html-Importer/` | Ops: static HTML → WP pages |
| **Plugin-Manager** | `NextGenTutors-Plugin-Manager/` | Operator fleet: install/activate stack plugins |

### Ecosystem platform (new tier)

| Package | Folder | Job |
|---------|--------|-----|
| **ecosystem-platform** | `ecosystem-platform/` | Multi-tenant control plane: IAM, blueprints, Odoo adapter, platform API `:8790`, Control Center UI |

### Sacred contracts

```
Theme          ──consumes──►  [ngc_*] shortcodes, ngc/v1 REST
Companion      ──owns──►      ngc_* namespace, NGC_* tables, NGC_AI_*
Plugin-Manager ──orchestrates─► wp plugin install/activate (never duplicates Companion logic)
Html-Importer  ──writes──►    post_content / pages only (never touches ngc_* tables)
AI-Integration ──bridges──►   governed event/callback transport (never owns Companion domain)
Ecosystem API  ──provisions──► Odoo DB-per-tenant via provider contracts (never owns Companion domain)
```

---

## Solution tiers

| Tier | What | Reality |
|------|------|--------|
| **0 · Product intent** | ZA tutoring marketplace end-to-end | Clear product thesis in architecture + Companion domain |
| **1 · Presentation** | BeyondInfinity + root `inc/` / `assets/` / templates / `ui-library` | Marketing pages, config, page composer, kinetic/motion, dashboards, Elementor. **BI package now syncable to full `inc/`** via `node scripts/sync-beyondinfinity-theme.mjs`; Docker still bind-mounts overlays for dev |
| **2 · Domain** | Companion (~313 `includes` PHP) | Matching, bookings/Amelia, Woo/PayFast/wallet/payouts, workflows, studio/builder, AI suite, gamification, platform kernel, demo, agents — **this is the heart** |
| **3 · Sacred satellites** | Plugin-Manager, AI-Integration, Html-Importer, BeyondMeasure | Fleet install, signed AI transport, HTML→pages ops, control-plane admin (SPA ship still soft) |
| **4 · Ops / platform** | Mission Control, Docker, agent-gateway, RAD gates, e2e, `.agent-audit`, `delivery/`, ecosystem-platform | Real local stack + evidence/audit tooling + control plane scaffold |
| **5 · Parallel / legacy** | Automation Hub, Universal API, `content-enhancement`, old `automations/`, sticky-ui | Hub **overlaps** Companion; content-enhancement = **do not activate** |

---

## Tier detail (selected updates)

### Tier 1 — Presentation

**Dual-root theme model:**

```
Repo root (source of truth for overlays)
  inc/, assets/, templates/, template-parts/, page-templates/, …
        │
        ├─ node scripts/sync-beyondinfinity-theme.mjs  → copies into BI package
        ├─ Windows: scripts/sync-beyondinfinity-theme.ps1 (junctions)
        └─ Docker: bind-mounts root → …/themes/nextgentutors-beyondinfinity/{inc,assets,…}

NextGenTutors-BeyondInfinity/
  After sync: full inc/ (109 PHP), assets, templates — boots standalone
  RAD gate: ARCH-BI-PACKAGE fails if functions.php requires are missing
```

### Tier 2 — Domain (Companion) — bootstrap fixes

| Capability | 25 Aug | 30 Aug |
|------------|--------|--------|
| Session orchestrator / classroom | Missing | **Shipped** (thin — reminders/meetings/join URL) |
| Product provisioner / subjects CMS / publish worker | Missing | **Shipped** |
| Session classroom product | N/A | **Still gap** — stubs ≠ full virtual classroom |

### Tier 4 — Ops & platform

| Piece | Maturity | Notes |
|-------|----------|-------|
| **Docker** | Live | `wordpress:6.9-php8.2-apache`; overlay `docker-compose.ecosystem.yml` for Odoo/Postgres/RabbitMQ |
| **ecosystem-platform** | STAGING | Tenant store, IAM L0–L7, blueprints, Odoo DB provisioner, API `:8790`, Control Center static UI |
| **RAD gate** | Live | Includes `ARCH-BI-PACKAGE` theme integrity check |

---

## Integrity risks (re-scored 30 Aug)

1. **Theme dual-root** — **MITIGATED** via sync script + gate; still run sync before release zips.
2. **WordPress vs fleet plugins** — **MITIGATED** — compose on WP 6.9; re-validate fleet on fresh stack.
3. **Missing bootstrap classes** — **MOSTLY CLOSED**; session product depth still thin.
4. **Phase 14** — **COMPLETE WITH LIMITATIONS**; automation evidence PASS at `.agent-audit/evidence/demo/audit-automation-20260830/`.
5. **Automation Hub overlap** — **OPEN** — document Companion as domain SOtT.
6. **Ecosystem-platform production** — **OPEN** — Postgres tenant store, backup/restore, live Odoo E2E in Docker.

---

## Phase 14 (demo evidence)

**Status:** `COMPLETE WITH LIMITATIONS`

**Evidence:**
- `.agent-audit/checkpoints/phase-14-complete.md`
- `.agent-audit/evidence/demo/audit-automation-20260830/evidence.json` (30 Aug automation PASS)
- Export: `node scripts/phase14-export-evidence.mjs` or `wp ngc demo_export_evidence`

---

## Verification commands (30 Aug pass)

```bash
node scripts/sync-beyondinfinity-theme.mjs
node rad-platform/cli/gate.mjs
cd ecosystem-platform && npm test && npm run test:e2e
node scripts/phase14-export-evidence.mjs
php NextGenTutors-Plugin-Manager/tests/run.php
```

---

## Bottom line

| Question | Answer |
|----------|--------|
| Is the intended product architecture present? | **Yes** — six sacred packages + ecosystem-platform scaffold |
| Is everything production-complete? | **No** |
| Where is the real product logic? | **Companion** (+ theme overlays for UX) |
| Biggest structural risk? | **Theme packaging** — mitigated by sync + gate; run before release |
| Biggest product gap? | **Live session classroom** (orchestrator exists; product thin) |
| Demo claim allowed? | **COMPLETE WITH LIMITATIONS** only |
| Ecosystem claim allowed? | **STAGING READY** only |

---

*Refreshed 30 August 2026 after audit remediation: theme sync, WP 6.9, Companion bootstrap fixes, ecosystem Odoo provision, Phase 14 automation evidence. See `docs/reports/CODEBASE-AUDIT-SWOT-20260830.md` for full SWOT/gap analysis.*
