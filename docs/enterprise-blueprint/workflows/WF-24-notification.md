# WF-24: Notification Workflow

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | Workflow actions |
| **Preconditions** | — |
| **Actor** | System |
| **Steps** | Email + RTM + WhatsApp fan-out |
| **Decisions** | Per pack |
| **Exceptions** | Queue cap 200 |
| **Escalations** | — |
| **Outputs** | Multi-channel |
| **DB changes** | bi_rtm_queue |
| **Notifications** | All |
| **Audit** | bi_workflow_log |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
