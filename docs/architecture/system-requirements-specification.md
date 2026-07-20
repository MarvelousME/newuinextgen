# System Requirements Specification (SRS)

## Functional System Requirements

- Role-based authentication/authorization.
- Parent/student/tutor/admin/finance/support workflows.
- Matching, booking, calendar visibility, reviews, analytics.
- Verification and self-healing tooling.

## Platform Requirements

- WordPress runtime.
- Companion plugin active.
- Theme active and companion-aware.
- MySQL with custom table support.

## Dependency Requirements

- WooCommerce for payment/invoice workflows (`PARTIAL`).
- FluentCRM for CRM workflows (`BLOCKED` without plugin runtime).
- Amelia for integration calendar sync (`PARTIAL`).
- MasterStudy for LMS mapping (`BLOCKED` without plugin runtime).

## Operational Requirements

- Daily health cron.
- Admin verification checks.
- Audit logging and workflow run persistence.

