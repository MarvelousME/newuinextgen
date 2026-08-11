# SYSTEM-INVENTORY

**Mode:** BROWNFIELD  
**Source:** `ARCHITECTURE.md`, `.agent-audit/01-repository-inventory.md`, discover snapshot  
**Updated:** 2026-08-10 (RAD kit bootstrap)

Machine refresh: `SYSTEM-INVENTORY.generated.md` / `discover-snapshot.json` (via `node rad-platform/cli/discover.mjs`).

## Canonical packages

| ID | Folder | Type | Responsibility |
|----|--------|------|----------------|
| beyondinfinity | `NextGenTutors-BeyondInfinity/` | Theme | Presentation |
| companion | `NextGenTutors-Companion/` | Plugin | Domain, REST, workflows, AI suite |
| ai-integration | `NextGenTutors-AI-Integration/` | Plugin | AI transport governance |
| html-importer | `NextGenTutors-Html-Importer/` | Plugin | HTML → pages migration |
| plugin-manager | `NextGenTutors-Plugin-Manager/` | Plugin | Stack install/health |

## Adjacent packages (not first-wave manifests)

| Path | Notes |
|------|-------|
| `nextgen-automation-hub/` | Parallel automation; consolidation risk |
| `ui-library/` | Shared UI catalog |
| `services/ngt-agent-gateway/` | Agent gateway service |
| `NextGenTutors-Mission-Control/` | Ops console overlap |

## Runtime

WordPress + MySQL; optional WooCommerce, Amelia, MasterStudy, FluentCRM.

## Evidence feeders

- `.agent-audit/01-repository-inventory.md`
- `.agent-audit/02-architecture-current-state.md`
- `docs/architecture/solution-architecture.md`
