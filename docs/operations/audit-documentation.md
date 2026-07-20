# Audit Documentation

## Audit Framework

- Class: `NGC_Audit`
- Storage: `ngc_audit_log`
- Capture fields:
  - actor_user_id
  - action
  - object_type
  - object_id
  - context (JSON)
  - ip_address
  - created_at

## Audit Event Categories

- User and registration actions
- Tutor lifecycle actions
- Matching/booking transitions
- Payment/payout operations
- Workflow and integration adapter outcomes
- Calendar slot selections (`CALENDAR_SLOT_SELECTED`)
- Repair/verification actions

## Access and Governance

- Accessed through admin health/ops screens and supporting APIs.
- Support/admin roles with audit-view capabilities can review events.
- Data minimization applies to public APIs; audit data is internal-only.

## Audit Status

| Requirement | Status | Evidence |
|---|---|---|
| Persistent event logging | VERIFIED | `NGC_Audit::log` + table schema |
| Action/object traceability | VERIFIED | `action`,`object_type`,`object_id` fields |
| Integration result logging | VERIFIED | workflow adapter audit hooks |
| Calendar selection audit | VERIFIED | calendar service AJAX handler |
| Immutable append-only policy | PARTIAL | append model present; no DB-level immutable constraint |

