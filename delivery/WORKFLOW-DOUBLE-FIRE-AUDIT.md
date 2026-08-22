# Workflow Double-Fire Audit

**Status:** PHASE 3/4 static audit  
**Date:** 2026-08-14  
**Rule:** Identify competing handlers. Do **not** disable production functionality from this report.

Severity: **H** = same business mutation possible from two runtimes · **M** = overlapping notifications / logs · **L** = observability only.

---

## DF-01 WooCommerce order completed

| Field | Value |
|---|---|
| Triggering event | `woocommerce_order_status_completed` |
| Competing handlers | `NGC_Payments::on_order_completed` (prio 30); `NGT_Hub_Workflows::on_wc_order_completed`; theme `bi_workflow_on_order_completed` (**skipped** if `NGC_Plugin` exists) |
| Runtimes | Companion payments (authority of settle); Hub JSON notifications; theme fallback |
| Resulting side effects | Payments: settle, invoices/wallet (domain). Hub: RTM + log. Theme: pack actions if Companion off |
| Idempotency | Payments: order meta `ngc_payment_settled` + `NGC_Idempotency`. Hub: not proven |
| Severity | **M** (Companion on) / **H** if Hub later gains mutations |
| Evidence | `class-ngc-payments.php` init; Hub `register_hooks`; `inc/workflows.php` early return |
| Visual | POTENTIAL DOUBLE FIRE — Hub vs Payments (different side-effect class) |

Also `woocommerce_payment_complete` → Payments only (PROVEN). ITN may complete the order and thus re-enter `order_status_completed`. Payments skip if already settled — **PROVEN** guard.

---

## DF-02 `user_register`

| Field | Value |
|---|---|
| Triggering event | `user_register` |
| Competing handlers | Theme `bi_workflow_on_user_register` (prio 20, **no Companion skip**); Hub `NGT_Hub_Workflows::on_user_register`; Studio if a published workflow binds USER_REGISTERED |
| Side effects | Theme pack: log/RTM (and any enabled pack actions). Hub: fires Hub event. Orchestrator registration is **not** this hook (forms skip `NGC_Workflows`) |
| Severity | **M** (duplicate staff notifications) |
| Evidence | `inc/workflows.php` line ~341 vs Hub `register_hooks` |
| Visual | POTENTIAL DOUBLE FIRE — notifications |

---

## DF-03 Tutor approved — role + onboarding

| Field | Value |
|---|---|
| Triggering event | Orchestrator `TUTOR_APPROVED` **and** Hub `ngt.tutor.approved` |
| Competing handlers | `wf_tutor_approved` (Amelia/LMS/email); Hub workflow `tutor_approved_onboarding` action `add_user_role` |
| Side effects | Domain onboarding vs Hub role grant |
| Idempotency | Orchestrator retry queue; Hub unknown |
| Severity | **H** if both fire on the same approval |
| Evidence | Orchestrator handler map; Hub `workflows.json` `tutor_approved_onboarding` |
| Visual | POTENTIAL DOUBLE FIRE |
| Action | Visualize only. Do not auto-disable Hub |

How `ngt.tutor.approved` is emitted vs `NGC_Tutor_Lifecycle` must be confirmed in Phase 8 (trace `NGC_Workflows::dispatch` / `bi_workflow_emit_tutor_approved`). Until then the **edge between the two runtimes is UNVERIFIED**; the **competing definitions are PROVEN**.

---

## DF-04 Studio importer vs live orchestrator

| Field | Value |
|---|---|
| Triggering event | Any imported trigger if workflow **published** |
| Competing handlers | `NGC_Studio_Runtime` dynamic hooks vs original orchestrator/hub |
| Side effects | `NGC_Studio_Engine` can call `NGC_Workflow_Orchestrator::run` (engine line ~377) **and** original producer may also run |
| Idempotency | Authority + engine; still **H** if authority off |
| Severity | **H** when imported graphs are published |
| Evidence | `NGC_Studio_Importer::import_all`; `NGC_Studio_Engine` orchestrator map |
| Visual | POTENTIAL DOUBLE FIRE |
| Studio v1 rule | Discovery graphs default **VIEW**. Publishing remains the existing explicit admin action, not auto-sync |

---

## DF-05 AutomatorWP vs Companion

| Field | Value |
|---|---|
| Triggering event | `automatorwp_trigger_event` / registered NextGen triggers |
| Competing handlers | AutomatorWP automations; Companion orchestrator; Studio |
| Guard | `ngc_automatorwp_should_execute_side_effects` → authority |
| Severity | **M** if plugin active and automations seeded; **UNVERIFIED** if plugin inactive |
| Evidence | `class-ngc-automatorwp-integration.php`; Plugin Manager registry |
| Visual | Show AutomatorWP as integration lane; mark UNVERIFIED until plugin + rows scanned |

---

## DF-06 Hub health cron vs Companion diagnostics

| Field | Value |
|---|---|
| Event | `ngt.daily.health_check` |
| Guard | `NGT_Hub_Companion_Delegate::companion_active()` skips Hub cron |
| Severity | **L** (mitigated in source) |
| Evidence | `class-ngt-hub-workflows.php` `schedule_health_cron` / `run_health_check` |
| Visual | YIELD edge, not double fire, **if** delegate class loaded |

---

## DF-07 Payout crons

| Field | Value |
|---|---|
| Companion | `ngc_monthly_payout_batch`, `ngc_biweekly_payout_batch` |
| Hub observability key | `ngt_monthly_payout_calculation` |
| Severity | **M** if both scheduled |
| Evidence | `NGC_Payout_Scheduler`; observability service Hub cron probe |
| Visual | POTENTIAL DOUBLE FIRE until runtime cron list is read |

---

## DF-08 FluentCRM / Amelia native automations

Not present as NextGen PHP. If those plugins have their own automation engines, they can mutate contacts/bookings **outside** `NGC_Workflow_Authority`.

| Status | **UNVERIFIED** (requires runtime plugin options / tables) |
| Visual | Integration lane + “native automation unknown” badge |

---

## What this audit will not do

- Disable Hub, AutomatorWP, FluentCRM, or Studio publish.
- Merge competing handlers.
- Claim double-fire **occurred** in production (no execution logs pulled).

Visualizer must list competing handlers, runtime, side-effect class, idempotency, severity, and evidence on the node inspector.
