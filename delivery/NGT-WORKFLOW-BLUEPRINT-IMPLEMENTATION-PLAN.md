# NGT Workflow Blueprint — Implementation Plan

**Date:** 2026-08-16  
**Status:** AWAITING APPROVAL — no production source changed  
**Brownfield rule:** Extend Companion + rad-platform; do not invent a second executor; do not claim `ecosystem-workflow` package already exists.

---

## 1. Target architecture (mapped to repo)

```text
USER JOURNEY (Learner | Tutor | Safety)
        ↓
CANONICAL DOMAIN EVENT (aliases over ngc_*/ngt.*)
        ↓
ECOSYSTEM WORKFLOW  ≡  NGC_Workflow_Authority + journey workflow defs
        ↓
BUSINESS RULE / POLICY
        ↓
APPLICATION COMMAND / SERVICE
        ↓
PORT (new thin interfaces)
        ↓
ADAPTER
        ↓
PLUGIN / PROVIDER
```

Cross-cutting: idempotency, outbox/queue, retry, DLQ, audit, correlation, IAM, observability, notifications, business rules options.

---

## 2. Exact modules / files to change (after approval)

### Slice foundation (1–4)

| Path | Change |
|---|---|
| `architecture/capabilities/ecosystem-workflow.json` | **New** capability manifest |
| `architecture/capabilities/ngt-journeys.json` | **New** learner/tutor/safety capability ids |
| `architecture/contracts/ngt-journey-events.v1.json` | Canonical event catalog |
| `NextGenTutors-Companion/includes/journeys/` | **New** package folder |
| `.../journeys/class-ngc-journey-events.php` | Canonical event names + emit/alias |
| `.../journeys/class-ngc-journey-state-machines.php` | State defs + transitions |
| `.../journeys/class-ngc-business-rules.php` | Options for fee, SLA, rewards, etc. |
| `.../journeys/class-ngc-journey-workflow-registry.php` | Registers journey workflows with authority |
| `includes/class-ngc-core-loader.php` | Load journeys |
| `includes/platform/class-ngc-workflow-authority.php` | Accept journey workflow actions (no parallel executor) |

### Ports (thin)

| Path | Change |
|---|---|
| `includes/ports/interface-ngc-booking-provider.php` | **New** |
| `includes/ports/interface-ngc-commerce-provider.php` | **New** |
| `includes/ports/interface-ngc-gamification-provider.php` | **New** |
| `includes/ports/interface-ngc-crm-projection.php` | **New** |
| `includes/ports/interface-ngc-learning-provider.php` | **New** |
| `includes/ports/interface-ngc-support-case-provider.php` | **New** |
| `includes/ports/interface-ngc-payout-provider.php` | **New** |
| Existing adapters | Implement ports; keep `NGC_Integration_Adapter` |

### Learner (5–7)

| Path | Change |
|---|---|
| `class-ngc-marketplace.php` | Ranking policy extraction; no direct Amelia DB |
| `class-ngc-payments.php` / session orchestrator | Emit `PaymentCaptured` / confirmation workflow |
| `class-ngc-session-reminders.php` | Idempotency keys; canonical reminder events |
| `gamification/*` | `FirstBookingRule` idempotent |
| FluentCRM adapter | Projection-only on confirmation |
| AutomatorWP | Shadow/disable payment-complete recipe |

### Tutor (8–14)

| Path | Change |
|---|---|
| Registration / tutor lifecycle | Canonical events; application file ACL |
| Listing eligibility policy | **New** class; gated until RC-02 confirmed |
| Earnings / reviews / payout | Ledger fields; fee/min rules; timezone |
| Payout provider | Manual stub + UNVERIFIED EFT |

### Safety (15–21)

| Path | Change |
|---|---|
| `class-ngc-safeguarding.php` | Canonical events; configurable SLA; case states |
| AI / fraud bridge | Flag only |
| FluentSupport adapter | Case transport |
| Recording | Feature flag OFF until RC-04 |
| Observer | Capability probe; no fake UI |

### Studio / API / observability (22–25)

| Path | Change |
|---|---|
| Workflow Studio discovery | Seed three journey graphs from registry |
| REST/Abilities | Journey status + rule explain |
| Metrics/audit | correlation_id on journey steps |
| Architecture fitness tests | Section 76 of prompt |

### Explicit non-goals until policy confirm

- Enabling product session recording  
- Auto-EFT bank transfers  
- Resolving Popular Tutor 50 bookings vs reviews  
- Publishing tutors on “any 3 badges” without mandatory subset confirmation  

---

## 3. Implementation slices (execute in order after APPROVED)

```text
Slice 1  — Journey discovery freeze (this delivery set)
Slice 2  — Canonical event map + aliases
Slice 3  — Journey state machines
Slice 4  — Trigger ownership (authority ON; dual-emitter inventory)
Slice 5  — Learner domain/workflow (PaymentCaptured confirmation)
Slice 6  — Learner providers (CRM/GamiPress/LMS/Notify ports)
Slice 7  — Learner E2E (headed)
Slice 8  — Tutor application
Slice 9  — Tutor verification
Slice 10 — Tutor onboarding/listing (policy-gated)
Slice 11 — Tutor performance rules
Slice 12 — Earnings ledger hardening
Slice 13 — Payout cycle + rules
Slice 14 — Tutor E2E
Slice 15 — Safeguarding facts
Slice 16 — Listing safety policy (after RC-02)
Slice 17 — Session safety / report
Slice 18 — AI flag adapter
Slice 19 — Safety escalation + SLA
Slice 20 — Compliance records (consent/vendor/DPIA scheduling)
Slice 21 — Safety E2E (synthetic)
Slice 22 — Workflow Studio journey graphs
Slice 23 — API / Abilities
Slice 24 — Observability
Slice 25 — Final regression + gate
```

Migration for each AutomatorWP/Hub duplicate:

```text
Discover → Shadow → Compare outcome → Disable legacy → Enable Ecosystem → Verify
```

Never leave `ecosystem-workflow` + AutomatorWP both executing the same core process.

---

## 4. Data migrations

| Migration | Notes |
|---|---|
| Event alias table/option | Map old `ngc_*` / `ngt.*` → canonical without breaking listeners |
| Business rules options | Seed defaults; do not overwrite custom |
| Earnings ledger columns | Additive columns only |
| Safeguarding status map | Map existing statuses → expanded SM carefully |
| AutomatorWP deactivate flags | Option list of disabled recipe keys |
| Discovery snapshot for Studio | Journey graphs as VIEW graphs (not auto-publish) |

No destructive drops. Idempotent seeders only.

---

## 5. Rollback strategy

1. Feature flags per journey workflow (`ngc_journey_learner_v1`, etc.) default OFF until E2E green.  
2. `ngc_workflow_authority_v1` remains ON; kill switch only for emergency (documented dual-fire risk).  
3. Re-enable specific AutomatorWP recipe only if journey flag OFF.  
4. DB migrations additive — reverse by ignoring new columns.  
5. Docker: reload plugins; no theme rewrite required for domain slices.

---

## 6. Definition of Done (blueprint program)

- Three journeys runnable under authority with ports/adapters  
- Dual AutomatorWP cores disabled for migrated flows  
- Rule conflicts either resolved in writing or feature-gated  
- Headed E2E evidence for learner/tutor/safety (synthetic safety)  
- DB + event + audit evidence packs  
- Fitness tests green  
- Final report answers Q1–Q30 with PROVEN / PARTIAL / UNVERIFIED only  

---

## 7. Relation to prior Workflow Studio plan

`delivery/WORKFLOW-IMPLEMENTATION-PLAN.md` (visualizer) remains valid as **VIEW/LAYOUT** over this journey registry.  
This blueprint plan owns **journey semantics**; Studio visualizes them. Do not merge into a second executor.
