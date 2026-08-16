# NGT Workflow E2E Plan

**Date:** 2026-08-16  
**Status:** Plan only — not executed  
**Stack:** Playwright headed (project e2e), demo/sandbox providers, Docker WP when available

---

## Evidence required per major step

```text
browser screenshot
HTTP/API
Ability (if used)
workflow execution / authority job
rule decision
DB state (redacted)
event state
audit record
notification state
```

HTTP 200 alone is insufficient.

Provider labels: FAKE | CONTRACT | SANDBOX | REAL | UNVERIFIED.

---

## Learner E2E

```text
Parent login/register
  → Find Tutor → filter → open profile
  → book → checkout → PayFast sandbox
  → booking confirmed → dashboard
  → session (join if provisioned)
  → complete (demo/admin)
  → rating → progress/rewards
```

Assert:

1. Exactly one confirmation workflow after payment  
2. First-booking points once  
3. Student enrollment idempotent  
4. Reminders once per type (if time mocked)  
5. One post-session workflow  

Artifacts: `delivery/NGT-LEARNER-E2E-EVIDENCE.md` (after run).

---

## Tutor E2E

```text
Tutor applies → admin verifies → onboarding
  → listing (if policy allows)
  → receive booking → teach → complete
  → earning → performance check → payout create
```

Assert:

1. Application SoR outside FluentCRM  
2. Verification not granted by GamiPress alone  
3. Earnings linked to payment/session evidence  
4. Same earning not in two payouts  
5. EFT marked UNVERIFIED if no bank adapter  

---

## Safety E2E (synthetic only)

```text
Tutor verification facts → listing eligibility
  → session → synthetic SafetyFlagRaised
  → case → notification → optional restriction → audit
```

No harmful real-world content. AI may only flag.

Assert:

1. AI cannot close case  
2. SLA timer measurable  
3. Observer mode: PROVEN or UNVERIFIED PROVIDER CAPABILITY  
4. Recording remains OFF unless RC-04 resolved  

---

## Architecture fitness tests (automated)

```text
test_learner_workflow_does_not_depend_directly_on_fluentcrm
test_tutor_domain_does_not_depend_on_gamipress
test_safeguarding_does_not_depend_directly_on_fluent_support
test_payment_domain_does_not_depend_on_payfast_sdk
test_workflow_is_single_automation_authority
test_no_duplicate_booking_welcome_workflow
test_no_duplicate_session_rating_request
test_no_duplicate_no_show_compensation
test_no_duplicate_payout_execution
```

---

## Environments

| Env | Use |
|---|---|
| Local Docker `:8890` | Headed E2E |
| Demo mode | Relational demo seeds |
| PayFast sandbox | Payment capture |
| Amelia | Optional; skip if inactive with PARTIAL |

Do not claim REAL provider verified without exercise.
