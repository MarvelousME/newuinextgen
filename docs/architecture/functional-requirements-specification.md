# Functional Requirements Specification (FRS)

This specification maps to detailed module/workflow definitions in:

- `docs/functional/functional-specification.md`
- `docs/workflows/workflow-documentation.md`

## Requirement Set

| ID | Requirement | Verification Artifact | Status |
|---|---|---|---|
| FR-01 | Register parent and create learner context | WF-01, parent dashboard endpoint | VERIFIED |
| FR-02 | Register student and provide dashboard data | WF-02, student dashboard endpoint | VERIFIED |
| FR-03 | Capture tutor applications and lifecycle decisions | WF-03/04/05/06 | VERIFIED |
| FR-04 | Match learners to tutors (manual and automated) | WF-07/08/09, match APIs | VERIFIED |
| FR-05 | Create and manage bookings with conflict prevention | WF-10/14/15, booking APIs | VERIFIED |
| FR-06 | Process payment/invoice/refund states | WF-11/12/13 | PARTIAL |
| FR-07 | Process tutor payouts | WF-16, payouts APIs | VERIFIED |
| FR-08 | Capture and expose reviews/ratings | WF-17/18, reviews APIs | VERIFIED |
| FR-09 | Provide support and escalation workflows | WF-19/20 | VERIFIED |
| FR-10 | Execute CRM/notification/email workflows | WF-21/22/23 | PARTIAL |
| FR-11 | Capture analytics/tracking and profile insights | WF-24 | VERIFIED |
| FR-12 | Provide role-based dashboards and health checks | WF-25 | VERIFIED |
| FR-13 | Expose privacy-safe public tutor calendars | calendar service + REST + shortcode | VERIFIED |

