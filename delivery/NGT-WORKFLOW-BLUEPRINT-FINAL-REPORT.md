# NGT Workflow Blueprint — Migration Execution Report

**Date:** 2026-08-16  
**Status:** FOUNDATION + JOURNEYS + **Conflict→Match alignment** (code)  
**Authority:** `NGC_Workflow_Authority` (= ecosystem-workflow)

Approval: **APPROVED & MIGRATE** · Conflicts: **aligned to Match** (see `NGT-WORKFLOW-RULE-CONFLICTS.md`)

---

## What shipped

### Foundation
- `includes/journeys/` — events, business rules, state machines, dual-fire guard, registry, bootstrap, listing eligibility
- `includes/ports/` — CRM, Gamification, Learning projection ports
- Autoload paths + `NGC_Journey_Bootstrap` in module list
- Architecture: `architecture/capabilities/ecosystem-workflow.json`, `architecture/contracts/ngt-journey-events.v1.json`

### Learner
- `NGC_Learner_Booking_Confirmation_Workflow` on `ngc_payment_settled` → authority queue
- Idempotent `NGC_First_Booking_Rule` (100 pts + Beginner; meta + idempotency)
- CRM active-customer projection via port (not AutomatorWP)

### Tutor
- `NGC_Tutor_Go_Live_Workflow` after `TUTOR_APPROVED`
- `NGC_Tutor_Listing_Eligibility` — mandatory ID + Background + Training; auto-publish OFF
- Hub `add_user_role` **skipped** when Companion owns tutor journey

### Safety
- Canonical events on case create; critical SLA = 2h via `ngt.safety.high_priority_response_sla`
- Recording **OFF** (`ngt.session.recording_enabled` = 0) — Match

### Gamification Match
- Session: Student **50** / Tutor **25** (`session_completed` / `session_completed_tutor`)
- Popular = **50 reviews**; Highly Rated ≥4.8 + 20 reviews
- Milestones: Quick Learner / Scholar / Master / Specialist / Elite Earner
- Meeting SoR documented as **Jitsi** (not Zoom)

### Payout
- `ngc_payout_create_amount` filter → minimum → `PAYOUT_CARRY_FORWARD`
- Platform fee helper; cycle event on monthly cron; EFT = UNVERIFIED

### Dual-fire / AutomatorWP
- Core events blocked when authority ON + `ngt.journey.disable_automatorwp_core`
- Studio importer: orchestrator + journey graphs import as **draft** (VIEW)

### Tests
- Fitness: `php NextGenTutors-Companion/scripts/run-journey-fitness.php` → **14/14 PASS**
- `node rad-platform/cli/gate.mjs` → **PASS**

---

## Still PARTIAL / UNVERIFIED (honest)

| Item | Status |
|---|---|
| Headed Playwright learner/tutor/safety E2E | **not run** |
| Real PayFast / Amelia / EFT | UNVERIFIED as REAL |
| Amelia reminder config disable in live DB | Ownership MATCH; config UNVERIFIED |
| Parent observer in Jitsi | UNVERIFIED |
| Full AutomatorWP DB recipe deactivation | Guard in code; live recipe rows not scanned |
| Workflow Studio canvas UX | Journey seeds as draft graphs only |
| Payout scheduler reading `ngt.payout.timezone` end-to-end | PARTIAL |

---

## Quality gate answers (sample)

1. Parent E2E book — **PARTIAL** (code path; headed not run)  
2. One confirmation — **PARTIAL** (authority + dual-fire guard)  
3. First booking once — **PROVEN** in code (meta + idempotency + Match 100)  
7. Application outside FluentCRM — **PARTIAL** (orchestrator still SoR)  
9. GamiPress cannot make tutor live — **PROVEN** (listing auto-publish false)  
11. Amelia cannot create earnings alone — unchanged / **PARTIAL**  
14–15. Fee/min configurable — **PROVEN** via `NGC_Business_Rules`  
16. EFT verified — **UNVERIFIED**  
20. AI only flags — **PROVEN** (safeguarding signal model)  
26. AutomatorWP dual disabled — **PARTIAL** (filter; not DB purge)  
27. Single authority — **PROVEN** (`NGC_Workflow_Authority`)

---

## Rollback

1. Set `ngt.journey.learner_enabled` / `tutor` / `safety` options off (via `NGC_Business_Rules::set`)  
2. Set `ngt.journey.disable_automatorwp_core` to `0` only if needed  
3. Revert journey package / gamification Match commits if required  
