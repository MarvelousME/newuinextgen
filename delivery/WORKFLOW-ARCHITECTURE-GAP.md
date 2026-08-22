# Workflow Studio — Architecture, SWOT, GAP (Phase 6)

**Date:** 2026-08-14  
**Constraint:** No source modifications in this phase.

---

## Current architecture (as-is)

```
                    NEXT GEN TUTORS v1.0 admin shell
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
 Automation Studio      Orchestration Cockpit   Workflow Admin
 (executor + CRUD)      (infra monitor)         (specs/verify)
        │
        ▼
 NGC_Studio_Runtime ──► (filter) ──► NGC_Workflow_Authority ──► Durable Queue
        │                                          │
 Orchestrator producers ───────────────────────────┘
 Hub / AutomatorWP producers ──────────────────────┘
        │
        ▼
 Domain adapters: Woo, PayFast, Amelia, MasterStudy, FluentCRM
```

There is **already** a single-authority design. There is **not** yet an evidence-backed visual map of all runtimes. The missing piece is visualization/observability, not a new engine.

---

## SWOT

### Strengths

- `NGC_Workflow_Authority` already forces producers off the hot path when enabled.
- Durable queue, DLQ, idempotency, audit, metrics exist.
- Orchestrator, payments, session orchestrator, reminders, payouts are real PHP, not JSON fiction.
- Studio compiler already uses `{nodes, edges}` (React Flow compatible).
- Admin shell is a single parent menu.
- Hub health cron already yields to Companion.

### Weaknesses

- Automation Studio UI is a fallback list, not a node canvas (`studio.bundle.js` absent).
- Studio REST is gated only by `manage_options` / `ngc_admin_operations`.
- Importer can clone orchestrator/hub into executable studio rows.
- Theme `user_register` still fires beside Hub.
- No discovery cache; any “scan all PHP” on page load would violate performance rules.
- Live execution overlay is studio-table-only, not payment/orchestrator traces.
- Requested caps (`ngt_view_workflows`, …) do not exist.

### Opportunities

- Reuse studio REST + compiler JSON + admin slug instead of a greenfield SPA.
- Overlay discovery as a **read model** on authority/queue/audit.
- ELK/Dagre layout without React, avoiding Gutenberg React collisions.
- Double-fire view is immediately valuable for operators.

### Threats

- Building “Workflow Studio” as a second publisher recreates DF-04.
- Introducing React/XYFlow solely for UX increases WP admin risk.
- Claiming CHAIN-PROVEN without DB/E2E would violate evidence rules.
- Elementor/Gutenberg contamination if assets are not page-scoped (existing studio enqueue is already page-scoped — keep that).

---

## GAP vs prompt requirements

| Requirement | Today | Gap |
|---|---|---|
| Visual graph of **real** workflows | Fallback list of studio DB rows | Canvas + discovery indexer |
| VIEW vs LAYOUT vs DESIGN vs PUBLISHED | Publish exists; no layout-only mode | Modes; layout store separate from compiled plan |
| Drag ≠ execution order | N/A (no canvas) | Must persist positions only |
| Six visualization modes | No | Workflow / platform / integration / event / lineage / runtime |
| Live execution on graph | Studio live REST exists | Join orchestrator/queue/payment traces |
| Inspector provenance | Partial in fallback | Source file/class/hook/evidence |
| Trace / impact / orphans / double-fire | Double-fire not visualized | Graph algorithms on cached topology |
| Event catalog | Triggers catalog + event map | Searchable registry UI |
| Health metrics | Queue/metrics exist; not per-workflow UI | Surface real counters; mark missing |
| RBAC granular caps | Admin-only | New caps mapped in authz matrix |
| Discovery cache + Rescan | Importer sync option | Snapshot table + invalidation |
| Export PNG/SVG/JSON/CSV | No | After canvas |
| Headed E2E | Not for this UI | Playwright after implementation |
| Demo safe execution | Demo env + studio simulate | Wire simulate to visual path, not live PayFast |
| React Flow | Compiler shape only | Vanilla canvas, same JSON |

---

## Target architecture (to-be, after approval)

```
                     WORKFLOW STUDIO (admin view)
                              │
             ┌────────────────┼─────────────────┐
             │                │                 │
        Discovery         Visualization     Observability
        (cached graph)    (VIEW/LAYOUT)     (traces, health)
             │                │                 │
             └────────────────┼─────────────────┘
                              │
                       Canonical Graph
                              │
                ┌─────────────┴──────────────┐
                │                            │
          Static Evidence              Runtime Evidence
                │                            │
                └─────────────┬──────────────┘
                              │
                     NGC_Workflow_Authority
                              │
              Queue/Worker/DLQ + existing domain runtimes
```

Studio **observes and controls** the authoritative runtime. Control plane verbs (pause/retry/replay) call existing classes with capability + nonce + audit.

---

## Non-goals for v1 (after approval)

- DESIGN mode that deploys new production graphs.
- Replacing orchestrator, Hub, or AutomatorWP.
- Installing Bit Flows or copying its assets.
- Scanning the full PHP tree on every admin request.
- Fabricating refund/no-show graphs.
