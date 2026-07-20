# Journey catalogue (Phase 14 §14.20)

Machine-readable, executable journey definitions for Demo Control Centre / `NGC_Demo_Journeys`.

**Status:** PARTIAL — catalogue complete for MATCH-001…008, BOOK-001…008, FIN-001…010 + 3 umbrella journeys; live seed graph coverage still varies by scenario.

## Inventory (29 JSON files)

| Family | IDs |
|--------|-----|
| Matching | MATCH-001 … MATCH-008 |
| Booking | BOOK-001 … BOOK-008 |
| Finance | FIN-001 … FIN-010 |
| Umbrella | JOURNEY-PARENT-001, JOURNEY-TUTOR-001, JOURNEY-OPS-001 |

## Schema (each file)

- `id`, `name`, `persona`, `scenario`, `family`, `executable`
- `preconditions[]`
- `steps[]` — objects with `action`
- `expected_events[]`, `expected_notifications[]`, `expected_integrations[]`, `expected_audit_events[]`
- `verification.method` / `automated_test`

## Execute

```bash
wp ngc demo_run_journey --id=MATCH-001 --allow-root
wp ngc demo_run_all_journeys --allow-root
```

Evidence writes to `.agent-audit/evidence/demo/<journey-id>/evidence.json`.

## Honesty note

Umbrella journeys and seeded graph labels (e.g. MATCH-001, BOOK-001) are exercised by `demo_seed` / `demo_verify`. Scenario-specific side effects for every MATCH/BOOK/FIN row are definition-complete and runner-executable; deep provider e2e for each row remains environment-dependent (see demo implementation report limitations).
