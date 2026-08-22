# Authz matrix (SSOT)

Runtime class: `NGC_Authz_Matrix`  
Published pack: `architecture/policies/authz-matrix.json`  
Default privileged decision: **DENY**.

Object-level booking/session checks stay in `NGC_Access` (IDOR). Role → capability grants stay in the matrix. Cross-boundary capability invoke still goes through `NGC_Policy_Bridge`.

## Privileged capabilities

`manage_options`, `ngc_manage_platform`, `ngc_manage_safeguarding`, `ngc_manage_fraud`, `ngc_view_finance`, `ngc_manage_payouts`, `ngc_view_ledger`.

Student / Parent / Tutor must not hold these. Unauthenticated callers are denied.

## Public REST (SEC-03)

These routes are intentionally unauthenticated and must stay throttled or read-only:

| Method | Route | Control |
|---|---|---|
| POST | `/ngc/v1/support/tickets` | IP throttle (8/hour) |
| GET | `/ngc/v1/support/status` | Adapter presence only |

Do not add privileged data to public routes. Metrics scrape uses a bearer token or admin session, not anonymous access.
