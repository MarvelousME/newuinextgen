# Executive Documentation

## Executive Summary

NextGen is a WordPress-based tutoring marketplace platform split across:

- Theme: `beyondinfinity`
- Companion plugin: `nextgencompanion`

Core capabilities implemented in code include onboarding, tutor lifecycle, matching, booking, payments orchestration hooks, invoicing, wallets, payouts, reviews, workflow automation, analytics/tracking, and public tutor calendars. Platform logic is plugin-centric, with theme-centric presentation and graceful fallbacks.

## Product Vision

Deliver a trusted tutoring marketplace with:

- role-aware experiences for parent/student/tutor/admin/finance/support
- verifiable tutor vetting and booking workflow controls
- auditable events, consent-aware tracking, and operational health checks

## Business Goals

- Acquire and convert parents/students through tutor discovery and booking.
- Improve tutor onboarding and activation with workflow automation.
- Reduce operational burden via admin verification/self-healing tools.
- Establish data transparency with real-data dashboards and profiling.

## Mission

Operate a secure, extensible, evidence-driven tutoring platform where every user role can complete its journey without custom code intervention.

## Stakeholders

- Business owner
- Operations/admin
- Finance team
- Support team
- Tutors and tutor applicants
- Parents/guardians
- Students
- Engineering/QA/DevOps

## User Personas

- **Parent/Guardian**: acquires tutor, manages learners, bookings, payments, reviews.
- **Student**: attends sessions, tracks progress, views schedules.
- **Tutor Applicant**: submits profile, progresses through vetting.
- **Approved Tutor**: receives matches, schedules lessons, tracks payouts.
- **Admin**: approves tutors, supervises workflows/system health.
- **Finance**: validates invoices/payouts/payment states.
- **Support**: handles issue triage and escalations.
- **Developer/QA/DevOps**: extends, tests, deploys, and operates platform.

## Platform Capabilities

| Capability | Status | Evidence |
|---|---|---|
| Role + capability model | VERIFIED | `NGC_Roles`, `beyondinfinity/inc/security.php` |
| Registration workflows | VERIFIED | `NGC_Registration`, orchestrator workflows |
| Tutor lifecycle (approve/reject/resubmit/suspend) | VERIFIED | `NGC_Tutor_Lifecycle` |
| Matching and booking | VERIFIED | `NGC_Matching`, `NGC_Bookings`, REST |
| Payments/invoices hooks | PARTIAL | requires WooCommerce runtime |
| CRM/LMS integrations | PARTIAL/BLOCKED | FluentCRM/MasterStudy runtime dependencies |
| Amelia integration | PARTIAL/BLOCKED | Amelia + API key required |
| Real-data analytics/tracking | VERIFIED | platform repository/analytics/tracking services |
| Public tutor calendars | VERIFIED | tutor calendar service + REST + shortcode + theme partial |

## Competitive Positioning (Repository Evidence Only)

- Strong operational control via built-in verification + self-healing.
- Workflow-driven automation with auditable logs and retry queue.
- Clear plugin/theme boundary for portability and maintainability.

## Platform Roadmap (Code-Constrained)

- **Near-term**: runtime UAT hardening for WooCommerce/Amelia/FluentCRM/MasterStudy.
- **Mid-term**: expand public API docs coverage and diagram export automation.
- **Long-term**: production observability and release automation for staged rollouts.

## Risks

- Third-party plugin availability/version drift.
- Runtime environment mismatch vs source assumptions.
- Data privacy regressions if future customizations bypass repository/service layer.

## Dependencies

- WordPress core (roles/users/posts/meta/REST/cron)
- WooCommerce (payment/order lifecycle)
- FluentCRM (CRM sync)
- Amelia (calendar/appointments)
- MasterStudy LMS (student/instructor profile mapping)

## Assumptions

- Companion plugin remains the source of truth for platform logic.
- Theme remains presentation layer with plugin-aware integrations.
- External plugin credentials and API keys are managed in admin settings.

