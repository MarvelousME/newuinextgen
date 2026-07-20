# WF-08: Manual Matching

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | Operational RTM |
| **Preconditions** | Intake in queue |
| **Actor** | Operations staff |
| **Steps** | Manual assignment external |
| **Decisions** | Staff judgment |
| **Exceptions** | — |
| **Escalations** | booking-issues |
| **Outputs** | External assignment |
| **DB changes** | External |
| **Notifications** | RTM |
| **Audit** | PARTIAL |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
