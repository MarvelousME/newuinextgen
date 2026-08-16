# NGT System Triggers — Complete Automation Map

**Date:** 2026-08-16  
**Source:** Business automation map (FluentCRM / AutomatorWP / GamiPress / Amelia / WooCommerce)  
**Repo check:** Static evidence only — live FluentCRM/AutomatorWP DB recipes not queried  
**Authority rule:** Domain state → Ecosystem workflow (`NGC_Workflow_Authority`). Plugins = projections / transports / calendar.

**Classification**

| Code | Meaning |
|---|---|
| **KEEP** | Remains as provider behaviour (usually marketing CRM) |
| **MIGRATE** | Move ownership to Ecosystem journey workflow + ports |
| **BRIDGE** | Emit/consume via projection; plugin may still execute nurture |
| **DISABLE** | Must not run once Ecosystem owns the same side effect |
| **CONFLICT** | Contradicts another rule or existing NGT catalog — needs product confirm |
| **UNVERIFIED** | Described in map; no Companion implementation found |

---

## 0. Evidence baseline (what the repo actually has)

| Layer | Evidence | Status |
|---|---|---|
| FluentCRM sequences (templates) | `automations/fluentcrm-sequence-*.json`, `assets/reference/important-ops/FluentCRM-Automation-JSON-Export.json` | CONFIGURED templates |
| AutomatorWP recipes | `automations/automation-*.json` (README: **deprecated**) | MIGRATE then DISABLE |
| GamiPress | `NGC_Gamipress_Adapter` + internal `NGC_Achievement_Engine` / `NGC_Scoring_Engine` | PARTIAL — catalogs **differ** from this map |
| Amelia | Adapter + booking sync; Notifier-for-Amelia not in Companion | Calendar VERIFIED; Notifier emails UNVERIFIED |
| WooCommerce / PayFast | `NGC_Payments` | VERIFIED settle path |
| Reminders | `NGC_Session_Reminders` 24h/1h/15m | VERIFIED |
| Referrals | `NGC_Referrals` default R50 + filter | VERIFIED |
| Safeguarding | `NGC_Safeguarding` AI=signal | VERIFIED case path |
| Payout cron | `NGC_Payout_Scheduler` 1st ~02:00 | VERIFIED schedule intent |

---

## 1. FluentCRM triggers (email & segmentation)

CRM may own **nurture sequences and tags**. It must not own booking/payment/session/earnings/safety state.

| ID | Trigger (as stated) | Actions (as stated) | Current authority | Target | Decision |
|---|---|---|---|---|---|
| FC-01 | Contact form submission | Tag Lead / Prospective Parent → list 2024 Leads → autoresponder → 24h → if no booking recommend tutors | FluentCRM sequence templates + lead nurture JSON | `TutorSearchStarted` / lead event → CRM **projection** → nurture sequence | **BRIDGE** (CRM owns emails) |
| FC-02 | Booking created (Woo/Amelia) | Active Customer; remove Lead; first_booking_date; confirm email+SMS; 72h → Engaged | AutomatorWP payment recipe + Hub RTM + NGT payments | `BookingConfirmed`/`PaymentCaptured` → NGT confirmation → CRM projection tags/lists → **CRM nurture only** after | **MIGRATE** state; **BRIDGE** nurture; **DISABLE** AutomatorWP duplicate confirm |
| FC-03 | Session completed | sessions_count++; emails at 1/5/10; Loyal tag; next reminder | FluentCRM export (last_session_date wait) PARTIAL | `SessionCompleted` → NGT updates projection fields → CRM sequence | **MIGRATE** counter SoR to NGT; **BRIDGE** CRM emails |
| FC-04 | Rating/review submitted | ≥5★ tell friends; &lt;4★ support; Satisfied/Needs Support tags | FluentCRM export quality automation PARTIAL | `RatingSubmitted` → NGT rating service → CRM tags | **MIGRATE** rating SoR; **BRIDGE** CRM tags |
| FC-05 | Payment failed (Woo) | Tag Payment Failed; emails; 48h; pause bookings; remove active | Woo `payment_failed` dispatch exists; CRM pause **UNVERIFIED** | `PaymentFailed` → NGT policy (pause?) → CRM tag/email | **MIGRATE** pause decision to NGT; **BRIDGE** CRM messaging |
| FC-06 | Inactivity 60 days | Inactive tag; offer; 30d wait; remove lists | **UNVERIFIED** in Companion | Scheduled journey → CRM projection | **BRIDGE** (CRM OK for marketing) |
| FC-07 | Tutor application submitted | Tutor Applicant list; 48h verify email; assign admin | Orchestrator + Hub + FluentCRM templates | `TutorApplicationSubmitted` → NGT → CRM projection | **MIGRATE** task SoR; **BRIDGE** CRM |
| FC-08 | Tutor verified | Verified/Active tags; welcome email+SMS; enroll onboarding | Orchestrator adapters + Hub role risk | `TutorVerified` → NGT GoLive → CRM tags; LMS via Learning port | **MIGRATE**; Hub `add_user_role` **DISABLE** |
| FC-09 | Tutor rejected | Rejected tag; email; archive | Orchestrator reject | `TutorRejected` → NGT → CRM | **MIGRATE** / **BRIDGE** email |
| FC-10 | Booking received (tutor) | bookings_count; tips at 1/10/50 Popular; SMS | CRM segment tips; Popular **badge** = 50 reviews | `BookingConfirmed` → NGT tutor notify → CRM segment | **MIGRATE**; Popular badge **MATCH** RC-01 (reviews) |
| FC-11 | Session completed & paid (tutor) | completed_sessions; total_earnings+=; Earning Tutors; milestones; Top Rated; Featured | Earnings in `ngc_earnings`; meta projection risky | Ledger SoR NGT; CRM segment projection only; Featured = marketplace policy | **MIGRATE** earnings; **BRIDGE** CRM; featured = marketplace **not** CRM |

### FluentCRM dual-fire watch

- Confirmation email+SMS on booking must not also fire from Amelia Notifier + NGT + AutomatorWP.  
- `sessions_count` / `total_earnings` in CRM custom fields = **projections**, not ledgers.

---

## 2. AutomatorWP triggers (orchestration — non-authoritative after migration)

| ID | Trigger | Actions | Repo | Target event / workflow | Decision |
|---|---|---|---|---|---|
| AW-01 | Session completed (Amelia) | Record `ngt_earnings`; update tutor meta; session summary email; LMS lesson | Deprecated recipes; earnings via Reviews/session paths PARTIAL | `SessionCompleted` → earnings eligibility → notify → Learning port | **MIGRATE** → **DISABLE** AutomatorWP |
| AW-02 | Rating submitted (GamiPress) | Recalc average; if &lt;4.0 flag+alert; sync CRM; award student points | Rating should not be owned by GamiPress | `RatingSubmitted` → TutorRatingService → quality rule → CRM/GamiPress ports | **MIGRATE**; GamiPress consumes only |
| AW-03 | Monthly cron 1st 02:00 | Gross −15% −penalties +bonuses; ≥R100 payout; invoice email; admin | `NGC_Payout_Scheduler` EXISTS | `TutorPayoutCycleStarted` → PayoutWorkflow + rules | **KEEP** Companion; **DISABLE** AutomatorWP cron twin |
| AW-04 | No-show (Amelia) | Refund; no-show count; deduct points; notify both | **UNVERIFIED** Companion path | `SessionNoShowDetected` → policy rules | **MIGRATE**; compensation % = **CONFLICT** RC-03 |
| AW-05 | Tutor application received | Admin review task; ack email; CRM segment | Orchestrator / applications | `TutorApplicationSubmitted` | **MIGRATE** → **DISABLE** AutomatorWP duplicate |
| AW-06 | User registration + referral | R50 credit; `ngt_referrals`; CRM tags | `NGC_Referrals` VERIFIED (filterable amount) | `ReferralRegistered` | **KEEP** Companion; **DISABLE** AutomatorWP twin; CRM tag **BRIDGE** |
| AW-07 | Safety flag (AI) | Fluent Support ticket; email; SMS; pause sessions; log | `NGC_Safeguarding` VERIFIED signal model | `SafetyFlagRaised` → Safety escalation | **KEEP** NGT; **DISABLE** AutomatorWP if duplicate |

---

## 3. Amelia / Notifier triggers (calendar + optional notices)

Amelia = availability / appointment execution. Transactional messages prefer NGT Notification ownership (see `NGT-JOURNEY-NOTIFICATION-OWNERSHIP.md`).

| ID | Trigger | Actions | Target | Decision |
|---|---|---|---|---|
| AM-01 | Booking created | Email customer confirm+calendar+Zoom; email tutor alert | `BookingConfirmed` after payment rules; Meeting port = **Jitsi** (Zoom copy DISABLE) | **DISABLE** Amelia customer confirm if NGT owns; tutor alert **MIGRATE** to NGT or **KEEP** one channel only |
| AM-02 | 24h before | Email+SMS customer; tutor summary | `SessionReminderDue24Hours` via `NGC_Session_Reminders` | **MIGRATE** to NGT; **DISABLE** Amelia duplicate |
| AM-03 | 1h before | SMS customer | `SessionReminderDue1Hour` | Same |
| AM-04 | Status changed | Cancel/reschedule email | `BookingCancelled` / reschedule events | **BRIDGE** or NGT-owned |
| AM-05 | No-show 15 min after | Tutor email + 50% compensation note | `SessionNoShowDetected` | **MIGRATE**; do not hard-code 50% in Amelia copy alone |

**Meeting SoR:** **Jitsi** (Companion). Amelia Zoom wording = **DISABLE** / non-authoritative.

---

## 4. GamiPress triggers (gamification projection)

Internal NGT catalog aligned to this map (**Match**). GamiPress remains **BRIDGE** only.

### Map vs repo (Match)

| Map rule | Map award | Repo | Status |
|---|---|---|---|
| Session completed | Student 50 / Tutor 25 | `session_completed` / `session_completed_tutor` in `NGC_Scoring_Engine` via `ngc_booking_completed` | **MATCH** |
| First booking | Beginner badge + 100 pts student | `NGC_First_Booking_Rule` + `first_booking` event; AutomatorWP dual-fire blocked | **MATCH** |
| 5 / 25 / 100 sessions (student) | Quick Learner / Scholar / Master | `NGC_Gamification_Milestones` | **MATCH** (code; runtime UNVERIFIED) |
| Tutor verified | Verified badge | `verified` / `tutor_approved` alias | **MATCH** / **BRIDGE** GamiPress |
| Rating ≥4.8 + 20 reviews | Highly Rated + featured meta | `tutor_rating_milestones` | **MATCH** (code) |
| 50+ reviews | Popular badge | `ngt.tutor.popular_review_threshold` = 50 reviews (RC-01) | **MATCH** |
| 30+ sessions one subject | Specialist | milestones subject counter | **MATCH** (code) |
| R100K+ lifetime earnings | Elite Earner | milestones on `ngt_lifetime_earnings` meta | **MATCH** (code; ledger sync PARTIAL) |

| ID | Trigger | Target | Decision |
|---|---|---|---|
| GP-01…GP-10 | (all above) | Domain event → `FirstBookingRule` / milestone rules → `GamificationProviderInterface` → GamiPress | **MIGRATE** rules to NGT; GamiPress **BRIDGE** only; never rating/listing authority |

---

## 5. WooCommerce triggers (commerce)

| ID | Trigger | Side effects in map | Target | Decision |
|---|---|---|---|---|
| WC-01 | Order/payment completed | Drives FC-02 / AutomatorWP payment recipe | `PaymentCaptured` via `NGC_Payments` | **KEEP** Woo as order SoR; **MIGRATE** downstream to Ecosystem |
| WC-02 | Payment failed | FC-05 | `PaymentFailed` | **KEEP** hook; **MIGRATE** business pause policy |

Never: Woo hook → direct Amelia DB write.

---

## 6. Canonical event bridge (map → Ecosystem)

| Business trigger family | Canonical event(s) |
|---|---|
| Lead / contact form | `TutorSearchStarted` / lead captured (alias existing find_tutor) |
| Booking + paid | `PaymentCaptured`, `BookingConfirmed` |
| Session done | `SessionCompleted` |
| Rating | `RatingSubmitted` |
| Payment failed | `PaymentFailed` |
| Inactivity | `LearnerInactivityDetected` (new scheduled) |
| Tutor apply / verify / reject | `TutorApplicationSubmitted`, `TutorVerified`, `TutorRejected` |
| Tutor booking received | `BookingConfirmed` (tutor lane) |
| Reminder 24h/1h | `SessionReminderDue24Hours`, `SessionReminderDue1Hour` |
| No-show | `SessionNoShowDetected` |
| Referral register | `ReferralRegistered` |
| Safety AI | `SafetyFlagRaised` |
| Payout month | `TutorPayoutCycleStarted` |

---

## 7. Single-authority matrix (after migration)

```text
PLUGIN TRIGGER (Woo / Amelia / WP / AI)
        ↓  (event source only)
CANONICAL DOMAIN EVENT
        ↓
ECOSYSTEM WORKFLOW (NGC_Workflow_Authority)
        ↓
BUSINESS RULES
        ↓
COMMANDS / SERVICES
        ↓
PORTS → ADAPTERS → FluentCRM | GamiPress | Amelia | MasterStudy | Support | SMS
```

| Concern | Allowed plugin role | Forbidden |
|---|---|---|
| FluentCRM | Tags, lists, nurture delays, marketing email | Booking pause, earnings math, listing featured |
| AutomatorWP | None for core (compatibility only) | Earnings, payout, safety, rating SoR |
| GamiPress | Award points/badges when commanded | Average rating, quality flag, featured search |
| Amelia | Calendar slots, optional one notify channel | Refund policy, compensation %, Zoom as meeting SoR |
| WooCommerce | Orders, payment statuses | Journey orchestration |

---

## 8. Duplicate kill list (must be unique after cutover)

| Side effect | Allowed single owner | Disable others |
|---|---|---|
| Booking confirmation email/SMS | NGT Notification | Amelia Notifier + AutomatorWP + Hub mail for same |
| 24h / 1h reminders | `NGC_Session_Reminders` | Amelia upcoming appointment |
| First-booking 100 pts | NGT FirstBookingRule (idempotent) | AutomatorWP Woo→100 pts |
| Session earnings write | NGT ledger | AutomatorWP + Amelia alone |
| Monthly payout | `NGC_Payout_Scheduler` | AutomatorWP monthly cron |
| Safety case create | `NGC_Safeguarding` | AutomatorWP safety recipe |
| Tutor role grant | NGT verification/onboarding | Hub `add_user_role` |
| Popular / Featured | Marketplace + policy | CRM or GamiPress alone |

---

## 9. Product confirmations (aligned to Match)

| Topic | Match decision | Runtime |
|---|---|---|
| Popular threshold | **50 reviews** (not bookings) | Code MATCH; E2E UNVERIFIED |
| Tutor session points | **25** | Code MATCH |
| First booking | **100 + Beginner** once | Code MATCH |
| Meeting link | **Jitsi** | Adapter VERIFIED class; live UNVERIFIED |
| No-show compensation | Default **50%** option | Path UNVERIFIED |
| Failed-payment pause | Ecosystem policy (not CRM alone) | PARTIAL |
| Recording | **OFF** | MATCH |
| Reminders | **NGT owns**; Amelia DISABLE dupes | MATCH ownership; Amelia config UNVERIFIED |
| Payout EFT | Create yes; EFT manual/UNVERIFIED | MATCH split |

FC-10 “Popular at 50 bookings” = CRM tip only — badge SoR is reviews (RC-01).

---

## 10. Relation to journey blueprints

| Journey | Trigger IDs primarily used |
|---|---|
| Learner | FC-01…06, WC-*, AM-01…04, GP student, AW-01/02/06 |
| Tutor | FC-07…11, AW-03…05, GP tutor, AM tutor alerts |
| Safety | AW-07 (+ safeguarding SLA) |

Studio visualization should import this catalog as nodes with provenance `business-map` + `repo-evidence` confidence tags.

---

## 11. Implementation stance

- **No production source changed** by this document.  
- On `APPROVED` for the blueprint plan, Slice 2 should load this file as the trigger inventory seed for `class-ngc-journey-events.php` + dual-fire detector.  
- Do not import AutomatorWP recipes into production as authoritative.

```text
STATUS: SYSTEM TRIGGER AUTOMATION MAP DOCUMENTED — AWAITING APPROVAL FOR IMPLEMENTATION

Reply APPROVED (blueprint plan) to execute migrations under ecosystem-workflow authority.
```
