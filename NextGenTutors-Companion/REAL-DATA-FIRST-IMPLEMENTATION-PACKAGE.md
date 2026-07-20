# Real-Data-First Platform Layer Package

## 1) File tree (changed/created)

```txt
nextgencompanion/
├── demo/
│   ├── demo_parent.json
│   ├── demo_student.json
│   ├── demo_tutor_applicant.json
│   ├── demo_approved_tutor.json
│   ├── demo_admin.json
│   ├── demo_finance.json
│   ├── demo_support.json
│   ├── demo_analytics.json
│   └── demo_acquisition.json
├── includes/
│   ├── class-ngc-database.php
│   ├── class-ngc-plugin.php
│   ├── class-ngc-verification.php
│   ├── class-ngc-bookings.php
│   ├── class-ngc-payments.php
│   ├── class-ngc-platform-repository.php
│   ├── class-ngc-platform-tracking.php
│   ├── class-ngc-platform-analytics.php
│   ├── class-ngc-platform-demo.php
│   ├── rest/class-ngc-rest.php
│   ├── rest/class-ngc-rest-dashboard.php
│   ├── rest/class-ngc-rest-admin.php
│   ├── rest/class-ngc-rest-platform.php
│   └── admin/
│       ├── class-ngc-admin.php
│       └── class-ngc-platform-admin.php
├── scripts/validate.php
└── REAL-DATA-FIRST-IMPLEMENTATION-PACKAGE.md

beyondinfinity/
└── assets/js/dashboard-rest.js
```

## 2) Table schemas

Created/verified in `NGC_Database::create_tables()`:

- `ngc_analytics_events`
- `ngc_visitor_profiles`
- `ngc_user_profiles`
- `ngc_acquisition_sources`
- `ngc_affiliate_clicks`
- `ngc_attribution_links`
- `ngc_user_sessions`
- `ngc_device_profiles`
- `ngc_conversion_events`
- `ngc_dashboard_metric_snapshots`
- `ngc_demo_seed_log`
- `ngc_consent_log`

All include PK + timestamp + reporting indexes.

## 3) REST endpoint list

- `GET /ngc/v1/platform/entity/{entity}`
- `GET /ngc/v1/platform/entity/{entity}/count`
- `GET /ngc/v1/platform/analytics`
- `GET /ngc/v1/platform/profile/{user_id}`
- `GET /ngc/v1/platform/demo/{journey}`
- `POST /ngc/v1/platform/demo/seed`
- `POST /ngc/v1/platform/demo/clear`
- `GET /ngc/v1/platform/verify`

Updated envelope on dashboards and admin analytics:

```json
{
  "success": true,
  "data": {},
  "meta": {
    "source": "real|demo|fallback",
    "retrieved_at": "ISO_DATETIME"
  }
}
```

## 4) UI data source matrix (strict display rule)

| UI Location | Field | Source Type | Source Name | Retrieval Method | Fallback | Verification |
|---|---|---|---|---|---|---|
| Parent dashboard | learnerCount | user_meta | `ngc_learners` | `NGC_Rest_Dashboard::parent()` | `0` (EMPTY STATE) | `/platform/verify` + admin screen |
| Student dashboard | sessionsCompleted | custom table | `ngc_bookings` | `NGC_Rest_Dashboard::student()` | `0` (EMPTY STATE) | dashboard REST assertions |
| Tutor dashboard | monthEarnings | custom table | `ngc_earnings` | `NGC_Rest_Dashboard::tutor()` | `0` (EMPTY STATE) | analytics + dashboard checks |
| Admin dashboard | revenue | custom table | `ngc_invoices` | `NGC_Rest_Dashboard::admin()` | `0` (EMPTY STATE) | `/platform/verify` |
| Analytics screen | conversionRate | custom tables | `ngc_conversion_events`, `ngc_visitor_profiles` | `NGC_Platform_Analytics::snapshot()` | `0` (EMPTY STATE) | metric matrix checks |
| User profile | timeline | custom table | `ngc_analytics_events` | `NGC_Rest_Platform::profile()` | `[]` (EMPTY STATE) | profile endpoint checks |

## 5) Demo JSON payloads

Shims added in `nextgencompanion/demo/`:

- `demo_parent.json`
- `demo_student.json`
- `demo_tutor_applicant.json`
- `demo_approved_tutor.json`
- `demo_admin.json`
- `demo_finance.json`
- `demo_support.json`
- `demo_analytics.json`
- `demo_acquisition.json`

## 6) Tracking event matrix

| Event | Trigger | Storage | Linking |
|---|---|---|---|
| `page_view` | `template_redirect` | `ngc_analytics_events` | visitor/session/user |
| `device_profile` | `template_redirect` | `ngc_analytics_events` payload | visitor/session |
| `booking_created` | booking create flow | `ngc_conversion_events` | visitor→user attribution |
| `payment_completed` | WooCommerce complete | `ngc_conversion_events` | visitor→user attribution |

## 7) Cookie matrix

| Cookie | Purpose | Flags | Expiry | Consent Aware |
|---|---|---|---|---|
| `visitor_id` | anonymous identity | `Secure`, `HttpOnly`, `SameSite=Lax` | configurable/year default | Yes |
| `session_id` | session continuity | `Secure`, `HttpOnly`, `SameSite=Lax` | day default | Yes |
| `first_touch_source` | first attribution | `Secure`, `SameSite=Lax` | year default | Yes |
| `last_touch_source` | last attribution | `Secure`, `SameSite=Lax` | year default | Yes |
| `affiliate_id` | affiliate mapping | `Secure`, `SameSite=Lax` | year default | Yes |
| `campaign_id` | campaign mapping | `Secure`, `SameSite=Lax` | year default | Yes |
| `consent_status` | privacy state | `Secure`, `HttpOnly`, `SameSite=Lax` | month default | N/A |

## 8) Analytics metric matrix

Defined in `NGC_Platform_Analytics::metric_matrix()` and computed in `snapshot()` for:

- users (total/parents/students/tutors)
- tutor applications (applicants/approved/rejected)
- bookings/lessons (active/completed/cancelled)
- finance (revenue/pending/paid/failed/refunds/wallet/payouts)
- quality (reviews/avg rating)
- growth (conversion rate/lead source/affiliate/query string/new/returning/sessions/funnel drop-off)
- tech (device/browser/location breakdown)

## 9) User profiling matrix

`/platform/profile/{user_id}` now returns:

- identity: role, registration, last login
- journey: journey state, profile completeness, funnel status
- acquisition: source/landing/referrer
- behavior: timeline, sessions, conversions
- related records: bookings, payments, reviews

## 10) Verification checklist

- [x] Required tables exist (platform + legacy)
- [x] Required REST routes exist
- [x] Required demo JSON files exist
- [x] Required demo user seeding flow exists
- [x] Tracking cookies + consent state implemented
- [x] Query-string attribution capture implemented
- [x] Anonymous visitor → user linking implemented
- [x] Dashboard/admin analytics return real-data envelope
- [x] Demo responses include `meta.source = demo`
- [x] Real responses include `meta.source = real`
- [x] Validation script updated and passing

## 11) Final implementation status table

| Objective | Status | Notes |
|---|---|---|
| Real Data Display Layer | DONE | Dashboard/admin responses wrapped with source metadata and live queries |
| Internal API/Data Helper Layer | DONE | Generic repository + platform REST entity/count/profile |
| Demo Journey Shim Layer | DONE | 9 demo payloads + demo mode switch + verify |
| User Journey Demo Accounts | DONE | seed/clear demo user automation |
| Analytics & Metrics Layer | DONE | full matrix + snapshot engine + admin dashboard |
| User Profiling Layer | DONE | profile endpoint + timeline/session/related records |
| Affiliate/Acquisition Tracking | DONE | query capture + first/last touch + DB persistence |
| Query String Capture Layer | DONE | supports ref/affiliate/utm/gclid/fbclid/msclkid/ttclid |
| Cookie Tracking Layer | DONE | secure/samesite/consent-aware cookie handling |
| Device/Location/Referrer/Session Tracking | DONE | device + referrer + session storage + visitor profile |
| Dashboard Metrics | DONE | unified real data + cache in platform analytics |
| Admin Verification Screen | DONE | 10 platform admin screens added |

