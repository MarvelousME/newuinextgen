# WF-03: Tutor Registration

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | POST become_tutor form |
| **Preconditions** | Valid nonce |
| **Actor** | Tutor applicant |
| **Steps** | Queue -> mail -> bi_form_submitted -> ngt.tutor_application.submitted |
| **Decisions** | Nonce valid? |
| **Exceptions** | 403 |
| **Escalations** | None |
| **Outputs** | Queue + workflow |
| **DB changes** | ngc_form_queue, bi_rtm_queue, bi_workflow_log |
| **Notifications** | RTM + admin mail |
| **Audit** | tutor_application_review |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
