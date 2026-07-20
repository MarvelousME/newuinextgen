# Operations Documentation

## Operational Model

- Daily health check via cron (`ngc_daily_health_check`).
- Verification checks evaluate schema, routes, roles, shortcodes, demo payloads.
- Self-healing can recreate missing tables/roles/pages.

## Core Operational Tasks

| Task | Actor | Tooling | Status |
|---|---|---|---|
| Review platform health | Admin/Ops | NextGen Health + Platform screens | VERIFIED |
| Approve/reject tutor applications | Admin/Support | NextGen Operations/REST | VERIFIED |
| Process tutor payouts | Finance/Admin | Payouts screen/REST | VERIFIED |
| Monitor workflow failures | Admin/Ops | Workflows logs + retry queue | VERIFIED |
| Validate real-data metrics | Admin | Platform analytics dashboard | VERIFIED |
| Run repair routines | Admin/DevOps | admin repair endpoint/screens | VERIFIED |

## SLO/Operational Indicators

- Workflow failure backlog size.
- Verification pass/fail status.
- Booking conflict rate.
- Payment and invoice processing health (where Woo active).
- Calendar availability response health.

## Runbook (High Level)

1. Check verification dashboard.
2. Inspect workflow runs and retry queue.
3. Inspect audit log for recent failures.
4. Run self-healing where safe.
5. Escalate external dependency failures (Woo/Amelia/FluentCRM/LMS).

