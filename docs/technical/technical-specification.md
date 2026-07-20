# Technical Specification

## System Architecture

- **Presentation**: `beyondinfinity` theme (templates, page defaults, UI scripts/styles).
- **Domain/Platform**: `nextgencompanion` plugin (services, repositories, workflows, adapters, REST).
- **Persistence**: WordPress core tables + custom `ngc_*` tables.
- **Automation**: Workflow orchestrator + retry queue + cron health checks.
- **Integration layer**: FluentCRM, Amelia, MasterStudy adapters.

## Module Architecture

- **Bootstrap**: `NGC_Plugin_Bootstrap::init()`
- **Data**: `NGC_Database`, `NGC_Platform_Repository`, domain services (`NGC_Bookings`, `NGC_Reviews`, etc.)
- **API**: `NGC_Rest*` classes + `NGC_Rest_Tutor_Calendar` (`nextgen/v1` namespace).
- **UI binders**: shortcodes + theme rendering helpers.

## Plugin Architecture

- Autoloaded classes by namespace-like class naming (`NGC_*`).
- Separation by folders:
  - `includes/` core services
  - `includes/rest/` REST controllers
  - `includes/adapters/` external/internal integration adapters
  - `includes/workflows/` orchestration and retries
  - `includes/admin/` operations/configuration screens

## Theme Architecture

- Default page content files under `beyondinfinity/inc/defaults/`.
- Shared rendering helpers in `beyondinfinity/inc/template-tags.php`.
- Companion-aware behavior via `beyondinfinity/inc/companion.php`.
- Tutor profile page: `beyondinfinity/single-tutors.php` + `bi_render_tutor_profile()`.

## Service Architecture

Key services:

- `NGC_Registration`, `NGC_Tutor_Lifecycle`, `NGC_Matching`, `NGC_Bookings`
- `NGC_Payments`, `NGC_Invoices`, `NGC_Wallet`, `NGC_Reviews`
- `NGC_Platform_Analytics`, `NGC_Platform_Tracking`, `NGC_Tutor_Calendar_Service`

Status: core services `VERIFIED`; external dependency-linked services `PARTIAL/BLOCKED`.

## Repository Architecture

- `NGC_Platform_Repository`: generic CRUD/search/count mapping over supported entities.
- `NGC_Tutor_Availability_Repository`: tutor-specific context, approval gating, and working-hour normalization.
- Domain classes use direct specialized queries where required (performance/control).

## API Architecture

- Internal namespace: `ngc/v1`
- Public tutor calendar namespace: `nextgen/v1`
- Shared envelope pattern increasingly adopted:
  - `success`
  - `data`
  - `meta.source`
  - `meta.retrieved_at`

## Event Architecture

- Event dispatch from service actions into workflow engine.
- Notable events:
  - tutor registration/approval/rejection/resubmission
  - booking created/cancelled/completed
  - payment received/failed/refunded
  - review submitted
  - calendar slot selected

## Workflow Architecture

- `NGC_Workflow_Orchestrator` maps workflows to ordered adapter steps.
- `NGC_Workflow_Retry_Queue` captures and retries failed workflow steps.
- `ngc_workflow_runs` table stores run telemetry.

## Queue Architecture

- Retry queue persisted in options + workflow run records.
- Scheduled retry processing via cron.

## Analytics Architecture

- Event ingestion + derived metrics:
  - `ngc_analytics_events`
  - `ngc_conversion_events`
  - profiling/session/acquisition tables
- Snapshot model for dashboard metrics.

## Tracking Architecture

- Request capture on `template_redirect`
- Consent-aware cookies
- first-touch/last-touch attribution
- visitor-to-user linking on login/register

## Security Architecture

- Capability gates in REST permission callbacks.
- Nonce validation for form and slot-selection actions.
- Sanitization and escaping standards across plugin/theme.
- Public API privacy shaping (no PII in public calendar slots).

## Database Architecture

- WordPress core identity/config/content tables.
- Custom `ngc_*` domain tables for marketplace, workflow, analytics, tracking, and verification.

## Required Diagram Set (status)

| Diagram Type | Status | Artifact |
|---|---|---|
| Class diagram | PARTIAL | `diagrams/svg/enterprise-diagram-suite.svg` (segment 4) |
| Service diagram | VERIFIED | `diagrams/svg/enterprise-diagram-suite.svg` (segment 5) |
| Repository diagram | VERIFIED | `diagrams/svg/enterprise-diagram-suite.svg` (segment 6) |
| Event diagram | VERIFIED | `diagrams/svg/enterprise-diagram-suite.svg` (segment 10) |
| Package diagram | PARTIAL | `diagrams/svg/enterprise-diagram-suite.svg` (segment 3) |
| Deployment diagram | VERIFIED | `diagrams/svg/enterprise-diagram-suite.svg` (segment 31) |

