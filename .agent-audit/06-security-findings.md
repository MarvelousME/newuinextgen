# 06 — Security Findings (Phase 3)

**Date:** 2026-07-20

| ID | Finding | Severity | Status | Evidence / Fix |
|----|---------|----------|--------|----------------|
| SEC-PF-01 | PayFast ITN validated signature only — no amount/merchant/replay checks | P0 | **FIXED** | `NGC_PayFast_Itn::validate_notification` + gateway handler |
| SEC-PF-02 | Production ITN allowed empty passphrase | P0 | **FIXED** | `passphrase_policy_ok` rejects non-sandbox empty passphrase |
| SEC-PF-03 | Duplicate ITN could re-enter settle paths | P1 | **FIXED** | Transient + order meta `_ngc_payfast_pf_payment_id`; idempotent 200 on replay |
| SEC-PF-04 | ITN endpoint unthrottled | P1 | **FIXED** | `NGC_Rate_Limiter::check( 'payfast_itn', 120, 60 )` |
| SEC-REST-01 | Section CMS public reads `__return_true` without throttle | P1 | **FIXED** | `public_throttled( 'section_cms_read', 90, 600 )` |
| SEC-REST-02 | Finance `user_id` param | — | VERIFIED OK | Only admins may resolve other user IDs |
| SEC-REST-03 | Marketplace public search | — | VERIFIED | Already throttled |
| SEC-001 | phpMyAdmin :8082 | P1 (non-local) | **FIXED** | Compose binds `${PMA_BIND:-127.0.0.1}:${PMA_PORT}:80`; `.env` / `.env.example` set `PMA_BIND=127.0.0.1` |
| SEC-IDOR-01 | Booking mutate/view by capability only | P0 | **FIXED** | `NGC_Access` + REST `can_view` / `can_mutate`; create/update payload sanitize |
| SEC-IDOR-02 | Match reject without ownership | P0 | **FIXED** | `NGC_Matching::reject` + REST `can_act` |
| SEC-IDOR-03 | Booking list ignored parent scope | P1 | **FIXED** | Parent list uses `query_for_parent`; strips foreign filters |
| SEC-IDOR-04 | Hub lesson complete any logged-in user | P1 | **FIXED** | `NGT_Hub_Lessons::user_can_complete` |
| AGT-INJ-01 | Prompt-injection style action IDs | P1 | **FIXED** (policy) | Unknown actions → DENY; harness covers |

## Abuse tests executed (harness)

| Test | Result |
|------|--------|
| Tampered PayFast amount | REJECTED VERIFIED |
| Invalid signature | REJECTED VERIFIED |
| Replay pf_payment_id | DETECTED VERIFIED |
| Production empty passphrase | REJECTED VERIFIED |
| Secret exfil / audit disable / shell / self-grant | DENIED VERIFIED |
| Forged autonomy level | REQUIRE_APPROVAL VERIFIED |
| Foreign booking view / create student | DENIED VERIFIED (unit) |
| Foreign match act | DENIED VERIFIED (unit) |
