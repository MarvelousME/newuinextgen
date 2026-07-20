# WF-04: Tutor Approval

**Evidence:** VERIFIED (theme runtime) / PARTIAL (user must exist)  
**Theme version:** 1.4.6

## BPMN Specification

| Field | Value |
|-------|-------|
| **Trigger** | `ngt.tutor.approved` via `bi_workflow_emit_tutor_approved()` |
| **Preconditions** | Valid WP user ID; admin `manage_options` for UI path |
| **Actor** | Admin (Operations screen) or companion bridge |
| **Steps** | Dispatch pack -> log -> `add_user_role` (alias `ngt_tutor` → `tutor`) -> set `ngt_tutor_verified` meta -> RTM tutor-support |
| **Decisions** | User exists? Role registered? |
| **Exceptions** | WP_Error if user missing; role alias fallback |
| **Escalations** | Manual follow-up if no WP user for applicant email |
| **Outputs** | Tutor role + verified meta + staff notifications |
| **DB changes** | User roles, user meta, `bi_rtm_queue`, `bi_workflow_log` |
| **Notifications** | RTM tutor-support |
| **Audit** | `tutor_approved_onboarding` workflow + `add_user_role` log |

## Admin path

Appearance → **NextGen Operations** → Approve tutor (when queue email matches WP user).

## Code anchors

- `inc/workflows.php` — `bi_workflow_apply_user_role()`, `bi_workflow_emit_tutor_approved()`
- `inc/admin.php` — Operations screen + `admin_post_bi_approve_tutor`
- `content/nextgen-workflow-pack.json` — `tutor_approved_onboarding`
