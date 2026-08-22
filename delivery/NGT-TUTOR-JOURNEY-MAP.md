# NGT Tutor Journey Map

**Date:** 2026-08-16  
**Authority target:** Ecosystem workflow + People / Marketplace / Finance domains in Companion

---

## Target chain

```text
APPLICATION → VERIFICATION → ONBOARDING → PUBLISHING
  → BOOKINGS → TEACHING → PERFORMANCE → EARNINGS → PAYOUT
```

---

## Step map

### 1. Application (`/become-a-tutor/`)

| | |
|---|---|
| Fields | Identity, contact, subjects, qualifications, ID, certificates, police clearance |
| Current | Theme page + Companion registration / tutor lifecycle → `TUTOR_REGISTERED` |
| Form provider | Presentation only (Fluent Forms if present = input, not SoR) |
| Target | `TutorApplicationSubmitted` → People domain `TutorApplication` |
| File security | Sensitive uploads — enforce type/size/ACL/private storage/audit (**PARTIAL** today) |
| Decision | **MIGRATE** application SoR fully into Companion tables/meta, not FluentCRM |

### 2. Verification (SLA)

| | |
|---|---|
| Checks | ID, SACE, background, references, training, curriculum |
| Current | Admin Tutor Applications + orchestrator approve/reject |
| SLA | Business intent 48h → rule `ngt.tutor.verification_sla_hours` (**ABSENT** as option) |
| Events | `TutorVerificationStarted`, `TutorVerified`, `TutorRejected` |
| Decision | FluentCRM / GamiPress **must not** decide verification |

### 3. Approved / Rejected actions

**TutorVerified → TutorGoLiveWorkflow (not “publish immediately”):**

- Domain verification state  
- CRM projection (Verified Tutors / remove Applicant)  
- Welcome notification  
- Gamification verified badge (projection)  
- MasterStudy onboarding enrollment  
- Marketplace **eligibility evaluation** only  

**TutorRejected:** reason, history retained, CRM projection, notify, leave review queue.

### 4. Onboarding

| | |
|---|---|
| Required | MasterStudy course, rates, availability, photo, profile |
| Events | `TutorOnboardingStarted`, `TutorOnboardingCompleted` |
| Go-live | **Not** course completion alone |

### 5. Publication policy

Evaluate: verification + profile completeness + qualifications + availability + rates + safety badges + training + marketplace rules → `TutorPublicationEligible` | `TutorPublicationDenied`.

**Policy conflict:** “≥3 verification badges” vs mandatory safety subset — see rule conflicts doc. Mark **POLICY REQUIRES CONFIRMATION**.

### 6. Teaching

```text
BookingConfirmed → TutorBookingReceived → Notification
  → SessionStarted → Attendance → SessionCompleted
  → TutorEarningEligibility (requires payment/settlement evidence)
```

Amelia completion alone must **not** create earnings.

### 7. Performance

| Rule intent | Target option | Current |
|---|---|---|
| rating &lt; 4.0 → review | `ngt.tutor.performance.minimum_rating` | **ABSENT** as named rule |
| Popular eligibility | `ngt.tutor.popular_review_threshold` | **MATCH** — 50 **reviews** (RC-01) |
| Month boundary → payout | Payout cycle | Scheduler exists |

### 8. Earnings ledger (authoritative)

Required fields: earning_id, tutor_id, booking_id, session_id, order_id, gross, platform_fee, bonus, penalty, net, currency, status, timestamps.

Current: `wp_ngc_earnings` + `NGC_Reviews` payout helpers — **PARTIAL** vs full ledger schema.

User meta `total_earnings` = projection only.

### 9. Monthly payout

| | |
|---|---|
| Schedule | 1st 02:00 — intent timezone `Africa/Johannesburg` (**UNVERIFIED** in code; uses WP `strtotime`) |
| Fee | Intent 15% → `ngt.payout.platform_fee_percent` (display 15 in UI library; apply in ledger **PARTIAL**) |
| Minimum | Intent R100 → `ngt.payout.minimum_amount` |
| Outcomes | PAYOUT_ELIGIBLE / CARRY_FORWARD / HOLD / REVIEW_REQUIRED |
| EFT | `PayoutProviderInterface` — if no bank adapter: **PAYOUT CREATION = IMPLEMENTED**, **EFT = UNVERIFIED** |
| Invoice | Document provider port (Fluent PDF = adapter if used) |

---

## State machine (proposed)

```text
APPLICATION_DRAFT → SUBMITTED → UNDER_REVIEW
  → VERIFIED | REJECTED | RESUBMISSION_REQUIRED
  → ONBOARDING → READY_FOR_LISTING → LIVE
  → SUSPENDED | INACTIVE
```

---

## Graph

```text
Apply → Verification → Approved/Rejected → Onboarding
  → Safety/Listing Rules → Go Live → Booking → Session
  → Earning → Performance → Payout Cycle → Payout
```
