# Workflow & Integration Catalog

**Complete reference** for events, workflow layers, third-party integrations, and content-pack bridges.

**Last updated:** 2026-07-13  
**Status taxonomy:** VERIFIED · PARTIAL · NOT VERIFIED · BLOCKED

---

## 1. Architecture overview

```
┌──────────────────────────────────────────────────────────────────┐
│  TRIGGERS — forms, WooCommerce, Amelia, CPT saves, cron          │
└────────────────────────────┬─────────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│  NGC_Workflows::dispatch( $event, $vars )                          │
│  → NGC_Workflow_Orchestrator (registration lifecycles)             │
│  → bi_workflow_dispatch() (theme pack actions)                     │
│  → NGC_Workflow_Integrate_Executor (integrate specs)               │
│  → AutomatorWP triggers (when active)                              │
└────────────────────────────┬─────────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│  ACTIONS — RTM messages, admin email, CRM tags, reminders, payouts │
└──────────────────────────────────────────────────────────────────┘
```

### Three executable layers

| Layer | Location | Executes actions? |
|-------|----------|-------------------|
| **Theme workflow pack** | `content/nextgen-workflow-pack.json` | Yes — RTM queue, `wp_mail`, log |
| **Companion orchestrator** | `NGC_Workflow_Orchestrator` | Yes — user provisioning, Amelia, CRM |
| **Integrate executor** | `NGC_Workflow_Integrate_Executor` | Yes — bound integrate specs |

### Two definition-only layers

| Layer | Location | Purpose |
|-------|----------|---------|
| **Command Center v2** | `integrate/catalog/v2/` (12 JSON) | BPMN-style definitions, AutomatorWP seed |
| **Completion Suite** | `integrate/catalog/completion/` (6 JSON) | Operational workflow definitions |

Import via **Companion → Workflows → Integrate Specs**.

---

## 2. Companion event catalog

Events dispatched by `NGC_Workflows::dispatch()` fire:

- `ngc_workflow_dispatched` (global)
- `ngc_{event_with_underscores}` (per-event, e.g. `ngc_find_tutor_submitted`)
- Theme pack via `bi_workflow_dispatch()` when event maps to `ngt.*` trigger

### 2.1 Form events

| Form ID | Event | Orchestrator? | Theme pack trigger |
|---------|-------|---------------|-------------------|
| `find_tutor` | `find_tutor.submitted` | No | `ngt.find_tutor.submitted` |
| `become_tutor` | `tutor_application.submitted` | Yes | `ngt.tutor_application.submitted` |
| `parent_register` | `parent_register.submitted` | Yes | `ngt.parent_register.submitted` |
| `student_register` | `student_register.submitted` | Yes | `ngt.student_register.submitted` |
| `contact_support` | `support.escalated` | No | — |

### 2.2 Lifecycle events

| Event | Source | Actions |
|-------|--------|---------|
| `tutor.approved` | `NGC_Workflow_Orchestrator::on_tutor_approved()` | Role change, Amelia employee, CRM tag, workflow dispatch |
| `tutor.rejected` | Tutor lifecycle admin | Notification, applicant status |
| `tutor.resubmission_requested` | Tutor lifecycle admin | Email + status |
| `match.assigned` | `NGC_Matching` | Booking prep, notifications |
| `match.auto_accepted` | `NGC_Matching::maybe_auto_accept()` | When `ngc_auto_assign_enabled` |
| `booking.created` | `NGC_Bookings` | Reminder queue, RTM |
| `booking.cancelled` | `NGC_Bookings` | Refund path, notifications |
| `lesson.completed` | Session lifecycle | Payout calc, review prompt |
| `payment.received` | WooCommerce / PayFast ITN | Invoice, wallet, RTM |
| `review.submitted` | `NGC_Reviews` | GamiPress points, CRM |
| `payout.calculated` | `NGC_Payout_Scheduler` | Finance queue |

### 2.3 Content pack events (Completion Suite bridge)

Dispatched by `NGC_Content_Pack_Bridge::on_operational_post()`:

| CPT | Event | Theme pack trigger |
|-----|-------|-------------------|
| `ngt_report` | `progress_report.submitted` | `ngt.progress_report.submitted` |
| `ngt_note` | `lesson_note.created` | `ngt.lesson_note.created` |
| `ngt_payout` | `payout.calculated` | `ngt.payout.calculated` |
| `ngt_resource` | `resource.recommended` | `ngt.resource.recommended` |
| `ngt_support_log` | `support.escalated` | — |

### 2.4 RTM bridge events

| Hook | When |
|------|------|
| `bi_workflow_rtm_queued` | After theme pack queues RTM message |
| `NGC_Content_Pack_Bridge::mirror_rtm_to_command_center()` | Mirrors to `ngt_rtm_message` CPT |

---

## 3. Theme workflow pack

**File:** `content/nextgen-workflow-pack.json`  
**Runner:** `inc/workflows.php` → `bi_workflow_dispatch()`

### Action types

| Type | Behaviour |
|------|-----------|
| `log_event` | Writes to workflow log option |
| `create_rtm_message` | Queues RTM → mirrored to Command Center |
| `wp_mail_admin` | Sends admin notification email |
| `fluentcrm_tag` | Tags contact (when FluentCRM active) |

### Pack workflows (enabled)

| Key | Trigger | Primary actions |
|-----|---------|-----------------|
| `parent_find_tutor_intake` | `ngt.find_tutor.submitted` | RTM staff + booking, admin email |
| `tutor_application_review` | `ngt.tutor_application.submitted` | RTM staff + tutor-support, admin email |
| `parent_register_intake` | `ngt.parent_register.submitted` | RTM staff, admin email |
| `student_register_intake` | `ngt.student_register.submitted` | RTM staff, admin email |
| `new_user_role_routing` | `wp.user_registered` | RTM staff log |
| `woocommerce_payment_completed` | `woocommerce.order.completed` | RTM payment + staff |
| `amelia_booking_created` | `amelia.booking.created` | RTM booking-issues |
| `progress_report_submitted` | `ngt.progress_report.submitted` | RTM staff, admin email |
| `lesson_note_created` | `ngt.lesson_note.created` | RTM tutor-support |
| `resource_recommended` | `ngt.resource.recommended` | RTM staff |
| `payout_calculated` | `ngt.payout.calculated` | RTM finance-room |
| `support_escalated` | `ngt.support.escalated` | RTM escalated-support |

---

## 4. Integrate pack specs

**Path:** `NextGenTutors-Companion/integrate/`

| Spec file | Blueprint WF | Runtime module |
|-----------|--------------|----------------|
| `workflow-01.json` | WF-01 Parent registration | `NGC_Workflow_Orchestrator` |
| `workflow-02.json` | WF-02 Student registration | `NGC_Workflow_Orchestrator` |
| `workflow-03.json` | WF-03 Tutor registration | `NGC_Tutor_Lifecycle` |
| `workflow-04.json` | WF-04 Tutor approval | `NGC_Tutor_Lifecycle` |
| `workflow-05.json` | WF-05 Tutor rejection | `NGC_Tutor_Lifecycle` |
| `workflow-06.json` | WF-06 Resubmission | `NGC_Tutor_Lifecycle` |
| `workflow-07.json` | WF-07 Matching | `NGC_Matching` |
| `workflow-08.json` | WF-08 Manual assign | `NGC_Matching` |
| `workflow-09.json` | WF-09 Auto matching | `NGC_Smart_Matching` |
| `workflow-10.json` | WF-10 Booking | `NGC_Bookings` |

### WP-CLI

```bash
wp ngc integrate_status
wp ngc integrate import
wp ngc integrate execute --event=tutor.approved
wp ngc process_reminders
wp ngc run_payout_batch
```

---

## 5. Command Center v2 catalog

**Path:** `NextGenTutors-Companion/integrate/catalog/v2/`  
**Source:** `content/_extracted/nextgen-command-center-v1.0/workflows-v2/`

| ID | Slug | Trigger event |
|----|------|---------------|
| NGT-V2-001 | parent-request-tutor | `find_a_tutor_form_submitted` |
| NGT-V2-002 | tutor-application | `tutor_application_submitted` |
| NGT-V2-003 | tutor-approved | `tutor_approved` |
| NGT-V2-004 | student-created | `student_created` |
| NGT-V2-005 | booking-created | `booking_created` |
| NGT-V2-006 | booking-cancelled | `booking_cancelled` |
| NGT-V2-007 | payment-received | `woocommerce_order_status_completed` |
| NGT-V2-008 | lesson-completed | `lesson_status_completed` |
| NGT-V2-009 | progress-report-submitted | `progress_report_submitted` |
| NGT-V2-010 | parent-review-submitted | `parent_review_submitted` |
| NGT-V2-011 | support-escalated | `support_escalated` |
| NGT-V2-012 | payout-approved | `payout_approved` |

`import_mode: definition_record` — stored in `ngc_workflow_spec_store` option.

Admin: **Import content-pack catalog** or **Seed AutomatorWP from v2 JSON**

---

## 6. Completion Suite catalog

**Path:** `NextGenTutors-Companion/integrate/catalog/completion/`

| ID | Name | Trigger |
|----|------|---------|
| 011 | Progress Report Submitted | `progress_report_created` |
| 012 | Tutor Lesson Note Created | `lesson_note_created` |
| 013 | Support Escalated | `support_escalated` |
| 014 | Tutor Payout Ready | `lesson_confirmed_paid` |
| 015 | Parent Review Created | `parent_review_created` |
| 016 | Learning Resource Recommended | `weakness_identified` |

---

## 7. AutomatorWP integration

**Class:** `NGC_AutomatorWP_Integration`  
**Importer:** `NGC_AutomatorWP_Importer`

| Item | Value |
|------|-------|
| Integration ID | `nextgencompanion` |
| Trigger | `nextgencompanion_workflow_event` |
| Meta | `event_name`, `payload_json` |

### Seed process

1. Companion → Workflows → Integrate Specs
2. Click **Seed AutomatorWP from v2 JSON**
3. Creates `ct_automation` posts via `ct_insert_object()`
4. Default actions: admin email notification per workflow

Filter: `ngc_automatorwp_recipe_actions` to customize seeded actions.

---

## 8. Third-party integration matrix

| Plugin | Adapter class | Workflows | Status |
|--------|---------------|-----------|--------|
| **WooCommerce** | `NGC_WooCommerce_Adapter` | WF-11 payment, WF-12 invoice, products | PARTIAL — requires live gateway |
| **PayFast** | `NGC_PayFast_Gateway` | WF-11 ITN handler | VERIFIED in code |
| **Amelia** | `NGC_Amelia` | WF-10 booking, tutor calendar | PARTIAL — license zip required |
| **FluentCRM** | `NGC_FluentCRM_Adapter` | WF-21 CRM tags | PARTIAL |
| **FluentSMTP** | `NGC_FluentSMTP_Adapter` | WF-22 email delivery | PARTIAL |
| **MasterStudy** | `NGC_MasterStudy_Adapter` | Course enrollment | PARTIAL |
| **GamiPress** | `NGC_Gamification` | WF-18 achievements | PARTIAL |
| **AutomatorWP** | `NGC_AutomatorWP_Integration` | Recipe automation | VERIFIED |
| **User Role Editor** | — | Capability management | VERIFIED |

### Bootstrap sequence

After Plugin Manager installs stack:

1. `NGC_Integrations_Bootstrap::configure_local_stack()` runs on `ngc_integrations_bootstrapped`
2. Creates WooCommerce products from integrate CSV (optional CLI)
3. Maps Amelia services, FluentCRM lists, GamiPress point types

---

## 9. Blueprint WF-01–WF-25 mapping

| WF | Name | Primary runtime |
|----|------|-----------------|
| WF-01 | Parent registration | Orchestrator + theme pack |
| WF-02 | Student registration | Orchestrator + theme pack |
| WF-03 | Tutor registration | Orchestrator + theme pack |
| WF-04 | Tutor approval | Orchestrator + Amelia |
| WF-05 | Tutor rejection | Orchestrator |
| WF-06 | Tutor resubmission | Orchestrator |
| WF-07 | Tutor matching (intake) | Matching + theme pack |
| WF-08 | Manual matching | `NGC_Matching` admin |
| WF-09 | Automated matching | `NGC_Smart_Matching` |
| WF-10 | Booking | `NGC_Bookings` + Amelia |
| WF-11 | Payment | WooCommerce + PayFast |
| WF-12 | Invoice | `NGC_Invoices` |
| WF-13 | Refund | `NGC_Payments` |
| WF-14 | Cancellation | `NGC_Bookings` |
| WF-15 | Session completion | Session lifecycle |
| WF-16 | Tutor payout | `NGC_Payout_Scheduler` |
| WF-17 | Parent review | `NGC_Reviews` |
| WF-18 | Tutor rating | GamiPress + reviews |
| WF-19 | Support ticket | Forms + RTM |
| WF-20 | Escalation | RTM escalated-support room |
| WF-21 | CRM sync | FluentCRM adapter |
| WF-22 | Email | FluentSMTP + templates |
| WF-23 | WhatsApp | BLOCKED — OpenWA optional |
| WF-24 | Reminders | `NGC_Session_Reminders` cron |
| WF-25 | Dashboards | Studio + REST dashboards |

Gap analysis: [FLOW-GAP-REPORT.md](FLOW-GAP-REPORT.md)  
Per-workflow specs: [../enterprise-blueprint/workflows/](../enterprise-blueprint/workflows/)

---

## 10. Retry and observability

| Component | Purpose |
|-----------|---------|
| `NGC_Workflow_Retry_Queue` | Hourly cron retries failed workflow steps |
| `ngc_workflow_runs` table | Execution history |
| Command Center `[ngcc_workflow_status_list]` | Staff visibility |
| Companion → Workflows → Logs | Admin log viewer |

---

## 11. Extension guide

### Add a new event

```php
// In your module after business action completes:
NGC_Workflows::dispatch( 'my_module.action_completed', [
    'user_id' => (string) $user_id,
    'email'   => $user->user_email,
    'custom'  => $value,
] );
```

### Bind integrate spec

Add to `integrate/workflow-XX.json` and register binding:

```php
add_filter( 'ngc_integrate_event_bindings', function ( $bindings ) {
    $bindings['my_module.action_completed'] = [
        'spec'  => 'workflow-XX',
        'event' => 'my_module.action_completed',
    ];
    return $bindings;
} );
```

### Add theme pack workflow

Edit `content/nextgen-workflow-pack.json`:

```json
{
  "key": "my_workflow",
  "trigger": { "event": "ngt.my_event" },
  "actions": [
    { "type": "create_rtm_message", "room": "staff", "message": "..." }
  ]
}
```

Map Companion event to `ngt.*` in `inc/workflows.php` event bridge if needed.

---

## Related docs

- [workflow-documentation.md](workflow-documentation.md) — WF-01–WF-25 narrative
- [FLOW-GAP-REPORT.md](FLOW-GAP-REPORT.md) — runtime gaps
- [../content-packs/COMMAND-CENTER.md](../content-packs/COMMAND-CENTER.md)
- [../content-packs/COMPLETION-SUITE.md](../content-packs/COMPLETION-SUITE.md)
- [../../NextGenTutors-Companion/integrate/README.md](../../NextGenTutors-Companion/integrate/README.md)
- [../DEVELOPER-GUIDE.md](../DEVELOPER-GUIDE.md)
