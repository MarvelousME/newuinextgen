# Automations — Deployment Templates (Deprecated Runtime Path)

These JSON files are **AutomatorWP v2 import recipes** and FluentCRM sequence definitions for sites that run the full Fluent stack (FluentForms, FluentCRM, Amelia, WooCommerce, GamiPress).

## Status: superseded by NextGen Companion

**Do not treat this folder as the live workflow engine.** Production behaviour is implemented in:

| Layer | Path |
|-------|------|
| Companion orchestrator | `NextGenTutors-Companion/includes/workflows/` |
| Theme workflow pack | `NextGenTutors-BeyondInfinity/content/nextgen-workflow-pack.json` |
| Integrate specs | `NextGenTutors-Companion/integrate/workflow-*.json` (workflow-01…10) |
| Blueprint SVGs | `NextGenTutors-BeyondInfinity/docs/enterprise-blueprint/diagrams/workflows/` |

## Files

| File | Blueprint WF | External trigger | Companion equivalent |
|------|--------------|------------------|----------------------|
| `automation-1-lead-intake.json` | WF-07 | FluentForm submit | `find_tutor.submitted` → `NGC_Matching` |
| `automation-2-booking-confirmed.json` | WF-10 | Amelia approved | `amelia.booking.created` → `NGC_Bookings` |
| `automation-3-payment-complete.json` | WF-11 | Woo order completed | `payment.received` → `NGC_Payments` |
| `automation-4-tutor-registration.json` | WF-03 | Become-tutor form | `TUTOR_REGISTERED` orchestrator |
| `automation-5-cancellation.json` | WF-14 | Amelia cancel | `booking.cancelled` theme pack |
| `fluentcrm-sequence-*.json` | WF-21 | CRM sequences | `NGC_Fluentcrm_Adapter` |

## Import instructions (optional legacy stack only)

1. Install AutomatorWP + FluentCRM + configured form/booking IDs.
2. Replace every `REPLACE_WITH_*` placeholder with live IDs from your environment.
3. Import each JSON via **AutomatorWP → Tools → Import**.

If Companion is active, prefer **WP Admin → Workflows** and the integrate pack instead of duplicating logic here.

```powershell
# Import all integrate/*.json specs into Companion store
wp ngc workflow_import
# Or via Docker:
powershell -File docker/scripts/install-phase2-stack.ps1
```

## Audit

```powershell
powershell -File scripts/run-flow-audit.ps1
```

See `docs/workflows/FLOW-GAP-REPORT.md` for SVG ↔ runtime coverage.
