# API Documentation

## API Families

- `ngc/v1` (internal platform REST)
- `nextgen/v1` (public tutor calendar)
- `admin-post` form submission handlers
- `wp-admin/admin-ajax.php` slot-selection action

## REST Endpoints

### Dashboard

| URL | Method | Auth | Parameters | Response | Status |
|---|---|---|---|---|---|
| `/wp-json/ngc/v1/dashboard/student` | GET | logged in | none | user+kpis+sessions | VERIFIED |
| `/wp-json/ngc/v1/dashboard/parent` | GET | logged in | none | user+kpis+learners | VERIFIED |
| `/wp-json/ngc/v1/dashboard/tutor` | GET | logged in | none | user+tutor KPIs | VERIFIED |
| `/wp-json/ngc/v1/dashboard/admin` | GET | admin | none | admin KPIs | VERIFIED |

### Matching

| URL | Method | Auth | Parameters | Response | Status |
|---|---|---|---|---|---|
| `/wp-json/ngc/v1/matches` | POST | `ngc_request_match` | learner needs | match row | VERIFIED |
| `/wp-json/ngc/v1/matches/{id}/accept` | POST | logged in | id | accepted result | VERIFIED |
| `/wp-json/ngc/v1/matches/{id}/reject` | POST | logged in | id | rejected result | VERIFIED |
| `/wp-json/ngc/v1/matches/{id}/assign` | POST | support/admin | id+tutor | assigned result | VERIFIED |

### Bookings

| URL | Method | Auth | Parameters | Response | Status |
|---|---|---|---|---|---|
| `/wp-json/ngc/v1/bookings` | GET | logged in | filters (`limit`,`status`) | bookings[] | VERIFIED |
| `/wp-json/ngc/v1/bookings` | POST | booking caps | booking payload | booking object | VERIFIED |
| `/wp-json/ngc/v1/bookings/{id}` | GET | owner/support | id | booking | VERIFIED |
| `/wp-json/ngc/v1/bookings/{id}` | PUT | booking caps | update payload | booking | VERIFIED |
| `/wp-json/ngc/v1/bookings/{id}` | DELETE | support | id | deleted flag | VERIFIED |
| `/wp-json/ngc/v1/bookings/{id}/status` | POST | booking caps | status | updated booking | VERIFIED |

### Reviews/Admin/Platform

| URL | Method | Auth | Parameters | Response | Status |
|---|---|---|---|---|---|
| `/wp-json/ngc/v1/reviews` | POST | parent review cap | rating/comment | review result | VERIFIED |
| `/wp-json/ngc/v1/reviews/tutor/{id}` | GET | public | tutor id | avg rating | VERIFIED |
| `/wp-json/ngc/v1/admin/tutors/{id}/approve` | POST | reviewer cap | id | app state | VERIFIED |
| `/wp-json/ngc/v1/admin/tutors/{id}/reject` | POST | reviewer cap | id+notes | app state | VERIFIED |
| `/wp-json/ngc/v1/admin/tutors/{id}/resubmit` | POST | reviewer cap | id+payload | app state | VERIFIED |
| `/wp-json/ngc/v1/admin/analytics` | GET | admin | none | stats | VERIFIED |
| `/wp-json/ngc/v1/admin/verification` | GET | admin | none | checks | VERIFIED |
| `/wp-json/ngc/v1/admin/payouts` | POST | payout cap | tutor_user_id,amount | payout id | VERIFIED |
| `/wp-json/ngc/v1/admin/repair` | POST | admin | none | repair result | VERIFIED |
| `/wp-json/ngc/v1/platform/entity/{entity}` | GET | login | filters | entity list | VERIFIED |
| `/wp-json/ngc/v1/platform/entity/{entity}/count` | GET | login | filters | count | VERIFIED |
| `/wp-json/ngc/v1/platform/analytics` | GET | admin | `demo` | analytics payload | VERIFIED |
| `/wp-json/ngc/v1/platform/profile/{user_id}` | GET | admin | user id | profile bundle | VERIFIED |
| `/wp-json/ngc/v1/platform/demo/{journey}` | GET | admin | journey | demo payload | VERIFIED |
| `/wp-json/ngc/v1/platform/demo/seed` | POST | admin | none | seed result | VERIFIED |
| `/wp-json/ngc/v1/platform/demo/clear` | POST | admin | none | clear result | VERIFIED |
| `/wp-json/ngc/v1/platform/verify` | GET | admin | none | verification report | VERIFIED |

### Public Tutor Calendar

| URL | Method | Auth | Parameters | Response | Status |
|---|---|---|---|---|---|
| `/wp-json/nextgen/v1/tutors/{tutor_id}/calendar` | GET | public | `from`,`to`,`subject`,`delivery_mode`,`timezone`,`demo` | privacy-safe calendar envelope | VERIFIED |

## AJAX/Internal Endpoints

| Endpoint | Method | Auth | Purpose | Security | Status |
|---|---|---|---|---|---|
| `admin-post.php?action=ngc_form_submit` | POST | public/private | public form ingestion | nonce + sanitization | VERIFIED |
| `admin-ajax.php?action=ngc_calendar_slot_selected` | POST | public/private | log slot CTA selection | nonce validation + audit log | VERIFIED |

## Error/Validation Model

- Validation failures return WordPress REST error envelopes (`code`, `message`, status).
- Admin and workflow errors are also tracked in audit/workflow logs.
- External integration failures degrade gracefully and surface `PARTIAL/BLOCKED` states.

## Versioning

- Namespace-level versioning (`ngc/v1`, `nextgen/v1`).
- Backward compatibility currently maintained by additive route strategy.

