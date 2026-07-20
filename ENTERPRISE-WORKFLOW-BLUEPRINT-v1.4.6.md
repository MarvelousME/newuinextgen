# Enterprise Workflow Blueprint — BeyondInfinity v1.4.6

**Project:** NextGen Tutors (BeyondInfinity WordPress theme)  
**Version:** 1.4.6  
**Generated:** 2026-07-02  
**Evidence policy:** VERIFIED = executable code path present · PARTIAL = scaffolding/UI/contracts only · NOT VERIFIED = no implementation found

---

## Scope and Evidence Baseline

| Category | Paths |
|----------|-------|
| **Primary code** | `functions.php`, `inc/*`, `assets/js/*`, `content/nextgen-workflow-pack.json`, `content/page-map.json` |
| **Supporting specs** | `IMPLEMENTATION-AND-MOTIVATIONS.md`, `KNOWN-LIMITATIONS.md`, `COMPARE-DIFFERENCES.md`, `SMARTHEAD-CONFIG-*`, defaults/content pages |

### Appendices (this dossier pack)

| File | Contents |
|------|----------|
| [APPENDIX-A-RBAC-MATRIX.md](docs/enterprise-blueprint/APPENDIX-A-RBAC-MATRIX.md) | Roles, page access, capabilities |
| [APPENDIX-B-TRIGGER-MATRIX.md](docs/enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md) | Event catalog with confidence tags |
| [APPENDIX-C-COMPANION-BOUNDARY.md](docs/enterprise-blueprint/APPENDIX-C-COMPANION-BOUNDARY.md) | Theme ↔ companion/plugin ownership boundary |
| [workflows/](docs/enterprise-blueprint/workflows/) | BPMN-style specs (25 workflows, one file each) |
| [diagrams/](docs/enterprise-blueprint/diagrams/) | SVG diagrams — 25 workflows, 20 triggers, 6 journeys |

---

## Phase 1 — Platform Reverse Engineering (Module Blueprint)

| Module | Status | Summary |
|--------|--------|---------|
| Authentication | **VERIFIED** | Login routing, dashboard access control (`inc/security.php`) |
| Registration | **VERIFIED** | Intake forms, queue, email, workflow dispatch (`inc/shortcodes-fallback.php`) |
| Parent / Student / Tutor Management | **PARTIAL** | Role pages + dashboard shells; REST data external |
| Tutor Vetting | **PARTIAL** | UI + meta flags; approval ops external |
| Tutor Marketplace | **PARTIAL** | Search/filter/display; CPT or demo fallback |
| Matching Engine | **PARTIAL** | Intake + notifications; no algorithm |
| Booking Engine | **PARTIAL** | Amelia contracts + UI CTAs |
| Payment Engine | **PARTIAL** | Woo `order_status_completed` hook only |
| Invoice Engine | **NOT VERIFIED** | — |
| Wallet System | **NOT VERIFIED** | — |
| Notification Engine | **VERIFIED** | Email, RTM queue, OpenWA (`inc/openwa.php`, `inc/workflows.php`) |
| CRM Integration | **PARTIAL** | Workflow contracts; no FluentCRM runtime |
| LMS Integration | **NOT VERIFIED** | — |
| Dashboard Engine | **PARTIAL** | UI + REST client; provider external |
| Analytics | **NOT VERIFIED** | Display KPIs only |
| Reviews / Ratings | **PARTIAL** | Display model; no full lifecycle |
| Support | **VERIFIED** | Support form + escalation path |
| Content Management | **VERIFIED** | Page registry, defaults, sync |
| SEO | **VERIFIED** | `inc/seo.php` |
| Admin Management | **VERIFIED** | `inc/admin.php` |
| Workflow Automation | **VERIFIED** | `inc/workflows.php` + workflow pack JSON |
| Audit Framework | **PARTIAL** | Workflow/page logs, drift checks |
| Verification Framework | **PARTIAL** | Webhook secret + tutor flags |
| Self-Healing Framework | **PARTIAL** | Front-page repair, page sync recovery |

---

## Phase 2 — Personas

| Persona | Status | Goals / permissions (summary) |
|---------|--------|--------------------------------|
| Visitor | **VERIFIED** | Discover, compare, submit leads |
| Parent | **PARTIAL** | Register learner, request tutor; `parent`/`parent_guardian` dashboards |
| Student | **PARTIAL** | Session visibility; `student` dashboard (external data) |
| Tutor Applicant | **PARTIAL** | Apply → queue → manual review |
| Approved Tutor | **PARTIAL** | Dashboard + notifications; ops external |
| Admin | **VERIFIED** | Page sync, workflow monitoring, OpenWA tools |
| Super Admin | **NOT VERIFIED** | No multisite super-admin process in theme |
| Operations Staff | **PARTIAL** | RTM rooms/messages; no explicit WP role |
| Finance Staff | **PARTIAL** | Payment room events; no dedicated role |
| Support Staff | **PARTIAL** | Escalation events; no ticket role model |

---

## Phase 3 — User Journeys (Reverse-Engineered)

### Parent — PARTIAL / VERIFIED mix

![Parent journey](../diagrams/journeys/journey-parent.svg)

1. Awareness → visit → subject discovery → tutor search → request submit (**VERIFIED**)
2. Lead capture → workflow/notification dispatch (**VERIFIED**)
3. CRM → matching → booking → payment → delivery → progress → rebooking → rating (**PARTIAL**, external)

### Student — PARTIAL

Registration + dashboard shells present; assignment/attendance/homework/progress external.

### Tutor Applicant — PARTIAL

Discovery → apply → notifications (**VERIFIED**); verification/review/onboarding (**PARTIAL**).

### Approved Tutor — PARTIAL

Dashboard visible; accept/schedule/hours/payout not fully in-theme.

### Admin — PARTIAL / VERIFIED mix

Lead/tutor queues, support escalation, reporting shells (**VERIFIED/PARTIAL**); full analytics external.

---

## Phase 4 — Workflow Coverage (25)

See [workflows/](workflows/) for per-workflow BPMN specs and [diagrams/workflows/](../diagrams/workflows/) for SVG flow charts.

| # | Workflow | Status |
|---|----------|--------|
| 1 | Parent Registration | VERIFIED |
| 2 | Student Registration | VERIFIED |
| 3 | Tutor Registration | VERIFIED |
| 4 | Tutor Approval | VERIFIED / PARTIAL |
| 5 | Tutor Rejection | NOT VERIFIED |
| 6 | Tutor Re-submission | NOT VERIFIED |
| 7 | Tutor Matching | PARTIAL |
| 8 | Manual Matching | PARTIAL |
| 9 | Automated Matching | NOT VERIFIED |
| 10 | Booking | PARTIAL |
| 11 | Payment | PARTIAL |
| 12 | Invoice | NOT VERIFIED |
| 13 | Refund | NOT VERIFIED |
| 14 | Cancellation | NOT VERIFIED |
| 15 | Session Completion | PARTIAL |
| 16 | Tutor Payout | NOT VERIFIED |
| 17 | Parent Review | PARTIAL |
| 18 | Tutor Rating | PARTIAL |
| 19 | Support | VERIFIED |
| 20 | Escalation | VERIFIED / PARTIAL |
| 21 | CRM | PARTIAL |
| 22 | Email | VERIFIED |
| 23 | WhatsApp | VERIFIED |
| 24 | Notification | VERIFIED |
| 25 | Dashboard | PARTIAL |

---

## Phase 5 — Trigger Matrix

Full matrix: [APPENDIX-B-TRIGGER-MATRIX.md](docs/enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md)

---

## Phase 6 — State Machines (Evidence-Aware)

### Tutor — PARTIAL

`Draft → Submitted → Under Review → Approved → Active → Suspended → Archived`  
Evidence: workflow/spec/docs; no full in-theme transition engine.

### Student — PARTIAL

`Created → Assigned → Active → Completed → Inactive`  
Evidence: forms + dashboards; transitions external.

### Booking — PARTIAL

`Requested → Matched → Confirmed → Paid → Scheduled → In Progress → Completed / Cancelled / Refunded`  
Evidence: contracts + UX; no full state store.

### Payment — PARTIAL

`Pending → Processing → Paid → Failed → Refunded`  
Evidence: Woo completed hook only.

---

## Phase 7 — System Structure (As Implemented)

```
┌─────────────────────────────────────────────────────────┐
│ Frontend (VERIFIED/PARTIAL)                             │
│ Public pages · role dashboards · intake forms           │
├─────────────────────────────────────────────────────────┤
│ Application Layer (VERIFIED)                            │
│ Security · workflows · config · page registry · OpenWA  │
├─────────────────────────────────────────────────────────┤
│ Integration Layer (PARTIAL)                             │
│ OpenWA VERIFIED · Woo PARTIAL · Amelia/CRM/LMS PARTIAL  │
├─────────────────────────────────────────────────────────┤
│ Persistence (VERIFIED/PARTIAL)                          │
│ WP core · wp_options queues/logs · external CPT/APIs    │
└─────────────────────────────────────────────────────────┘
```

### Theme `wp_options` queues

| Option key | Purpose |
|------------|---------|
| `ngc_form_queue` | Form submissions (last 100) |
| `bi_rtm_queue` | Staff room messages (last 200) |
| `bi_workflow_log` | Workflow audit log (last 200) |
| `bi_openwa_inbox` | Inbound WhatsApp messages |

---

## Phase 8 — Deliverables Pack

1. Functional decomposition (this document, Phase 1)
2. System context (Phase 7)
3. User journey maps (Phase 3)
4. Workflow coverage map (Phase 4 + `workflows/`)
5. BPMN flows (per-workflow files)
6. Sequence model: form → queue → notify → dispatch; REST dashboard path
7. Event storming model (Appendix B)
8. State machines (Phase 6)
9. Trigger matrix (Appendix B)
10. RBAC matrix (Appendix A)
11. Dashboard navigation (`content/page-map.json` + `bi_dashboard_page_map()`)
12. Data flow: intake, workflow queue, OpenWA, REST rendering
13. ER model (logical): users/roles/posts/meta/options + queue entities
14. Module dependency graph: theme ↔ companion/external boundaries
15. Notification matrix: email / RTM / WhatsApp
16. Integration matrix: OpenWA/Woo verified vs others partial
17. Automation matrix: workflow pack actions and dispatchers
18. Operational handbook: see `KNOWN-LIMITATIONS.md` + evidence tags here

---

## Operational Dependencies (Critical)

- Companion/plugin APIs (`ngc/v1`, full `ngc_*`) remain a dependency boundary.
- Full booking/payment/invoice/payout lifecycle is not in this theme.
- Many companion docs are marked **NOT PRODUCTION-VERIFIED**.
- Enterprise readiness requires environment validation: plugins, API credentials, data model, end-to-end UAT.

---

## Code Anchors (Quick Reference)

| Concern | File |
|---------|------|
| Dashboard RBAC | `inc/security.php` |
| Custom roles | `inc/roles.php` |
| Form intake | `inc/shortcodes-fallback.php` |
| Workflow engine | `inc/workflows.php` |
| Workflow definitions | `content/nextgen-workflow-pack.json` |
| OpenWA | `inc/openwa.php` |
| Page registry | `content/page-map.json`, `inc/pages-registry.php` |
