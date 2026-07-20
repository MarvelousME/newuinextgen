# Workflow Documentation

Canonical visual spec: `NextGenTutors-BeyondInfinity/docs/enterprise-blueprint/diagrams/workflows/WF-*.svg`

Machine-readable manifest and gap report (regenerate after SVG or runtime changes):

```powershell
powershell -File scripts/run-flow-audit.ps1
```

- Manifest: `docs/workflows/flow-manifest.json`
- Gap report: `docs/workflows/FLOW-GAP-REPORT.md`

Playwright E2E specs use **blueprint `WF-NN` IDs** in `e2e/workflows/blueprint-wf*.spec.ts`.

## Workflow Catalog (WF-01 to WF-25)

| WF | Name | Trigger | Preconditions | Actors | Sequence (high-level) | Decisions/Exceptions | Retries | Notifications | Audit Events | Outputs | Verification | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| WF-01 | Parent Registration | parent register form | valid email + nonce | Parent/System/Admin | submit > register > parent dashboard data | duplicate email, invalid payload | workflow retry queue | welcome + admin email | parent registered | parent user + learner links | form + REST checks | VERIFIED |
| WF-02 | Student Registration | student register form | valid email | Student/System/Admin | submit > register > optional LMS mapping | duplicate email | retry queue | student/admin emails | student registered | student profile | registration checks | VERIFIED |
| WF-03 | Tutor Registration | become tutor form | required profile fields | Applicant/System/Admin | submit app > queue > workflow | invalid app data | retry queue | applicant/admin email | tutor registered | application row | tutor app list | VERIFIED |
| WF-04 | Tutor Approval | admin action/REST | application pending + mapped user | Admin/System | approve > role/meta > integrations | missing user/plugin/API key | retry queue | tutor/admin/integration failure emails | tutor approved | active tutor | workflow logs | VERIFIED |
| WF-05 | Tutor Rejection | admin action/REST | application pending | Admin/System | reject > notes > notify | invalid app id | retry queue | rejection/resubmit emails | tutor rejected | rejected status | workflow logs | PARTIAL |
| WF-06 | Tutor Resubmission | resubmit endpoint | rejected application | Tutor/Admin/System | update fields > pending > notify | stale state | retry queue | resubmit emails | tutor resubmitted | pending app | workflow logs | PARTIAL |
| WF-07 | Tutor Matching | match create API/form | learner context | Parent/Support/System | request > score tutors > persist | no candidates | n/a | optional intake notices | match requested | ranked matches | match API checks | PARTIAL |
| WF-08 | Manual Matching | assign endpoint | support/admin cap | Support/Admin | choose tutor > assign | invalid tutor/state | n/a | optional | match assigned | assigned row | assign route tests | PARTIAL |
| WF-09 | Automated Matching | POST /ngc/v1/match/smart | tutor data available | System | compute score > rank list | sparse data | n/a | none (use WF-08) | scoring context | ranked list | REST smart match | DEFERRED |
| WF-10 | Booking | booking API/calendar CTA | available slot + auth | Parent/Student/Tutor/System | create > conflict check > persist > dispatch | slot conflict | n/a | booking flows | booking created | booking row | bookings API tests | PARTIAL |
| WF-11 | Payment | Woo order complete | Woo installed/order metadata | Parent/System/Finance | order complete > invoice/wallet/events | Woo unavailable | n/a | payment notifications | payment completed | paid markers | WC UAT | PARTIAL |
| WF-12 | Invoice | order/payment linkage | booking/order mapping | System/Finance | generate invoice row | Woo absent | n/a | invoice notice | invoice issued | invoice record | finance UAT | PARTIAL |
| WF-13 | Refund | Woo refund hook | Woo order exists | Finance/System | refund > wallet adjust > event | Woo absent | n/a | refund notifications | payment refunded | refund trace | WC UAT | PARTIAL |
| WF-14 | Cancellation | booking status transition | booking exists | Parent/Tutor/Support | cancel > dispatch | invalid status | n/a | cancellation alerts | booking cancelled | cancelled booking | booking status tests | PARTIAL |
| WF-15 | Session Completion | booking status complete | booking confirmed | Tutor/System | complete > log session > earnings | invalid booking state | n/a | lesson notifications | lesson completed | session log + earning | completion tests | PARTIAL |
| WF-16 | Tutor Payout | bi-weekly cron batch | pending earnings | Finance/Admin/System | process payout > update earnings | no pending earnings | n/a | payout processed notices | payout processed | payout row | payout tests | PARTIAL |
| WF-17 | Parent Review | reviews endpoint | parent booking context | Parent/System | submit review > upsert rating | invalid rating/tutor | n/a | optional | review created | review + rating rows | reviews tests | PARTIAL |
| WF-18 | Tutor Rating | review processing | review exists | System | aggregate/avg rating updates | sparse reviews | n/a | none | rating updated | tutor rating KPI | rating checks | PARTIAL |
| WF-19 | Support | support form | valid submission | User/Support/System | submit > queue > triage | form validation errors | n/a | support ack/escalation | support submitted | support record in queue | form flow checks | VERIFIED |
| WF-20 | Escalation | support escalation workflow | support issue requires escalation | Support/Admin/System | escalate > notify admin | missing escalation handler | retry queue | escalation notifications | support escalated | audit/workflow run | workflow verify | PARTIAL |
| WF-21 | CRM | workflow adapters | external plugin active | System/Admin | upsert/list/tag mapping | plugin unavailable/api mismatch | retry queue | sync failure emails | CRM sync events | CRM contact state | adapter verify | PARTIAL |
| WF-22 | Email | template adapter send | template key/context | System/Admin | render template > wp_mail | mail transport failure | retry queue | email sent/failed | email audit | delivered/failed mail | test send | VERIFIED |
| WF-23 | WhatsApp | OpenWA bridge | phone configured | System | outbound message | transport failure | retry queue | WhatsApp notice | message sent | delivery log | openwa verify | PARTIAL |
| WF-24 | Notification / Reminders | session.scheduled | Amelia session exists | System/Tutor/Parent | queue 24h/1h/15m reminders | delivery failure | retry queue | email/SMS/WhatsApp | reminder.sent | delivery status | reminder cron | PARTIAL |
| WF-25 | Dashboard | dashboard REST requests | auth + role for secured views | All roles/System | fetch role KPIs > render cards | missing data/routes | n/a | none | optional health check | dashboard payloads | route/UI checks | PARTIAL |

## BPMN/Sequence Diagram Artifacts

- Blueprint SVGs: `NextGenTutors-BeyondInfinity/docs/enterprise-blueprint/diagrams/workflows/`
- Suite overlay: `diagrams/svg/enterprise-diagram-suite.svg`
- Legacy AutomatorWP templates (deprecated): `automations/README.md`

## E2E spec map

| Playwright spec | Blueprint WFs covered |
|-----------------|----------------------|
| `blueprint-wf01-wf02-wf19-registration.spec.ts` | WF-01, WF-02, WF-19 |
| `blueprint-wf03-tutor-registration.spec.ts` | WF-03 |
| `blueprint-wf07-wf10-booking-payment.spec.ts` | WF-07, WF-10 |
| `blueprint-wf24-reminders-homepage.spec.ts` | WF-24 (+ homepage touchpoints) |
| `blueprint-wf16-wf17-wf18-reviews-pricing.spec.ts` | WF-16, WF-17, WF-18 |
