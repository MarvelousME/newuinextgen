# WF-02: Student Registration

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | POST `student_register` form |
| **Preconditions** | Valid nonce |
| **Actor** | Visitor / student |
| **Steps** | Sanitize -> queue -> mail -> OpenWA -> dispatch `ngt.student_register.submitted` |
| **Decisions** | Nonce valid? |
| **Exceptions** | 403 |
| **Escalations** | None |
| **Outputs** | Queued submission + workflow actions |
| **DB changes** | `ngc_form_queue`, `bi_rtm_queue`, `bi_workflow_log` |
| **Notifications** | Admin email (+ OpenWA) + RTM staff |
| **Audit** | `student_register_intake` workflow |

## Code anchors

- `inc/shortcodes-fallback.php` — form intake
- `inc/workflows.php` — dispatcher
- `content/nextgen-workflow-pack.json` — `student_register_intake`
