# WF-21: CRM Workflow

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | FluentCRM contract |
| **Preconditions** | CRM external |
| **Actor** | Marketing |
| **Steps** | No direct API in theme |
| **Decisions** | — |
| **Exceptions** | — |
| **Escalations** | — |
| **Outputs** | — |
| **DB changes** | External |
| **Notifications** | — |
| **Audit** | — |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
