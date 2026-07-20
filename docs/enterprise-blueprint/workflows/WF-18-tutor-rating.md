# WF-18: Tutor Rating

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | Rating display |
| **Preconditions** | — |
| **Actor** | Visitor |
| **Steps** | Render stars from tutor data |
| **Decisions** | — |
| **Exceptions** | — |
| **Escalations** | — |
| **Outputs** | Display |
| **DB changes** | Post meta / demo |
| **Notifications** | — |
| **Audit** | — |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
