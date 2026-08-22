# BOOKING-COMMERCE Security Report

**Updated:** 2026-08-09

## Controls verified

| Control | Status | Evidence |
| --- | --- | --- |
| Session launch requires login | PASS | Headed COMMERCE-005 → 401/403 |
| Launch party + join window server-side | PASS | COMMERCE-004 → 409 `ngc_join_window` |
| Meeting URL not embedded in dashboard HTML | PASS | COMMERCE-003 asserts no `meet.jit.si` |
| Public session payload omits live URL | IMPLEMENTED | `NGC_Rest_Sessions::public_session` |
| Checkout REST requires login + ownership | IMPLEMENTED | `NGC_Parent_Checkout::rest_create_checkout` |
| Join only when booking `confirmed` | IMPLEMENTED | `NGC_Meetings::can_join_status` |
| Unpaid/failed order cannot become `ready` | PASS | `payment_failure_blocks_ready` on `bc-20260809-113004` |
| Payment settle requires WC paid/processing | PRE-EXISTING | `NGC_Payments::settle_order` |

## Residual risks

| Risk | Severity | Notes |
| --- | --- | --- |
| Full IDOR matrix (cross-parent/child/tutor) not exhaustively headed | Medium | Launch neg for anonymous only |
| Price tampering via alternate order create paths | Medium | Provisioner resolves product; enforce on all entry points |
| Meeting provisioned only after pay — legacy confirmed-without-order still allowed | Low | Intentional adapter for non-commerce bookings |
| Course Player not yet primary launch target | Medium | Launch may still prefer meeting URL when MS player absent |

## Verdict

```text
STAGING ONLY
```
