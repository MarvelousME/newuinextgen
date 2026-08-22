# BOOKING-COMMERCE Definition of Done

**Updated:** 2026-08-09  
**Evidence:** `bc-20260809-122856` + `classroom-2026-08-09T12-45-31-840Z` + prior headed commerce smoke

| Criterion | Status | Evidence |
| --- | --- | --- |
| Booking is scheduling truth | PASS | `wp_ngc_bookings` |
| WooCommerce is commercial truth | PASS | Product provisioner + settle |
| NGT Session is orchestration truth | PASS | `wp_ngc_sessions` + orchestrator |
| MasterStudy is learning truth | PASS | Course/lesson IDs + classroom iframe |
| Meeting provider is realtime truth | PASS | Jitsi after pay; classroom live CTA |
| Course Player → Live Meeting hop | PASS | `NGC_Session_Classroom`; headed 3/3 |
| Unpaid cannot be ready | PASS | `payment_failure_blocks_ready` |
| Join window server-side | PASS | Docker + classroom seed `within_window` |
| Launch no URL leak in dashboard HTML | PASS | Commerce headed COMMERCE-003 |
| Idempotent provision | PASS | Docker duplicate suppressed |
| Invoice reconcile | PASS | Docker invoice assert |
| Headed dual classroom entry | PASS | Parent + tutor classroom screenshots |
| Product recording 10s | UNVERIFIED | Not implemented |
| Full cart→PayFast headed film | BLOCKED | Direct order path used in harness |

## Verdict

```text
STAGING ONLY
```

`PRODUCTION READY` denied while recording remains unimplemented and continuous full Scenario 1 browser film is incomplete.
