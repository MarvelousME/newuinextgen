# NextGen Tutors — Codebase Brief

**For:** Head of team · **Product:** NextGen Tutors (SA tutoring marketplace) · **Date:** 2026-07-21
**Stack:** WordPress (multi-package) · **Local demo:** Docker @ `http://localhost:8900`

---

## Elevator summary

NextGen Tutors is a **WordPress marketplace** for South African tutoring — discovery, AI-assisted matching, booking, payments, learning, CRM/automation, and role dashboards. The codebase is a **multi-package solution**, not a single app: a presentation **theme** and a domain **plugin** with a strict responsibility split, plus operator and migration plugins.

> **Theme renders. Plugin decides.** Money, matching, and compliance live in the plugin — never in templates.

---

## The five canonical packages

| Package | Folder | Role |
|---------|--------|------|
| **BeyondInfinity** (theme) | `NextGenTutors-BeyondInfinity/` | Brand, pages, UI, dashboards chrome (Hello Elementor child) |
| **Companion** (plugin) | `NextGenTutors-Companion/` | Domain system of record: data, REST `ngc/v1`, matching, bookings, wallet, fraud/safeguarding, internal agents, demo |
| **AI-Integration** (plugin) | `NextGenTutors-AI-Integration/` | Signed asynchronous bridge from Companion outbox → external `agents-api` (no domain ownership, no direct LLM runtime) |
| **Plugin-Manager** | `NextGenTutors-Plugin-Manager/` | Operator console to install/activate the plugin fleet |
| **Html-Importer** | `NextGenTutors-Html-Importer/` | One-time static-HTML → WP content migration |

Supporting: `docker/` (local stack), `e2e/` (Playwright), `config/` (business SSOT), `ui-library/`, `documentation/` + `docs/`, `.agent-audit/` (demo + audits).

---

## Architecture at a glance

```text
Browser (parents / tutors / admins)
        │  pages + [ngc_*] forms
        ▼
BeyondInfinity theme (UX)
        │  shortcodes + REST ngc/v1
        ▼
NextGenTutors-Companion (domain SoR)
   users/roles · matches · bookings · wallet · reviews
   fraud/safeguarding · internal agents · outbox/events · demo
        │
        ├── adapters (Woo/PayFast, Amelia, LMS, FluentCRM, …)
        └── transactional outbox
                 │ signed HTTPS (async)
                 ▼
NextGenTutors-AI-Integration
                 │
                 ▼
         agents-api (Coolify) → LiteLLM / RAGFlow / workers
```

**Boundary:** Companion remains source of truth. AI-Integration transports redacted events and stores recommendation results; it cannot approve tutors or alter prices. External model execution is not inside WordPress.

---

## Quality & test posture

| Suite | Purpose | Latest |
|-------|---------|--------|
| Companion unit (`tests/run.php`) | Domain + demo catalogue | **64 assertions pass** |
| Playwright `e2e/` (headed) | Public workflows + Phase 14 | **41 pass / 2 skip** |
| Phase 14 walkthrough | Seed → login → ops → reset | **5/5 headed** |

---

## Readiness snapshot

| Area | Status | Note |
|------|--------|------|
| Local platform (Docker) | **Usable** | Demo/training ready on `:8900` |
| Theme + Companion core | **Strong** | Clear ownership, working demo path |
| Phase 14 demo | **Strong, w/ limits** | Evidence must be exported on host |
| Woo / PayFast production | **Partial** | Needs secrets + ITN verification |
| CRM / Amelia / LMS live sync | **Partial / verifiable** | Adapters exist; not always fully seeded |
| External AI stack (`agents-api`) | **Plugin shipped; API not verified** | `NextGenTutors-AI-Integration` bridges outbox → agents-api; live MATCH-001 external AI still needs configured Coolify stack |
| Production sign-off | **Not approved** | Needs image pinning, secrets, backups, E2E evidence, observability |

---

## Recommended priorities

**Stabilize & prove**
1. Export real Phase 14 evidence packs on the demo host (`wp ngc demo_export_evidence`).
2. Complete PayFast sandbox creds + payment webhook e2e.
3. Confirm CRM / Amelia / LMS report **VERIFIED** on target env.

**Scale integrations**
4. Build `nextgentutors-ai-integration` plugin (signed outbox → `agents-api`).
5. Keep marketing AI stack (Twenty / Mautic / n8n / MDCMS / RAG) **separate** from WP container.
6. Pin Coolify images, lock secrets, add readiness/health + Grafana correlation.

**Do not**
- Merge the AI/automation project into the WordPress Docker image.
- Put payment/matching logic in theme templates.
- Mark Phase 14 unconditionally COMPLETE while integrations/evidence remain partial.

---

## Reference documents

| Doc | Why |
|-----|-----|
| `ARCHITECTURE.md` | Package ownership + SOLID mapping |
| `BESPOKE-THEME-AND-COMPANION-GUIDE.md` | Safe customisation guide |
| `docs/SYSTEM-SETUP-GAP-MATRIX.md` | Honest PASS/PARTIAL map |
| `.agent-audit/checkpoints/phase-14-complete.md` | Demo phase status |
| `.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md` | 30-step evaluator walkthrough |
| `documentation/platform-architecture.md` | Deeper system map |

---

## Key commands

```bash
# Orchestration (Companion CLI)
wp ngt system inspect | preflight | configure | seed | verify | run-all
wp ngc demo_seed | demo_verify | demo_run_all_journeys | demo_export_evidence

# Headed E2E
cd e2e && $env:BASE_URL='http://localhost:8900'; npm run test:all-headed
```
