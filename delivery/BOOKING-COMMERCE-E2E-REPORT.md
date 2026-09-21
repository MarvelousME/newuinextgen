# Booking → Commerce → Session → Live Lesson — E2E Report

**Date:** 2026-09-17  
**Environment:** Docker `newuinextgen-wordpress-1` + `newuinextgen-db-1` (WordPress published `0.0.0.0:8890`, site URL `http://localhost:8890`)  
**Companion:** 1.9.23  

Statuses used: PASS / FAIL / BLOCKED / NOT APPLICABLE only.

## Unit

| Test | Result | Evidence |
|---|---|---|
| Session domain smoke (`tests/run-session-unit.php`) | PASS | 29 passed, 0 failed (state machine, join window, 16 SKUs, price/tutor/subject tamper, correlation, adult identity) |

## WordPress integration + PayFast sandbox ITN

| Test | Result | Order | Booking | Session | Lesson | Meeting | DB Evidence | Browser Evidence |
|---|---|---|---|---|---|---|---|---|
| Parent pays for child — unpaid cannot join | PASS | 2159 pending then paid | 31 via `NGC_Bookings::create` | 26 `awaiting_payment` then `ready`/`completed` | — until paid | — until paid | `api/wp-integration.json` unpaid launch `ngc_join_denied` | PASS (headed dashboards) |
| Parent pays for child — paid provision | PASS | 2159 / 320.00 | 31 | 26 `NGT-SES-20260917-CAF07090` | course 2122 lesson 2160 | Jitsi room provisioned | `database/session-invoice-sku.txt` | PASS |
| Duplicate `EnsureSessionProvisioned` | PASS | 2159 | 31 | same id 26 | same | same | integration check duplicate same session | NOT APPLICABLE |
| Invoice one per order, amount = WC total | PASS | 2159 | 31 | 26 | — | — | invoice 15 `NGC-INV-2026-01015` 320.00 user 24 | PASS |
| Stranger cannot launch | PASS | 2159 | 31 | 26 | — | — | `ngc_session_forbidden` | NOT APPLICABLE |
| Join too early (5 minute window) | PASS | 2159 | 31 | 26 | — | — | reason `too_early` | PASS (JOIN href empty on dashboard) |
| Student JOIN LESSON (server launch) | PASS | 2159 | 31 | 26 | player issued on launch | URL issued only in launch response | integration JSON | PASS |
| Tutor JOIN LESSON | PASS | 2159 | 31 | 26 | same | same room | integration JSON | PASS |
| Tutor complete | PASS | 2159 | 31 | 26 `completed` | lesson recorded | — | DB session 26 status completed | PASS |
| Payment failed | PASS | 2161 | 32 | 27 `failed` | none | none | DB session 27 | NOT APPLICABLE |
| Refund after paid | PASS | 2162 | 33 | 28 `refunded` meeting revoked | course 2122 lesson 2163 | revoked | DB session 28 | NOT APPLICABLE |
| Adult student self-purchase live order | BLOCKED | — | — | — | — | — | Identity unit PASS only | BLOCKED |
| Unauthenticated REST launch | PASS | — | — | — | — | — | Playwright POST `/wp-json/ngc/v1/sessions/1/launch` → 401/403/404 | PASS |
| Policy `booking.create` parent | PASS | — | 31 | — | — | — | `booking.create allowed for parent` then `booking created` 31 | PASS (parent login) |
| Headed Playwright find-tutor → dashboards | PASS | — | — | — | — | — | 4/4 `booking-commerce-session-lesson.spec.ts --headed` | PASS screenshots + video + trace |
| PayFast hosted ITN with sandbox credentials | PASS | 2165 / 320.00 | 34 | 29 `NGT-SES-20260917-0019E51E` | course 2122 lesson 2166 | Jitsi | `payfast-sandbox-20260917-024540` ITN `COMPLETE` pf `pf_ngt_20260917024549_JmHM9W`; hosted 302 | PASS headed sandbox |

## Browser evidence

| Artifact | Result |
|---|---|
| Headed screenshots | PASS `delivery/evidence/booking-commerce-20260917-003256/browser/*.png` |
| Video | PASS `headed-journey.webm` |
| Playwright trace | PASS `headed-journey.trace.zip` |

## Notes

- WooCommerce `payment_complete()` is invoked **from the PayFast ITN handler**, not from the test harness.
- Sandbox hosted POST to `https://sandbox.payfast.co.za/eng/process` returned **302** to payment session `72ffa3e2-c413-4ad9-aee9-03b431cf2233`.
- Live merchant credentials are not in the repo. Uncheck Sandbox in WooCommerce Payments and paste them when you go live.
