# Workflow Security Audit

**Status:** PENDING — Phases 8–18 not started. No implementation security test has been executed.

**Date:** 2026-08-14

## Static findings (pre-implementation)

| Finding | Evidence | Risk |
|---|---|---|
| Studio REST uses `manage_options` OR `ngc_admin_operations` only | `NGC_Rest::require_admin` | Over-privileged; no `ngt_view_workflows` split |
| Requested workflow caps do not exist | repo grep empty | GAP |
| PayFast ITN has signature, replay, rate limit | `NGC_PayFast_ITN`, `NGC_Rate_Limiter` | KEEP |
| Authority jobs use idempotency | `NGC_Idempotency::once` | KEEP |
| Inspector must redact secrets/child data | Not centralized for studio live payloads | GAP for visualizer |
| Studio assets already page-scoped | `NGC_Studio_Admin::enqueue_assets` | KEEP pattern |

## Tests (not run)

- RBAC: not run  
- Unauthorized REST: not run  
- Secret leakage: not run  
- Nonce/capability on control verbs: not applicable until implemented  

No pass/fail counts are claimed.
