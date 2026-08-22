# Workflow Studio — File-by-File Implementation Plan (Phase 7)

**Status:** STOPPED — awaiting explicit approval before Phase 8  
**Date:** 2026-08-14  
**No source code has been modified for this capability.**

This plan extends Companion. It does **not** add a second workflow executor.

---

## Graph library decision

| Option | Fit | Decision |
|---|---|---|
| React Flow / XYFlow | Prompt preferred; compiler already uses RF JSON | **Defer.** Companion has no React toolchain; `studio.bundle.js` is missing; WP admin already loads Gutenberg React |
| Orchestration cockpit JS | Vanilla drag workspace | **Wrong domain** (servers/APIs, not workflow nodes) |
| **Vanilla canvas + ELK (elkjs)** | Layout without React; same `{nodes,edges}` as `NGC_Studio_Compiler` | **v1 choice** |
| Dagre | Fallback layout if ELK bundle size is a problem | Secondary |

Canvas must be namespaced (`.ngc-wf-studio`) and enqueued only on `ngc-automation-studio` (and its sub-views). No Elementor/Gutenberg/theme enqueue.

Bit Flows is **UI/UX reference only**. No proprietary assets.

---

## Modes (v1)

| Mode | Allowed | Forbidden |
|---|---|---|
| VIEW | Select, search, filter, inspect, zoom/pan, legend | Mutate definitions |
| LAYOUT | Drag nodes, auto-layout, persist positions | Change edges, hooks, or publish |
| DESIGN | Out of v1 except read-only “model” badge | Deploy |
| PUBLISHED | Existing Studio publish button unchanged | Visualizer must not auto-publish discovery graphs |

---

## Persistence (new, separate concerns)

Reuse:

- `ngc_studio_workflows` / `ngc_studio_executions` — existing authored graphs + studio traces  
- Platform `queue_messages` / DLQ / idempotency / audit / metrics  

Add (Companion schema, not a second SSOT for business workflows):

1. `ngc_workflow_discovery_snapshots` — JSON graph + scan metadata + diff parent  
2. `ngc_workflow_layouts` — `graph_id`, `view_mode`, `positions`, `user_id`, updated_at  
3. Option `ngc_workflow_discovery_version` for cache invalidation  

Do **not** write discovery nodes into `compiled` plans.

---

## Admin navigation

- Keep parent: **NEXT GEN TUTORS v1.0** (`NGC_Admin_Shell`).
- Relabel screen `ngc-automation-studio` title to **Workflow Studio**.
- Sub-views via `?view=` (no extra `add_menu_page`): Workflows, Live Monitor, Platform Map, Event Map, Integrations, Executions, Failed, DLQ, Health, Settings.
- Do **not** add a second root menu. Cockpit stays infra.

---

## REST

Namespace: existing `ngc/v1`.

Proposed routes (permission callbacks **not** `require_admin` only):

| Route | Cap | Purpose |
|---|---|---|
| `GET /workflow-studio/graph` | `ngt_view_workflows` | Cached canonical graph + filters |
| `POST /workflow-studio/rescan` | `ngt_manage_workflows` | Rebuild snapshot |
| `GET /workflow-studio/workflows` | `ngt_view_workflows` | List + health (real metrics or `unavailable`) |
| `GET /workflow-studio/workflows/{id}` | `ngt_view_workflows` | One workflow graph |
| `GET/PUT /workflow-studio/layouts/{id}` | view / manage | Positions only |
| `GET /workflow-studio/events` | `ngt_view_workflows` | Event catalog |
| `GET /workflow-studio/executions` | `ngt_view_workflow_executions` | Joined traces (redacted) |
| `GET /workflow-studio/executions/{id}` | executions + redact | Inspector |
| `GET /workflow-studio/trace` | executions | Trace by execution/event/correlation id |
| `GET /workflow-studio/impact/{node}` | view | Up/down dependents |
| `GET /workflow-studio/diff` | view | Snapshot diff |
| `POST /workflow-studio/control/{verb}` | `ngt_control_workflows` / `ngt_retry_workflows` / `ngt_manage_dlq` | **Delegates to existing authority/DLQ/runtime** |
| `GET /workflow-studio/source` | `ngt_view_workflow_source` | Provenance snippet (no secrets) |
| `GET /workflow-studio/export` | view + audit | JSON/CSV; PNG/SVG client-side |

All: auth, capability, nonce/`X-WP-Nonce`, sanitization, SQL prepared, redaction service.

---

## RBAC (new caps)

Register and map in `architecture/policies/authz-matrix.json` + Companion role setup:

| Cap | Admin | Finance | Support |
|---|---|---|---|
| `ngt_view_workflows` | yes | yes | yes |
| `ngt_view_workflow_executions` | yes | yes | limited |
| `ngt_view_workflow_source` | yes | no | no |
| `ngt_manage_workflows` | yes | no | no |
| `ngt_control_workflows` | yes | no | no |
| `ngt_retry_workflows` | yes | no | no |
| `ngt_manage_dlq` | yes | no | no |
| `ngt_view_workflow_audit` | yes | yes | no |

Do not grant these to Parent/Student/Tutor.

---

## File-by-file plan

### A. Discovery (Phase 8) — new files

| File | Responsibility |
|---|---|
| `NextGenTutors-Companion/includes/workflow-studio/class-ngc-workflow-studio.php` | Bootstrap, feature flag, hooks |
| `.../class-ngc-workflow-graph-model.php` | Node/edge/workflow value objects + confidence enum |
| `.../class-ngc-workflow-discovery-service.php` | Orchestrates scanners; writes snapshot; **no page-load full scan** |
| `.../class-ngc-workflow-discovery-cache.php` | Read snapshot; version; invalidate |
| `.../scanners/class-ngc-wf-scan-orchestrator.php` | Orchestrator handler map |
| `.../scanners/class-ngc-wf-scan-integrate.php` | `integrate/workflow-*.json` |
| `.../scanners/class-ngc-wf-scan-hub.php` | Hub JSON + hooks |
| `.../scanners/class-ngc-wf-scan-studio.php` | DB published/draft studio graphs (provenance = studio) |
| `.../scanners/class-ngc-wf-scan-payments.php` | Payments, PayFast, session orchestrator, reminders, payouts |
| `.../scanners/class-ngc-wf-scan-events.php` | Known `do_action` catalog from registered maps (not recursive repo walk at runtime) |
| `.../scanners/class-ngc-wf-scan-queue.php` | Queue names, DLQ, retry cron |
| `.../class-ngc-workflow-double-fire.php` | Competing handlers on same event |
| `.../class-ngc-workflow-orphan-detector.php` | Triggers without actions, etc. |
| `.../class-ngc-workflow-coverage.php` | Formula + per-workflow scores from snapshot only |
| `.../class-ngc-workflow-diff.php` | Snapshot vs snapshot |
| `.../class-ngc-workflow-redaction.php` | Central redact for inspector |

Runtime PHP scan of the whole tree is **forbidden**. Static maps + existing registries + optional CLI/admin Rescan that walks **known files** only.

### B. Schema / loader

| File | Change |
|---|---|
| `includes/database/class-ngc-schema-statements.php` | New tables |
| `includes/class-ngc-database.php` | Table names if needed |
| `includes/platform/class-ngc-platform-schema.php` | Only if queue tables need a view (prefer not) |
| `includes/class-ngc-core-loader.php` | `require` + `NGC_Workflow_Studio::init()` |

### C. REST / control (Phase 9, 11–13)

| File | Change |
|---|---|
| `includes/rest/class-ngc-rest-workflow-studio.php` | **New** routes |
| `includes/rest/class-ngc-rest.php` | Register + permission helpers per cap |
| `includes/platform/class-ngc-workflow-authority.php` | **No new execute path.** Control plane calls `request_execute` / existing handlers |
| `includes/platform/class-ngc-queue-dlq.php` | Reuse `replay` |
| `includes/studio/class-ngc-studio-runtime.php` | Optional pause flag **only if** already safe; otherwise control = do not register **new** execute |
| `includes/platform/class-ngc-authz-matrix.php` + `architecture/policies/authz-matrix.json` | New caps |

Control verbs:

- Enable/disable/pause/resume → existing studio status / authority kill-switch **documented**, not a silent Hub disable  
- Retry → `NGC_Workflow_Retry_Queue` / authority retry handler  
- Replay DLQ → `NGC_Queue_DLQ::replay`  
- Demo execute → `NGC_Studio_Simulator` or orchestrator with demo guard (`NGC_Demo_Env`)

Each control: capability, nonce, confirm flag, audit, idempotency key.

### D. Admin UI (Phase 10, 12, 33, 37–40)

| File | Change |
|---|---|
| `includes/admin/class-ngc-studio-admin.php` | Relabel, view router, enqueue canvas (not global) |
| `includes/admin/framework/screens.php` | Title **Workflow Studio**; keywords |
| `assets/workflow-studio/studio.css` | **New** namespaced CSS (NextGen tokens) |
| `assets/workflow-studio/studio.js` | **New** canvas: pan/zoom/fit, select, minimap, snap, groups, legend, search, filters, fullscreen, light/dark |
| `assets/workflow-studio/layout-elk.js` | ELK vendor **or** locally vendored elkjs (license preserved) |
| `assets/studio/studio-fallback.js` | Keep as “authored workflows” tab; do not delete |
| `assets/studio/studio-shell.css` | Share tokens only if needed; avoid z-index wars |

UI features mapped to JS modules inside `studio.js` (split files if size warrants): inspector, live poll (existing 1500ms / SSE pattern from studio localize), impact panel, trace panel, health table.

**Do not** enqueue on Elementor, Gutenberg, Woo screens, or frontend.

### E. Tests (Phase 14–15)

| File | Purpose |
|---|---|
| `NextGenTutors-Companion/tests/phpunit/WorkflowStudio/` | Graph extract, redaction, permissions, double-fire detector, coverage formula |
| REST tests | Unauthorized 401/403; no secrets in fixtures |
| `tests/` or `e2e/` Playwright spec `workflow-studio.spec.ts` | Headed: open, list, open tutor registration, drag, reload layout, search, filter, zoom, impact, RBAC |

E2E must not be marked passed unless executed. Demo data: existing relational demo, not new fake tutors.

### F. Delivery reports after implementation (Phases 16–18)

Update:

- `delivery/WORKFLOW-SECURITY-AUDIT.md`  
- `delivery/WORKFLOW-E2E-EVIDENCE.md`  
- `delivery/WORKFLOW-DATABASE-EVIDENCE.md`  
- `delivery/WORKFLOW-COVERAGE.md`  
- `delivery/WORKFLOW-STUDIO-FINAL-REPORT.md`  

Those files currently state **not executed**.

---

## Explicit non-changes

- Do not rewrite `NGC_Workflow_Orchestrator` handlers.  
- Do not change payment settle order.  
- Do not uninstall Hub or AutomatorWP.  
- Do not make `NGC_Studio_Importer` auto-publish.  
- Do not copy Bit Flows.  
- Do not add `add_menu_page` for a new root.

---

## Implementation sequence after approval

8. Discovery services + schema + cache  
9. REST (read)  
10. Canvas VIEW + LAYOUT + list/search/filter/legend  
11. Execution join + live poll + redaction  
12. Impact, orphans, double-fire, diff, six view modes  
13. Caps + control plane wrapping existing runtimes  
14. PHP/JS tests  
15. Headed Playwright  
16. DB evidence queries (redacted)  
17. Gate + security pass  
18. Final report with **measured** counts only  

---

## Approval gate

Reply with explicit approval to implement Phases 8–18 as specified in this file.  
Until then: **no PHP/JS/CSS product source changes.**
