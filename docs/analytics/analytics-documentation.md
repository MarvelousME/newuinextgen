# Analytics Documentation

## Analytics Domains

- user analytics
- tutor analytics
- student analytics
- parent analytics
- affiliate analytics
- conversion analytics
- revenue analytics
- funnel analytics
- device/session analytics
- attribution analytics

## Metric Matrix

| Metric | Formula | Source Tables | Filters/Dimensions | Retention | Verification | Status |
|---|---|---|---|---|---|---|
| total_users | count users | `wp_users` | role/date | WP retention | snapshot/admin | VERIFIED |
| total_parents | count role parent/guardian | `wp_usermeta` | role | WP retention | snapshot/admin | VERIFIED |
| total_students | count role student | `wp_usermeta` | role | WP retention | snapshot/admin | VERIFIED |
| total_tutors | count role tutor | `wp_usermeta` | role | WP retention | snapshot/admin | VERIFIED |
| tutor_applicants | count applications | `ngc_tutor_applications` | status | ops retention | snapshot/admin | VERIFIED |
| approved_tutors | count approved apps | `ngc_tutor_applications` | status | ops retention | snapshot/admin | VERIFIED |
| rejected_tutors | count rejected apps | `ngc_tutor_applications` | status | ops retention | snapshot/admin | VERIFIED |
| active_bookings | count requested+confirmed | `ngc_bookings` | status/date | ops retention | dashboard/admin | VERIFIED |
| completed_lessons | count completed | `ngc_bookings` | status/date | ops retention | dashboard/admin | VERIFIED |
| cancelled_lessons | count cancelled | `ngc_bookings` | status/date | ops retention | dashboard/admin | VERIFIED |
| revenue | sum paid invoices | `ngc_invoices` | status=paid | finance retention | admin analytics | PARTIAL |
| pending_payments | count issued invoices | `ngc_invoices` | status | finance retention | admin analytics | PARTIAL |
| paid_invoices | count paid invoices | `ngc_invoices` | status | finance retention | admin analytics | PARTIAL |
| failed_payments | count payment_failed events | `ngc_analytics_events` | event key | analytics retention | snapshot | PARTIAL |
| refunds | count payment_refunded events | `ngc_analytics_events` | event key | analytics retention | snapshot | PARTIAL |
| wallet_balances | sum latest balance_after | `ngc_wallet_ledger` | user | finance retention | wallet checks | VERIFIED |
| tutor_payouts | sum paid payouts | `ngc_payouts` | status/date | finance retention | payout screen | VERIFIED |
| reviews | count reviews | `ngc_reviews` | date/tutor | quality retention | reviews API | VERIFIED |
| average_tutor_rating | avg ratings | `ngc_ratings` | tutor | quality retention | ratings API | VERIFIED |
| conversion_rate | conversions/visitors | `ngc_conversion_events`,`ngc_visitor_profiles` | time/source | analytics retention | snapshot | VERIFIED |
| lead_source_performance | grouped source counts | `ngc_acquisition_sources` | source/campaign | analytics retention | acquisition dashboard | VERIFIED |
| affiliate_performance | grouped affiliate counts | `ngc_affiliate_clicks` | affiliate/campaign | analytics retention | affiliate dashboard | VERIFIED |
| query_string_performance | grouped campaigns/utm | `ngc_acquisition_sources` | utm fields | analytics retention | acquisition dashboard | VERIFIED |
| device_breakdown | grouped payload device_type | `ngc_analytics_events` | device_type | analytics retention | snapshot | VERIFIED |
| browser_breakdown | grouped payload browser | `ngc_analytics_events` | browser | analytics retention | snapshot | VERIFIED |
| location_breakdown | grouped country/city | `ngc_visitor_profiles` | country/city | analytics retention | snapshot | VERIFIED |
| returning_users | session_count > 1 | `ngc_user_profiles` | user/time | analytics retention | profiling dashboard | VERIFIED |
| new_users | users in period | `wp_users` | registration date | WP retention | snapshot | VERIFIED |
| session_count | count sessions | `ngc_user_sessions` | visitor/user | analytics retention | session analytics | VERIFIED |
| funnel_drop_off | stage deltas | `ngc_conversion_events` | stage/time | analytics retention | snapshot | VERIFIED |

## Pipeline Artifacts

- `diagrams/svg/enterprise-diagram-suite.svg` (segments 25, 26, 27)

