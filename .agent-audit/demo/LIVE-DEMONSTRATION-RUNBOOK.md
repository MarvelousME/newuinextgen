# Live demonstration runbook (Phase 14)

**Status:** READY FOR EVALUATOR (seed first)  
**Seed version:** 14.0.0

## Preconditions

1. Docker stack running at `http://localhost:8900` (WordPress + Companion active).
2. Log into WP Admin and open **Platform → Demo Control Centre** (`/wp-admin/admin.php?page=ngc-demo-control`).
3. Click **Enable demo mode**, then **Seed all** (wait for `Seed complete` flash).
4. Click **Verify** — result must show **PASS** (note any failures before continuing).
5. Copy the shared demo password from the persona directory table on the Control Centre page.

---

## Demonstration sequence (30 steps)

### Step 1 — New parent registration

- **Demo user:** `demo.parent.new@nextgen.local` (NGT-DEMO-P0002) — or register a fresh parent via `/register/`
- **Starting page:** `http://localhost:8900/register/`
- **Action:** Complete parent registration form (name, email, password, POPIA consent checkbox) and submit.
- **Expected screen result:** Success message; redirect to `/login/` or `/parent-dashboard/` with onboarding prompt.
- **Expected database result:** New `wp_users` row with role `parent`; demo meta `is_demo=1`, `demo_scenario_id=new-parent`.
- **Expected domain event:** `ParentRegistered` / `parent_register.submitted`
- **Expected trigger:** `parent_register.submitted` → workflow `PARENT_REGISTERED`
- **Expected notification:** Sandbox welcome email queued to parent inbox (Control Centre log).
- **Expected integration result:** `crm-parent-sync` stub records new contact (sandbox, no external send).
- **Expected audit event:** `user_registered` with demo correlation ID.
- **Verification method:** Login as new parent; confirm role in **Users** admin or `wp ngc demo_verify --allow-root`.

### Step 2 — Minor student creation

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001, Thandi Molefe)
- **Starting page:** `http://localhost:8900/parent-dashboard/` → **Children** / add-child form
- **Action:** Add minor learner profile (display name, grade, date of birth, link to parent guardian).
- **Expected screen result:** Child card appears on parent dashboard (e.g. Lerato Molefe, Grade 9).
- **Expected database result:** Row in `ngc_child_learners` with `parent_user_id` linked; `is_demo=1`, `demo_seed_version=14.0.0`.
- **Expected domain event:** `child_learner.created`
- **Expected trigger:** `child_learner.created` → LMS enrolment workflow
- **Expected notification:** `child-profile-created` → `demo.parent@nextgen.local`
- **Expected integration result:** `lms-student-sync` sandbox enrolment record for minor.
- **Expected audit event:** `child_learner_created` with parent and child IDs.
- **Verification method:** Parent dashboard lists child; seed graph key `children` populated after re-seed.

### Step 3 — Consent capture

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** `http://localhost:8900/privacy-policy/` or consent modal on `/parent-dashboard/`
- **Action:** Grant POPIA / child-data processing consent and submit consent form.
- **Expected screen result:** Consent status badge shows **Granted**; child features unlocked.
- **Expected database result:** Row in `ngc_platform_repository` (`consent`) with `consent_status=granted`, demo meta attached.
- **Expected domain event:** `consent.recorded`
- **Expected trigger:** `consent.recorded` → compliance retention workflow
- **Expected notification:** Sandbox consent confirmation to parent (no external delivery).
- **Expected integration result:** Consent flag synced to CRM stub (`crm-parent-sync`).
- **Expected audit event:** `demo_consent_recorded` / `consent-recorded`
- **Verification method:** Inspect seed graph event `consent-recorded`; audit log filter on Control Centre.

### Step 4 — Tutor search

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** `http://localhost:8900/find-a-tutor/`
- **Action:** Enter subject **Mathematics**, grade **Grade 9**, province **Gauteng**; run search / submit assessment request.
- **Expected screen result:** Tutor marketplace shows ranked candidates including Dr Nomsa Khumalo (Mathematics).
- **Expected database result:** New or existing match request row in `ngc_matches` with notes `Demo scenario MATCH-001`.
- **Expected domain event:** `match.requested`
- **Expected trigger:** `match.requested` → matching workflow dispatch
- **Expected notification:** None yet (awaiting AI ranking).
- **Expected integration result:** Search index / marketplace cache refreshed (local).
- **Expected audit event:** `match_request_created` with subject and province.
- **Verification method:** REST `/ngc/v1/matches` or seed graph key `MATCH-001` after seed.

### Step 5 — AI-assisted matching

- **Demo user:** `demo.admin@nextgen.local` (NGT-DEMO-A0001) or system (post-search)
- **Starting page:** **WP Admin → Operations → Agent Ops** (`/wp-admin/admin.php?page=ngc-agent-ops`) or match detail for MATCH-001
- **Action:** Review AI matching-assistant recommendation for MATCH-001; confirm ranked tutor assignment.
- **Expected screen result:** Match detail shows AI score, recommended tutor **Dr Nomsa Khumalo**, scenario label MATCH-001.
- **Expected database result:** `ngc_matches` row updated with `tutor_user_id` for NGT-DEMO-T0001; agent task `AI-001` in seed graph.
- **Expected domain event:** `match.proposed`
- **Expected trigger:** `agent.recommend` (matching-assistant) → `match.proposed`
- **Expected notification:** `match-proposed` → `demo.parent@nextgen.local`
- **Expected integration result:** `booking-sync` pre-link created; CRM match note (sandbox).
- **Expected audit event:** `match_manual_assign` (admin override if manual assign used)
- **Verification method:** `wp ngc demo_run_journey --id=MATCH-001 --allow-root`; inspect seed graph `agents.AI-001`.

### Step 6 — Match acceptance

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** `http://localhost:8900/parent-dashboard/` → **Matches**
- **Action:** Open MATCH-001 (Mathematics / Dr Nomsa Khumalo) and click **Accept match**.
- **Expected screen result:** Match status **Accepted**; **Book session** CTA enabled.
- **Expected database result:** `ngc_matches.status=accepted`; event `match.accepted:{id}` in seed graph.
- **Expected domain event:** `match.accepted`
- **Expected trigger:** `match.accepted` → booking-offer workflow
- **Expected notification:** `match-accepted` → `demo.tutor.approved@nextgen.local`
- **Expected integration result:** Tutor CRM profile updated with active match (sandbox).
- **Expected audit event:** `match_accepted` with match_id and parent_id.
- **Verification method:** Parent dashboard match list; journey `MATCH-001` PASS in evidence export.

### Step 7 — Booking creation

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** Parent dashboard → accepted MATCH-001 → **Book session**
- **Action:** Select available slot (+2 days), confirm learner **Lerato Molefe**, submit booking request.
- **Expected screen result:** Booking summary shown with status **Pending payment** or **Requested** (BOOK-001 path).
- **Expected database result:** Row in `ngc_bookings` with `notes=BOOK-001`, amount **ZAR 450.00**, demo meta attached.
- **Expected domain event:** `booking.created`
- **Expected trigger:** `booking.created` → payment-init workflow
- **Expected notification:** Booking request acknowledgement (sandbox) to parent.
- **Expected integration result:** `commerce-order` draft order created (PayFast sandbox).
- **Expected audit event:** `booking_created` with booking_id and match_id.
- **Verification method:** Seed graph key `bookings.BOOK-001`; `wp ngc demo_run_journey --id=BOOK-001 --allow-root`.

### Step 8 — Sandbox payment

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** Booking checkout / PayFast sandbox redirect (demo mode forces sandbox)
- **Action:** Complete sandbox payment for BOOK-001 (wallet debit or PayFast sandbox ITN simulate).
- **Expected screen result:** Payment success screen; receipt reference displayed (no real charge).
- **Expected database result:** Wallet ledger debit or sandbox payment record; booking payment flag set.
- **Expected domain event:** `payment.succeeded` / `PaymentSucceeded`
- **Expected trigger:** `payment.succeeded` → booking-confirm workflow (FIN-001)
- **Expected notification:** `payment-receipt` → `demo.parent@nextgen.local`
- **Expected integration result:** `commerce-order` status **paid** (sandbox adapter); PayFast ITN logged.
- **Expected audit event:** `payment_succeeded` with amount 450 ZAR and booking_id.
- **Verification method:** `wp ngc demo_run_journey --id=FIN-001 --allow-root`; notification log entry `payment-receipt`.

### Step 9 — Booking confirmation

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** `http://localhost:8900/parent-dashboard/` → **Bookings**
- **Action:** Refresh bookings list after payment; open BOOK-001 detail.
- **Expected screen result:** BOOK-001 status **Confirmed**; session date/time and tutor visible.
- **Expected database result:** `ngc_bookings.status=confirmed` for BOOK-001; graph event `booking.confirmed:{id}`.
- **Expected domain event:** `booking.confirmed`
- **Expected trigger:** `booking.confirmed` → calendar-sync + reminder schedule workflow
- **Expected notification:** `booking-confirmed` → `demo.parent@nextgen.local`
- **Expected integration result:** `booking-sync` pushes confirmed slot to tutor calendar adapter.
- **Expected audit event:** `booking_confirmed`
- **Verification method:** Parent and tutor dashboards show same BOOK-001; verify PASS on seed graph.

### Step 10 — Tutor calendar update

- **Demo user:** `demo.tutor.approved@nextgen.local` (NGT-DEMO-T0001, Dr Nomsa Khumalo)
- **Starting page:** `http://localhost:8900/tutor-dashboard/` → **Calendar** / **Bookings**
- **Action:** Open calendar view; locate BOOK-001 Mathematics session (+2 days).
- **Expected screen result:** Confirmed session block on tutor calendar with learner name and subject.
- **Expected database result:** Booking row linked to `tutor_user_id` NGT-DEMO-T0001; calendar slot marked busy.
- **Expected domain event:** `calendar.updated` (derived from `booking.confirmed`)
- **Expected trigger:** `booking.confirmed` → tutor calendar sync
- **Expected notification:** Session assigned notice to tutor (sandbox).
- **Expected integration result:** Tutor calendar REST `/ngc/v1/tutor-calendar` returns BOOK-001 event.
- **Expected audit event:** `tutor_calendar_synced` with booking_id.
- **Verification method:** Login as tutor; confirm BOOK-001 visible; REST calendar endpoint returns event.

### Step 11 — Session reminder

- **Demo user:** `demo.admin@nextgen.local` (NGT-DEMO-A0001)
- **Starting page:** **Platform → Demo Control Centre** → clock / scheduler panel
- **Action:** Click **Advance clock +1 day**, then **Process schedulers** (or CLI `wp ngc demo_advance_time --seconds=86400`).
- **Expected screen result:** Control Centre shows advanced demo clock; scheduler run summary.
- **Expected database result:** Demo clock offset updated; scheduled reminder jobs marked processed.
- **Expected domain event:** `DemoClockAdvanced`
- **Expected trigger:** `scheduler.session_reminder` (cron tick on advanced time)
- **Expected notification:** `scheduler-tick` / session-reminder → parent and tutor (sandbox log)
- **Expected integration result:** No external SMS/email sent (sandbox capture only).
- **Expected audit event:** `demo_clock_advanced` with new offset timestamp.
- **Verification method:** Notification log on Control Centre shows reminder entries after clock advance.

### Step 12 — Session completion

- **Demo user:** `demo.tutor.approved@nextgen.local` (NGT-DEMO-T0001)
- **Starting page:** Tutor dashboard → **BOOK-COMPLETED** session (seeded −7 days) or mark BOOK-001 complete
- **Action:** Mark session **Completed** with attendance notes and duration.
- **Expected screen result:** Session status **Completed**; parent notified prompt shown to tutor.
- **Expected database result:** `ngc_bookings.status=completed` for BOOK-COMPLETED; graph event `lesson.completed:{id}`.
- **Expected domain event:** `SessionCompleted` / `lesson.completed`
- **Expected trigger:** `lesson.completed` → review-request + progress-report workflow
- **Expected notification:** `session-completed` and `review-request` → `demo.parent@nextgen.local`
- **Expected integration result:** LMS attendance stub updated (`lms-student-sync`).
- **Expected audit event:** `lesson_completed` with booking_id and tutor_id.
- **Verification method:** Seed graph `bookings.BOOK-COMPLETED`; notifications `session-completed` in log.

### Step 13 — Tutor earnings

- **Demo user:** `demo.tutor.approved@nextgen.local` (NGT-DEMO-T0001)
- **Starting page:** Tutor dashboard → **Earnings** / **Wallet**
- **Action:** View earnings summary after BOOK-COMPLETED session.
- **Expected screen result:** Earnings line for ZAR 450.00 (or net after platform fee); payout status **Pending**.
- **Expected database result:** Payout row created in wallet/payout table; graph `wallet.payout_pending` set.
- **Expected domain event:** `TutorEarningsCreated`
- **Expected trigger:** `lesson.completed` → earnings accrual workflow
- **Expected notification:** `tutor-payout-held` → `demo.tutor.approved@nextgen.local`
- **Expected integration result:** Finance ledger entry (accrual only; no bank transfer).
- **Expected audit event:** `tutor_earnings_created` with payout_id.
- **Verification method:** **WP Admin → Payouts** (`ngc-payouts`) lists pending demo payout; seed graph wallet keys.

### Step 14 — Parent progress report

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** Parent dashboard → **Progress** / child **Lerato Molefe**
- **Action:** Open progress report for completed Mathematics session (BOOK-COMPLETED).
- **Expected screen result:** Progress report card with subject, tutor, session date, competency notes.
- **Expected database result:** Progress report post or platform record linked to booking and student.
- **Expected domain event:** `progress_report.submitted`
- **Expected trigger:** `progress_report_created` → `ngt.progress_report.submitted`
- **Expected notification:** Progress report available notice to parent (sandbox).
- **Expected integration result:** `ngt_report` / LMS grade sync stub (sandbox).
- **Expected audit event:** `progress_report_submitted` with student and booking IDs.
- **Verification method:** Workflow catalog NGT-V2-009 fired; parent dashboard progress tab populated.

### Step 15 — Review submission

- **Demo user:** `demo.parent@nextgen.local` (NGT-DEMO-P0001)
- **Starting page:** Parent dashboard → **Reviews** prompt for BOOK-COMPLETED
- **Action:** Submit 5-star review: *"Demo review — excellent Mathematics session."*
- **Expected screen result:** Thank-you confirmation; review visible on tutor profile summary.
- **Expected database result:** Review row `REV-001` in reviews table linked to BOOK-COMPLETED and tutor.
- **Expected domain event:** `review.submitted`
- **Expected trigger:** `review.submitted` → tutor-rating workflow
- **Expected notification:** Review confirmation to parent; tutor rating update notice (sandbox).
- **Expected integration result:** Tutor profile rating aggregate updated (local).
- **Expected audit event:** `review_created` with rating=5 and booking_id.
- **Verification method:** Seed graph `reviews.REV-001`; tutor dashboard shows updated rating.

### Step 16 — Tutor payout

- **Demo user:** `demo.finance@nextgen.local` (NGT-DEMO-F0001)
- **Starting page:** **WP Admin → Finance → Payouts** (`/wp-admin/admin.php?page=ngc-payouts`)
- **Action:** Open pending payout for Dr Nomsa Khumalo; preview PayFast batch export (do not confirm live transfer).
- **Expected screen result:** Payout row **Pending** / **Held**; export preview shows ZAR amount; demo mode blocks real disbursement.
- **Expected database result:** Payout record status `pending` or `held`; no production gateway confirmation.
- **Expected domain event:** `TutorPayoutHeld`
- **Expected trigger:** `payout.scheduled` → finance approval workflow
- **Expected notification:** `tutor-payout-held` → `demo.tutor.approved@nextgen.local`
- **Expected integration result:** PayFast export queued in preview mode only (`external_side_effects=false`).
- **Expected audit event:** `payout_preview_exported` with tutor_id and amount.
- **Verification method:** Finance role can access payouts page; demo env flag confirms sandbox block.

### Step 17 — Refund or cancellation

- **Demo user:** `demo.parent@nextgen.local` (parent cancel) or `demo.finance@nextgen.local` (refund)
- **Starting page:** Parent dashboard → **BOOK-PENDING-PAY** or Finance → refunds
- **Action:** Cancel unpaid booking BOOK-PENDING-PAY **or** issue partial refund on completed booking (FIN-004 path).
- **Expected screen result:** Booking status **Cancelled** or refund receipt shown; payment failure notice for pending path.
- **Expected database result:** `ngc_bookings` status `cancelled` for BOOK-PENDING-PAY or refund ledger entry (FIN-004).
- **Expected domain event:** `booking.cancelled` or `refund.completed`
- **Expected trigger:** `booking.cancelled` / `refund.completed` → reconciliation workflow
- **Expected notification:** `payment-failure` (pending path) or refund confirmation (sandbox) to parent.
- **Expected integration result:** `commerce-order` status reversed / credited (sandbox adapter).
- **Expected audit event:** `booking_cancelled` or `refund_completed` with booking_id.
- **Verification method:** `wp ngc demo_run_journey --id=BOOK-006 --allow-root` or `--id=FIN-004 --allow-root`; graph key BOOK-PENDING-PAY.

### Step 18 — Tutor application approval

- **Demo user:** `demo.admin@nextgen.local` (approver) — applicant: `demo.tutor.submitted@nextgen.local` (NGT-DEMO-T0006)
- **Starting page:** **WP Admin → Operations → Tutor Applications** (`/wp-admin/admin.php?page=ngc-applications`)
- **Action:** Open submitted application; click **Approve**; confirm role elevation to tutor.
- **Expected screen result:** Application status **Approved**; applicant can access tutor dashboard.
- **Expected database result:** User meta `ngc_application_status=approved`; tutor role granted; `TutorApproved` event logged.
- **Expected domain event:** `TutorApproved` / `tutor.approved`
- **Expected trigger:** `tutor.approved` → onboarding + vetting workflow
- **Expected notification:** `tutor-approval` → applicant email (sandbox)
- **Expected integration result:** CRM tutor profile created (sandbox); vetting checklist closed.
- **Expected audit event:** `tutor_approved` with application_id and admin_id.
- **Verification method:** `wp ngc demo_run_journey --id=JOURNEY-TUTOR-001 --allow-root`; login as newly approved tutor.

### Step 19 — Tutor rejection and resubmission

- **Demo user:** `demo.tutor.resubmit@nextgen.local` (NGT-DEMO-T0007) — reviewer: `demo.admin@nextgen.local`
- **Starting page:** Tutor dashboard → **Application** (resubmit state) or admin **Applications** queue
- **Action:** Review rejection notes (*police clearance expired*); upload corrected document; resubmit application.
- **Expected screen result:** Status **Resubmission required** → **Submitted** after resubmit; reviewer notes visible.
- **Expected database result:** User meta `ngc_application_status=resubmission_required` then `submitted`; reviewer notes stored.
- **Expected domain event:** `TutorResubmissionRequested` → `TutorApplicationSubmitted`
- **Expected trigger:** `tutor.application.resubmit` → vetting re-review workflow
- **Expected notification:** `tutor-resubmission` then `tutor-application-submitted` (sandbox)
- **Expected integration result:** Document verification stub re-queued (sandbox).
- **Expected audit event:** `tutor_resubmission_requested` and `tutor_application_submitted`
- **Verification method:** Seed graph events `TutorResubmissionRequested`; applicant meta on NGT-DEMO-T0007.

### Step 20 — Fraud signal and case review

- **Demo user:** `demo.fraud@nextgen.local` (NGT-DEMO-FRD0001)
- **Starting page:** **WP Admin → Operations → Fraud cases** (`/wp-admin/admin.php?page=ngc-fraud-cases`)
- **Action:** Open demo case *payout detail change* (severity **High**, score 72); review evidence panel.
- **Expected screen result:** Case detail with entity user, evidence JSON, recommended hold action.
- **Expected database result:** Fraud case row linked to suspended tutor NGT-DEMO-T0004; signal `registration_velocity` on parent.
- **Expected domain event:** `FraudSignalRaised` / `fraud.signal.raised`
- **Expected trigger:** `fraud.signal.raised` → case review workflow (FIN-009 chargeback path)
- **Expected notification:** `fraud-case-created` → `demo.fraud@nextgen.local`
- **Expected integration result:** Payout hold flag set on affected tutor (sandbox).
- **Expected audit event:** `fraud_case_opened` with case_id and severity.
- **Verification method:** Seed graph `fraud.case`; `wp ngc demo_run_journey --id=FIN-009 --allow-root`.

### Step 21 — Security alert

- **Demo user:** `demo.security@nextgen.local` (NGT-DEMO-SEC0001)
- **Starting page:** **WP Admin → Platform → System log** (`/wp-admin/admin.php?page=ngc-system-log`) or security queue
- **Action:** Open **Suspicious login — new device** alert from seed; acknowledge / assign.
- **Expected screen result:** Alert detail shows scenario `new_device`, timestamp, recommended MFA step.
- **Expected database result:** Security alert entry in system log / notifications option with `SecurityAlert` payload.
- **Expected domain event:** `SecurityAlert`
- **Expected trigger:** `security.alert.raised` → `require_mfa` workflow branch
- **Expected notification:** `suspicious-login` → `demo.security@nextgen.local`
- **Expected integration result:** MFA challenge stub logged (no external IdP call).
- **Expected audit event:** `security_alert_raised` scenario `new_device`
- **Verification method:** Seed graph event `SecurityAlert:new_device`; notification log filter `suspicious-login`.

### Step 22 — Compliance case

- **Demo user:** `demo.compliance@nextgen.local` (NGT-DEMO-C0001)
- **Starting page:** **WP Admin → Operations** compliance queue or fraud case cross-link (`escalate_compliance`)
- **Action:** Open compliance escalation tied to fraud signal `off_platform_payment`; record review outcome.
- **Expected screen result:** Compliance case with POPIA / payment-policy checklist and resolution notes.
- **Expected database result:** Compliance case or escalated fraud sub-status with `escalate_compliance` action logged.
- **Expected domain event:** `compliance.case.opened`
- **Expected trigger:** `escalate_compliance` (from fraud engine weight ≥ 40)
- **Expected notification:** Compliance review assigned notice to `demo.compliance@nextgen.local` (sandbox).
- **Expected integration result:** Policy hold on affected booking/payout until cleared (sandbox).
- **Expected audit event:** `compliance_case_opened` linked to fraud case_id.
- **Verification method:** Fraud engine rule `off_platform_payment` fires `escalate_compliance`; compliance persona can access ops menus.

### Step 23 — Safeguarding escalation

- **Demo user:** `demo.safeguarding@nextgen.local` (NGT-DEMO-SFG0001)
- **Starting page:** **WP Admin → Operations → Safeguarding** (`/wp-admin/admin.php?page=ngc-safeguarding`)
- **Action:** Open high-priority demo case for learner Lerato; confirm assignment and escalation notes.
- **Expected screen result:** Case priority **High**; assigned officer; AI signal flag set; redacted minor details.
- **Expected database result:** Safeguarding case row with `subject_user_id` NGT-DEMO-S0002; assignee NGT-DEMO-SFG0001.
- **Expected domain event:** `SafeguardingAlertRaised` / `safeguarding.alert.raised`
- **Expected trigger:** `safeguarding.alert.raised` → officer assignment workflow
- **Expected notification:** `safeguarding-escalation` → `demo.safeguarding@nextgen.local`
- **Expected integration result:** Case sync to safeguarding CRM stub (sandbox); no external agency contact.
- **Expected audit event:** `safeguarding_case_assigned` with case_id and officer_id.
- **Verification method:** Seed graph `safeguarding.case`; login as safeguarding officer confirms queue access.

### Step 24 — Notification inspection

- **Demo user:** `demo.admin@nextgen.local` (NGT-DEMO-A0001)
- **Starting page:** **Platform → Demo Control Centre** → notification log / sandbox delivery panel
- **Action:** Filter notification log for seed run; inspect entries (`booking-confirmed`, `match-proposed`, `fraud-case-created`, etc.).
- **Expected screen result:** Chronological sandbox notification list with recipient, template key, domain event, payload JSON.
- **Expected database result:** `NGC_Demo_Notifications` option or notification store contains all seed emissions.
- **Expected domain event:** (inspection only — no new event)
- **Expected trigger:** (inspection only)
- **Expected notification:** All prior step notifications visible with `email_mode=sandbox`.
- **Expected integration result:** Zero external SMTP/API deliveries confirmed (`external_side_effects=false`).
- **Expected audit event:** `demo_notifications_inspected` (evaluator note optional)
- **Verification method:** Cross-check notification keys against seed graph `events[]`; evidence export includes `notifications` array.

### Step 25 — Trigger and event inspection

- **Demo user:** `demo.admin@nextgen.local` (NGT-DEMO-A0001)
- **Starting page:** **WP Admin → Platform → System log** (`/wp-admin/admin.php?page=ngc-system-log`) and **Audit log** (`ngc-audit`)
- **Action:** Filter for `demo_seed_completed`, `match.accepted`, `booking.confirmed`, agent events; expand correlation IDs.
- **Expected screen result:** System log rows show domain events with timestamps matching seed graph order.
- **Expected database result:** Audit table rows for `demo_seed_completed`, `match_manual_assign`, `agent_approval`, etc.
- **Expected domain event:** `demo_seed_completed` (aggregate)
- **Expected trigger:** Workflow engine dispatch records for MATCH-001 / BOOK-001 chain
- **Expected notification:** (inspection only)
- **Expected integration result:** Integration probe statuses visible in seed graph / verify output.
- **Expected audit event:** Full audit trail searchable by `demo_scenario_id` and seed version 14.0.0.
- **Verification method:** `wp ngc demo_verify --allow-root` lists expected events; compare to system log UI.

### Step 26 — Financial reconciliation

- **Demo user:** `demo.finance@nextgen.local` (NGT-DEMO-F0001)
- **Starting page:** **WP Admin → Finance → Payouts / Wallet** and Demo Control Centre seed graph (wallet keys)
- **Action:** Reconcile wallet top-up FIN-006 (ZAR 2000), BOOK-COMPLETED payment (ZAR 450), pending payout, and refund entries.
- **Expected screen result:** Reconciliation view balances: parent wallet credit, session debit, tutor accrual, pending payout — no mismatch flags.
- **Expected database result:** `wallet_ledger` rows for `DEMO-WALLET-TOPUP-14.0.0`, booking payments, payout_pending ID in graph.
- **Expected domain event:** `WalletTopUp`, `PaymentSucceeded`, `TutorPayoutHeld`
- **Expected trigger:** `finance.reconcile` (financial-reconciliation agent, AI-009 context)
- **Expected notification:** `wallet-topup`, `payment-receipt`, `invoice-issued`, `tutor-payout-held`
- **Expected integration result:** `commerce-order` totals match ledger (sandbox); chargeback FIN-009 frozen payout noted.
- **Expected audit event:** `finance_reconciliation_passed` or verify check `financial_reconciliation`
- **Verification method:** `wp ngc demo_run_journey --id=FIN-006 --allow-root`; seed graph wallet section; demo_verify financial checks PASS.

### Step 27 — AI-agent approval request

- **Demo user:** `demo.aiops@nextgen.local` (NGT-DEMO-AI0001)
- **Starting page:** **WP Admin → Operations → Agent Ops** (`/wp-admin/admin.php?page=ngc-agent-ops`)
- **Action:** Open pending approval **AI-009** (`finance.refund.propose`, ZAR 50); click **Approve** with note *Demo AI-009 approval*.
- **Expected screen result:** Task status **Approved**; agent `financial-reconciliation` cleared to proceed (propose only).
- **Expected database result:** Agent task ID in seed graph `agents.AI-009`; decision recorded approve=true.
- **Expected domain event:** `agent.approval.requested` → `agent.approval.granted`
- **Expected trigger:** `finance.refund.propose` → human-in-the-loop gate
- **Expected notification:** Agent ops approval notice to AI ops inbox (sandbox).
- **Expected integration result:** Refund **not** executed (propose vs execute separation enforced).
- **Expected audit event:** `agent_approval` with task_id AI-009 and approver_id.
- **Verification method:** Seed graph `agents.AI-009`; audit log `agent_approval`; journey JOURNEY-OPS-001 step `ai-approve`.

### Step 28 — AI-agent policy denial

- **Demo user:** `demo.aiops@nextgen.local` (NGT-DEMO-AI0001)
- **Starting page:** **Agent Ops** → policy evaluation panel / task **AI-008**
- **Action:** Review denied action `finance.refund.execute` for agent `financial-reconciliation` at autonomy level 1.
- **Expected screen result:** Policy denial banner with rule ID; action blocked; no refund executed.
- **Expected database result:** Policy evaluation result stored in seed graph `agents.AI-008` (deny).
- **Expected domain event:** `agent.policy.denied`
- **Expected trigger:** `finance.refund.execute` blocked by `NGC_Agent_Policy_Engine`
- **Expected notification:** Policy denial alert to AI ops (sandbox).
- **Expected integration result:** No commerce refund API call (execute path blocked).
- **Expected audit event:** `agent_policy_denied` with agent_id and action key.
- **Verification method:** Seed graph `agents.AI-008`; `wp ngc demo_run_journey --id=JOURNEY-OPS-001 --allow-root`; audit filter `agent_policy_denied`.

### Step 29 — AI-agent kill switch

- **Demo user:** `demo.aiops@nextgen.local` (NGT-DEMO-AI0001)
- **Starting page:** **Agent Ops** → global controls / kill switch panel
- **Action:** Toggle **Global pause** ON (all agents paused), confirm audit entry, then toggle OFF (resume).
- **Expected screen result:** Banner **All agents paused** then cleared; queued tasks show paused state during pause.
- **Expected database result:** Option `ngc_agent_global_pause` true then false; graph keys `AI-010-paused` / `AI-010-resumed`.
- **Expected domain event:** `agent.kill_switch.activated` → `agent.kill_switch.deactivated`
- **Expected trigger:** `agent.global_pause` admin command
- **Expected notification:** Kill switch notice to AI ops and admin (sandbox).
- **Expected integration result:** All agent tool calls blocked during pause; resume restores queue processing.
- **Expected audit event:** `agent_kill_switch` with paused=true then paused=false timestamps.
- **Verification method:** Seed graph agent AI-010 flags; audit log entries; journey JOURNEY-OPS-001 step `kill-switch`.

### Step 30 — Demo reset and reseed

- **Demo user:** `demo.admin@nextgen.local` (NGT-DEMO-A0001)
- **Starting page:** **Platform → Demo Control Centre**
- **Action:** Click **Reset all** (confirm), wait for completion, then **Seed all** again; run **Verify**.
- **Expected screen result:** Reset success flash; seed complete flash; Verify **PASS** on second cycle.
- **Expected database result:** Demo-tagged rows removed; fresh seed graph with MATCH-001, BOOK-001, fraud/safeguarding/agent scenarios recreated idempotently.
- **Expected domain event:** `demo_reset_completed` → `demo_seed_completed`
- **Expected trigger:** `demo.reset` → `demo.seed` admin commands
- **Expected notification:** Reset/seeding summary logged to admin (sandbox).
- **Expected integration result:** Integration probes re-verified; sandbox adapters reinitialized.
- **Expected audit event:** `demo_reset_completed` and `demo_seed_completed` with `demo_seed_version=14.0.0`.
- **Verification method:** `wp ngc demo_reset --yes --allow-root && wp ngc demo_seed --allow-root && wp ngc demo_verify --allow-root` — must PASS twice.

---

## Verification commands

```bash
wp ngc demo_verify --allow-root
wp ngc demo_export_evidence --allow-root
wp ngc demo_run_all_journeys --allow-root
```

Evidence path: `.agent-audit/evidence/demo/<journey-id>/evidence.json`
