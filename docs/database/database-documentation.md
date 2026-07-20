# Database Documentation

## Scope

- WordPress core tables
- Companion custom tables (`ngc_*`)
- Third-party plugin tables where referenced (Amelia/FluentCRM/MasterStudy)

## WordPress Core Data (Used)

| Table | Purpose | Status |
|---|---|---|
| `wp_users` | user identities | VERIFIED |
| `wp_usermeta` | roles, profile meta, tutor flags, tracking links | VERIFIED |
| `wp_posts` | tutor CPT and pages | VERIFIED |
| `wp_postmeta` | tutor metadata (rate/mode/mappings) | VERIFIED |
| `wp_options` | platform settings, workflow configs, demo flags | VERIFIED |

## Custom Platform Tables

| Table | Purpose | Key Columns | Relationships | Indexes | Retention | Verification |
|---|---|---|---|---|---|---|
| `ngc_matches` | tutor matching requests/results | student_user_id, tutor_user_id, status, score | users/bookings | status, student, tutor | active ops + audit trail | `NGC_Verification` |
| `ngc_bookings` | lesson bookings lifecycle | match_id, student_user_id, tutor_user_id, scheduled_at, status | matches/users/invoices | status, scheduled_at | operational history | REST + health checks |
| `ngc_invoices` | invoice records | invoice_number, user_id, booking_id, amount, status | bookings/orders/users | unique invoice_number, status | finance retention policy | admin analytics |
| `ngc_wallet_ledger` | credit/debit ledger | user_id, entry_type, amount, balance_after | users | user_id, created_at | finance retention policy | wallet operations |
| `ngc_payouts` | tutor payouts | tutor_user_id, amount, status, paid_at | users/earnings | tutor_user_id, status | finance retention policy | payouts admin |
| `ngc_reviews` | parent reviews | booking_id, parent_user_id, tutor_user_id, rating | bookings/users | tutor_user_id | product quality history | reviews API |
| `ngc_ratings` | normalized ratings | tutor_user_id, student_user_id, booking_id, rating | users/bookings | tutor_user_id | product quality history | rating calculations |
| `ngc_audit_log` | audit events | actor_user_id, action, object_type, object_id, context | all modules | action, object_type, created_at | long-term operational | audit screen |
| `ngc_tutor_applications` | vetting queue | user_id, email, status, review_notes | users | status, email | lifecycle + compliance | tutor lifecycle |
| `ngc_session_logs` | session attendance | booking_id, attendance, progress_note | bookings | booking_id | learning ops history | lesson completion flows |
| `ngc_earnings` | tutor earnings | tutor_user_id, booking_id, amount, status, payout_id | bookings/payouts | tutor_user_id, status | finance retention | payout workflow |
| `ngc_workflow_runs` | workflow execution telemetry | workflow_key, status, context, results | workflow engine | workflow_key, status | ops retention | workflow admin |
| `ngc_analytics_events` | behavior/events | event_key, user_id, visitor_id, session_id, payload | sessions/profiles | event_key, created_at | analytics retention | analytics dashboard |
| `ngc_visitor_profiles` | anonymous visitor profiles | visitor_id, first_touch, last_touch | acquisition/sessions | visitor_id unique | analytics retention | tracking checks |
| `ngc_user_profiles` | linked user profiling | user_id, journey_state, session_count | users/sessions/events | user_id unique | analytics retention | profiling dashboard |
| `ngc_acquisition_sources` | source attribution touches | visitor_id, user_id, touch_type, source, campaign | visitors/users | source, campaign | analytics retention | acquisition dashboard |
| `ngc_affiliate_clicks` | affiliate click records | visitor_id, affiliate_id, campaign | visitors/users | affiliate_id, campaign | analytics retention | affiliate dashboard |
| `ngc_attribution_links` | conversion attribution links | user_id, visitor_id, object_type, object_id | users/visitors/objects | object_type/object_id | analytics retention | attribution checks |
| `ngc_user_sessions` | user/visitor sessions | session_id, visitor_id, user_id, page_views | profiles/events | session_id unique | analytics retention | session analytics |
| `ngc_device_profiles` | device fingerprints | visitor_id, device_type, browser, os | visitors/sessions | visitor_id, device_type | analytics retention | device breakdown |
| `ngc_conversion_events` | conversion events | event_key, user_id, visitor_id, value | users/visitors | event_key, created_at | analytics retention | conversion metrics |
| `ngc_dashboard_metric_snapshots` | computed metric snapshots | metric_key, metric_value, computed_at | analytics | metric_key, computed_at | configurable | metric audit |
| `ngc_demo_seed_log` | demo seeding actions | seed_key, seed_hash, created_records | demo manager | seed_key | short/ops retention | demo manager |
| `ngc_consent_log` | consent decisions | visitor_id, user_id, consent_status, context | visitors/users | visitor_id, consent_status | compliance retention | privacy checks |

## Third-Party Plugin Data (Referenced)

| Plugin | Referenced Tables/APIs | Status |
|---|---|---|
| Amelia | `amelia_appointments`, `amelia_customer_bookings`, API via admin-ajax bridge | PARTIAL (runtime required) |
| FluentCRM | FluentCRM contact/list/tag APIs/tables | BLOCKED (plugin runtime required) |
| MasterStudy LMS | role/meta integrations | BLOCKED (plugin runtime required) |

## ERD/Dataflow Artifacts

- `diagrams/svg/enterprise-diagram-suite.svg` (segments 8, 9, 25)

