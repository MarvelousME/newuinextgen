# UI Inventory — Beyond Measure

| Surface | Status |
|---------|--------|
| Command Center | Wired to `/health` |
| Full nav IA | Wired to `/nav` |
| Talent Intelligence workstation | Wired + Explain drawer |
| Universal CRUD (TanStack in TS source) | Talent resource; fallback table in `build/fallback.js` |
| Access Matrix | Wired |
| Dependency Map (React Flow in TS source) | Wired via `/dependency-graph`; graph UI in webpack build |
| Notifications | Wired + acknowledge |
| Schema-driven config + Danger Zone | Wired |
| Subsystem enable/disable impact dialog | Wired |
| Placeholders for unwired domains | Present |
| Entity drawer | Present |

## Build note

`npm install` failed in this environment (`ENOSPC`). Runtime uses `build/fallback.js` (wp.element) until `npm run build` produces `build/index.js`. TypeScript SPA sources remain under `admin/app/`.
