# WF-10: Booking Workflow

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | amelia.booking.created |
| **Preconditions** | Amelia active |
| **Actor** | Parent / tutor |
| **Steps** | Log -> RTM booking + tutor-support |
| **Decisions** | — |
| **Exceptions** | Amelia inactive |
| **Escalations** | booking-issues |
| **Outputs** | Notifications |
| **DB changes** | bi_rtm_queue |
| **Notifications** | RTM |
| **Audit** | amelia_booking_created |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
