# Administrator Manual — Automation Studio

## Getting started

1. Ensure NextGenTutors-Companion **v1.5.0+** is active.
2. Open **Automation Studio** in wp-admin.
3. On first load, 24 prebuilt templates are seeded as draft workflows.

## Workflow Designer

1. Select a workflow from the sidebar dropdown.
2. Drag nodes from the palette onto the canvas.
3. Connect nodes by dragging from output to input handles.
4. Click **Save & Apply** — workflow compiles and hooks register immediately.
5. Click **Publish** — workflow status becomes `published` and triggers go live.

## REST API

| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/ngc/v1/studio/workflows` | List workflows |
| PUT | `/ngc/v1/studio/workflows/{id}` | Save & apply |
| POST | `/ngc/v1/studio/workflows/{id}/publish` | Publish |
| POST | `/ngc/v1/studio/workflows/{id}/simulate` | Dry-run |
| POST | `/ngc/v1/studio/events/emit` | Emit custom trigger |
| GET | `/ngc/v1/studio/verify` | Health check |

All endpoints require `manage_options` and `X-WP-Nonce`.
