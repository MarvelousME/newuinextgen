# Solution Architecture Document

## Architecture Views

## 1. Context View

- External actors: Visitor, Parent, Student, Tutor, Admin, Finance, Support.
- Core platform: WordPress + `beyondinfinity` + `nextgencompanion`.
- External systems: WooCommerce, FluentCRM, Amelia, MasterStudy.

## 2. Logical View

- **Theme layer**: page rendering, profile UI, shortcode containers, fallback UX.
- **Companion layer**:
  - domain services
  - workflow orchestration
  - REST API
  - admin operations
  - analytics/tracking
  - tutor public calendar stack

## 3. Process View

- Request > REST > service > repository/adapters > audit/workflow > response envelope.
- Cron process runs health checks and self-healing.

## 4. Deployment View

- WordPress PHP runtime + MySQL.
- Plugin/theme deployed in `wp-content`.
- Optional integrations deployed as plugins.

## 5. Data View

- Core identity data in WP tables.
- Operational data in `ngc_*` tables.
- Tracking/analytics data in dedicated normalized tables.

## Plugin/Theme Boundary

| Ownership | Theme (`beyondinfinity`) | Companion (`nextgencompanion`) |
|---|---|---|
| UI presentation | Yes | Limited |
| Domain business logic | No | Yes |
| Data schemas and migrations | No | Yes |
| Workflows and event orchestration | No | Yes |
| Integrations/adapters | No | Yes |
| REST APIs | Minimal theme bridge only | Yes |

Status: `VERIFIED` by implementation and motivations dossier.

## Architectural Decision Highlights

- Data and automation moved to plugin boundary for portability and survivability.
- REST-first dashboard/profile/calendars with source metadata.
- Adapter pattern for external plugin dependencies.
- Verification/self-healing built into admin operation model.

## Constraints

- External integration runtime not present in repository execution context.
- Production verification requires WordPress staging with plugin dependencies installed.

## Architecture Status Table

| Segment | Status | Evidence |
|---|---|---|
| Theme-companion separation | VERIFIED | `IMPLEMENTATION-AND-MOTIVATIONS.md` + code structure |
| Workflow orchestration | VERIFIED | orchestrator + workflow admin/logs/retries |
| External integrations | PARTIAL/BLOCKED | adapter code exists; runtime dependencies required |
| Public tutor calendars | VERIFIED | service + adapters + REST + shortcode + theme inject |
| Verification/self-healing architecture | VERIFIED | verification + repair endpoints/screens |

