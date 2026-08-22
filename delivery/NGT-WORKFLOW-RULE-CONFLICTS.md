# NGT Workflow Rule Conflicts

**Date:** 2026-08-16  
**Rule:** Conflicts aligned to **Match** (System Triggers + product SoR). Residual items stay POLICY / UNVERIFIED only where product still must confirm numbers.

---

## RC-01 Popular Tutor threshold — **RESOLVED (Match)**

| Position A | Position B | **Match** |
|---|---|---|
| 50 **bookings** | 50 **reviews** | **50 reviews** |

**Implementation:** `ngt.tutor.popular_review_threshold` = 50; `NGC_Gamification_Milestones::tutor_rating_milestones`.  
FC-10 “50 bookings” remains a **CRM segment tip only**, not the Popular badge rule.

---

## RC-02 Listing verification badges — **RESOLVED (Match, soft-gated)**

| Position A | Position B | **Match** |
|---|---|---|
| Any **3 of 5** badges | Mandatory ID + Background + Training | **Mandatory safety subset** |

**Implementation:** `NGC_Tutor_Listing_Eligibility` prefers mandatory checks; auto-publish remains **OFF** until ops enable. Count-of-3 is not the gate.

---

## RC-03 No-show compensation vs refunds — **POLICY (default documented)**

| Position A | Position B |
|---|---|
| 50% compensation (narrative) | Woo/PayFast refund / cancel rules |

**Match default:** `ngt.no_show.compensation_percent` = 50.00 (option-backed). Dedicated no-show workflow still **UNVERIFIED** at runtime.

---

## RC-04 Session recording — **RESOLVED (Match = OFF)**

| Live product | Blueprint | **Match** |
|---|---|---|
| Recording **not offered** | Parent opt-in consent | **Recording OFF** |

**Implementation:** `ngt.session.recording_enabled` default `0`. No recording pipeline.

---

## RC-05 Tutor payout automation — **RESOLVED (Match split)**

| Position A | Position B | **Match** |
|---|---|---|
| Automatic EFT | Manual / fraud hold | **Payout create = IMPLEMENTED; EFT = UNVERIFIED / manual** |

---

## RC-06 Reminder ownership — **RESOLVED (Match)**

| Position A | Position B | **Match** |
|---|---|---|
| `NGC_Session_Reminders` | Amelia native | **NGT owns transactional reminders; Amelia reminders DISABLE** for same messages |

---

## RC-07 First-booking reward — **RESOLVED (Match)**

| Position A | Position B | **Match** |
|---|---|---|
| First booking only → 100 + Beginner | AutomatorWP 100 on every Woo order | **FirstBookingRule** idempotent; AutomatorWP core blocked |

**Implementation:** `ngt.booking.first_booking_reward` = 100; badge key `beginner` (alias `payment_first`).

---

## RC-08 Safeguarding SLA naming — **RESOLVED (Match)**

Blueprint “2-hour response” maps to **critical** priority (`ngt.safety.high_priority_response_sla` = 2).

---

## RC-09 Payout timezone — **RESOLVED (Match default)**

Canonical business timezone option: `ngt.payout.timezone` = `Africa/Johannesburg`. Scheduler TZ wiring remains **PARTIAL** until cron uses the option end-to-end (**UNVERIFIED** at runtime).

---

## Decision log

| ID | Decision | Owner | Date |
|---|---|---|---|
| RC-01 | Popular = 50 **reviews** | Match / System Triggers | 2026-08-16 |
| RC-02 | Mandatory ID+Background+Training (soft) | Match | 2026-08-16 |
| RC-03 | Default 50% option; path UNVERIFIED | Match default | 2026-08-16 |
| RC-04 | Recording OFF | Match / product copy | 2026-08-16 |
| RC-05 | Create payout yes; EFT manual/UNVERIFIED | Match | 2026-08-16 |
| RC-06 | NGT reminders own; Amelia DISABLE dupes | Match | 2026-08-16 |
| RC-07 | First booking 100 + Beginner once | Match | 2026-08-16 |
| RC-08 | critical = 2h | Match | 2026-08-16 |
| RC-09 | Africa/Johannesburg default | Match | 2026-08-16 |
