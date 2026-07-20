# WF-07: Tutor Matching

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | ngt.find_tutor.submitted |
| **Preconditions** | Valid find_tutor form |
| **Actor** | Parent / staff |
| **Steps** | Dispatch -> RTM staff + booking-issues -> mail |
| **Decisions** | SLA messaging only |
| **Exceptions** | No matcher |
| **Escalations** | booking-issues room |
| **Outputs** | Staff notification |
| **DB changes** | bi_rtm_queue |
| **Notifications** | RTM + mail |
| **Audit** | parent_find_tutor_intake |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
