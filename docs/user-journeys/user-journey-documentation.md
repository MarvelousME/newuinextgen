# User Journey Documentation

## Journey Map Matrix

| Persona | Entry Point | Key Steps | Forms/Screens | Emails/Notifications | Dashboard Interactions | Exit Points | Failure Paths | Status |
|---|---|---|---|---|---|---|---|---|
| Visitor | Home/landing/search | browse > find tutor > view profile/calendar | home, find tutor, tutor profile | none pre-auth | none | lead submission/login | no tutors/calendar unavailable | VERIFIED |
| Parent | register/login | register > add learner > request match > book > pay > review | parent register, find tutor, booking | welcome/booking/payment | parent dashboard KPIs/sessions | active tutoring lifecycle | payment/booking failure | VERIFIED |
| Student | register/login | register > join sessions > track progress | student register, dashboard | welcome/session notices | student dashboard sessions/next lesson | session completion | registration/auth issues | VERIFIED |
| Child | parent-linked flow | parent creates child profile > session participation | parent form + dashboard | parent-facing notices | represented via parent dashboard | linked learner record | missing learner mapping | VERIFIED |
| Tutor Applicant | apply | submit application > wait review > resubmit if needed | become tutor form | received/rejected/resubmit notices | limited dashboard | approved/rejected | invalid application state | VERIFIED |
| Approved Tutor | approval/login | approved > receive matches > teach > payout | tutor dashboard, bookings | approval/payout notices | tutor KPIs/pending payout | active teaching state | payout/booking conflicts | VERIFIED |
| Admin | wp-admin/login | review tutors > monitor workflows > verification/repair | NextGen/Workflows/Platform screens | system alerts | admin dashboard analytics | healthy operations | failed checks/adapter errors | VERIFIED |
| Finance | wp-admin/login | monitor invoices/payments/payouts | payouts + analytics screens | payout/payment notifications | finance-focused metrics | settled balances | Woo/runtime missing | PARTIAL |
| Support | wp-admin + support forms | triage support > matching assist > escalation | support forms, matches, bookings | escalation alerts | support/admin data views | issue resolved/escalated | missing context/external failures | VERIFIED |

## Parent Journey (Detailed)

1. Entry: `/register` or parent onboarding form.
2. Parent account and learner profiles are created.
3. Parent requests tutor matching.
4. Parent reviews tutor profile and calendar.
5. Parent books available slot.
6. Parent pays invoice/order (Woo flow where configured).
7. Parent monitors lesson outcomes and submits review.

## Tutor Journey (Detailed)

1. Entry: `/become-a-tutor`.
2. Application submitted into vetting queue.
3. Admin approves/rejects/resubmits.
4. On approval: role assignment, optional Amelia/LMS/CRM integrations.
5. Tutor receives matches and booking requests.
6. Tutor completes sessions and tracks pending payout.
7. Finance processes payout.

## Admin Journey (Detailed)

1. Open NextGen Operations + Workflows + Platform screens.
2. Review tutor applications and lifecycle actions.
3. Monitor booking/payment/invoice analytics.
4. Run verification checks.
5. Execute self-healing if checks fail.
6. Audit workflow logs and retry failures.

## Journey Diagram Artifacts

- `diagrams/svg/enterprise-diagram-suite.svg` (segments 15, 16, 17, 18, 19)

