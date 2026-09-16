# NextGen Tutors — System Architecture Reference

**Last Updated:** 2026-09-16  
**Audience:** architects, senior developers, security reviewers  
**Canonical code root:** `newuinextgen/`  
**Workspace SoT:** `SOURCE-OF-TRUTH.md` (WeTransfer root)

> Progressive disclosure: start with [§1 Executive Summary](#1-executive-summary), then [§2 Architecture Overview](#2-architecture-overview). Implementation detail lives in [docs/CODEMAPS/](../CODEMAPS/INDEX.md).

---

## 1. Executive Summary

NextGen Tutors is a **South African tutoring marketplace** delivered primarily as a **WordPress modular monolith**:

| Concern | Owner package | Version constant |
|---------|---------------|------------------|
| Presentation | `NextGenTutors-BeyondInfinity/` (TutorFabulous brand) | `BI_VERSION` **2.0.0** |
| Domain / API / agents | `NextGenTutors-Companion/` | `NGC_VERSION` **1.9.19** |
| AI transport governance | `NextGenTutors-AI-Integration/` | plugin header |
| Ops install / health | `NextGenTutors-Plugin-Manager/` | — |
| HTML → pages migration | `NextGenTutors-Html-Importer/` | — |
| Admin control-plane SPA | `NextGenTutors-BeyondMeasure/` | — |
| Durable agent workloads | `services/ngt-agent-gateway/` | Node ≥22 |
| Architecture gate | `rad-platform/` + `architecture/` | CI `rad-architecture` |

**Governing principles**

1. **Theme renders; Companion owns data** — no business writes from theme PHP.  
2. **Sacred contracts** — shortcodes `[ngc_*]`, REST `ngc/v1`, capability IDs.  
3. **Default DENY** — `NGC_Policy_Bridge` on privileged domain mutations.  
4. **Feedstock is not runtime** — do not activate `content-enhancement` zips or old Core plugins.  
5. **RAD in CI** — discover → validate → gate prevents silent architecture decay.

Local staging: WordPress **:8890**, Agent Gateway **:8787**, Ecosystem API **:8790** (overlay).

---

## 2. Architecture Overview

### 2.1 C4-style context

```
┌─────────────┐     ┌──────────────────┐     ┌────────────────────┐
│ Parents /   │────►│ WordPress site   │────►│ PayFast / WooCommerce│
│ Tutors /    │     │ (Theme+Companion)│     │ Amelia / FluentCRM   │
│ Ops admins  │     └────────┬─────────┘     └────────────────────┘
└─────────────┘              │
                             ├──── HMAC ────► Agent Gateway
                             └──── Bearer ──► Ecosystem Platform
```

### 2.2 Container view

```
Browser / Elementor
        │
        ▼
 BeyondInfinity theme
        │  shortcodes / REST
        ▼
 Companion (modular monolith)
   matching · bookings · payments · AI · agents · platform kernel
        │
        ├─ MySQL (wp_* + wp_ngc_*)
        ├─ Agent Gateway (SQLite tasks)
        └─ Optional Talent / Memory / Ecosystem
```

### 2.3 System boundaries

| Inside Companion | Outside Companion |
|------------------|-------------------|
| Matching, bookings, payments, wallet | Theme markup/CSS |
| Capability registry, Policy Bridge, Authz | Html-Importer page writes |
| Secret vault reveal (server) | Plugin-Manager fleet install |
| Agent control plane + tool gateway | Agent Gateway Node process |
| BYOK model registry | AI-Integration transport plugin |

Detailed ownership: [DATA-OWNERSHIP-MATRIX.md](../../architecture/current-state/DATA-OWNERSHIP-MATRIX.md).

---

## 3. Design Decisions (why)

| Decision | Why | Where documented |
|----------|-----|------------------|
| Modular monolith (not many WP plugins for domain) | Avoid REST/schema collisions; single deploy unit | `NGC_Module_Registry`, content-enhancement README |
| Policy Bridge on mutations | Parallel call paths were unauthorized | TD-RAD-001, `authorize_domain()` |
| Hub quiet when Companion active | Dual finance authority risk | TD-RAD-004 |
| Vault `env:` + encrypted options | No secrets in repo/compose defaults | TD-RAD-005 |
| Agent Gateway separate Node service | Isolate untrusted/durable workloads from PHP | ADR agent gateway + `services/` |
| RAD discover/gate in CI | Architecture as executable contracts | ADR-0006 |
| TutorFabulous mount / BeyondInfinity package | Brand vs package identity | `THEME-TUTORFABULOUS.md` |

ADRs live under `architecture/decisions/`.

---

## 4. Core Components

### 4.1 BeyondInfinity theme

- **Edit root:** `NextGenTutors-BeyondInfinity/`  
- **Docker activate:** `nextgentutors-tutorfabulous`  
- **Jobs:** templates, design tokens, UI Library partials, Elementor/NextGen widgets, consume Companion contracts  
- **Codemap:** [theme.md](../CODEMAPS/theme.md)

### 4.2 Companion plugin

- **Bootstrap:** `nextgencompanion.php` → autoload `NGC_*` / `BIA_*` → plugin loader  
- **Internal modules:** matching, payments, ai, integrations, platform (`NGC_Module_Registry`)  
- **Platform kernel:** Capability Registry, Policy Bridge, Authz Matrix, Observability, Vault  
- **Codemaps:** [platform.md](../CODEMAPS/platform.md), [companion-domain.md](../CODEMAPS/companion-domain.md)

### 4.3 Agentic stack

- Control plane (16 seeded agents) → Policy Engine → Tool Gateway  
- High-impact actions → `awaiting_approval`  
- External execution → HMAC → Agent Gateway  
- **Codemap:** [agentic.md](../CODEMAPS/agentic.md)

### 4.4 Ops packages

- **Plugin-Manager** — install/activate stack plugins; deny-list legacy cores  
- **Html-Importer** — one-shot `post_content` only  
- **AI-Integration** — signed/redacted transport; no domain ownership  
- **BeyondMeasure** — admin SPA control plane; does not own scoring/payments

### 4.5 RAD platform

- CLI: discover (incl. lockfiles) → validate → gate  
- Sacred packages / capability JSON under `architecture/`  
- **Codemap:** [rad.md](../CODEMAPS/rad.md)

---

## 5. Data Models

### 5.1 Primary stores

| Store | Owner | Notes |
|-------|-------|-------|
| `wp_ngc_*` tables | Companion | matches, bookings, invoices, wallet, studio, gamification, … |
| `tutors` CPT + taxonomies | Companion | subject, province, grade, learning_format |
| `wp_posts` pages | WP / Html-Importer (write-once) | theme reads |
| Secret vault options / `env:` | Companion | server reveal only |
| Gateway SQLite | Agent Gateway | durable tasks |

### 5.2 Privileged write path

```
Caller → Domain service method
      → NGC_Policy_Bridge::authorize_domain(capabilityId)
      → Authz / requiredPermissions (or trusted_system for WC/ITN/cron)
      → Persist + audit
```

Capabilities of note: `matching.propose`, `booking.create`, `payment.authorize` — see [CAPABILITY-INVENTORY.md](../../architecture/current-state/CAPABILITY-INVENTORY.md).

---

## 6. Integration Points

### 6.1 Theme ↔ Companion

- Shortcodes `[ngc_*]`, `[ng_ui_component]`  
- REST `ngc/v1` (legacy mirror `ngt/v1` — collision risk with old Core)  
- Filters/hooks for UI providers and workflows  

### 6.2 Companion ↔ commerce / CRM

- WooCommerce + PayFast ITN (`NGC_PayFast_Gateway`)  
- Amelia (bookings adapter)  
- FluentCRM / GamiPress / MasterStudy via adapters  

### 6.3 Companion ↔ Agent Gateway

- `NGC_Agent_Gateway_Client` + mu-plugin bridge constants  
- Endpoints: `/health`, `/v1/tasks`, `/v1/mcp/*`  
- MCP execute requires tool approval + allowlist  

### 6.4 Ecosystem Platform

- Compose overlay path `../../ecosystem-platform` from `docker/`  
- Bearer fail-closed; empty production token exits  

---

## 7. Deployment Architecture

```
docker/ compose (COMPOSE_PROJECT_NAME=newuinextgen)
  ├─ WordPress :8890
  │    mounts BeyondInfinity → themes/nextgentutors-tutorfabulous
  │    mounts Companion + sibling plugins
  ├─ MySQL 8
  ├─ Agent Gateway :8787 (optional profile)
  └─ ecosystem overlay :8790 (optional)
```

**Verify**

```powershell
powershell -File scripts/verify-solution.ps1
php NextGenTutors-Companion/tests/run.php
node rad-platform/cli/validate.mjs
node rad-platform/cli/gate.mjs
```

**Release**

```powershell
powershell -File scripts/build-release.ps1
```

Production packaging also under `PRODUCTION/` (release kits; not feedstock for live edit).

---

## 8. Performance Characteristics

| Area | Approach | Notes |
|------|----------|-------|
| Theme motion | CSS / IntersectionObserver / selective GSAP | Prefer documented motion engine; avoid heavy overlays on first paint |
| Companion | Lazy module bootstraps; REST scoped by role | Large admin suites enqueue only on NGC screens |
| Agent Gateway | Durable SQLite; no in-PHP unbounded agent loops | Scale gateway horizontally later if needed |
| UI Library | Data providers cache-friendly queries | No hardcoded roster in partials |

Bottlenecks to watch: N+1 tutor meta queries, Elementor + motion stacks, synchronous LLM calls on admin request path.

---

## 9. Security Model

| Layer | Control | Status |
|-------|---------|--------|
| Capability invoke | `NGC_Policy_Bridge` default DENY | Active on core mutations |
| Agent actions | `NGC_Agent_Policy_Engine` + kill switches | Active |
| Platform admin | `NGC_Authz_Matrix` | Active |
| Secrets | `NGC_Secret_Vault` (`env:` + encrypted options) | Mitigated; external SM optional |
| AI transport | AI-Integration policy gate | Active |
| MCP | SSRF guards + allowlists | Active |
| Tenant | `NGC_Tenant_Context` | Partial |
| Payment settle | `trusted_system` only for WC/cron/ITN | Hardened 2026-09 |

See [SECURITY-BOUNDARIES.md](../../architecture/current-state/SECURITY-BOUNDARIES.md) and [ARCHITECTURE-RISKS.md](../../architecture/current-state/ARCHITECTURE-RISKS.md).

**Do not**

- Commit `.env` / shared secrets  
- Activate `content-enhancement` Core/Plugin zips beside Companion  
- Trust anonymous uid=0 as payment authority  

---

## 10. Appendices

### A. Glossary

| Term | Meaning |
|------|---------|
| Sacred package | Contracted capability/package boundary enforced by RAD |
| Policy Bridge | Capability-id authorization facade over Authz + registry |
| Quiet-domain | Hub defers finance/domain when Companion is active |
| Feedstock | Reference trees/zips not for live activation |
| TutorFabulous | Brand / Docker theme slug for BeyondInfinity package |

### B. Reading paths

| Role | Path |
|------|------|
| Developer onboarding | [SYSTEM-OVERVIEW.md](../SYSTEM-OVERVIEW.md) → [DEVELOPER-GUIDE.md](../DEVELOPER-GUIDE.md) → CODEMAPS |
| Architect review | This document → [TECHNICAL-DEBT-REGISTER.md](../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md) → ADRs |
| Ops / agents | [GUIDES/AGENTIC-HOW-TO-USE.md](../GUIDES/AGENTIC-HOW-TO-USE.md) → [agentic.md](../CODEMAPS/agentic.md) |

### C. Related indexes

- Docs hub: [docs/README.md](../README.md)  
- Codemaps: [docs/CODEMAPS/INDEX.md](../CODEMAPS/INDEX.md)  
- SOLID package contract: [ARCHITECTURE.md](../../ARCHITECTURE.md)  
- Deletion / quarantine log: [docs/DELETION_LOG.md](../DELETION_LOG.md)  

### D. Document maintenance

Update this reference when:

- Capability or Policy Bridge coverage changes  
- New deployable package or sidecar is added  
- RAD gate contracts change  
- Version constants (`BI_VERSION` / `NGC_VERSION`) bump for a release  

Prefer editing **canonical** trees only (`SOURCE-OF-TRUTH.md`).
