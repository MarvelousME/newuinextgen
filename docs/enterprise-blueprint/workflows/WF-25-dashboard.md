# WF-25: Dashboard Workflow

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | Login + page access |
| **Preconditions** | Role match |
| **Actor** | Authenticated user |
| **Steps** | Shell -> dashboard-rest.js -> ngc/v1 |
| **Decisions** | Role gate |
| **Exceptions** | REST fallback |
| **Escalations** | — |
| **Outputs** | Dashboard view |
| **DB changes** | External API |
| **Notifications** | — |
| **Audit** | — |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
