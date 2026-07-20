# WF-23: WhatsApp Workflow

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | OpenWA send/webhook |
| **Preconditions** | OpenWA enabled |
| **Actor** | System / user |
| **Steps** | bi_openwa_* |
| **Decisions** | Enabled flag |
| **Exceptions** | API errors |
| **Escalations** | — |
| **Outputs** | WhatsApp msg |
| **DB changes** | bi_openwa_inbox |
| **Notifications** | WhatsApp |
| **Audit** | PARTIAL |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
