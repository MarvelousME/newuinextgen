# Workflow Relationship Map

**Status:** PHASE 4–5 (canonical model + extracted relationships)  
**Date:** 2026-08-14  
**Rule:** Every edge below is either PROVEN (source registration) or labelled UNVERIFIED / INFERRED.

---

## Canonical node model (to persist in discovery cache, not in business tables)

```
Node
  id, workflow_id, type, label
  source, source_file, source_class, source_method, hook, integration
  status, configuration, evidence[], confidence, metadata

Edge
  source, target, relationship, condition, sequence
  evidence[], verification_status
```

**Persistence rule:** layout coordinates live in a **layout** store. Business execution order lives in existing runtimes (orchestrator / payments / studio compiled plan). Discovery snapshots are append-only.

---

## 1. Workflow view — Tutor registration (PROVEN core)

```
TRIGGER NGC_Tutor_Lifecycle / NGC_Registration
        │ PROVEN: NGC_Workflow_Orchestrator::run('TUTOR_REGISTERED')
        ▼
ACTION  NGC_Workflow_Orchestrator::wf_tutor_registered
        │ PROVEN: method exists
        ├─► INTEGRATION FluentCRM     CONFIGURED (adapter class)
        ├─► ACTION      Email adapter CONFIGURED
        └─► ACTION      Audit adapter CONFIGURED
                │
                ▼
        EVENT ngc_workflow_completed  PROVEN do_action
```

Approval branch (PROVEN trigger, PARTIAL integrations):

```
TRIGGER admin approve (NGC_Tutor_Lifecycle)
        │ PROVEN: run('TUTOR_APPROVED')
        ▼
CONDITION authority_enabled?
        │ PROVEN: filter ngc_orchestrator_should_execute_side_effects
        ├─ TRUE (authority on) ─► QUEUE workflow ─► WORKER ─► same handler
        └─ FALSE ─► in-process wf_tutor_approved
                ├─► Amelia employee     PARTIALLY VERIFIED
                ├─► MasterStudy instr.  PARTIALLY VERIFIED
                └─► Email / verification CONFIGURED
```

**UNVERIFIED (do not draw as proven):** “Availability → Bookable” as an automatic post-approval workflow.

**DOUBLE FIRE overlay (see double-fire audit):** Hub workflow `tutor_approved_onboarding` action `add_user_role`.

---

## 2. Workflow view — Booking / payment / session (PROVEN spine)

```
TRIGGER WooCommerce payment_complete OR order_status_completed
        │ PROVEN: NGC_Payments::init
        ▼
CONDITION already settled? (order meta ngc_payment_settled)
        │ PROVEN: skip in NGC_Payments
        ├─ TRUE  ─► TERMINAL (idempotent no-op)
        └─ FALSE ─► ACTION settle_order
                      │ PROVEN: do_action ngc_payment_settled
                      ▼
                 EVENT ngc_payment_settled
                      │
          ┌───────────┼──────────────┐
          ▼           ▼              ▼
   SessionOrchestrator  Ledger   DomainEventBridge
   PROVEN prio 20     PROVEN    PROVEN prio 5
          │
          ▼
   EnsureSessionProvisioned  (method exists; LMS/meeting writes PARTIAL)
```

PayFast:

```
WEBHOOK woocommerce_api_ngc_payfast_itn
   │ PROVEN
   ▼
CONDITION signature / amount / replay
   │ PROVEN NGC_PayFast_ITN
   ├─ fail ─► audit reject TERMINAL
   └─ COMPLETE ─► WC order complete ─► same settle path
```

---

## 3. Platform map (entities from code + tables, not marketing)

```
Parent (WP user + role)
   │ owns/manages   PROVEN: capability ngc_manage_children in authz matrix
   ▼
Student / Child (WP user)
   │ books          PROVEN: NGC_Bookings + parent checkout
   ▼
Tutor (WP user + application)
   │ provides       INFERRED from bookings.tutor_id (table exists; FK usage PARTIAL)
   ▼
Session (NGC_Sessions)
   │
   ├── Booking (ngc_bookings)     PROVEN
   ├── Payment (Woo order + ngc_payment_settled) PROVEN
   └── Meeting (Jitsi adapter)    CONFIGURED
          │
          ▼
      PayFast ITN                 PROVEN
```

Amelia employee/appointment links are **PARTIALLY VERIFIED** (adapters exist). Do not claim a DB FK without Phase 16 queries.

---

## 4. Integration map (static)

```
NextGen Companion (domain)
 ├── WordPress (users, cron, REST)
 ├── WooCommerce (NGC_Payments)
 ├── PayFast (NGC_PayFast_Gateway / ITN)
 ├── Amelia (NGC_Amelia_Adapter + bootstrap)
 ├── MasterStudy (NGC_Masterstudy_Adapter)
 ├── FluentCRM (NGC_Fluentcrm_Adapter)
 ├── FluentSMTP (mail transport; not a workflow engine) UNVERIFIED as distinct node
 ├── AutomatorWP (integration + importer) CONFIGURED
 ├── Automation Hub (JSON + hooks) CONFIGURED
 ├── Agent Gateway (Domain Event Bridge envelopes) CONFIGURED
 ├── MCP / A2A (packages exist; workflow coupling UNVERIFIED)
 └── Theme workflow pack (fallback notifications)
```

---

## 5. Event map (publisher → event → subscribers)

| Event | Publisher | Subscribers (static) | Confidence |
|---|---|---|---|
| `TUTOR_REGISTERED` (orchestrator key) | Registration / tutor lifecycle | Orchestrator handler | PROVEN |
| `ngc_form_submitted` | Forms | `NGC_Workflows` (skips registration forms), reminders not this hook | PROVEN |
| `ngc_tutor_approved` | Lifecycle | Studio trigger map; orchestrator via run() | PROVEN map |
| `ngc_payment_settled` | `NGC_Payments` | Session orchestrator, ledger, event bridge | PROVEN |
| `ngc_booking_confirmed` | `NGC_Bookings` | Session orchestrator | PROVEN |
| `ngc_booking_cancelled` | Bookings | Session orchestrator | PROVEN |
| `ngc_booking_created` | Bookings | Reminders, event bridge | PROVEN |
| `ngc_workflow_dispatched` | `NGC_Workflows` | Reminders, event bridge | PROVEN |
| `ngc_workflow_completed` | Orchestrator | (discover listeners in Phase 8 index) | PROVEN emit |
| `woocommerce_order_status_completed` | Woo | Payments, Hub, theme (theme skips if Companion) | PROVEN |
| `woocommerce_payment_complete` | Woo | Payments | PROVEN |
| `woocommerce_api_ngc_payfast_itn` | Woo API | PayFast | PROVEN |
| `user_register` | WP | Theme pack, Hub | PROVEN |
| `ngt_automation_event_fired` | Hub | Domain event bridge | PROVEN |
| `ngc_studio_event` | Studio | Studio runtime | PROVEN |

Full subscriber lists for every `do_action` will be produced by the discovery indexer (Phase 8), not guessed here.

---

## 6. Data lineage (registration example)

```
Become a Tutor form
   │ PROVEN ngc_form_submitted
   ▼
WP User (+ tutor application records)
   │ PROVEN Registration / Tutor Lifecycle
   ▼
Orchestrator TUTOR_REGISTERED
   │ CONFIGURED adapter
   ▼
FluentCRM contact
   │ UNVERIFIED until DB evidence
   ▼
Admin review
   │ PROVEN applications screen
   ▼
TUTOR_APPROVED
   ├─ Amelia employee     PARTIAL
   ├─ MasterStudy         PARTIAL
   └─ Hub add_user_role   CONFIGURED (competing)
```

Payment lineage:

```
Checkout / PayFast redirect
   │ PROVEN gateway
   ▼
ITN or WC payment_complete
   │ PROVEN
   ▼
Woo order meta ngc_payment_settled
   │ PROVEN
   ▼
Session row (NGC_Sessions)  PARTIAL until DB
   ▼
Invoice / wallet / ledger   PARTIAL (classes exist)
```

---

## 7. Runtime execution overlay

Existing traces (do not invent metrics):

| Store | What it can overlay | Gap |
|---|---|---|
| `ngc_studio_executions` | Studio engine path | Only studio-published graphs |
| Durable queue + DLQ | Authority jobs | Payload action, not node-level path |
| Orchestrator run log (`log_run_start/end`) | Orchestrator workflow key | Confirm table/option in Phase 8 |
| `NGC_Audit` / `NGC_Immutable_Audit` | payment_completed, workflow.execute | Redaction required |
| `NGC_Metrics` | `workflow_authority_executions_total` | Counters only |

Visualizer must join traces by `trace_id` / `idempotency_key` / `order_id` / `booking_id` where present. Missing join = node stays ○ Waiting, not fake ● Success.

---

## 8. Impact analysis seeds (upstream / downstream)

**WooCommerce payment complete**

- Upstream: checkout, PayFast ITN, parent-checkout redirect  
- Downstream: settle → session provision → ledger → event bridge → (INFERRED) dashboards  

**Tutor approved**

- Upstream: application review  
- Downstream: orchestrator adapters + Hub `add_user_role` + studio if published  

Selecting a node in v1 computes dependents from the **cached graph**, not by executing anything.
