# WF-20: Escalation Workflow

**Evidence:** VERIFIED/PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | ngt.support.escalated |
| **Preconditions** | Support intake |
| **Actor** | Support / admin |
| **Steps** | RTM + admin mail |
| **Decisions** | Priority high |
| **Exceptions** | No ticket lifecycle |
| **Escalations** | escalated-support |
| **Outputs** | Staff alert |
| **DB changes** | bi_rtm_queue |
| **Notifications** | RTM + mail |
| **Audit** | Workflow log |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
