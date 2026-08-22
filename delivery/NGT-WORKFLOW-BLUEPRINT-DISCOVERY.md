# NGT Workflow Blueprint Discovery

**Date:** 2026-08-16  
**Phase:** DISCOVER → MAP → GAPS → PLAN (no production source changed)  
**Evidence policy:** VERIFIED = callable path in repo · PARTIAL = scaffolding/adapters · UNVERIFIED = narrative only · ABSENT = package/name not found

---

## 1. Critical architecture finding

The prompt assumes packages named:

| Prompt name | Repo status |
|---|---|
| `ecosystem-platform` | **ABSENT** as a deployable package |
| `ecosystem-workflow` | **ABSENT** as a package; closest = `NGC_Workflow_Authority` + orchestrator/studio/hub |
| `ngt-booking` | **ABSENT**; booking lives in Companion `NGC_Bookings` + Amelia adapter |
| `ngt-marketplace` | **ABSENT**; marketplace = Companion `NGC_Marketplace` |
| `ngt-people` | **ABSENT**; people = WP users + Companion registration/tutor lifecycle |
| `BookingProviderInterface` | **ABSENT** as named interface; generic `NGC_Integration_Adapter` exists |
| `CommerceProviderInterface` / `GamificationProviderInterface` | **ABSENT**; GamiPress adapter class exists |

**What does exist (sacred packages + ops):**

| Package | Role |
|---|---|
| BeyondInfinity theme | Pages, hero, find/become tutor presentation |
| Companion | Domain, REST, workflows, bookings, payments, safeguarding, marketplace |
| Beyond Measure | Control-plane SPA (not journey owner) |
| AI-Integration | Transport for AI signals |
| Plugin-Manager | Stack install (AutomatorWP, GamiPress, Amelia, …) |
| Html-Importer | Content import only |
| `rad-platform/` | Architecture governance kit (manifests/capabilities/gate) |
| `nextgen-automation-hub` | Bundled notification workflows (parallel producer) |
| `services/ngt-agent-gateway` | Agent HTTP gateway |

**Target mapping (do not invent packages that already “exist”):**

```text
Prompt "ecosystem-workflow"
  → KEEP/EVOLVE: NGC_Workflow_Authority (+ durable queue)
  → producers: Orchestrator, Studio, Hub, AutomatorWP (must yield)

Prompt "ngt-booking"
  → EVOLVE: NGC_Bookings + Session Orchestrator + ports over Amelia

Prompt "ngt-marketplace"
  → EVOLVE: NGC_Marketplace (+ Talent ranking policy)

Prompt "ngt-people"
  → EVOLVE: NGC_Registration + NGC_Tutor_Lifecycle + applications

Prompt "ecosystem-platform"
  → EVOLVE: rad-platform manifests + Companion platform kernel
```

Legacy dual emitters are already warned: kill switch `ngc_workflow_authority_kill` restores dual-fire; AutomatorWP JSON under `automations/` is **deprecated runtime path** (`automations/README.md`).

---

## 2. Inventory — pages / journeys surfaces

| Surface | Owner | Evidence |
|---|---|---|
| Homepage / hero → `/find-a-tutor` | Theme kinetic / Elementor-capable | `inc/defaults-production/home.php`; Elementor native path |
| `/find-a-tutor/` | Theme + `[ngc_find_tutor_form]` + marketplace | `page-find-tutor.php`, `NGC_Marketplace` |
| Tutor cards / filters | Companion marketplace | subject, grade, province, price, rating; availability via meta/talent **PARTIAL** |
| Tutor profile | Theme tags + Companion data | `inc/tags/tutors.php` |
| `/become-a-tutor/` | Theme + registration forms | `page-become-a-tutor.php`, `NGC_Registration` |
| Parent checkout | Companion | `NGC_Parent_Checkout` + Woo + PayFast |
| Dashboards | Theme shells + `ngc/v1/dashboard/*` | REST-backed |
| Safety guide copy | Theme defaults | Observer mentioned; **recording explicitly not offered** |

**Legacy builders:** WPBakery / Elementor isolation CSS exists; homepage is BeyondInfinity kinetic/Elementor — **not** SmartHead/Slider Revolution as architectural requirement. Do not retain Slider Revolution as a dependency.

---

## 3. Inventory — workflows / events / authority

| Mechanism | Current authority | Target authority | Decision |
|---|---|---|---|
| `NGC_Workflow_Authority` | Primary side-effect executor when option ON | `ecosystem-workflow` (same role, clearer naming) | **KEEP** → rename/capability wrap |
| `NGC_Workflow_Orchestrator` | Registration lifecycle (7 handlers) | Journey workflows under authority | **KEEP** / **MIGRATE** into journey workflows |
| `NGC_Studio_*` | Visual graph executor if published | Visualization + optional DESIGN; not dual fire | **BRIDGE** VIEW; publish gated |
| Automation Hub JSON | Notifications/RTM; one `add_user_role` | Notification projections | **MIGRATE** notifications; **DISABLE** role mutation |
| Theme `bi_workflow_*` | Fallback when Companion inactive | Fallback only | **KEEP** fallback |
| AutomatorWP recipes (`automations/*.json`) | Deprecated import templates | Compatibility adapter only | **DISABLE** for core journeys when Ecosystem live |
| FluentCRM sequences JSON | Marketing nurture templates | CRM projection / nurture | **KEEP** as CRM-owned sequences |
| `NGC_Payments` + PayFast ITN | Payment settle → `ngc_payment_settled` | Commerce adapter → `PaymentCaptured` | **KEEP** / event alias |
| `NGC_Session_Orchestrator` | Booking/payment → session provision | Booking/session application service | **KEEP** |
| `NGC_Session_Reminders` | 24h/1h/15m | Reminder workflows + idempotency keys | **KEEP** / harden idempotency |
| `NGC_Payout_Scheduler` | Monthly 1st 02:00 + biweekly | `TutorPayoutCycleStarted` | **KEEP**; timezone **UNVERIFIED** (WP timezone) |
| `NGC_Safeguarding` | Cases + SLA tick; AI = signal only | Safety journey authority | **KEEP** |
| `NGC_Referrals` | Register + convert on payment; default R50 filterable | Referral workflow | **KEEP** |
| `NGC_Reviews` + earnings/payouts | Ratings + earnings/payout records | Finance/earnings SoR | **KEEP** / strengthen ledger fields |
| GamiPress adapter | Projection/awards | GamificationProvider | **KEEP** as adapter only |
| FluentSupport adapter | Support tickets | Safety case transport | **BRIDGE** |
| Action Scheduler | Observed in observability | Not NextGen journey bus | **DO NOT** own journeys |

---

## 4. AutomatorWP / legacy recipe → target map

| Legacy recipe | Current trigger | Current actions | Target event | Target workflow | Legacy status |
|---|---|---|---|---|---|
| Booking → Welcome (`automation-3`) | Woo order completed | FluentCRM tags/lists, GamiPress 100 pts, MasterStudy enroll | `PaymentCaptured` + `BookingConfirmed` | `LearnerBookingConfirmationWorkflow` | **MIGRATE** then **DISABLE** AutomatorWP |
| Session → Rating | Amelia completed (intent) | (recipe incomplete in folder) | `SessionCompleted` | `PostSessionWorkflow` | **MIGRATE** |
| Rating → Score | GamiPress (intent) | — | `RatingSubmitted` → TutorRatingService | `RatingWorkflow` | **MIGRATE** (GamiPress consumes) |
| Verification → Go Live | Tutor approved | CRM/LMS/badge (orchestrator) | `TutorVerified` | `TutorGoLiveWorkflow` + listing policy | **MIGRATE** |
| Monthly payout | AutomatorWP cron (intent) / Companion scheduler | Payout records | `TutorPayoutCycleStarted` | `PayoutWorkflow` | Companion **KEEP**; AutomatorWP **DISABLE** |
| No-show | Amelia status (intent) | — | `SessionNoShowDetected` | Policy-driven | **UNVERIFIED** path |
| Referral | user_register + cookie | R50 pending | `ReferralRegistered` | Referral workflow | **KEEP** Companion |
| Safety escalation | AI flag | Safeguarding case | `SafetyFlagRaised` | Safety escalation | **KEEP** Companion; AutomatorWP **DISABLE** |

---

## 5. Domain events — current vs canonical

Current names are mostly `ngc_*` / `ngt.*` / Woo hooks. Canonical PascalCase events from the prompt are **mostly ABSENT** as a single catalog.

| Canonical (target) | Closest current | Status |
|---|---|---|
| `TutorApplicationSubmitted` | `TUTOR_REGISTERED` / form submit | **BRIDGE** alias |
| `TutorVerified` / `TutorRejected` | `TUTOR_APPROVED` / `TUTOR_REJECTED` | **BRIDGE** |
| `BookingRequested` / `BookingConfirmed` | booking statuses + `ngc_booking_confirmed` | **BRIDGE** |
| `PaymentCaptured` | `ngc_payment_settled` | **BRIDGE** |
| `SessionReminderDue*` | reminder queue offsets | **PARTIAL** |
| `SessionCompleted` | `ngc_booking_completed` / lesson hooks | **PARTIAL** |
| `RatingSubmitted` | `NGC_Reviews` | **PARTIAL** |
| `TutorPayoutCycleStarted` | payout cron | **PARTIAL** |
| `SafetyFlagRaised` | safeguarding create + AI signal flag | **PARTIAL** |
| `RecordingConsentGranted` | **CONFLICT** — product copy says recording not offered | **POLICY** |
| Marketplace search events | marketplace query only | **UNVERIFIED** as events |

---

## 6. Ports / adapters found

| Adapter | Interface | Role |
|---|---|---|
| FluentCRM | `NGC_Integration_Adapter` | CRM create/update |
| Amelia | same | Employee/booking sync |
| MasterStudy | same | LMS roles/enrollment |
| Email / Audit / Verification / Jitsi | same / dedicated | Notifications, meetings |
| GamiPress | `NGC_Gamipress_Adapter` | Points/badges projection |
| FluentSupport | loaded in core loader | Support cases |
| PayFast | Woo gateway + ITN | Payment capture |
| WooCommerce | hooks in `NGC_Payments` | Order authority |

**Missing named ports:** `BookingProviderInterface`, `CommerceProviderInterface`, `NotificationApplicationService` as first-class APIs — to be introduced as thin interfaces over existing classes (not parallel engines).

---

## 7. Duplicate automation risks (summary)

See also `delivery/WORKFLOW-DOUBLE-FIRE-AUDIT.md`.

1. Woo completed → Payments **and** Hub RTM **and** optional AutomatorWP  
2. Tutor approved → Orchestrator **and** Hub `add_user_role`  
3. `user_register` → Theme pack **and** Hub  
4. Studio importer published graphs vs orchestrator  
5. Reminder: Amelia native + `NGC_Session_Reminders` (**POTENTIAL**)  
6. Payout: Companion scheduler vs Hub monthly cron probe  

---

## 8. Classification rollup

| Decision | Items |
|---|---|
| **KEEP** | Workflow Authority, Payments+PayFast, Bookings+Session Orchestrator, Marketplace, Safeguarding, Referrals, Reminders, Payout scheduler, POPIA consent, theme presentation |
| **MIGRATE** | AutomatorWP recipes → journey workflows; Hub mutations → notifications only; event names → canonical aliases |
| **BRIDGE** | Studio visualization; FluentCRM nurture; GamiPress awards; Amelia calendar; FluentSupport tickets; MasterStudy enrollment |
| **DISABLE** | AutomatorWP for payment/welcome/payout/safety cores once Ecosystem workflows proven; Hub `add_user_role` |
| **REMOVE** | Do not remove packages blindly; deprecate dual emitters via authority ON + recipe deactivate |

---

## 9. What was not proven in this scan

- Live plugin activation matrix in Docker  
- Live AutomatorWP recipe rows in DB  
- Real EFT bank transfer  
- Parent observer mode in Jitsi  
- Background screening vendor APIs  
- Exact 15% fee and R100 minimum as coded business rules (UI library shows platform_fee 15 in display; payout path does not clearly apply configurable fee in `NGC_Reviews` skim)

No production implementation source was modified for this document.
