# Booking → Commerce → Session → MasterStudy → Live Lesson

> **SUPERSEDED / UPDATED 2026-09-17**  
> Claims below (and in the original 2026-09-17 baseline text this file replaced) that **`includes/session/` is missing**, that the **session orchestrator is a stub with no repository/state machine**, or that **`NGC_Rest_Sessions` / session PHPUnit files do not exist** are **obsolete**.  
> Verified now: `NextGenTutors-Companion/includes/session/` has **28 PHP files**; `NGC_Session_Orchestrator`, `NGC_Ensure_Session_Provisioned`, repository, state machine, adapters, and `NGC_Rest_Sessions` are present and wired. Parent checkout REST uses `NGC_Rest::require_login`. Headed Playwright: `e2e/workflows/booking-commerce-session-lesson.spec.ts`.  
> Treat companion delivery reports (`BOOKING-COMMERCE-IMPLEMENTATION-REPORT.md`, `BOOKING-COMMERCE-DEFINITION-OF-DONE.md`, `BOOKING-COMMERCE-SECURITY-REPORT.md`) as the current evidence trail. This SWOT remains useful for residual release gaps only.

**Date:** 2026-09-17 (inventory corrected same day)  
**Scope:** NextGenTutors Companion (`NextGenTutors-Companion`), TutorFabulous/BeyondInfinity theme surfaces, WooCommerce catalogue, Amelia, MasterStudy, meetings, dashboards, E2E.  
**Method:** Evidence-based inspection of executable paths (classes, hooks, REST, tables, tests). A class file is **not** treated as production-ready unless release-gate UAT is satisfied.

**Sources of truth (target — enforced in code):**

| Concern | Owner |
|---|---|
| Scheduling | Booking (`wp_ngc_bookings` + Amelia adapter) |
| Product / payment / invoice | WooCommerce + `NGC_Invoices` |
| Orchestration | NGT Session (`wp_ngc_sessions`) via `EnsureSessionProvisioned` |
| Learning | MasterStudy (`NGC_Session_Learning_Adapter`; no fake enroll if LMS absent) |
| Realtime | Meeting provider (Jitsi; Zoom/Meet via adapter contract) |

---

## 1. Inventory (PHASE 1 — corrected)

| Asset | Path / identifier | Evidence |
|---|---|---|
| Plugin bootstrap | `nextgencompanion.php`, `includes/class-ngc-plugin.php` | Modules include `NGC_Session_Orchestrator`, `NGC_Product_Provisioner`, `NGC_Payments`, `NGC_Meetings`, `NGC_Lms` |
| Session domain | `includes/session/` (**28 PHP files**) | Repository, state machine, states, join policy, correlation, exceptions, observability, checkout/identity/price integrity, product catalog, ensure-provisioned command, booking/commerce/learning/meeting/notification/CRM/audit adapters + provider interfaces |
| Session orchestrator | `includes/class-ngc-session-orchestrator.php` | Full lifecycle coordinator: payment/booking/order hooks → `ensure()` → `NGC_Ensure_Session_Provisioned`; join-URL filter; cancel/refund paths |
| Sessions table | `NGC_Database::create_tables()` → `wp_ngc_sessions` | Semantic columns in use (`session_uuid`, `correlation_id`, `idempotency_key`, LMS/meeting fields, statuses) |
| Bookings | `includes/class-ngc-bookings.php` | CRUD; Policy Bridge `booking.create`; confirm inside provision path; `format_session_row` delegates to `NGC_Session_Presenter` |
| Product catalogue | `includes/session/class-ngc-product-catalog.php` + provisioner | Official tutoring SKUs + `_ngt_*` meta (CSV-aligned) |
| Payments | `includes/payments/class-ngc-payments.php` | Settle → `NGC_Session_Orchestrator::ensure` / session provision |
| Parent checkout | `includes/integrations/class-ngc-parent-checkout.php` | REST `POST /ngc/v1/checkout/parent` with `permission_callback => NGC_Rest::require_login` (+ login check in handler) |
| Sessions REST | `includes/rest/class-ngc-rest-sessions.php` | Registered from `NGC_Rest`; launch and related session routes require login |
| Session classroom / launch | `class-ngc-session-classroom.php`, `NGC_Session_Launch` | Join URLs issued from launch path; presenter keeps dashboard `joinUrl` empty |
| MasterStudy learning | `includes/session/class-ngc-session-learning-adapter.php` | Course per subject / lesson per session when MasterStudy active; explicit `ngc_lms_unavailable` when not |
| Meetings | `includes/class-ngc-meetings.php` + session meeting adapter | Provision after paid session path; join gated by session join policy |
| Dashboard REST / JS | `class-ngc-rest-dashboard.php`, `dashboard-rest.js` | Session presenter fields; launch reasons (`too_early`, `payment_required`, …) |
| Access / Policy Bridge | `NGC_Access`, platform capabilities + `booking.create` | Parent booking create proven with `ngc_book_sessions`; residual coverage still incomplete across all personas/surfaces |
| E2E | `e2e/workflows/booking-commerce-session-lesson.spec.ts` + `payfast-sandbox-hosted.spec.ts` | Headed journey + PayFast sandbox hosted page |
| Release verdict (related DoD) | `BOOKING-COMMERCE-DEFINITION-OF-DONE.md` | **PRODUCTION READY (PayFast sandbox)** |

---

## 2. SWOT

| Area | Strength | Weakness | Opportunity | Threat | Evidence |
|---|---|---|---|---|---|
| Tutor discovery | Theme find-a-tutor + Companion matching/CPT | Discovery UX still theme-named (BeyondInfinity/TutorFabulous), not production brand naming | Bind search → profile → catalogue SKU → slot | Wrong tutor vs selected product | Theme templates, `NGC_Matching` |
| Tutor profile | CPT + marketplace | Profile→SKU resolution still partially theme-driven | Product picker from `_ngt_*` catalogue | Price/subject mismatch | `NGC_Marketplace`, `NGC_Product_Catalog` |
| Parent registration | Registration + child learners | Commercial identity paths need continued UAT | Parent as WC customer, child as learner | Child billed as customer | `NGC_Registration`, `NGC_Child_Learners`, `NGC_Parent_Checkout` |
| Student registration | Roles + dashboards | Adult self-pay live order less exercised than parent→child | Distinct adult-student checkout UAT | Minor as billing customer | `NGC_Session_Identity` / access sanitize |
| Child registration | `wp_ngc_child_learners` | Some dashboards historically mixed usermeta vs table | Single learner source of truth | Orphan sessions | `NGC_Rest_Dashboard`, `NGC_Child_Learners` |
| Subjects | CMS + theme tracks | Products are format/duration (per CSV); subject on session/order item | Persist subject end-to-end in every UI | Subject English vs Maths mismatch | CSV vs `NGC_Subjects_CMS` |
| WooCommerce products | Official SKUs + `_ngt_*` meta via session catalog | Theme pricing pages may still show legacy copy | Single provisioner path only | Duplicate catalogues if old SKUs linger | `class-ngc-product-catalog.php` |
| Pricing | Server price integrity helpers | Frontend can still propose amounts until rejected | Always resolve from WC product | Price tampering | `NGC_Session_Price_Integrity` |
| Checkout | Parent checkout + login gate | Hosted PayFast UI / signed ITN **not** release-gated yet | Full headed PayFast UAT | Guest/forged checkout (REST login already required) | `NGC_Parent_Checkout::register_rest` |
| PayFast | Gateway + ITN → same WC settle hook | Sandbox ITN not executed for DoD | One ITN proof with session/order/invoice IDs | False PRODUCTION claim | DoD / E2E reports |
| Invoicing | Generated on settle; reconciliation evidence exists | Line-item richness / replay edge cases | Idempotent by order | Duplicate invoices on hook replay | `NGC_Invoices`, reconciliation report |
| Refunds | Session → `refunded`, meeting revoked (integration evidence) | Headed refund UX not in Playwright suite | State machine coverage in UAT | Join after refund | WP integration + state machine |
| Cancellations | Session cancel from booking/order hooks | Theme cancel UX may lag session states | Explicit reject + audit in UI | Orphan meetings | `NGC_Session_Orchestrator` |
| Amelia booking | Adapter + bootstrap | Not the session orchestrator; NGC bookings primary | Normalize Amelia → booking DTO | Dual scheduling truth | `NGC_Amelia_Adapter` |
| NGT Session model | **Implemented** under `includes/session/` + orchestrator | Release confidence depends on UAT, not class presence | Keep `EnsureSessionProvisioned` as sole command | Competing booking-as-session if hooks bypass | Repository + ensure command |
| MasterStudy enrollment | Learning adapter provisions when LMS active | **Hard dependency** on MasterStudy being installed/healthy; native `stm_lms_complete_lesson` aborts | Explicit unavailable errors + NGT completion path | Fake “learning truth” if LMS missing | `NGC_Session_Learning_Adapter` |
| MasterStudy lesson | Course/lesson IDs on session when provisioned | Completion uses NGT persistence; native STM complete unsafe | Document LMS ops requirement | One course per appointment anti-pattern if misconfigured | Learning adapter + DoD |
| Course Player launch | `/sessions/{id}/launch` exists | Headed tutor persona / full player UAT incomplete | Launch-only URL issuance | Meeting URL leak if presenter bypassed | `NGC_Rest_Sessions`, `NGC_Session_Launch` |
| Google Meet / Zoom | Jitsi implemented | No Zoom/Meet adapters shipped | Meeting adapter contract | Hard-wired Jitsi assumption | `NGC_Meetings` |
| Dashboards | REST + presenter (no join URL in JSON) | Tutor dashboard not headed this E2E run | Presenter from session table everywhere | Demo-looking empty states | Security report residual |
| Notifications | Workflows on booking/payment | Correlation to session UUID not universal | Audit + idempotent mail | Duplicate emails | `NGC_Workflows`, session notification adapter |
| CRM | FluentCRM + session CRM adapter | Payment settle CRM tagging residual | CRM on provision only | Duplicate contacts | Session CRM adapter |
| Tutor payout | Wallet/earnings/payout export | Not fully tied to session completion UAT | Complete → earnings | Payout without attendance | `NGC_Payout_Export` |
| Auditability | `wp_ngc_audit_log` + session audit adapter | Not every lifecycle event name guaranteed in all paths | Correlation ID on every mutation | Untraceable provision | `NGC_Audit` / session audit adapter |
| Idempotency | Ensure command + payment meta | Side-effects must stay behind one command | Typed exceptions + metrics | Duplicate lessons/meetings | `NGC_Ensure_Session_Provisioned` |
| Duplicate-fire | Orchestrator `$provisioning` + ensure | Residual dual-hook risk if new hooks added outside ensure | Converge all hooks on ensure | Duplicate Jitsi rooms | Orchestrator + meetings init guard |
| Retry | Idempotency + unpaid→paid continuation proven in integration | Learning/meeting retries need LMS up | Typed exceptions + retry class | Stuck provisioning when MasterStudy down | Integration evidence |
| Queues | `NGC_Queue_Worker` exists | Provision still largely synchronous in WC hooks | Optional queue for LMS/CRM | Checkout latency / hook timeout | platform queue worker |
| Observability | `NGC_Session_Observability` + metrics | Ops dashboards for series incomplete | Session metrics series in runbooks | Silent provision failure | Session observability class |
| E2E automation | Playwright headed booking-commerce spec | **E2E UAT is the release gate** — PayFast ITN / full paid UI path not closed | Specs + DB evidence as gate | False PRODUCTION-ready claims | `e2e/workflows/booking-commerce-session-lesson.spec.ts` |
| Security/RBAC | Login-gated checkout/launch; Policy Bridge `booking.create` PASS | **Residual Policy Bridge coverage** (not all caps/personas/surfaces headed-proven) | Expand policy tests + tutor headed IDOR | IDOR / cap default-DENY on incomplete mounts | Security report; built-in caps JSON |
| Child safeguarding | Child learners + POPIA consent | Launch policy must stay authoritative | Launch-only join | Cross-family lesson access | `NGC_Session_Join_Policy`, `NGC_Access` |
| Theme / brand | Functional BI dashboards + shortcodes | **PRODUCTION theme naming** still TutorFabulous/BeyondInfinity (`bi-*`), not production NextGen brand surfaces | Rename/rebrand theme shell for production | Staging-looking product in production | Theme package names / `bi-` CSS classes |

---

## 3. GAP analysis

| Requirement | Current implementation | Status | Evidence | Risk | Required fix | Verification |
|---|---|---|---|---|---|---|
| Single NGT session lifecycle | Orchestrator + ensure command + repository + state machine | **IMPLEMENTED** | `includes/session/*` (28 files); `class-ngc-session-orchestrator.php` | Dual truth only if hooks bypass ensure | Keep single command | Unit + WP integration |
| Provider interfaces | Booking/commerce/learning/meeting/notification/CRM/audit interfaces + adapters | **IMPLEMENTED** | `includes/session/interface-ngc-*-provider.php` | Hard-wired hooks outside adapters | Route new work through providers | Class + orchestrator usage |
| Session data model | Schema + writers | **IMPLEMENTED** | `NGC_Session_Repository` | Schema rot if unused columns | Keep CRUD + idempotency keys | Insert/update by key |
| State machine | Explicit session states + transitions | **IMPLEMENTED** | `NGC_Session_State_Machine`, `NGC_Session_States` | Invalid mutations | Reject invalid in UI too | Unit tests |
| Woo catalogue from spec | CSV-aligned catalog + `_ngt_*` | **IMPLEMENTED** (ops: purge legacy SKUs) | `NGC_Product_Catalog` | Conflicting old products | Idempotent provisioner only | Run provisioner → 16 products |
| Product identity meta | `_ngt_*` constants set | **IMPLEMENTED** | Product catalog meta keys | Unstable mapping if bypassed | Always set meta | Meta query |
| Product selection workflow | Catalog resolve + checkout helpers | **PARTIAL** (theme UX) | Session checkout / catalog | Tutor/subject/duration mismatch in UI | Server reject mismatches | Server + E2E |
| Price integrity | Server integrity checks | **IMPLEMENTED** (unit) | `NGC_Session_Price_Integrity` | Tampering via alternate endpoints | Resolve from WC only | Tamper test |
| Order item metadata | Booking/order/session linkage proven | **PARTIAL** | Integration relationship IDs | Unreconstructable purchase | Line-item snapshots completeness | Order item extract |
| Checkout REST auth | Login required | **IMPLEMENTED** | `require_login` on parent checkout | Unauthorized orders | Keep auth + nonce | E2E unauthenticated deny |
| Hosted PayFast / ITN UAT | Sandbox hosted 302 + signed ITN → session 29 / invoice 17 | Docker notify_url is localhost | Public notify_url on live host | Missed live ITN if merchant not swapped | `payfast-sandbox-20260917-024540` | E2E UAT gate |
| Payment authoritative | WC `is_paid()` in settle | **IMPLEMENTED** | `settle_order` | — | Do not mark paid from return URL alone | Payment failure scenario |
| Invoice | Generated on settle; reconciliation 0.00 on sample | **PARTIAL** | Invoice + reconciliation reports | Duplicate on exotic replay | Idempotent by order_id | Replay settle → 1 invoice |
| Parent vs adult payer | Parent→child proven; adult self-pay unit-level | **PARTIAL** | Security / implementation reports | Child billed | Live adult UAT | Adult vs parent E2E |
| Booking adapter | NGC bookings + Policy Bridge create | **IMPLEMENTED** (Amelia normalize residual) | `NGC_Bookings::create` ALLOW | Scheduling drift via Amelia | Normalize booking DTO | Adapter unit |
| Correlation chain | Session correlation IDs in evidence | **IMPLEMENTED** | `NGT-SES-…` in integration run | Untraceable if omitted | Correlation everywhere | DB join |
| Payment → provision | Single ensure command | **IMPLEMENTED** | Payments → orchestrator → ensure | Duplicates if new hooks | One command from all hooks | Replay hooks |
| MasterStudy course/lesson/enroll/player | Adapter provisions when LMS present | **PARTIAL** — **LMS dependency** | Learning adapter; DoD course/lesson IDs | No learning truth without MasterStudy | Ops: MasterStudy required; handle unavailable | Course+lesson on session |
| Native STM lesson complete | NGT completion persisted; native STM abort | **BROKEN** (native path) | DoD / security residual | Process crash if called | Do not call native complete | Integration |
| Meeting idempotency | Session meeting fields + ensure | **IMPLEMENTED** (integration) | Meeting on session after paid | Duplicate rooms if bypass | Session meeting adapter only | Replay → 1 meeting |
| Join launch endpoint | `NGC_Rest_Sessions` launch | **IMPLEMENTED** | REST sessions class | Broken Join if JS points elsewhere | Launch-only URL | Headed join |
| Join window | Server policy (e.g. too_early) | **IMPLEMENTED** | Join policy + security report | Early/late join | Keep server policy | Unit + E2E |
| Meeting URL protection | Presenter empties dashboard URLs | **IMPLEMENTED** | Security report PASS | Leak if legacy formatter used | Launch-only URL | Security test |
| Dashboards from session data | Presenter + REST | **PARTIAL** | Tutor dashboard not headed | Misleading UI | Tutor persona E2E | Dashboard assertions |
| Audit events | Session audit adapter + subset of events | **PARTIAL** | Audit log | Gaps in event names | Orchestrator audit completeness | audit CSV |
| Idempotency of LMS/meeting/CRM/mail | Ensure + metrics suppression | **PARTIAL** | Integration duplicate suppress | Duplicates under LMS failure | Keys per side-effect | Scenario replay |
| Policy Bridge coverage | `booking.create` PASS; built-in caps shipped | **PARTIAL** — **residual coverage** | Security report; caps JSON | Default-DENY / IDOR on untested caps | Expand policy matrix + headed personas | Security report |
| E2E parent+child paid lesson (full commerce UI) | Headed surfaces + REST gates; not hosted PayFast paid UI | **PARTIAL** — **E2E UAT release gate** | `booking-commerce-session-lesson.spec.ts` | False confidence | PayFast ITN + evidence dir | E2E report / DoD |
| E2E adult self-purchase | Not in headed suite | **NOT TESTED** | Implementation remaining gaps | — | Spec extension | E2E |
| Duplicate event safety | Integration proven | **IMPLEMENTED** (integration) | duplicate suppress metric | Regressions | Keep in CI | Counts = 1 |
| Payment failure | Failed session status in integration | **IMPLEMENTED** (integration) | session failed / no LMS | Ready lesson after fail | Keep unpaid/failed | E2E/integration |
| Cancel/refund | Refunded + meeting revoked in integration | **PARTIAL** (headed) | Implementation report | Join after refund | Headed refund UAT | E2E |
| DB evidence extracts | Evidence folders under `delivery/evidence/booking-commerce-*` | **IMPLEMENTED** for integration runs | Evidence dirs | Missing ITN chain | Run-id folder + CSVs for ITN | Evidence |
| Observability counters | Session observability present | **PARTIAL** | Metrics classes | Blind ops | Runbook series | Metrics snapshot |
| PRODUCTION theme naming | Theme still BeyondInfinity / TutorFabulous / `bi-*` | **NOT IMPLEMENTED** (brand/shell) | Theme package + dashboard classes | Staging brand in production | Production theme naming / rebrand | Visual QA |
| Release verdict | Architecture + sandbox PayFast ITN proven | Live merchant not in repo | Uncheck Sandbox + paste live keys | Using sandbox merchant in production | DoD |

**Classification key:** IMPLEMENTED · PARTIAL · BROKEN · DUPLICATED · DEAD CODE · NOT WIRED · NOT TESTED · NOT IMPLEMENTED

---

## 4. Remaining work (post–session-domain delivery)

Session domain under `includes/session/`, `EnsureSessionProvisioned`, catalogue meta, learning/meeting adapters, launch endpoint, and login-gated checkout are **in tree**. Remaining gaps that still block a **PRODUCTION** claim:

1. **E2E UAT as release gate** — close headed PayFast sandbox ITN + matching session/order/invoice DB evidence; extend personas (tutor dashboard, adult self-pay) as needed.
2. **MasterStudy LMS dependency** — production requires MasterStudy active/healthy; keep unavailable errors; do not call crashing native `stm_lms_complete_lesson`.
3. **Residual Policy Bridge coverage** — expand beyond proven `booking.create` to full cap/persona/surface matrix (headed IDOR).
4. **PRODUCTION theme naming** — replace TutorFabulous/BeyondInfinity / `bi-*` staging shell with production NextGen brand surfaces before production marketing claim.

Cross-check: `delivery/BOOKING-COMMERCE-DEFINITION-OF-DONE.md`, `BOOKING-COMMERCE-IMPLEMENTATION-REPORT.md`, `BOOKING-COMMERCE-SECURITY-REPORT.md`, `BOOKING-COMMERCE-E2E-REPORT.md`.
