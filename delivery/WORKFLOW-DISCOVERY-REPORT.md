# Workflow Discovery Report

**Status:** PHASE 1 + 3 COMPLETE (static repository analysis)  
**Date:** 2026-08-14  
**Method:** Source tracing of PHP/JS/JSON. Database row counts and live plugin activation were **not** queried. Runtime labels are therefore **UNVERIFIED** unless noted as static-proven.

**Formula used later (not applied to live percentages here):**  
- Node/edge confidence: `PROVEN` only if a callable is registered in source (or a config row exists and the consumer class exists).  
- `CHAIN-PROVEN` only after a full path has runtime telemetry (Phase 11/16).  
- Coverage % will be `(nodes+edges with PROVEN or CONFIGURED) / (all nodes+edges in that workflow graph)`. Missing telemetry must be marked unavailable, not zero-filled as if measured.

---

## Executive inventory (static)

| Category | Count (static) | Notes |
|---|---:|---|
| Orchestrator domain workflows | 7 | `TUTOR_*`, `PARENT_REGISTERED`, `STUDENT_REGISTERED`, `CHILD_REGISTERED` |
| Integrate JSON specs | 10 | `integrate/workflow-01` … `workflow-10` |
| Hub bundled workflows | 10 | `nextgen-automation-hub/config/workflows.json` |
| Studio trigger catalog keys | 38 | `NGC_Studio_Triggers::catalog()` |
| Companion event map entries | 32 | `NGC_Workflows::$event_map` |
| Durable queue names | 5 | default, workflow, safeguard, fraud, notify |
| Bit Flows | 0 | Not in repository |
| `studio.bundle.js` (React SPA) | 0 | Fallback JS only |

Classification key: **VERIFIED** (callable path in source) · **PARTIALLY VERIFIED** (spec + some code, gaps) · **CONFIGURED BUT INACTIVE** (JSON/option, runtime not proven) · **DUPLICATE** (same mutation reachable from >1 runtime) · **UNVERIFIED** (described but not traced) · **ORPHANED** (definition with no producer).

---

## 1. Registration / tutor lifecycle (orchestrator)

### WF-ORCH-TUTOR-REGISTERED

| Field | Value |
|---|---|
| Workflow ID | `TUTOR_REGISTERED` |
| Name | Tutor registered |
| Business purpose | After become-a-tutor registration: CRM, audit, admin notification |
| Source file | `NextGenTutors-Companion/includes/workflows/class-ngc-workflow-orchestrator.php` |
| Class / method | `NGC_Workflow_Orchestrator::wf_tutor_registered` |
| WordPress hook | Indirect: `NGC_Tutor_Lifecycle` / `NGC_Registration` → `NGC_Workflow_Orchestrator::run` |
| Trigger | Tutor application / user create path |
| Conditions | Adapter results; retry via `NGC_Workflow_Retry_Queue` on failure |
| Actions | FluentCRM contact, email/audit adapters |
| Integrations | FluentCRM, email, audit |
| Downstream | `ngc_workflow_completed` |
| Queues | Authority `workflow` queue if flag on; else in-request |
| Retries | `NGC_Workflow_Retry_Queue` + `ngc_workflow_retry_cron` |
| DLQ | Only if authority path uses durable queue max-attempts |
| Idempotency | Authority `idempotency_key` when queued |
| REST | None dedicated |
| RBAC | Admin review later (`ngc_review_tutors`) |
| Execution authority | Orchestrator → Authority |
| Evidence | Static method map lines 101–108 |
| Status | **VERIFIED** (code path) · runtime **UNVERIFIED** |

### WF-ORCH-TUTOR-APPROVED / REJECTED / RESUBMITTED

| ID | Method | Integrations (from adapters()) | Status |
|---|---|---|---|
| `TUTOR_APPROVED` | `wf_tutor_approved` | Amelia, MasterStudy, email, verification, Jitsi | **PARTIALLY VERIFIED** — handlers exist; live Amelia/LMS writes need DB evidence |
| `TUTOR_REJECTED` | `wf_tutor_rejected` | Email / CRM / audit | **VERIFIED** (code) |
| `TUTOR_RESUBMITTED` | `wf_tutor_resubmitted` | CRM / email | **VERIFIED** (code) |

Producer: `NextGenTutors-Companion/includes/class-ngc-tutor-lifecycle.php`.

### WF-ORCH-PARENT / STUDENT / CHILD

| ID | Producer | Status |
|---|---|---|
| `PARENT_REGISTERED` | `NGC_Registration` | **VERIFIED** (code) |
| `STUDENT_REGISTERED` | `NGC_Registration` | **VERIFIED** (code) |
| `CHILD_REGISTERED` | `NGC_Registration` | **VERIFIED** (code) |

`NGC_Workflows::on_form_submitted` **explicitly skips** `become_tutor`, `parent_register`, `student_register` so the theme pack does not also run those forms. **PROVEN** skip.

---

## 2. Integrate JSON specs (WF-01 … WF-10)

Specs live in `NextGenTutors-Companion/integrate/`. Executor: `NGC_Workflow_Integrate_Executor` (can call `NGC_Workflow_Orchestrator::run` for bound events).

| Spec ID | Name | Trigger (as written) | Companion modules named | Code-backed? | Status |
|---|---|---|---|---|---|
| `workflow-01-tutor-onboarding` | Tutor Onboarding | Form submit / approve | AutomatorWP, FluentCRM, Amelia, LMS | Orchestrator + lifecycle | **PARTIALLY VERIFIED** — JSON is a blueprint; orchestrator implements a subset |
| `workflow-02-booking-payment` | Booking & Payment | WC paid OR Amelia paid | Woo, PayFast, Amelia, CRM, LMS | `NGC_Payments` + session orchestrator | **PARTIALLY VERIFIED** — do not assume Peach/calendar/LMS shell unless traced |
| `workflow-03-reminder-notification` | Reminders | Upcoming session | Amelia, Notifyer, CRM | `NGC_Session_Reminders` | **VERIFIED** (class + cron `ngc_process_reminder_queue`) |
| `workflow-04-review-rating` | Review & Rating | Session completed | Amelia, GamiPress, CRM | `NGC_Reviews` exists; 2-hour wait / GamiPress **UNVERIFIED** | **PARTIALLY VERIFIED** |
| `workflow-05-tutor-payout` | Tutor Payout | Monthly 02:00 | Amelia, Woo, CRM | `NGC_Payout_Scheduler` (`ngc_monthly_payout_batch`, `ngc_biweekly_payout_batch`) | **PARTIALLY VERIFIED** — cron exists; “1st at 02:00” schedule **UNVERIFIED** without cron args |
| `workflow-06-lead-intake` | Lead Intake | find_tutor form | Matching | `NGC_Workflows` + matching | **VERIFIED** (dispatch not skipped) |
| `workflow-07-booking-confirmed` | Booking Confirmed | Amelia approved / NGC confirmed | Bookings, Amelia | `ngc_booking_confirmed` → session orchestrator | **VERIFIED** |
| `workflow-08-payment-complete` | Payment Completed | WC completed / PayFast ITN | Payments, PayFast, invoices, wallet | `NGC_Payments::settle` + ITN | **VERIFIED** |
| `workflow-09-automated-matching` | Auto matching | `match.proposed` + score | Matching | option `ngc_auto_assign_enabled` | **PARTIALLY VERIFIED** |
| `workflow-10-cancellation` | Cancellation | Status → cancelled | Bookings, Amelia | `ngc_booking_cancelled` | **VERIFIED** (hook exists); refund branch separate |

JSON `actors` listing AutomatorWP / Notifyer / Taurus Observer is **CONFIGURED** documentation, not proof those plugins execute the step.

---

## 3. Booking / payment / session (domain hooks)

### WF-PAY-SETTLE

| Field | Value |
|---|---|
| Workflow ID | `ngc_payment_settled` |
| Trigger | `woocommerce_payment_complete`, `woocommerce_order_status_completed` → `NGC_Payments`; PayFast ITN `woocommerce_api_ngc_payfast_itn` |
| Conditions | Order meta `ngc_payment_settled` skip; ITN signature/replay (`NGC_PayFast_ITN`) |
| Actions | Audit, conversions, `do_action('ngc_payment_settled')` |
| Downstream | `NGC_Session_Orchestrator::on_payment_settled`, `NGC_Ledger`, `NGC_Domain_Event_Bridge` |
| Idempotency | Order meta + `NGC_Idempotency` + ITN replay checks |
| Status | **VERIFIED** |

### WF-SESSION-PROVISION

| Field | Value |
|---|---|
| Class | `NGC_Session_Orchestrator` |
| Triggers | `ngc_booking_confirmed`, `ngc_payment_settled`, `ngc_payment_refunded`, `woocommerce_order_status_failed`, `ngc_booking_cancelled`, `ngc_booking_completed` |
| Purpose | Single `EnsureSessionProvisioned` across booking + commerce + LMS + meeting |
| Status | **VERIFIED** (hooks registered) · meeting/LMS writes **UNVERIFIED** without DB |

### WF-REMINDERS

| Field | Value |
|---|---|
| Class | `NGC_Session_Reminders` |
| Cron | `ngc_process_reminder_queue` |
| Offsets | 24h, 1h, 15m |
| Extra triggers | `ngc_workflow_dispatched`, `ngc_booking_created` |
| Status | **VERIFIED** |

### WF-PAYOUT

| Field | Value |
|---|---|
| Class | `NGC_Payout_Scheduler` |
| Crons | `ngc_monthly_payout_batch`, `ngc_biweekly_payout_batch` |
| Status | **VERIFIED** (scheduler) · Hub also has `ngt_monthly_payout_calculation` in observability — **DUPLICATE risk** |

---

## 4. Automation Hub (bundled JSON)

File: `nextgen-automation-hub/config/workflows.json`  
Engine: `NGT_Hub_Workflows`  
Hooks: `user_register`, `woocommerce_order_status_completed`, health cron `ngt_daily_health_check` (skipped if Companion delegate active).

| Key | Trigger event | Actions (types) | Status |
|---|---|---|---|
| `parent_find_tutor_intake` | `ngt.find_tutor.submitted` | log, RTM, wp_mail_admin | **CONFIGURED** |
| `tutor_application_review` | `ngt.tutor_application.submitted` | log, RTM, mail | **CONFIGURED** |
| `new_user_role_routing` | `wp.user_registered` | log, RTM | **DUPLICATE** vs theme `user_register` |
| `woocommerce_payment_completed` | `woocommerce.order.completed` | log, RTM | **DUPLICATE** vs `NGC_Payments` (notifications vs settle) |
| `amelia_booking_created` | `amelia.booking.created` | log, RTM | **CONFIGURED** |
| `lesson_completed_awards` | `ngt.lesson.completed` | log, RTM | **CONFIGURED** |
| `support_escalation` | `ngt.support.escalated` | log, RTM, mail | **CONFIGURED** |
| `tutor_approved_onboarding` | `ngt.tutor.approved` | log, **add_user_role**, RTM | **POTENTIAL DOUBLE FIRE** vs orchestrator approval |
| `dashboard_health_check` | `ngt.daily.health_check` | log, RTM | Companion owns health cron when active |
| `lesson_completed_notify_parent` | `ngt.lesson.completed` | mail user, RTM | **CONFIGURED** |
| `payment_overdue_escalation` | `ngt.payment.overdue` | support case, RTM, mail | **CONFIGURED** |

Hub `add_user_role` on tutor approved is a **real mutation**, not just a log. Visualizer must mark **POTENTIAL DOUBLE FIRE** with orchestrator `wf_tutor_approved`.

---

## 5. Theme workflow pack (fallback)

File: `inc/workflows.php` + `content/nextgen-workflow-pack.json`  
`bi_workflow_dispatch()` runs enabled pack workflows.

WooCommerce completed/failed/refunded **return early if `NGC_Plugin` exists** — **PROVEN**.  
`user_register` → `bi_workflow_on_user_register` does **not** skip Companion — **PROVEN double subscriber** with Hub.

---

## 6. Automation Studio (existing visual runtime)

| Piece | Path | Role |
|---|---|---|
| Admin | `class-ngc-studio-admin.php` slug `ngc-automation-studio` | Mount |
| REST | `class-ngc-rest-studio.php` `ngc/v1/studio/*` | CRUD, publish, simulate, live |
| Compiler | `class-ngc-studio-compiler.php` | React-Flow shaped graphs |
| Engine | `class-ngc-studio-engine.php` | Graph/linear execute, conditions, loops |
| Runtime | `class-ngc-studio-runtime.php` | Published workflows → WP hooks |
| Importer | `class-ngc-studio-importer.php` | Copies hub/integrate/orchestrator/templates into DB |
| UI | `assets/studio/studio-fallback.js` | List/CRUD — **not** a node canvas |
| SPA bundle | `studio.bundle.js` | **ABSENT** |
| Tables | `ngc_studio_workflows`, `ngc_studio_executions`, triggers, versions, forms, emails, notifications, dashboards | Persistence |
| Permission | `NGC_Rest::require_admin` = `manage_options` OR `ngc_admin_operations` | No `ngt_view_workflows` caps yet |

Studio is **already an executor**. The requested visualizer must overlay discovery **without** treating every imported row as published production logic.

---

## 7. Event bridge / agents

`NGC_Domain_Event_Bridge` maps Hub + Companion hooks into agent envelopes (`LessonCompleted`, `BookingCreated`, `OrderCompleted`, …) with idempotent `emit_once`. Downstream: Agent Gateway / A2A / MCP **if those packages are loaded**. Envelope emit is **VERIFIED**; Gateway delivery is **UNVERIFIED** without runtime.

---

## 8. Other scheduled / background work (not full business workflows)

Discovered, to show on Event/Infrastructure maps as cron/worker nodes (not invented business graphs):

- `ngc_queue_tick` — queue worker fallback
- `ngc_workflow_retry_cron` — orchestrator retries
- Platform analytics / export / gamification / safeguarding / privacy / metrics / reconciliation crons (Companion)
- AI-Integration cron, Plugin-Manager queue, talent/memory workers
- Action Scheduler: observed as “present/absent” in observability, not NextGen’s workflow bus

---

## 9. REST / webhooks

| Endpoint / hook | Owner | Role |
|---|---|---|
| `ngc/v1/studio/*` | Companion | Studio CRUD + live |
| `woocommerce_api_ngc_payfast_itn` | `NGC_PayFast` | Payment webhook |
| `ngc/v1/admin` | Cockpit | Infra snapshot |
| Platform kernel REST | Queue/DLQ | Replay |

Full `register_rest_route` catalogue is large; discovery cache (Phase 8) should index routes tagged workflow/payment/booking rather than rescan PHP on each admin load.

---

## 10. JavaScript-triggered business operations

Admin:

- `assets/studio/studio-fallback.js` — REST to studio (save/publish/simulate)
- `assets/js/ngc-orchestration-cockpit.js` — cockpit layout/entities, not domain workflows
- Dashboard REST consumers — operational, not a second orchestrator

Frontend booking/checkout JS can create orders; settlement still goes through Woo/PayFast PHP. Do not draw a “JS executor” unless a specific REST mutation is proven.

---

## 11. Requested lifecycles vs evidence

### Tutor lifecycle

```
Tutor Registration (orchestrator VERIFIED)
  → FluentCRM (adapter CONFIGURED)
  → Admin Review (Tutor Applications screen VERIFIED)
  → Approve / Reject (orchestrator VERIFIED)
  → Amelia / MasterStudy (adapter calls PARTIALLY VERIFIED)
  → Hub add_user_role (CONFIGURED, DOUBLE FIRE risk)
  → Availability / Bookable (UNVERIFIED as a single chained workflow)
```

### Parent / child

```
Parent Registration (orchestrator VERIFIED)
  → Child Registration (orchestrator VERIFIED)
  → MasterStudy student (adapter PARTIALLY VERIFIED)
  → Subject / tutor search (UI + matching; not one workflow object)
```

### Booking

```
Find tutor (Hub + NGC_Workflows VERIFIED dispatch)
  → Matching (PARTIALLY VERIFIED)
  → Woo + PayFast (VERIFIED settle)
  → ngc_payment_settled → session provision (VERIFIED hooks)
  → Amelia booking (PARTIALLY VERIFIED)
  → Dashboards (UNVERIFIED as workflow steps)
```

### Session

```
Reminders (VERIFIED cron)
  → Join / A/V (Jitsi adapter registered; runtime UNVERIFIED)
  → Completion / attendance / payout (hooks + payout scheduler PARTIALLY VERIFIED)
```

### Cancellation / refund

```
ngc_booking_cancelled → session orchestrator (VERIFIED)
woocommerce_order_status_refunded → NGC_Payments + session (VERIFIED)
No-show / reschedule / tutor-vs-student cancellation as distinct graphs: UNVERIFIED (do not invent)
```

---

## 12. Gaps (show as gaps, not fake nodes)

- Bit Flows: not installed.
- AutomatorWP: importer + integration exist; live automations **UNVERIFIED**.
- FluentCRM native automation UI: not inventoried from DB.
- Studio React canvas: missing.
- Granular workflow caps (`ngt_view_workflows`, …): **do not exist**.
- Discovery snapshot cache: **does not exist**.
- Layout persistence separate from compiled plans: studio graph JSON currently **is** the plan — dragging in a future DESIGN mode would be dangerous if wired to compile.

---

## 13. Classification summary (static only)

| Status | Items |
|---|---|
| VERIFIED | Orchestrator 7 handlers; payment settle; session orchestrator hooks; reminders class; payout scheduler; theme Woo skip; registration form skip in `NGC_Workflows`; studio engine/runtime; durable queue + DLQ; PayFast ITN |
| PARTIALLY VERIFIED | Integrate specs 01,02,04,05,09; Amelia/LMS adapter effects; auto-assign |
| CONFIGURED BUT INACTIVE | Hub JSON (activation depends on Hub event fire); studio imported unpublished rows (DB unknown) |
| DUPLICATE / DOUBLE FIRE candidates | `user_register` theme+Hub; tutor approved Hub `add_user_role` vs orchestrator; payment completed Hub RTM vs `NGC_Payments` (side-effect class differs); studio importer vs live orchestrator if published |
| UNVERIFIED | No-show, reschedule split, GamiPress review branch, Peach payments, Notifyer/WhatsApp, Bit Flows, live metrics |
| ORPHANED | Not claimed without DB (unpublished studio rows / unused hooks) |

**No workflow was fabricated because the prompt expected it.**
