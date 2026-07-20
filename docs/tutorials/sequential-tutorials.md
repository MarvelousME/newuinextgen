# Sequential Tutorials

## Tutorial 1: Platform Installation

1. Install/activate `beyondinfinity` theme.
2. Install/activate `nextgencompanion` plugin.
3. Configure plugin settings (workflow integrations, tracking, demo mode as needed).
4. Confirm role/capability installation (`parent`, `student`, `tutor`, `ngc_finance`, `ngc_support`, `tutor_applicant`).
5. Verify setup:
   - NextGen Health checks
   - Workflows verification
   - Platform verification

Status: `VERIFIED` (source-level), runtime UAT required.

## Tutorial 2: Parent Journey

1. Register parent profile.
2. Create/link student/child profile.
3. Search/select tutor profile.
4. Review match and tutor calendar availability.
5. Book lesson slot.
6. Pay invoice/order.
7. Attend lesson.
8. Submit tutor review.

Status: booking/review `VERIFIED`; payment/invoice `PARTIAL` (Woo runtime).

## Tutorial 3: Tutor Journey

1. Register as tutor applicant.
2. Submit profile/documents.
3. Wait for vetting decision.
4. If approved, role and integrations are provisioned.
5. (If available) configure Amelia mapping.
6. (If available) verify MasterStudy mapping.
7. Receive and accept matches.
8. Conduct lessons and mark completion.
9. Receive payout processing.

Status: core lifecycle `VERIFIED`; Amelia/LMS `PARTIAL/BLOCKED`.

## Tutorial 4: Admin Journey

1. Review tutor applications.
2. Approve/reject/resubmit as needed.
3. Monitor matching/bookings.
4. Monitor payments/invoices/payouts.
5. Review analytics and user profiles.
6. Run verification suite.
7. Execute self-healing when checks fail.
8. Monitor workflow logs/retries.

Status: `VERIFIED`.

## Tutorials for Workflow Coverage

Each workflow in `WF-01 ... WF-25` maps to operational procedures documented in:

- `docs/workflows/workflow-documentation.md`
- `docs/administration/administration-documentation.md`
- `docs/troubleshooting/troubleshooting-guide.md`

