# WF-15: Session Completion

**Evidence:** PARTIAL  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | ngt.lesson.completed |
| **Preconditions** | External LMS |
| **Actor** | Tutor / system |
| **Steps** | Log -> RTM lesson-issues |
| **Decisions** | — |
| **Exceptions** | No theme emitter |
| **Escalations** | lesson-issues |
| **Outputs** | Progress note |
| **DB changes** | bi_rtm_queue |
| **Notifications** | RTM |
| **Audit** | lesson_completed_awards |

## Code anchors

- inc/shortcodes-fallback.php — form intake
- inc/workflows.php — dispatcher
- inc/security.php — dashboard access (WF-25)
- inc/openwa.php — WhatsApp (WF-23)
