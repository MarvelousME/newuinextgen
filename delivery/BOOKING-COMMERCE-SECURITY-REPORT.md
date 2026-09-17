# Booking / session security report

**Date:** 2026-09-17  
**Companion:** 1.9.23

## Proven

| Control | Result | Evidence |
|---|---|---|
| Unauthenticated launch | PASS | Playwright POST `/wp-json/ngc/v1/sessions/1/launch` denied (401/403/404) |
| Parent checkout REST requires login | PASS | `NGC_Rest::require_login` on `/ngc/v1/checkout/parent`; Playwright unauthenticated POST denied |
| Unpaid join denied | PASS | session 26 while `awaiting_payment` → `ngc_join_denied` / `payment_required` |
| Non-participant launch denied | PASS | stranger → `ngc_session_forbidden` |
| Join window server-side | PASS | too_early 409; allowed only inside 5 minutes before start |
| Meeting URL not in dashboard payload | PASS | `NGC_Session_Presenter` sets `joinUrl`/`meetingUrl` empty; headed dashboard JOIN href empty/`#` |
| Price tamper rejected | PASS | unit: `NGC_Session_Price_Integrity` `ngc_price_tamper` |
| Tutor mismatch rejected | PASS | unit: `ngc_tutor_mismatch` |
| Subject mismatch rejected | PASS | unit: `ngc_subject_mismatch` |
| Minor is not billing customer | PASS | parent 24 billed; child 25 `parent_pays_for_child` |
| Confirmed booking without session cannot join via meetings helper | PASS | `NGC_Meetings::can_join_status` requires session join policy |
| Duplicate paid provision | PASS | same session id 26 |
| `booking.create` Policy Bridge | PASS | Parent 24 with `ngc_book_sessions`; capability registered in Docker; booking 31 created via `NGC_Bookings::create` |
| PayFast sandbox ITN | PASS | Order 2165 signed ITN `COMPLETE`; amount tamper rejected; replay idempotent; hosted 302 payment session |

## Fail / residual risk

| Control | Result | Evidence | Required action |
|---|---|---|---|
| Headed IDOR across all dashboards | PARTIAL | Parent dashboard headed; student dashboard screenshot taken; tutor dashboard not headed this run | Add tutor persona headed spec |
| Live PayFast merchant | NOT IN REPO | Sandbox merchant `10000100` is prefilled | Uncheck Sandbox and paste live Merchant ID / Key / Passphrase |
| Launch response contains meeting URL | Expected after auth | Student/tutor launch JSON includes Jitsi URL | Do not log launch JSON to public telemetry; HTTPS only |
| Native STM complete | FAIL | `stm_lms_complete_lesson` aborts PHP | Keep NGT completion path; do not call native STM complete |

## Secrets

No PayFast merchant keys, ITN passphrases, or WP passwords were written into reports or evidence JSON beyond test users `@example.test`.
