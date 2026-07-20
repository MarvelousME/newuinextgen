# WF-19: Support Workflow

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | POST contact_support |
| **Preconditions** | Valid nonce |
| **Actor** | Any user |
| **Steps** | Queue -> mail -> ngt.support.escalated |
| **Decisions** | Nonce valid? |
| **Exceptions** | 403 |
| **Escalations** | escalated-support |
| **Outputs** | Escalation |
| **DB changes** | ngc_form_queue, bi_rtm_queue |
| **Notifications** | RTM + mail |
| **Audit** | support_escalation |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
