# Demo evidence packs (Phase 14 §14.24)

Per-journey evidence under `<journey-id>/` as required by the master directive.

**Status:** SCAFFOLDED — directories + exporter ready; packs are populated by:

```bash
wp ngc demo_export_evidence --allow-root
wp ngc demo_run_journey --id=MATCH-001 --allow-root
wp ngc demo_run_all_journeys --allow-root
```

## Layout

```text
.agent-audit/evidence/demo/
  INDEX.md
  all-journeys/evidence.json
  JOURNEY-PARENT-001/evidence.json
  MATCH-001/…
  BOOK-001/…
  FIN-001/…
```

Each `evidence.json` includes §14.24 fields: journey id/version, demo user, timestamps, initial/final state, steps, screens, commands/API calls, records, events, handlers, notifications, integrations, audit, finance, agents/policy, test result, failures.

## Honesty

Empty directories without `evidence.json` mean export has not been run on this host yet. Catalogue scaffolding is created by `NGC_Demo_Evidence::scaffold_catalogue_dirs()` during export-all.
