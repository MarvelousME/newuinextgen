# BOOKING → COMMERCE → SESSION → MASTERSTUDY → LIVE LESSON  
## SWOT + GAP Analysis (Evidence Baseline)

**Generated:** 2026-08-09  
**Repo:** `newuinextgen`  
**Companion:** `NextGenTutors-Companion` 1.9.18  
**Environment note:** Local WP `:8890` was restarting during this baseline; classifications are from **source/runtime evidence**, not assumed green paths.

**Target ownership model (required):**

| Concern | Authority |
| --- | --- |
| Scheduling / commercial booking | Booking (Amelia-normalized or `wp_ngc_bookings`) |
| Product / payment / invoice | WooCommerce (+ `NGC_Invoices` derived from WC) |
| Orchestration lifecycle | **NGT Session** (missing as first-class entity) |
| Learning | MasterStudy |
| Realtime A/V | Meeting provider (currently Jitsi) |

---

## 1. Inventory snapshot (Phase 1)

### Core classes / files

| Area | Evidence |
| --- | --- |
| Bookings | `NextGenTutors-Companion/includes/class-ngc-bookings.php` — table `wp_ngc_bookings`; statuses; `ngc_booking_confirmed`; join via REST |
| Meetings | `includes/class-ngc-meetings.php`, `includes/adapters/class-ngc-jitsi-meeting-adapter.php` — room on confirm; join URL in booking `meta.meeting` |
| Payments | `includes/class-ngc-payments.php` — `woocommerce_payment_complete` / completed → `settle_order()` (idempotent) |
| Parent checkout | `includes/integrations/class-ngc-parent-checkout.php` — single lesson-credit product option `ngc_lesson_credit_product_id` |
| WC catalog CSV | `includes/integrations/class-ngc-woocommerce-catalog.php` + `integrate/nextgen-tutors-woocommerce-products.csv` (SKU `NGT-*`) |
| PayFast | `includes/integrations/class-ngc-payfast-gateway.php`, `class-ngc-payfast-itn.php`, `class-ngc-payfast.php` |
| Invoices | `includes/class-ngc-invoices.php` → `wp_ngc_invoices` from WC order |
| Wallet | `includes/class-ngc-wallet.php` — credited on settle |
| MasterStudy | `includes/adapters/class-ngc-masterstudy-adapter.php` (instructor/student roles only); `includes/integrations/class-ngc-lms.php` (lesson-passed → workflow) |
| Amelia | `includes/integrations/class-ngc-amelia-bootstrap.php`, Amelia adapter/admin under workflows |
| Idempotency | `includes/platform/class-ngc-idempotency.php` |
| Session logs | `wp_ngc_session_logs` schema in `class-ngc-database.php` — attendance-ish, **not** orchestration session |
| Dashboards / Join UI | Theme+Companion `assets/js/dashboard-rest.js`; REST `NGC_Rest_Dashboard`, `NGC_Rest_Bookings::join` |
| Lesson E2E (prior) | `e2e/workflows/lesson-av-recording-e2e.spec.ts`, `delivery/evidence/lesson-e2e/` |

### Tables (selected)

From `NGC_Database::table_names()` / `dbDelta` schemas:

- `wp_ngc_bookings` — scheduling + `order_id` + `amelia_booking_id` + `meta`
- `wp_ngc_invoices`, `wp_ngc_wallet_ledger`, `wp_ngc_earnings`, `wp_ngc_session_logs`
- **No** `wp_ngc_sessions` orchestration table

### REST (selected)

- `ngc/v1/dashboard/{student,parent,tutor}`
- `ngc/v1/bookings`, `ngc/v1/bookings/{id}/join`
- Parent checkout REST in `NGC_Parent_Checkout::register_rest`
- **No** `sessions/{id}/launch` orchestrator endpoint

---

## 2. SWOT

| Area | Strength | Weakness | Opportunity | Threat | Evidence |
| ---- | -------- | -------- | ----------- | ------ | -------- |
| Tutor discovery / profile | Theme marketplace + matching CPT sources | Pricing on profile may not resolve WC SKUs | Wire package picker → WC product keys | Orphan bookings without products | Matching + theme find-tutor paths; CSV products unused by checkout |
| Parent / student / child registration | Demo registry + forms + child_learners table | Billing customer rules not enforced end-to-end | Explicit parent-as-payer vs adult-as-payer policy in checkout | Minor billed incorrectly | `NGC_Demo_Registry`, `wp_ngc_child_learners`, `NGC_Parent_Checkout` |
| Subjects | Booking `subject` string; tutor subjects meta | No stable `subject_id` → product → MS course map | Subject registry + `_ngt_subject_id` product meta | Subject/product mismatch | `wp_ngc_bookings.subject`; MS adapter subjects user meta only |
| WooCommerce products | Spec CSV with ZAR packages; import by SKU; virtual | Catalog import skips updates; no `_ngt_product_key`; checkout uses generic R320 credit product | Idempotent **NGT Product Provisioner** with meta keys | Duplicate products / wrong price on book | `class-ngc-woocommerce-catalog.php`, CSV lines 1–17; `NGC_Parent_Checkout::ensure_product()` |
| Pricing integrity | WC product prices for catalog SKUs | Frontend/booking amount can diverge; generic credit product | Server-side resolve price from product only | Price tampering | Parent checkout hard-codes `_regular_price` 320 |
| Checkout / PayFast | NGC PayFast gateway + ITN; settle idempotent | Guest order user resolution fragile; not full cart UX for tutor packages | Standard WC cart with order item meta | Fake “paid” if ITN skipped | `NGC_Payments::settle_order`, PayFast gateway |
| Invoicing | `NGC_Invoices::generate_from_order` on settle | Parent dashboard invoice UX partial; may duplicate if called outside settle | Single invoice path tied to order | Competing invoice plugins | `class-ngc-invoices.php`; settle calls generate |
| Refunds / cancel | WC refund hooks in `NGC_Payments` | Session/meeting revoke not unified | Orchestrator cancel/refund transitions | Orphan join URLs after refund | `on_order_refunded` |
| Amelia | Bootstrap + adapter + tutor employee sync | Dual scheduling truth vs `ngc_bookings`; elevated DB writes in Docker | BookingProviderInterface normalizing Amelia | Double bookings | Amelia bootstrap/admin; bookings UNIQUE `amelia_booking_id` |
| NGT Session model | `session_logs` + booking status + meeting meta | **No orchestration session entity / state machine** | Introduce `wp_ngc_sessions` + orchestrator | Competing statuses on booking/meta/wallet | DB schema; no Session Orchestrator class |
| MasterStudy | Adapter for instructor/student roles; LMS hook on lesson passed | **No enroll / lesson-per-session / Course Player launch** | LearningProviderInterface | Join bypasses LMS entirely | `NGC_Masterstudy_Adapter`, `NGC_Lms` |
| Meeting | Jitsi idempotent ensure on confirm | Zoom/Google Meet not live; join opens Jitsi URL directly | MeetingProviderInterface already partially there | Public meet.jit.si privacy | `NGC_Meetings`, prior lesson E2E |
| Dashboards | REST dashboards + Join CTAs (tutor sessions fixed 2026-08-09) | No join window; no payment/invoice columns; no MS player | Derive from session projection | Stale demo KPIs | `dashboard-rest.js`, `class-ngc-rest-dashboard.php` |
| Notifications / CRM | Workflows + Fluent bridges | Not correlated to session UUID | correlation_id on all events | Duplicate emails | Workflows / intelligence |
| Tutor payout | Earnings + payouts tables | Completion → earnings path incomplete vs MS | Session completed → earnings | Double pay | `wp_ngc_earnings`, payout export |
| Audit / idempotency | Audit log; payment + booking idempotency | No single `EnsureSessionProvisioned` | Converge all hooks | Duplicate meetings/enrollments | `NGC_Idempotency`, payments settle |
| Queues / observability | Workflow runs, intel KPIs, observability service | No commerce/session metrics set as specified | Add counters on orchestrator | Silent failures | diagnostics classes |
| E2E automation | Lesson AV headed E2E; PayFast docker scripts; WC catalog unit test | Full parent→pay→MS→join not proven | New commerce E2E suite | Greenwash without WC | `lesson-av-recording-e2e.spec.ts`, `WooCommerceCatalogTest.php` |
| Security / RBAC | Booking `can_view`; join gated; wrong-tutor 403 in E2E | Meeting URL still in HTML href; no time window | Launch endpoint with server checks | IDOR / URL leakage | REST bookings join; lesson E2E neg tests |
| Child safeguarding | Consent log, safeguarding demo cases | Not wired into join gate | Join requires consent flags | Minor session without guardian gates | demo seeder safeguarding |

---

## 3. GAP analysis (executable path)

| Requirement | Current implementation | Status | Evidence | Risk | Required fix | Verification |
| ----------- | ---------------------- | ------ | -------- | ---- | ------------ | ------------ |
| Booking = scheduling truth | `wp_ngc_bookings` primary; Amelia optional sync | **PARTIAL** | `class-ngc-bookings.php`; Amelia adapter | Dual writes | BookingProviderInterface; Amelia as adapter only | Create booking → one row; Amelia id nullable |
| WooCommerce = commercial truth | WC + PayFast + invoices; also wallet credit as parallel money | **PARTIAL / DUPLICATED** | `NGC_Payments`, `NGC_Wallet`, CSV catalog vs lesson-credit product | Wallet ≠ order line | Treat WC order as payment truth; wallet as derived ledger only | Settle once; invoice = order total |
| NGT Session = orchestration truth | Booking status + meeting meta + session_logs | **NOT IMPLEMENTED** | No `ngc_sessions` table/class | Untraceable chain | Session entity + state machine + orchestrator | Correlation ID spans order/booking/session/meeting/MS |
| MasterStudy = learning truth | Role provisioning only | **NOT IMPLEMENTED** (session path) | `NGC_Masterstudy_Adapter` actions `create_instructor\|create_student` only | Join skips LMS | Enroll + lesson associate + player URL | Launch returns MS player then meeting |
| Meeting = realtime truth | Jitsi via `NGC_Meetings` | **IMPLEMENTED** (Jitsi); Zoom/Meet **NOT IMPLEMENTED** | Jitsi adapter; prior E2E same-room | Public Jitsi | Keep Jitsi adapter; optional Meet/Zoom later | One room per session idempotent |
| Product catalogue matches spec | CSV SKUs `NGT-*` | **PARTIAL** | CSV + `import_from_csv` skip-if-SKU | Stale prices | Provisioner upsert + `_ngt_*` meta | Run 10× → one product per key |
| Product selection on tutor profile | Not wired to CSV packages | **NOT WIRED** | Parent checkout generic product | Wrong package | Profile → subject/duration → resolve product | E2E product-selected screenshot |
| Cart / checkout | Programmatic WC order + PayFast redirect | **PARTIAL** | `NGC_Parent_Checkout::create_order` | Bypasses cart UX | Prefer WC cart with validated meta | Headed checkout path |
| Payment authoritative | `settle_order` requires `$order->is_paid()` | **IMPLEMENTED** | `class-ngc-payments.php` | — | Keep; remove any fake paid | Failed payment E2E |
| Invoice | NGC invoices from order | **IMPLEMENTED** (backend) | `generate_from_order` | Dashboard gaps | Surface on parent dash | Invoice # = order total |
| Order item metadata | Only order meta `ngc_booking_id` | **PARTIAL** | Parent checkout | Cannot reconstruct purchase | Persist session UUID, tutor, subject, duration on item | DB extract |
| Parent vs adult payer | Soft assumption in parent checkout | **PARTIAL** | Parent checkout class name | Child as WC customer | Enforce policy in create_order | Adult E2E + parent E2E |
| Booking ↔ WC correlation | `order_id` on booking + meta on order | **PARTIAL** | bookings schema `order_id` | Missing session UUID | correlation_id everywhere | Chain assertion |
| EnsureSessionProvisioned | Payment confirms booking; meeting on confirm; no MS | **NOT IMPLEMENTED** as single command | Scattered hooks | Duplicates | One idempotent provisioner | Replay payment/booking hooks |
| MS course/lesson/enrollment | Absent | **NOT IMPLEMENTED** | LMS adapter | Learning DoD fail | MS adapter expand | DB MS + player |
| Join window | Status only `requested\|confirmed` | **NOT IMPLEMENTED** | `NGC_Meetings::can_join_status` | Early join abuse | Options join_before/after; server enforce | Countdown + deny early |
| Launch endpoint | `bookings/{id}/join` returns Jitsi URL | **PARTIAL** | REST bookings | Skips MS/auth window | `sessions/{id}/launch` | Auth matrix E2E |
| Student/parent/tutor dashboards | Sessions + join | **PARTIAL** | dashboard-rest | Missing invoice/payment/MS | Project from session | Headed dashboards |
| Audit trail | NGC_Audit on many events | **PARTIAL** | audit_log table | Missing session events | Emit required event types | audit CSV |
| Idempotency / duplicate-fire | Payment + booking create | **PARTIAL** | NGC_Idempotency | Meeting/MS still at risk | Orchestrator once keys | Scenario 3 E2E |
| Security IDOR / URL leakage | can_view on join API; href still exposes meet URL | **PARTIAL** | REST + dashboard HTML | URL share leak | Launch issues short-lived redirect | Neg E2E |
| Headed commerce E2E | Lesson AV only | **NOT TESTED** for full chain | lesson-e2e evidence | False readiness | Scenarios 1–5 | E2E report PASS/FAIL only |
| Observability metrics | Partial intel/observability | **NOT IMPLEMENTED** (named counters) | observability service | Blind ops | Metric increments on orchestrator | Metrics dump |

### Status legend applied honestly

- Features with classes but **no complete executable path** → not IMPLEMENTED.
- Wallet + WC both acting as “paid” → DUPLICATED concern.
- Direct Jitsi join without MS → architecture **violation** of target model.

---

## 4. Competing sources of truth (must consolidate)

```text
TODAY (problematic):
  Booking.status  +  Booking.meta.meeting  +  Wallet balance  +  WC order
       ↑                    ↑                     ↑                ↑
   UI "session"         Join lesson           "credits"         Invoice

TARGET:
  WC Order ──► Booking ──► NGT Session ──┬──► MasterStudy lesson/enrollment
                                         └──► Meeting room
  Invoice ◄── WC Order (derived)
  Wallet  ◄── optional ledger derived from WC (not parallel authority)
```

---

## 5. Implementation priority (Phases 4+)

1. **NGT Session table + state machine + `NGC_Session_Orchestrator::ensure_provisioned`**
2. **Product provisioner** (`_ngt_product_key`, upsert from CSV/spec)
3. **Checkout path**: resolve product → WC cart/order item meta → pay → settle → ensure_provisioned
4. **MasterStudy adapter**: course by subject, enroll, lesson stub, player URL
5. **Launch endpoint** with join window + RBAC (MS then meeting)
6. **Dashboard projections** from session
7. **Headed E2E + DB evidence** (blocked until WP healthy)

---

## 6. Baseline verdict (pre-implementation)

```text
STAGING ONLY — architecture incomplete
```

**BLOCKER (architecture):** No NGT Session orchestration truth; MasterStudy not on join path; product selection not bound to WC catalogue SKUs.

**BLOCKER (environment):** Local WordPress stack restart in progress at analysis time — full runtime verification deferred to Phases 13–16.

---

## 7. Evidence index for this document

| Path | Role |
| --- | --- |
| `includes/class-ngc-bookings.php` | Booking domain |
| `includes/class-ngc-meetings.php` | Meeting on confirm |
| `includes/class-ngc-payments.php` | WC settle idempotent |
| `includes/integrations/class-ngc-parent-checkout.php` | Generic lesson product |
| `includes/integrations/class-ngc-woocommerce-catalog.php` | CSV import |
| `integrate/nextgen-tutors-woocommerce-products.csv` | Spec products |
| `includes/adapters/class-ngc-masterstudy-adapter.php` | LMS roles only |
| `includes/integrations/class-ngc-lms.php` | MS progress hook |
| `includes/class-ngc-database.php` | Schema; no sessions table |
| `delivery/evidence/lesson-e2e/` | Prior Jitsi join E2E |
| `delivery/FINAL-LESSON-AUDIO-VIDEO-E2E-VERIFICATION-REPORT.md` | AV limitations |

---

## 8. Explorer follow-up (2026-08-09)

Confirmed by inventory agents ([Explore commerce booking LMS](b05a42cc-21a7-413a-b9d3-4e4df72203bf), [Explore product pricing checkout](d0295b32-ea49-4ca4-9c87-3d4f2c677deb)):

| Finding | Remediation applied |
| --- | --- |
| Amelia sync inserted `status=confirmed` without Woo payment | `NGC_Bookings::sync_from_amelia` now defaults to `requested` (`ngc_amelia_sync_initial_status`) |
| Dashboard leaked Jitsi `joinUrl` in HTML | `format_session_row` omits URLs; UI POSTs `/sessions/{id}/launch` |
| Checkout REST was public (`__return_true`) | Requires login + parent/student ownership of booking |
| Checkout ignored booking subject/tutor/duration | `hydrate_args_from_booking` + richer `_ngt_*` order item meta |
| No classic WC cart; CSV SKUs vs R320 credit | Already documented; provisioner resolves `ngt-online-1hr` when meta present |

Environment: WordPress `:8890` still **DOWN** — headed commerce E2E remains **BLOCKED**.

*End of Phase 1–3 baseline. Implementation must not claim PRODUCTION READY until Phases 13–16 produce PASS evidence for mandatory E2E scenarios.*

---

## 9. Post-implementation closure (2026-08-09 evening)

Baseline gaps above were remediated in Companion **1.9.19**. Re-verified evidence:

| Baseline blocker | Current status | Evidence |
| --- | --- | --- |
| No NGT Session entity | IMPLEMENTED | `wp_ngc_sessions` + orchestrator |
| MS not on join path | PARTIAL → linked after pay | course/lesson IDs on session; Course Player not headed-proven as primary |
| Product selection vs WC SKUs | PARTIAL | Idempotent provisioner + `_ngt_product_key`; UI cart path still partial |
| WP `:8890` down | RESTORED | Docker PASS `bc-20260809-113004`; headed smoke 6/6 |
| Unpaid → ready | FIXED | `payment_failure_blocks_ready` PASS |

**Final reports:** see `BOOKING-COMMERCE-*-REPORT.md` / `DEFINITION-OF-DONE.md`.

**Verdict:** `STAGING ONLY` — not `PRODUCTION READY` (full headed Scenario 1 still BLOCKED).
