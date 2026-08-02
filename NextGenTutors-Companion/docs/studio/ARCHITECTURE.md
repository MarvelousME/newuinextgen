# NextGen Automation Studio — Architecture

## Overview

The Automation Studio is an enterprise visual orchestration platform embedded in **NextGenTutors-Companion v1.5+**. It extends the existing `NGC_Workflow_Orchestrator`, adapter layer, and integrate JSON specs with a **data-driven execution engine** and **hot-reload runtime**.

## Platform modules

| Module | Class | Purpose |
|--------|-------|---------|
| Workflow Designer | React `@xyflow/react` SPA | Drag-drop canvas, save/publish |
| Trigger Engine | `NGC_Studio_Triggers` | 40+ trigger catalog + hook map |
| Compiler | `NGC_Studio_Compiler` | Graph → execution plan |
| Realtime Apply | `NGC_Studio_Runtime` | Hot-register hooks without restart |
| Execution Engine | `NGC_Studio_Engine` | Step interpreter + adapter delegation |
| Event Bus | `NGC_Studio_Event_Bus` | Routes events to runtime + legacy orchestrator |
| Simulation | `NGC_Studio_Simulator` | Dry-run + replay |
| Templates | `NGC_Studio_Templates` | 24 prebuilt tutoring workflows |
| Source Importer | `NGC_Studio_Importer` | Hub + Integrate + Orchestrator + Templates → Studio rows |
| Verification | `NGC_Studio_Verification` | Health checks |
| REST API | `NGC_Rest_Studio` | `ngc/v1/studio/*` CRUD + execute + `/studio/import` |
| Admin Shell | `NGC_Studio_Admin` | wp-admin mount `#ngc-studio-root` |

## Source import (visible CRUD)

All workflow definitions from other packs are synced into Studio so they appear in the list and can be edited:

| Source | Prefix | Origin |
|--------|--------|--------|
| Templates | `tpl_*` | `NGC_Studio_Templates` |
| Automation Hub | `hub_*` | `workflows.json` / Hub runtime |
| Integrate + Catalog | `integrate_*` | `integrate/workflow-*.json` + `catalog/` |
| Orchestrator | `orch_*` | Trigger Manager WF-* keys |

- Auto-sync on `admin_init` / Studio page open (once per `SYNC_VERSION`)
- Manual: **Import / Sync All Sources** button or `POST /ngc/v1/studio/import`
- CLI: `wp ngc studio_import [--force]`
- UI: Create / Read / Update / Delete / Publish / Simulate per workflow (`studio-fallback.js`)

## Database tables

- `wp_ngc_studio_workflows` — definitions + graph + compiled JSON
- `wp_ngc_studio_versions` — publish snapshots
- `wp_ngc_studio_triggers` — active trigger bindings
- `wp_ngc_studio_forms` — form designer schemas (Phase 2)
- `wp_ngc_studio_emails` — email designer templates (Phase 2)
- `wp_ngc_studio_notifications` — multi-channel rules (Phase 2)
- `wp_ngc_studio_executions` — run monitor + audit path

## Realtime apply pipeline

```
Admin clicks Save
  → REST PUT /studio/workflows/{id}
  → NGC_Studio::save_and_apply()
  → NGC_Studio_Compiler::compile()
  → NGC_Studio_Repository::update (persist)
  → NGC_Studio_Runtime::apply_workflow()
  → Dynamic add_action() per trigger
  → Triggers live immediately
```

No cache flush. No plugin reinstall. No server restart.

## Build

```bash
cd NextGenTutors-Companion/build-src
npm install
npm run build:copy
```

Output: `assets/studio/studio.bundle.js` + `studio.bundle.css`

Fallback: `assets/studio/studio-fallback.js` (no build required)

## Access

wp-admin → **NEXT GEN TUTORS → Automation Studio** (`admin.php?page=ngc-automation-studio`)
