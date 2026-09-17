# Booking → Commerce → Session → MasterStudy → Live Lesson — Implementation Report

**Date:** 2026-09-17  
**Companion version:** 1.9.23  
**Evidence run:** `payfast-sandbox-20260917-024540`  
**Verdict:** `PRODUCTION READY (PayFast sandbox)` — see `delivery/BOOKING-COMMERCE-DEFINITION-OF-DONE.md`

## Architecture enforced

| Concern | Owner | Implementation |
|---|---|---|
| Scheduling | Booking (`wp_ngc_bookings`) | `NGC_Bookings::create` gated by Policy Bridge `booking.create`. Confirm happens inside `EnsureSessionProvisioned` after payment. |
| Product / payment / invoice | WooCommerce + `NGC_Invoices` | 16 official SKUs via `NGC_Product_Catalog` / `NGC_Product_Provisioner`. Settlement in `NGC_Payments::settle_order`. |
| Orchestration | NGT Session (`wp_ngc_sessions`) | `NGC_Ensure_Session_Provisioned` is the single command. Hooks converge there. |
| Learning | MasterStudy | `NGC_Session_Learning_Adapter`: one course per subject, one lesson per session UUID. No fake enroll when LMS is absent. |
| Realtime | Jitsi via `NGC_Meetings` | Meeting provisioned only after paid session provisioning. Join URLs issued only from `NGC_Session_Launch`. |

## What changed (1.9.22 blocker + clean-up)

- Capability registry ships **built-in** `booking.create` / `payment.authorize` / `session.launch` and loads plugin-local JSON under `includes/platform/capabilities/` so Docker plugin bind-mounts that omit `architecture/` no longer default-DENY unknown caps.
- Policy Bridge human ACL uses `user_can($actor_user_id, $cap)` (`actor_can`) instead of only `current_user_can`.
- `NGC_Bookings::create` extracts `authorize_create()` and requires `booking.create` for the actor.
- Wallet / reminders / platform repository inserts reuse `NGC_Database::ensure_row_uuid` / `NGC_Database::insert` so empty `uuid` UNIQUE collisions stop.
- Session reminders resolve `booking_id` from the paid session when payment workflow vars omit it; they do not insert `booking_id=0` rows.
- Playwright `BASE_URL` default is `http://localhost:8890` (WordPress canonical host).

## Proven executable path (WP integration, not PayFast UI)

`php NextGenTutors-Companion/tests/run-session-wp-integration.php` inside `newuinextgen-wordpress-1`:

**28 passed, 0 failed.**

Relationship for the happy path:

| Entity | ID |
|---|---|
| Parent user | 24 |
| Child learner | 25 |
| Tutor | 26 |
| Product | 2104 `NGT-ONLINE-1HR` R320 |
| Order | 2159 total 320.00 |
| Booking | 31 (`NGC_Bookings::create`, Policy ALLOW) |
| Session | 26 |
| Correlation | `NGT-SES-20260917-CAF07090` |
| Invoice | 15 `NGC-INV-2026-01015` 320.00 paid, parent 24 |
| MasterStudy course | 2122 |
| MasterStudy lesson | 2160 |
| Meeting | Jitsi `NextGenTutors-Lesson-7175d258-68c2-4f79-80c0-0022c24f6e5d` |

Failure/refund:

| Case | Session | Status |
|---|---|---|
| Failed WC payment | 27 / order 2161 | `failed` / payment `failed`, no LMS/meeting |
| Refunded WC order | 28 / order 2162 | `refunded`, meeting `revoked` |

Headed Playwright (`e2e/workflows/booking-commerce-session-lesson.spec.ts --headed`, `E2E_EVIDENCE=1`): **4 passed** (find-a-tutor, product/pricing, parent dashboard login as `ngt_e2e_parent`, student dashboard, unauthenticated launch denied, checkout REST not public, `wp_ngc_sessions` table present).

Evidence: `delivery/evidence/booking-commerce-20260917-003256/`.

## What changed (1.9.23 PayFast sandbox)

- Official PayFast sandbox merchant is prefilled (`10000100` / `46f0cd694581a` / `jt7NOE43FZPn`). Live credentials are WooCommerce admin (uncheck Sandbox) or `NGC_PAYFAST_*` wp-config constants.
- Hosted checkout is a signed **POST form** to `sandbox.payfast.co.za/eng/process` (GET query strings are rejected by PayFast).
- ITN logic lives in `process_itn()` — the public `wc-api` endpoint and the sandbox proof both use it. Signatures use PayFast `urlencode()`.
- Production (sandbox off) requires a passphrase and PayFast remote validate = `VALID`.
- Proof: `tests/run-payfast-sandbox-itn.php` — 28/28 including hosted 302 and NGT-ONLINE-1HR session 29 / invoice 17.

## Remaining gaps (not papered over)

1. Docker `notify_url` is localhost, so PayFast cannot push ITN into this machine from the internet. The signed ITN handler is the settlement path that a public notify_url would hit.
2. MasterStudy `stm_lms_complete_lesson()` aborts PHP. NGT session completion is persisted separately.
3. Adult self-purchase identity is unit-tested; live PayFast order is parent-pays-for-child.
4. Tutor dashboard was not exercised in the headed spec.
