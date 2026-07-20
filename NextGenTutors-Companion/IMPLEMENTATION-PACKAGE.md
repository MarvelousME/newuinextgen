# NextGen Companion — Implementation Package v1.0.0

**Theme:** BeyondInfinity v1.4.6 (`beyondinfinity/`)  
**Plugin:** NextGen Companion v1.0.0 (`nextgencompanion/`)  
**Validation:** `php scripts/validate.php` — 30 PHP files, exit 0  
**Runtime UAT:** Requires live WordPress — not executed in this workspace

---

## 1. File tree (created / changed)

```
nextgencompanion/
├── nextgencompanion.php
├── uninstall.php
├── IMPLEMENTATION-PACKAGE.md
├── scripts/validate.php
└── includes/
    ├── class-ngc-plugin.php
    ├── class-ngc-database.php
    ├── class-ngc-roles.php
    ├── class-ngc-post-types.php
    ├── class-ngc-registration.php          ← parent/student provisioning + learner linking
    ├── class-ngc-workflows.php
    ├── class-ngc-matching.php
    ├── class-ngc-bookings.php
    ├── class-ngc-payments.php
    ├── class-ngc-invoices.php
    ├── class-ngc-wallet.php
    ├── class-ngc-tutor-lifecycle.php
    ├── class-ngc-reviews.php               ← earnings + payouts
    ├── class-ngc-audit.php
    ├── class-ngc-verification.php
    ├── class-ngc-self-healing.php
    ├── admin/class-ngc-admin.php
    ├── shortcodes/class-ngc-shortcodes.php
    ├── rest/
    │   ├── class-ngc-rest.php
    │   ├── class-ngc-rest-dashboard.php    ← + /dashboard/parent
    │   ├── class-ngc-rest-matching.php     ← + /matches/{id}/reject
    │   ├── class-ngc-rest-bookings.php
    │   ├── class-ngc-rest-reviews.php      ← NEW
    │   └── class-ngc-rest-admin.php        ← + payouts, resubmit, repair
    └── integrations/
        ├── class-ngc-fluentcrm.php
        ├── class-ngc-lms.php
        └── class-ngc-amelia.php

beyondinfinity/ (theme integration)
├── inc/workflows.php                         ← skip form dispatch when companion active
├── inc/companion.php                         ← parent REST path /dashboard/parent
├── inc/admin.php                             ← Operations screen (tutor approve)
├── inc/security.php
└── content/nextgen-workflow-pack.json      ← 19 workflows (+ payment_received, booking_cancelled)
```

Full source lives at the paths above in the repository.

---

## 2. Database tables (`NGC_Database::create_tables()`)

| Table | Purpose |
|---|---|
| `{prefix}ngc_matches` | Match requests, scoring, status |
| `{prefix}ngc_bookings` | Session lifecycle |
| `{prefix}ngc_invoices` | Invoice records from WC orders |
| `{prefix}ngc_wallet_ledger` | Parent/student wallet credits/debits |
| `{prefix}ngc_payouts` | Tutor payout batches |
| `{prefix}ngc_reviews` | Parent reviews |
| `{prefix}ngc_ratings` | Tutor rating aggregates |
| `{prefix}ngc_earnings` | Per-session tutor earnings |
| `{prefix}ngc_audit_log` | Audit trail |
| `{prefix}ngc_tutor_applications` | Vetting queue |
| `{prefix}ngc_session_logs` | Attendance |

**Activation:** `register_activation_hook` → `NGC_Database::create_tables()` + `NGC_Roles::install()`  
**Upgrade:** `plugins_loaded` recreates tables if missing  
**Uninstall:** `uninstall.php` drops tables when `NGC_DROP_TABLES` defined

---

## 3. Activation / bootstrap

1. Activate **BeyondInfinity** theme  
2. Activate **NextGen Companion** plugin  
3. Tables + roles created on activation  
4. `NGC_Plugin_Bootstrap::init()` loads all modules on `plugins_loaded` priority 5  
5. Theme defers form workflow dispatch to companion (`bi_workflow_on_form_submitted` guard)  
6. Daily cron `ngc_daily_health_check` → verification + self-healing

---

## 4. REST / AJAX endpoints (`ngc/v1`)

| Method | Route | Capability | Handler |
|---|---|---|---|
| GET | `/dashboard/student` | logged in | `NGC_Rest_Dashboard::student` |
| GET | `/dashboard/parent` | logged in | `NGC_Rest_Dashboard::parent` |
| GET | `/dashboard/tutor` | logged in | `NGC_Rest_Dashboard::tutor` |
| GET | `/dashboard/admin` | admin / `ngc_admin_operations` | `NGC_Rest_Dashboard::admin` |
| POST | `/matches` | `ngc_request_match` | create match |
| POST | `/matches/{id}/accept` | logged in | accept + auto-booking |
| POST | `/matches/{id}/reject` | logged in | reject match |
| POST | `/matches/{id}/assign` | `ngc_manage_matches` | manual assign |
| GET | `/bookings` | logged in | list (scoped by role) |
| POST | `/bookings` | `ngc_book_sessions` | create |
| GET | `/bookings/{id}` | owner / support | get |
| PUT | `/bookings/{id}` | `ngc_book_sessions` | update |
| DELETE | `/bookings/{id}` | support | delete |
| POST | `/bookings/{id}/status` | `ngc_book_sessions` | transition |
| POST | `/reviews` | `ngc_submit_reviews` | parent review |
| GET | `/reviews/tutor/{id}` | public | average rating |
| POST | `/admin/tutors/{id}/approve` | `ngc_review_tutors` | approve |
| POST | `/admin/tutors/{id}/reject` | `ngc_review_tutors` | reject |
| POST | `/admin/tutors/{id}/resubmit` | `ngc_review_tutors` | resubmit |
| GET | `/admin/analytics` | admin | KPI stats |
| GET | `/admin/verification` | admin | health checks |
| POST | `/admin/payouts` | `ngc_manage_payouts` | process payout |
| POST | `/admin/repair` | admin | self-healing |

**AJAX / admin-post:** `admin_post_ngc_form_submit` — all 5 public forms

---

## 5. Workflow trigger matrix

| Logical event | Dispatch key | Source | Workflow pack key |
|---|---|---|---|
| TUTOR_SUBMITTED | `ngt.tutor_application.submitted` | `become_tutor` form | `tutor_application_review` |
| TUTOR_APPROVED | `ngt.tutor.approved` | `NGC_Tutor_Lifecycle::approve` | `tutor_approved_onboarding` |
| TUTOR_REJECTED | `ngt.tutor.rejected` | `NGC_Tutor_Lifecycle::reject` | `tutor_application_rejected` |
| PARENT_CREATED | `ngt.parent_register.submitted` | `parent_register` form | `parent_register_intake` |
| STUDENT_CREATED | `ngt.student_register.submitted` | `student_register` form | `student_register_intake` |
| MATCH_REQUESTED | `ngt.find_tutor.submitted` | `find_tutor` form | `parent_find_tutor_intake` |
| MATCH_ACCEPTED | `ngt.match.accepted` | `NGC_Matching::accept` | `match_accepted_provisioning` |
| BOOKING_CREATED | `amelia.booking.created` | `NGC_Bookings::create` | `amelia_booking_created` |
| LESSON_COMPLETED | `ngt.lesson.completed` | booking → completed | `lesson_completed_awards` |
| BOOKING_CANCELLED | `ngt.booking.cancelled` | booking → cancelled | `booking_cancelled_alert` |
| INVOICE_GENERATED | `ngt.invoice.issued` | `NGC_Invoices::generate_from_order` | `invoice_issued_notification` |
| PAYMENT_RECEIVED | `ngt.payment.received` | `NGC_Payments::on_order_completed` | `payment_received_notification` |
| PAYMENT_FAILED | `ngt.payment.failed` | WC failed hook | `payment_failed_alert` |
| REFUND_PROCESSED | `ngt.payment.refunded` | WC refunded hook | `payment_refunded_alert` |
| PAYOUT_GENERATED | `ngt.payout.processed` | `NGC_Reviews::process_payout` | `tutor_payout_processed` |
| SUPPORT_ESCALATION | `ngt.support.escalated` | `contact_support` form | `support_escalation` |
| HEALTH_CHECK | `ngt.daily.health_check` | daily cron | `dashboard_health_check` |

---

## 6. RBAC matrix

| Role | Dashboard | Key capabilities |
|---|---|---|
| `parent` / `parent_guardian` | `/parent-dashboard` | `ngc_request_match`, `ngc_book_sessions`, `ngc_submit_reviews`, `ngc_manage_wallet` |
| `student` | `/student-dashboard` | `ngc_book_sessions` |
| `tutor` | `/tutor-dashboard` | `ngc_view_earnings`, `ngc_accept_matches` |
| `ngc_finance` | `/admin-dashboard` | `ngc_manage_invoices`, `ngc_manage_payouts` |
| `ngc_support` | `/admin-dashboard` | `ngc_manage_matches`, `ngc_review_tutors`, `ngc_manage_bookings` |
| `administrator` | all | all `ngc_*` caps |

Page guards: `beyondinfinity/inc/security.php`

---

## 7. Verification checklist

| Check | Auto | Repair |
|---|---|---|
| 11 DB tables exist | `NGC_Verification::run_checks()` | `NGC_Self_Healing::repair_tables()` |
| 6 custom roles | verification | `NGC_Roles::install()` |
| 11 shortcodes registered | verification | companion registers on `init` |
| REST routes `/dashboard/student` + `/parent` | verification | reactivate plugin |
| Admin notice on failure | `NGC_Admin::verification_notice` | Health → Run repair |
| Audit log writable | implicit on first event | — |
| WooCommerce present | admin notice | install WC for payments |
| FluentCRM present | info notice | optional |
| MasterStudy present | info notice | optional |

**Admin UI:** WP Admin → NextGen → Health

---

## 8. Test checklist

### Static (no WP)
- [x] `php scripts/validate.php` — syntax + manifest

### Activation (WP)
- [ ] Activate theme + plugin — no fatal errors
- [ ] Confirm 11 tables in `wp_*ngc_*`
- [ ] Confirm roles: parent, student, tutor, ngc_finance, ngc_support
- [ ] Visit NextGen → Health — all checks green

### Forms
- [ ] Submit `parent_register` — user created, workflow dispatched, audit logged
- [ ] Submit `student_register` — user created, parent link if `parent_email`
- [ ] Submit `become_tutor` — row in `ngc_tutor_applications`
- [ ] Submit `find_tutor` — row in `ngc_matches` with score

### Tutor lifecycle
- [ ] Approve application (admin UI or REST) — tutor role, `ngc_tutor_verified`, workflow
- [ ] Reject — `ngt.tutor.rejected` dispatched
- [ ] Resubmit via REST — status back to pending

### Matching / booking
- [ ] POST `/matches` — automated scoring
- [ ] POST `/matches/{id}/assign` — manual match
- [ ] POST `/matches/{id}/accept` — booking auto-created
- [ ] POST `/bookings/{id}/status` `{status: completed}` — earning + lesson workflow

### Payments (requires WooCommerce)
- [ ] Complete order with `ngc_booking_id` meta — wallet credit, invoice, payment.received
- [ ] Failed order — payment.failed workflow
- [ ] Refund — wallet debit, payment.refunded workflow

### Payouts
- [ ] Complete booking → pending earning
- [ ] Process payout (admin or REST) — `ngt.payout.processed`

### Dashboards
- [ ] GET `/ngc/v1/dashboard/parent` — learners + sessions
- [ ] GET `/ngc/v1/dashboard/tutor` — earnings KPIs
- [ ] Frontend dashboards render via `dashboard-rest.js`

### Reviews
- [ ] POST `/reviews` — rating stored, affects match scoring

---

## 9. Final status table

| Area | Previous | Final | Evidence |
|---|---|---|---|
| Parent Management | PARTIAL | **VERIFIED (code)** | `NGC_Registration`, `/dashboard/parent`, learner meta |
| Student Management | PARTIAL | **VERIFIED (code)** | `register_student`, student dashboard REST |
| Tutor Management | PARTIAL | **VERIFIED (code)** | `NGC_Tutor_Lifecycle`, CPT, marketplace meta |
| Tutor Vetting | PARTIAL | **VERIFIED (code)** | `ngc_tutor_applications`, admin Applications UI |
| Tutor Marketplace | PARTIAL | **VERIFIED (code)** | `NGC_Post_Types`, verified tutor scoring filter |
| Matching Engine | PARTIAL | **VERIFIED (code)** | `NGC_Matching::score_tutors`, REST CRUD |
| Booking Engine | PARTIAL | **VERIFIED (code)** | `NGC_Bookings`, REST lifecycle |
| Payment Engine | NOT VERIFIED | **BLOCKED — WooCommerce** | `NGC_Payments` + safe admin notice; enable WC |
| Invoice Engine | NOT VERIFIED | **BLOCKED — WooCommerce** | `NGC_Invoices::generate_from_order` |
| Wallet System | PARTIAL | **VERIFIED (code)** | `NGC_Wallet` ledger |
| CRM Integration | NOT VERIFIED | **BLOCKED — FluentCRM** | `NGC_Fluentcrm` + safe notice |
| LMS Integration | NOT VERIFIED | **BLOCKED — MasterStudy** | `NGC_Lms` + safe notice |
| Dashboard Engine | PARTIAL | **VERIFIED (code)** | 4 REST endpoints + shortcodes |
| Analytics | PARTIAL | **VERIFIED (code)** | `/admin/analytics` |
| Reviews / Ratings | PARTIAL | **VERIFIED (code)** | `NGC_Reviews`, REST `/reviews` |
| Audit Framework | PARTIAL | **VERIFIED (code)** | `NGC_Audit`, Health audit table |
| Verification Framework | PARTIAL | **VERIFIED (code)** | `NGC_Verification` |
| Self-Healing Framework | PARTIAL | **VERIFIED (code)** | `NGC_Self_Healing::repair_all` |
| Escalation Workflow | VERIFIED | **VERIFIED (code)** | `support_escalation` pack |
| Tutor Approval Workflow | PARTIAL | **VERIFIED (code)** | approve + `tutor_approved_onboarding` |
| Tutor Rejection Workflow | PARTIAL | **VERIFIED (code)** | reject + `tutor_application_rejected` |
| Tutor Re-submission | PARTIAL | **VERIFIED (code)** | `resubmit` + REST |
| Tutor Matching Workflow | PARTIAL | **VERIFIED (code)** | auto score on find_tutor |
| Manual Matching | PARTIAL | **VERIFIED (code)** | REST assign + admin Matches |
| Automated Matching | PARTIAL | **VERIFIED (code)** | `score_tutors` algorithm |
| Booking Workflow | PARTIAL | **VERIFIED (code)** | create → confirmed → completed |
| Payment Workflow | NOT VERIFIED | **BLOCKED — WooCommerce** | hooks wired, needs WC runtime |
| Invoice Workflow | NOT VERIFIED | **BLOCKED — WooCommerce** | `invoice_issued_notification` |
| Refund Workflow | NOT VERIFIED | **BLOCKED — WooCommerce** | `payment_refunded_alert` |
| Cancellation Workflow | PARTIAL | **VERIFIED (code)** | `booking.cancelled` dispatch |
| Session Completion | PARTIAL | **VERIFIED (code)** | completed → earnings + lesson workflow |
| Tutor Payout Workflow | NOT VERIFIED | **VERIFIED (code)** | `process_payout` + admin Payouts UI |
| Parent Review Workflow | PARTIAL | **VERIFIED (code)** | REST POST `/reviews` |
| Tutor Rating Workflow | PARTIAL | **VERIFIED (code)** | ratings table + marketplace score |
| CRM Workflow | NOT VERIFIED | **BLOCKED — FluentCRM** | tags via `ngc_fluentcrm_upsert` action |
| Dashboard Workflow | VERIFIED | **VERIFIED (code)** | daily health_check |
| PayFast gateway | NOT VERIFIED | **BLOCKED — not in repo** | Use WooCommerce PayFast plugin separately |

**Code VERIFIED** = implementation exists, passes `php -l` / `validate.php`.  
**Production VERIFIED** = requires WordPress activation UAT on your host.

---

## 10. Deploy steps

1. Copy `nextgencompanion/` to `wp-content/plugins/nextgencompanion/`
2. Copy/update `beyondinfinity/` theme
3. Activate plugin → verify Health screen
4. Install optional: WooCommerce, FluentCRM, MasterStudy LMS, Amelia
5. Run test checklist §8 on staging
6. `wp cron event run ngc_daily_health_check` (optional smoke test)
