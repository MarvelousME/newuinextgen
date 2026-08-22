# BOOKING-COMMERCE Implementation Report

**Date:** 2026-08-09  
**Companion version:** 1.9.19  
**Baseline SWOT/GAP:** `delivery/BOOKING-COMMERCE-LESSON-SWOT-GAP.md`

## Release verdict

```text
STAGING ONLY
```

## Implemented (Phases 4–12)

| Component | Path | Behaviour |
| --- | --- | --- |
| Sessions table | `includes/class-ngc-database.php` → `wp_ngc_sessions` | UUID, correlation_id, order/booking/MS/meeting fields |
| State machine | `includes/session/class-ngc-session-states.php` | Validated transitions; FAILED → AWAITING_PAYMENT for retry |
| Repository | `includes/session/class-ngc-sessions.php` | CRUD + idempotency_key unique |
| Orchestrator | `includes/session/class-ngc-session-orchestrator.php` | `ensure_provisioned`; **READY/meeting/MS only when paid**; launch prefers classroom |
| Classroom shell | `includes/session/class-ngc-session-classroom.php` | `?ngt_classroom={id}` — Course Player iframe + Enter live meeting |
| Product provisioner | `includes/integrations/class-ngc-product-provisioner.php` | CSV upsert + `_ngt_product_key` |
| REST launch | `includes/rest/class-ngc-rest-sessions.php` | `POST /ngc/v1/sessions/{id}/launch` → `classroom_url` |
| MS learning | `includes/adapters/class-ngc-masterstudy-adapter.php` | enroll/lesson helpers |
| Parent checkout | `class-ngc-parent-checkout.php` | hydrate booking → product + `_ngt_*` item meta; login+ownership |
| Dashboard join | `dashboard-rest.js` (×3) | POST launch; prefer classroom/player/meeting |
| Amelia sync | `NGC_Bookings::sync_from_amelia` | defaults to `requested` |
| Join gate | `NGC_Meetings::can_join_status` | **confirmed only** |
| Invoice UUID | `NGC_Invoices` + `NGC_Database::ensure_row_uuid` | Fixes blank uuid UNIQUE failures |
| Docker commerce script | `scripts/booking-commerce-e2e-docker.php` | Scenarios 1–5 DB chain — PASS |
| Classroom seed | `scripts/seed-classroom-join-window.php` | Paid session forced into join window |
| Headed Playwright | commerce smoke + `classroom-course-player-e2e.spec.ts` | Classroom **3/3 PASS** |
| Unit tests | `tests/run-session-unit.php` | PASS |

### Hook convergence

```text
ngc_booking_confirmed  ──┐
ngc_payment_settled    ──┼──► ensure_provisioned
woocommerce failed     ──┘      (no READY / no meeting until paid)
```

### Critical fix this completion pass

Unpaid/`failed` WooCommerce orders previously still reached session `status=ready`. Orchestrator now holds `awaiting_payment`/`failed` and skips meeting + MasterStudy side effects until paid. Proven by `payment_failure_blocks_ready` on run `bc-20260809-113004`.

## Evidence locations

| Artifact | Path |
| --- | --- |
| Docker PASS | `delivery/evidence/booking-commerce/bc-20260809-122856/` |
| Classroom seed | `delivery/evidence/booking-commerce/classroom-seed/latest.json` |
| Classroom headed | `delivery/evidence/booking-commerce/classroom-2026-08-09T12-45-31-840Z/` |
| Prior PASS + DB export | `delivery/evidence/booking-commerce/bc-20260809-104545/` |
| Headed screenshots/API | `delivery/evidence/booking-commerce/bc-headed-2026-08-09T11-06-07-428Z/` |

## Remaining gaps (explicit)

1. Full headed purchase → PayFast → join window → Course Player → dual join → complete  
2. Classic WC cart UX from tutor profile package selection (current path uses direct order creation)  
3. Exhaustive cross-role IDOR headed matrix  
4. Observability metrics backend may be partial depending on `NGC_Metrics` availability  
