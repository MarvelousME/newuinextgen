# WF-01: Parent Registration

**Evidence:** VERIFIED  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | POST `parent_register` form |
| **Preconditions** | Valid nonce `ngc_form_parent_register` |
| **Actor** | Visitor / parent |
| **Steps** | Sanitize -> `ngc_form_queue` -> admin mail -> optional OpenWA -> `bi_form_submitted` -> dispatch `ngt.parent_register.submitted` |
| **Decisions** | Nonce valid? |
| **Exceptions** | 403 invalid submission |
| **Escalations** | None |
| **Outputs** | Queued submission + workflow actions |
| **DB changes** | `ngc_form_queue`, `bi_rtm_queue`, `bi_workflow_log` |
| **Notifications** | Admin email (+ OpenWA) + RTM staff |
| **Audit** | `parent_register_intake` workflow |

## Code anchors

- `inc/shortcodes-fallback.php` — form intake
- `inc/workflows.php` — dispatcher
- `content/nextgen-workflow-pack.json` — `parent_register_intake`
