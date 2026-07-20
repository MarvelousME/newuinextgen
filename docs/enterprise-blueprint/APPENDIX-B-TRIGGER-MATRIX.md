# Appendix B — Trigger Matrix

**Theme:** BeyondInfinity v1.4.6  
**Evidence sources:** `inc/workflows.php`, `content/nextgen-workflow-pack.json`, `inc/shortcodes-fallback.php`, `inc/openwa.php`

**Diagrams:** [Trigger catalog SVG](diagrams/triggers/trigger-matrix-catalog.svg) · per-trigger flows in [diagrams/triggers/](diagrams/triggers/)

---

## Legend

| Tag | Meaning |
|-----|---------|
| **VERIFIED** | Hook or dispatcher exists in theme code |
| **PARTIAL** | Contract/scaffolding only; incomplete lifecycle |
| **NOT VERIFIED** | No implementation found |

---

## Primary Event Catalog

| Logical trigger | Event key | Source | Dispatcher | Actions | Notify | Queue | Retry | Audit | Status |
|-----------------|-----------|--------|------------|---------|--------|-------|-------|-------|--------|
| USER_REGISTERED | `wp.user_registered` | `user_register` hook | `bi_workflow_on_user_register` | log, RTM staff | RTM | `bi_rtm_queue` | None | `bi_workflow_log` | **VERIFIED** |
| TUTOR_SUBMITTED | `ngt.tutor_application.submitted` | Form `become_tutor` | `bi_form_submitted` → dispatch | log, RTM×2, admin mail | RTM + mail | `bi_rtm_queue` | None | Yes | **VERIFIED** |
| TUTOR_APPROVED | `ngt.tutor.approved` | Admin Operations / `bi_workflow_emit_tutor_approved()` | `bi_workflow_dispatch` | log, add role, RTM | RTM tutor-support | `bi_rtm_queue` | None | Yes | **VERIFIED** |
| TUTOR_REJECTED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| STUDENT_CREATED | `ngt.student_register.submitted` | Form `student_register` | `bi_form_submitted` → dispatch | log, RTM, admin mail | RTM + mail | `ngc_form_queue`, `bi_rtm_queue` | None | Yes | **VERIFIED** |
| PARENT_CREATED | `ngt.parent_register.submitted` | Form `parent_register` | `bi_form_submitted` → dispatch | log, RTM, admin mail | RTM + mail | `ngc_form_queue`, `bi_rtm_queue` | None | Yes | **VERIFIED** |
| MATCH_REQUESTED | `ngt.find_tutor.submitted` | Form `find_tutor` | `bi_form_submitted` → dispatch | log, RTM×2, admin mail | RTM + mail | Both queues | None | Yes | **VERIFIED** |
| MATCH_ACCEPTED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| BOOKING_CREATED | `amelia.booking.created` | Amelia plugin (external) | `bi_workflow_dispatch` | log, RTM×2 | RTM | `bi_rtm_queue` | None | Yes | **PARTIAL** |
| BOOKING_CONFIRMED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| PAYMENT_RECEIVED | `woocommerce.order.completed` | WooCommerce hook | `bi_workflow_on_order_completed` | log, RTM×2 | RTM payment-issues + staff | `bi_rtm_queue` | None | Yes | **PARTIAL** |
| PAYMENT_FAILED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| LESSON_STARTED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| LESSON_COMPLETED | `ngt.lesson.completed` | External/companion | `bi_workflow_dispatch` | log, RTM lesson-issues | RTM | `bi_rtm_queue` | None | Yes | **PARTIAL** |
| INVOICE_GENERATED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| PAYOUT_GENERATED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| REVIEW_SUBMITTED | — | — | — | — | — | — | — | — | **NOT VERIFIED** |
| SUPPORT_CREATED | `ngt.support.escalated` | Form `contact_support` | `bi_form_submitted` → dispatch | log, RTM, admin mail | RTM + mail | Both queues | None | Yes | **VERIFIED** |
| DAILY_HEALTH_CHECK | `ngt.daily.health_check` | External/cron (implied) | `bi_workflow_dispatch` | log, RTM admin | RTM | `bi_rtm_queue` | None | Yes | **PARTIAL** |

---

## Form ID → Event Mapping (`bi_workflow_on_form_submitted`)

| `ngc_form_id` | Workflow event | Workflow pack key |
|---------------|----------------|-------------------|
| `find_tutor` | `ngt.find_tutor.submitted` | `parent_find_tutor_intake` |
| `become_tutor` | `ngt.tutor_application.submitted` | `tutor_application_review` |
| `contact_support` | `ngt.support.escalated` | `support_escalation` |
| `parent_register` | `ngt.parent_register.submitted` | `parent_register_intake` |
| `student_register` | `ngt.student_register.submitted` | `student_register_intake` |

Registration forms queue to `ngc_form_queue`, trigger admin email/OpenWA, and dispatch workflow events via `bi_workflow_on_form_submitted()`. WP user provisioning remains companion scope.

---

## Workflow Action Types (theme runtime)

| Action type | Handler | Status |
|-------------|---------|--------|
| `log_event` | `bi_workflow_log()` | **VERIFIED** |
| `create_rtm_message` | `bi_workflow_queue_rtm()` | **VERIFIED** |
| `wp_mail_admin` | `wp_mail(admin_email)` | **VERIFIED** |
| `add_user_role` | `bi_workflow_apply_user_role()` | **VERIFIED** |
| Custom types | `do_action('bi_workflow_action_{type}')` | Extension point |

---

## Notification Channels

| Channel | Trigger paths | Evidence |
|---------|---------------|----------|
| Email | Form handler, workflow `wp_mail_admin`, OpenWA errors | **VERIFIED** |
| RTM queue | All enabled workflow pack actions | **VERIFIED** |
| WhatsApp (OpenWA) | Form notify, REST `/send`, inbound webhook | **VERIFIED** |

REST routes (namespace `bi/v1`): `/openwa/webhook`, `/openwa/status`, `/openwa/send`.

---

## External Emitters (contracts only — theme does not fire)

These events are defined in `nextgen-workflow-pack.json` but have **no theme hook** that calls `bi_workflow_dispatch()`:

- `amelia.booking.created` — requires Amelia integration plugin
- `ngt.lesson.completed` — requires companion/LMS
- `ngt.tutor.approved` — dispatch via `bi_workflow_emit_tutor_approved()` (admin Operations screen or companion)
- `ngt.daily.health_check` — requires cron/automation

Companion or AutomatorWP-style bridges should call `bi_workflow_dispatch( $event, $vars )` or fire equivalent hooks.
