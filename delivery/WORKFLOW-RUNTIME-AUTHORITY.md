# Workflow Runtime Authority Matrix

**Status:** PHASE 2 COMPLETE (static source analysis)  
**Date:** 2026-08-14  
**Rule:** Workflow Studio must remain DISCOVERY + VISUALIZATION + OBSERVABILITY + CONTROL PLANE. It must not become a competing executor.

Runtime database/plugin-active state was **not** queried in this phase. Authority classifications below are from source code and configuration. Runtime confirmation is deferred to Phases 11 and 16 after approval.

---

## Decision (binding)

| Runtime | Can trigger business logic | Authority | Duplicate risk | Decision |
|---|---:|---|---:|---|
| `NGC_Workflow_Authority` | Yes (when option on) | **Primary side-effect executor** | Low | **KEEP** |
| `NGC_Workflow_Orchestrator` | Yes | Domain registration lifecycle | Medium (if authority off) | **KEEP / ADAPTER** — yield to authority when flag on |
| `NGC_Studio_Runtime` + `NGC_Studio_Engine` | Yes (published graphs) | Visual-authored executor | **High** if importer publishes discovered graphs | **KEEP as existing runtime**; Studio UI v1 is VIEW/LAYOUT over it, not a second engine |
| `NGC_Durable_Queue` + `NGC_Queue_Worker` | Yes | Worker | Low | **KEEP** — authority enqueue target |
| `NGC_Queue_DLQ` | Replay only | Recovery | Low | **KEEP** — control-plane target |
| `NGC_Workflow_Retry_Queue` | Yes | Orchestrator retry (option queue + hourly cron) | Medium vs durable queue | **ADAPTER** — visualize; do not add a third retry path |
| `NGC_Payments` / PayFast ITN | Yes | Commerce settlement | Medium vs Hub + theme pack | **KEEP** — domain owner of payment settle |
| `NGC_Session_Orchestrator` | Yes | Session provisioning | Low | **KEEP** |
| `NGC_Session_Reminders` | Yes | Reminder worker | Low | **KEEP** |
| `NGC_Payout_Scheduler` | Yes | Finance cron | Medium vs Hub monthly payout cron | **KEEP** (Companion) / Hub cron **YIELD** |
| `NGC_Workflows` → `bi_workflow_dispatch()` | Yes (notifications/RTM) | Theme pack dispatcher | Medium | **ADAPTER** — notifications, not domain mutations (except `add_user_role`) |
| Theme `inc/workflows.php` pack | Yes when Companion inactive for Woo; **always** for `user_register` | Fallback | Medium | **KEEP fallback**; Woo hooks already skip if `NGC_Plugin` exists |
| Automation Hub `NGT_Hub_Workflows` | Yes | Bundled JSON + WP hooks | Medium | **ADAPTER** — health cron already yields to Companion |
| AutomatorWP (`NGC_AutomatorWP_Integration`) | Yes if plugin active | Integration | Medium | **ADAPTER** — already filtered by authority |
| FluentCRM native automations | Unknown without runtime | External | Medium | **OBSERVE** — do not disable |
| Amelia native notifications | Unknown without runtime | External | Medium | **OBSERVE** |
| MasterStudy native hooks | Unknown without runtime | External | Medium | **OBSERVE** |
| Action Scheduler | Present as observability probe only | Worker (Woo) | Low in NextGen code | **DO NOT introduce** NextGen jobs onto it |
| Bit Flows | **Not present in repo** | n/a | None | **DO NOT INSTALL** as a runtime |
| **Workflow Studio (proposed visualizer)** | **No** | Visualization / control | None if implemented as specified | **VIEW / LAYOUT / CONTROL** |

---

## Primary authority

**Class:** `NGC_Workflow_Authority`  
**File:** `NextGenTutors-Companion/includes/platform/class-ngc-workflow-authority.php`

When `NGC_Platform::authority_enabled()` is true (option default `'1'`, kill-switch can disable):

1. Filters return `false` for producer side effects:
   - `ngc_studio_should_execute_side_effects`
   - `ngc_orchestrator_should_execute_side_effects`
   - `ngc_hub_should_execute_side_effects`
   - `ngc_automatorwp_should_execute_side_effects`
2. Producers call `NGC_Workflow_Authority::from_producer(...)`.
3. Jobs enqueue on `NGC_Queue_Worker::QUEUE_WORKFLOW`.
4. Worker executes `execute_job()` with `NGC_Idempotency::once()`.
5. Handlers:
   - `ngc_workflow_authority_execute_studio_workflow`
   - `ngc_workflow_authority_execute_orchestrator_run`
   - `ngc_workflow_authority_execute_retry_workflow`
   - `ngc_workflow_authority_execute_hub_event`

**Evidence:** static — class exists, filters registered in `init()`.  
**Runtime evidence:** not collected this phase. Label live “authority on/off” as **UNVERIFIED** until option is read.

---

## Producer → authority contract (proven in source)

```
Studio Runtime.dispatch_event
  └─ filter ngc_studio_should_execute_side_effects
       ├─ false + authority class → from_producer(studio, studio_workflow)
       └─ true → NGC_Studio_Engine::execute  (direct; authority off)

Orchestrator.run
  └─ filter ngc_orchestrator_should_execute_side_effects
       ├─ false → from_producer(orchestrator, orchestrator_run)
       └─ true → handler methods (wf_tutor_registered, …)

AutomatorWP action
  └─ filter ngc_automatorwp_should_execute_side_effects
       └─ false → from_producer(automatorwp, …)
```

---

## What Workflow Studio must never do

1. Compile discovered orchestrator/hub/payment graphs into **published** `studio_workflows` that `NGC_Studio_Runtime` executes.
2. Call adapters (FluentCRM / Amelia / MasterStudy / Woo / PayFast) directly from the UI.
3. Enqueue a parallel `workflow` queue message type besides existing authority jobs.
4. Disable AutomatorWP, FluentCRM, Amelia, or Hub silently.
5. Treat drag-and-drop layout as execution order.

Existing `NGC_Studio_Importer` already copies Hub / Integrate / Orchestrator definitions into `studio_workflows`. That is a **double-fire hazard if those rows are published**. Visualizer v1 must treat imported rows as **documentation graphs** unless an administrator explicitly publishes them (existing behaviour — do not expand it).

---

## Queue / DLQ / retry (infrastructure already present)

| Mechanism | Class | Persistence | Role |
|---|---|---|---|
| Durable queue | `NGC_Durable_Queue` | `queue_messages` (platform schema) | Authority jobs |
| Worker | `NGC_Queue_Worker` | cron `ngc_queue_tick` + CLI | Process queues: default, workflow, safeguard, fraud, notify |
| DLQ | `NGC_Queue_DLQ` | DLQ table | Max-attempt failures |
| Idempotency | `NGC_Idempotency` | platform table | Fingerprint / once |
| Orchestrator retry | `NGC_Workflow_Retry_Queue` | option `ngc_workflow_retry_queue` + cron `ngc_workflow_retry_cron` | Failed orchestrator runs |
| Studio executions | `NGC_Studio_Repository` | `ngc_studio_executions` | Studio engine traces |

Control-plane retry/replay **must** call these classes. Do not invent a new DLQ.

---

## Admin navigation authority

Single parent menu: `NGC_Admin_Shell` → **NEXT GEN TUTORS v1.0**.

Existing automation screens (do not add a second root menu):

| Slug | Title | Role today |
|---|---|---|
| `ngc-automation-studio` | Automation Studio | Graph CRUD + publish (fallback UI; React bundle **absent**) |
| `ngc-orchestration-cockpit` | Orchestration Cockpit | Infra monitor (servers/APIs/schedules), **not** business workflow graphs |
| `ngc-workflow-verification` | Workflow Verification | Integrate/runtime checks |
| `ngc-workflow-integrate` | Integrate Specs | WF-01..WF-10 JSON |
| `ngt-hub` | Automation Hub | Hub admin |
| Platform kernel | Queue / DLQ | Existing replay UI |

**Proposed mount:** extend `ngc-automation-studio` (relabel to **Workflow Studio**) with sub-views. Do not register `add_menu_page` for a new root.

---

## Graph library decision (Phase 6/7)

See `delivery/WORKFLOW-IMPLEMENTATION-PLAN.md`.

**Decision:** do **not** introduce React / XYFlow in v1.  
**Reason:** Companion has no React build; `studio.bundle.js` is missing; `NGC_Studio_Compiler` already stores React-Flow-shaped `{nodes, edges}`; adding React would collide with WordPress admin / Gutenberg React versions. Implement a namespaced vanilla canvas that reads the same JSON. XYFlow remains a later option without a model migration.
