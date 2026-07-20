# WF-22: Email Workflow

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | Form / wp_mail_admin |
| **Preconditions** | WP mail configured |
| **Actor** | System |
| **Steps** | wp_mail to admin |
| **Decisions** | — |
| **Exceptions** | Delivery external |
| **Escalations** | — |
| **Outputs** | Email |
| **DB changes** | — |
| **Notifications** | Email |
| **Audit** | — |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
