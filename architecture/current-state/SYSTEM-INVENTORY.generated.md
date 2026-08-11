# SYSTEM-INVENTORY (discover)

Generated: 2026-08-10T21:12:40.762Z

## Operating mode

**BROWNFIELD** — existing WordPress tutoring platform with Companion domain plugin and BeyondInfinity theme.

## Packages

| ID | Folder | Present |
|----|--------|---------|
| beyondinfinity | `NextGenTutors-BeyondInfinity/` | yes |
| companion | `NextGenTutors-Companion/` | yes |
| ai-integration | `NextGenTutors-AI-Integration/` | yes |
| html-importer | `NextGenTutors-Html-Importer/` | yes |
| plugin-manager | `NextGenTutors-Plugin-Manager/` | yes |

## Audit feeders

- OK `.agent-audit/01-repository-inventory.md`
- OK `.agent-audit/02-architecture-current-state.md`
- OK `.agent-audit/11-functional-capability-matrix.md`
- OK `ARCHITECTURE.md`

## Notes

- Discovery refreshes machine snapshot only; curated inventories in `architecture/current-state/*.md` remain authoritative narrative.
- Run `node rad-platform/cli/gate.mjs` after design/implement changes.
