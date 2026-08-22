# NGT Learner Journey Map

**Date:** 2026-08-16  
**Authority target:** Ecosystem workflow (= `NGC_Workflow_Authority` + journey workflows)  
**Plugins:** adapters / event sources only

---

## Target chain

```text
DISCOVERY → SEARCH → PROFILE → BOOKING → PAYMENT → CONFIRMATION
  → REMINDERS → SESSION → POST-SESSION → PROGRESS
```

---

## Step map (current → target)

### 1. Discovery

| | |
|---|---|
| UX | Homepage hero CTA → `/find-a-tutor/` |
| Current | BeyondInfinity kinetic/Elementor home (`inc/defaults-production/home.php`); CTA URLs to find-a-tutor |
| Legacy | SmartHead / WPBakery / Slider Revolution — **not required** for architecture |
| Target | Theme presentation only; no CRM/Amelia on homepage |
| Decision | **KEEP** theme; do not reintroduce Slider Revolution |

### 2. Search & filter

| | |
|---|---|
| UX | Subject, Grade, Province, Price, Rating, Availability → Tutor cards |
| Current | `NGC_Marketplace` filters subject/grade/province/price/rating; talent re-rank optional |
| Gap | Availability via `BookingProviderInterface` **ABSENT**; may read Amelia/meta directly (**risk**) |
| Target | `MarketplaceSearchQuery` → marketplace service → projections; ranking = `MarketplaceRankingPolicy` |
| Decision | **MIGRATE** search off direct Amelia/GamiPress DB |

### 3. Booking + payment

| | |
|---|---|
| Current | Parent checkout → Woo order → PayFast ITN → `NGC_Payments::settle` → `ngc_payment_settled` → `NGC_Session_Orchestrator` |
| Bookings SoR | `wp_ngc_bookings` (+ optional `amelia_booking_id`) |
| Gap | Direct Woo→Amelia DB write must not exist; sync via adapter |
| Target | `BookingRequested` → rules → `CreateBookingCommand` → Booking port → Amelia adapter; `CreateOrder` → Commerce port → Woo → PayFast; `PaymentCaptured` → confirmation workflow |
| Decision | **KEEP** spine; introduce ports + canonical events |

### 4. Payment complete automation (7 intents)

| Intent | Target owner | Current |
|---|---|---|
| CRM active_customer tag / list move | FluentCRM **projection** | AutomatorWP recipe + FluentCRM adapter (PARTIAL) |
| Welcome nurture sequence | FluentCRM sequence | JSON templates in `automations/` |
| First-booking reward (100 pts) | Rule → Gamification port → GamiPress | AutomatorWP 100 pts on **any** completed payment (not first-booking gated in recipe) |
| Confirmation SMS | Notification service | **PARTIAL** / UNVERIFIED SMS provider |
| Ensure student identity | NGT people (WP user) | WP user from checkout |
| Enroll subject course | MasterStudy adapter | AutomatorWP enroll action (placeholders) |
| Confirm booking/session | Booking/session services | Session orchestrator on settle |

**Idempotency:** first-booking reward must key on `user_id + first_booking` — **not implemented** in AutomatorWP recipe (awards on every completed order).

### 5. Reminders

| | |
|---|---|
| Current | `NGC_Session_Reminders` 24h / 1h / 15m |
| Target | `SessionReminderDue24Hours` / `SessionReminderDue1Hour` (+ optional 15m) |
| Rule | Idempotency `session_id + reminder_type + occurrence` |
| Risk | Duplicate Amelia + Ecosystem reminders |

### 6. Session

| | |
|---|---|
| Meeting | Jitsi adapter (**VERIFIED** class); Amelia is calendar, not A/V authority |
| Events | `SessionStarted`, `AttendanceRecorded`, `SessionNotesRecorded`, `SessionCompleted` — **PARTIAL** |
| Decision | Meeting provider owns room/URL; NGT owns business session state |

### 7. Post-session + progress

| | |
|---|---|
| Target | `PostSessionWorkflow` → rating request, learning history, CRM projection, gamification |
| Then | `RatingSubmitted` → rating service → quality rules / marketplace / CRM / rewards |
| Progress projections | completed_sessions, subject_sessions, points, badges, courses — **NGT projection**, not FluentCRM/GamiPress SoR |

---

## State machine (proposed — align to existing booking statuses where possible)

```text
DISCOVERED → SELECTING_TUTOR → BOOKING_REQUESTED → PAYMENT_PENDING
  → PAYMENT_CAPTURED → BOOKING_CONFIRMED → SESSION_SCHEDULED
  → SESSION_READY → SESSION_ACTIVE → SESSION_COMPLETED
  → RATING_PENDING → COMPLETED
```

Do not invent parallel status columns if `NGC_Bookings` statuses already cover the commercial booking; map session states onto session entity / meta.

---

## Graph (Workflow Studio)

```text
Homepage → Find Tutor → Search → Tutor Profile → BookingRequested
  → Booking Rules → Checkout → PaymentCaptured → BookingConfirmed
  → CRM / Rewards / Enrollment / Notification
  → 24h Reminder → 1h Reminder → Session → SessionCompleted
  → Rating Request → RatingSubmitted → Progress
```

Every node must resolve to a class/hook/event; decorative nodes forbidden.
