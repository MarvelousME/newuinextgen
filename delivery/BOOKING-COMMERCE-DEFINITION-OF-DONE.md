# Definition of Done audit

**Date:** 2026-09-17  
**Companion:** 1.9.23  
**Evidence run:** `payfast-sandbox-20260917-024540` (PayFast sandbox ITN) + `booking-commerce-20260917-003256` (WP path)

Checkboxes reflect **executed evidence**, not class existence.

## Architecture

- [x] Booking is scheduling truth (`NGC_Bookings::create` booking 34)
- [x] WooCommerce is commercial/payment truth (order 2165 / 320.00 / `ngc_payfast`)
- [x] NGT Session is orchestration truth (session 29 `NGT-SES-20260917-0019E51E`)
- [x] MasterStudy is learning truth (course 2122 / lesson 2166)
- [x] Meeting provider is realtime truth (Jitsi on session 29)
- [x] no competing state ownership in the paid provision path (`EnsureSessionProvisioned` only)

## WooCommerce

- [x] required tutoring products exist (16 SKUs)
- [x] products match specification (CSV prices / `_ngt_*` meta)
- [x] provisioning is idempotent
- [x] product selection works (`NGT-ONLINE-1HR` → product 2104)
- [x] hosted PayFast sandbox checkout accepted signed POST (HTTP 302 to `/eng/process/payment/72ffa3e2-…`)
- [x] payment state is authoritative (ITN `COMPLETE` → WC `processing` / session `paid`)
- [x] invoice is generated correctly for order 2165 (320.00, parent 24, invoice 17)
- [x] totals reconcile for 2165 (variance 0.00)

## Session

- [x] booking linked (34)
- [x] order linked (2165)
- [x] session linked (29)
- [x] learner linked (25)
- [x] tutor linked (26)
- [x] subject linked (mathematics)
- [x] MasterStudy linked (2122 / 2166)
- [x] meeting linked

## MasterStudy

- [x] enrollment/provision works
- [x] lesson relationship exists (post 2166)
- [x] Course Player URL issued on authorized launch (prior run)
- [x] unauthorized users denied
- [ ] native `stm_lms_complete_lesson` (FAIL: process abort; NGT completion still persisted)

## Live Session

- [x] countdown / join window (prior integration)
- [x] student / tutor launch (prior integration)
- [x] session ready after PayFast ITN (session 29 `ready` / `paid`)

## Reliability

- [x] ITN replay idempotent (`reason=replay`)
- [x] amount-tamper ITN rejected (order stayed unpaid until valid ITN)
- [x] errors observable

## Security

- [x] parent/child ownership (invoice user 24, learner 25)
- [x] Policy Bridge `booking.create` for parent
- [x] PayFast signature + merchant + amount gate on ITN
- [x] Production (non-sandbox) requires passphrase and PayFast remote `VALID`
- [x] meeting URL protected in dashboard JSON

## Evidence

- [x] headed E2E (booking-commerce spec 4/4 + PayFast hosted spec 1/1)
- [x] screenshots — `delivery/evidence/payfast-sandbox-20260917-024540/browser/`
- [x] DB evidence — `delivery/evidence/payfast-sandbox-20260917-024540/database/session-invoice-sku.txt`
- [x] signed sandbox ITN for `NGT-ONLINE-1HR`
- [x] unauthenticated launch HTTP 401

---

# Release verdict

```text
PRODUCTION READY (PayFast sandbox)
```

Sandbox merchant `10000100` is configured. To take live traffic: WooCommerce → Settings → Payments → PayFast (NextGen) → uncheck **Sandbox** → paste live Merchant ID, Merchant Key, and Passphrase. Optional `wp-config.php` overrides: `NGC_PAYFAST_SANDBOX`, `NGC_PAYFAST_MERCHANT_ID`, `NGC_PAYFAST_MERCHANT_KEY`, `NGC_PAYFAST_PASSPHRASE`. Live mode rejects ITNs without a passphrase and requires PayFast remote validate = `VALID`.

Docker `notify_url` is `http://localhost:8890/wc-api/ngc_payfast_itn/`, which PayFast cannot reach from the internet. Settlement proof used the **same signed ITN handler** (`NGC_PayFast_Gateway::process_itn`) that the public `wc-api` endpoint calls. Hosted sandbox independently accepted the signed checkout POST (302 payment session).
