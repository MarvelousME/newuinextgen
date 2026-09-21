# Flow Gap Report — SVG → Runtime

**Generated:** 2026-07-06T05:58:50.548Z
**Source manifest:** `docs/workflows/flow-manifest.json`

## Summary

| Metric | Count |
|--------|------:|
| Blueprint workflows (SVG) | 25 |
| Full runtime coverage | 8 |
| Partial / stale | 17 |
| No runtime binding | 0 |
| SVG placeholder but runtime exists | 0 |

## Stale SVG diagrams (update recommended)

_None detected._

## Per-workflow matrix

| WF | Title | SVG status | Coverage | Wired | Gaps |
|----|-------|------------|----------|-------|------|
| WF-01 | Parent Registration | VERIFIED | partial | theme pack: parent_register_intake, orchestrator: PARENT_REGISTERED | trigger drift: SVG "POST parent_register" vs runtime "parent_register form" |
| WF-02 | Student Registration | VERIFIED | partial | theme pack: student_register_intake, orchestrator: STUDENT_REGISTERED | trigger drift: SVG "POST" vs runtime "student_register form" |
| WF-03 | Tutor Registration | VERIFIED | full | theme pack: tutor_application_review, orchestrator: TUTOR_REGISTERED | — |
| WF-04 | Tutor Approval | VERIFIED (theme runtime) / PARTIAL (user must exist) | full | theme pack: tutor_approved_onboarding, orchestrator: TUTOR_APPROVED | — |
| WF-05 | Tutor Rejection | PARTIAL (Companion) | full | theme pack: tutor_application_rejected, orchestrator: TUTOR_REJECTED | — |
| WF-06 | Tutor Re-submission | PARTIAL (Companion) | partial | orchestrator: TUTOR_RESUBMITTED, module: NGC_Tutor_Lifecycle | trigger drift: SVG "tutor.more_info_requested" vs runtime "tutor resubmit" |
| WF-07 | Tutor Matching | PARTIAL | partial | theme pack: parent_find_tutor_intake, module: NGC_Matching | trigger drift: SVG "ngt.find_tutor.submitt" vs runtime "find_tutor form / match API" |
| WF-08 | Manual Matching | PARTIAL | partial | module: NGC_Matching | trigger drift: SVG "Operational RTM" vs runtime "REST matches assign" |
| WF-09 | Automated Matching | UNKNOWN | partial | module: NGC_Matching | Scoring implemented; auto-assign deferred — use WF-08 manual assign; Scoring implemented; auto-assign deferred — use WF-08 manual assign |
| WF-10 | Booking Workflow | PARTIAL | full | theme pack: amelia_booking_created, module: NGC_Bookings | — |
| WF-11 | Payment Workflow | PARTIAL | partial | theme pack: woocommerce_payment_completed, module: NGC_Payments | no Playwright spec match for: wf02-booking-payment.spec.ts |
| WF-12 | Invoice Workflow | PARTIAL (Companion) | partial | theme pack: invoice_issued_notification, module: NGC_Invoices | trigger drift: SVG "Woo order paid" vs runtime "ngt.invoice.issued" |
| WF-13 | Refund Workflow | PARTIAL (Companion) | partial | theme pack: payment_refunded_alert, module: NGC_Payments | trigger drift: SVG "Woo refund hook" vs runtime "ngt.payment.refunded" |
| WF-14 | Cancellation Workflow | PARTIAL (Companion) | full | theme pack: booking_cancelled_alert, integrate: workflow-02-booking-payment | — |
| WF-15 | Session Completion | PARTIAL | full | theme pack: lesson_completed_awards, integrate: workflow-04-review-rating | — |
| WF-16 | Tutor Payout | PARTIAL (Companion) | full | theme pack: tutor_payout_processed, module: NGC_Payout_Scheduler | — |
| WF-17 | Parent Review | PARTIAL | partial | module: NGC_Reviews, integrate: workflow-04-review-rating | trigger drift: SVG "UI display" vs runtime "review submit" |
| WF-18 | Tutor Rating | PARTIAL | partial | module: NGC_Reviews, integrate: workflow-04-review-rating | trigger drift: SVG "Rating display" vs runtime "review.submitted" |
| WF-19 | Support Workflow | VERIFIED | partial | theme pack: support_escalation, form: contact_support | trigger drift: SVG "POST contact_support" vs runtime "contact_support form" |
| WF-20 | Escalation Workflow | VERIFIED/PARTIAL | partial | theme pack: support_escalation | — |
| WF-21 | CRM Workflow | PARTIAL | partial | module: NGC_Fluentcrm_Adapter | trigger drift: SVG "FluentCRM contract" vs runtime "CRM adapter" |
| WF-22 | Email Workflow | VERIFIED | partial | module: NGC_Email_Adapter | trigger drift: SVG "Form / wp_mail_admin" vs runtime "email adapter" |
| WF-23 | WhatsApp Workflow | VERIFIED | full | module: theme/inc/openwa.php, e2e: blueprint-wf24-reminders-homepage.spec.ts | — |
| WF-24 | Notification Workflow | VERIFIED | partial | module: NGC_Session_Reminders, integrate: workflow-03-reminder-notification | trigger drift: SVG "Workflow actions" vs runtime "session.scheduled" |
| WF-25 | Dashboard Workflow | PARTIAL | partial | module: NGC_Studio_Dashboards | no Playwright spec match for: phase3 docker + dashboards; trigger drift: SVG "Login + page access" vs runtime "dashboard REST" |

## Automations folder (parallel path)

| File | Blueprint WF | External trigger | Companion equivalent |
|------|--------------|------------------|---------------------|
| automation-1-lead-intake.json | WF-07 | fluentform_form_submitted | NGC_Matching |
| automation-2-booking-confirmed.json | WF-10 | amelia_after_appointment_status_changed | NGC_Bookings |
| automation-3-payment-complete.json | WF-11 | woocommerce_order_status_changed | NGC_Payments |
| automation-4-tutor-registration.json | WF-03 | fluentform (tutor) | tutor_application_review |
| automation-5-cancellation.json | WF-14 | amelia cancel | booking_cancelled_alert |

## Strict translation actions

1. **Normalize IDs** — use blueprint `WF-NN` everywhere (rename Playwright `wf01` → blueprint WF-03).
2. **Refresh stale SVGs** — rows marked `svg-stale` (companion implemented, diagram still says not implemented).
3. **Wire WF-09** — automated matching: add orchestrator + Studio template or mark SVG as deferred.
4. **Deprecate or import `automations/`** — replace `REPLACE_WITH_*` or route through Companion adapters only.
5. **Re-run audit** — `node scripts/svg-to-flow-manifest.mjs && node scripts/audit-flow-gaps.mjs`
