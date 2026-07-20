# NextGen Command Center

Mission Control dashboards, RTM staff communication, and workflow v2 definition catalog.

| Field | Value |
|-------|--------|
| **Plugin slug** | `nextgen-command-center` |
| **Version** | 1.0.0 (`NGCC_VERSION`) |
| **Source zip** | `content/nextgen-command-center-v1.0.zip` |
| **Extracted** | `content/_extracted/nextgen-command-center-v1.0/` |

---

## Purpose

Provides the **operational visibility layer** for NextGen Tutors staff:

- Live Mission Control metrics (merged with Companion analytics when active)
- Role-based dashboard shells (parent, student, tutor)
- Staff RTM (real-time messaging) hub with Jitsi audio/video room links
- System event logging (WooCommerce payments, etc.)
- Workflow v2 JSON definition import (12 workflows)

Command Center does **not** replace Companion business logic — it surfaces and communicates operational state.

---

## Installation

1. Extract zip to `wp-content/plugins/nextgen-command-center/`
2. Activate plugin
3. WP Admin → **NextGen Command Center** → **Run / Repair Setup**
4. Optional: **Import Workflow JSON v2**

Docker: auto-mounted from `content/_extracted/nextgen-command-center-v1.0/`.

---

## Custom post types

| CPT | Purpose |
|-----|---------|
| `ngt_rtm_room` | Staff/tutor chat rooms with Jitsi URLs |
| `ngt_rtm_message` | Messages within rooms |
| `ngt_system_event` | Payment/commerce event log |
| `ngt_task` | Operational tasks |
| `ngt_workflow` | Imported v2 workflow definitions |

---

## Pages created by setup

| Slug | Shortcode |
|------|-----------|
| `/nextgen-command-center/` | `[ngcc_command_center]` |
| `/nextgen-parent-dashboard/` | `[ngcc_parent_dashboard]` |
| `/nextgen-student-dashboard/` | `[ngcc_student_dashboard]` |
| `/nextgen-tutor-dashboard/` | `[ngcc_tutor_dashboard]` |
| `/nextgen-staff-rtm/` | `[ngcc_rtm_hub]` |

---

## RTM rooms (seeded)

| Room slug | Purpose |
|-----------|---------|
| `admin-room` | Governance, escalations |
| `staff-room` | Daily operations |
| `tutor-support-room` | Tutor onboarding and delivery |
| `booking-room` | Matching, Amelia bookings |
| `finance-room` | Payments, payouts |
| `support-escalation-room` | Support Board / Fluent Support escalations |

### RTM bridge from theme workflow pack

When Companion/theme workflows fire `create_rtm_message` actions, messages are queued in `bi_rtm_queue` and **mirrored** to Command Center `ngt_rtm_message` posts via `NGC_Content_Pack_Bridge`.

Room mapping:

| Workflow pack room | Command Center room |
|--------------------|---------------------|
| `staff` | `staff-room` |
| `admin` | `admin-room` |
| `tutor-support` | `tutor-support-room` |
| `booking-issues` | `booking-room` |
| `payment-issues` | `finance-room` |
| `escalated-support` | `support-escalation-room` |

---

## REST API

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `ngcc/v1/rooms/{id}/messages` | GET | List room messages |
| `ngcc/v1/rooms/{id}/messages` | POST | Send message |

Auth: logged-in user with room role access.

---

## Mission Control metrics

`NGCC_Admin::metrics()` returns live counts. When Companion is active, merges:

- `NGC_Platform_Analytics::snapshot()` — users, bookings, revenue, applicants
- `ngc_matches` table — parent leads
- `ngc_workflow_runs` — workflow execution history
- Completion Suite CPTs — progress reports, notes, payouts

Filter: `ngcc_mission_control_metrics`

---

## Workflow v2 catalog

Location: `workflows-v2/` (12 JSON files, `NGT-V2-001` through `012`)

| ID | Slug | Trigger |
|----|------|---------|
| 001 | parent-request-tutor | `find_a_tutor_form_submitted` |
| 002 | tutor-application | `tutor_application_submitted` |
| 003 | tutor-approved | `tutor_approved` |
| 004 | student-created | `student_created` |
| 005 | booking-created | `booking_created` |
| 006 | booking-cancelled | `booking_cancelled` |
| 007 | payment-received | `woocommerce_order_status_completed` |
| 008 | lesson-completed | `lesson_status_completed` |
| 009 | progress-report-submitted | `progress_report_submitted` |
| 010 | parent-review-submitted | `parent_review_submitted` |
| 011 | support-escalated | `support_escalated` |
| 012 | payout-approved | `payout_approved` |

These are **definition records** (`import_mode: definition_record`). Execution is via Companion orchestrator + theme workflow pack + AutomatorWP recipes.

Imported copies also live in `NextGenTutors-Companion/integrate/catalog/v2/`.

---

## WooCommerce bridge

On `woocommerce_order_status_completed`, creates `ngt_system_event` post for Mission Control audit trail.

---

## Admin

**WP Admin → NextGen Command Center**

- Mission Control metric grid
- Run / Repair Setup
- Import Workflow JSON v2
- Page and shortcode reference

---

## Related docs

- [INTEGRATION-CATALOG.md](../workflows/INTEGRATION-CATALOG.md)
- [PACKAGES.md](../PACKAGES.md)
- [OPERATOR-TUTORIALS.md](../tutorials/OPERATOR-TUTORIALS.md)
