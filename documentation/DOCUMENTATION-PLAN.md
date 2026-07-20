# Documentation generation plan

**Generated:** 2026-07-20  
**Skills applied:** code-documentation-doc-generate, docs-architect, tutorial-engineer

## Audience map

| Audience | Primary artifacts |
|----------|-------------------|
| Architects | `platform-architecture.md` |
| API / FE | `api-reference-ngc-v1.md` |
| Ops / compliance / SRE | `ops-privacy-observability.md` |
| Evaluators / new engineers | `tutorials/01-phase14-demo-walkthrough.md` |
| Phase status | `master-directive-status.md`, `.agent-audit/*` |

## Artifacts produced this pass

| Path | Type |
|------|------|
| [platform-architecture.md](./platform-architecture.md) | Architecture reference + Mermaid |
| [api-reference-ngc-v1.md](./api-reference-ngc-v1.md) | REST / CLI reference |
| [ops-privacy-observability.md](./ops-privacy-observability.md) | PRIV-001 / OBS-001 ops |
| [tutorials/01-phase14-demo-walkthrough.md](./tutorials/01-phase14-demo-walkthrough.md) | Hands-on tutorial |
| [README.md](./README.md) | Hub index (updated) |

## Already existing (kept)

UI library guides, ADRs, guard runbook, dual-UI docs, `.agent-audit/demo/*` Phase 14 ops stubs.

## Automation

| Tool | Status |
|------|--------|
| `.github/workflows/docs-lint.yml` | Internal markdown link check on doc changes |
| Manual sync | Re-run this plan when REST routes or demo seed version change |

## Assumptions & gaps

| Item | Note |
|------|------|
| OpenAPI YAML | Not generated this pass — route table is controller-based; add OpenAPI when FE contract freezes |
| Full endpoint param schemas | PARTIAL — Studio surface is large; link to Companion Studio manual |
| Screenshots | Not embedded (disk + UI drift); tutorial uses textual checkpoints |
| Production URLs | Intentionally omitted |

## Follow-ups

1. Generate OpenAPI 3 for `ngc/v1` core (bookings/matching/metrics) from route registration.
2. Add tutorial `02-privacy-dsar-walkthrough.md` for Tools → Export/Erase.
3. Add tutorial `03-metrics-prometheus-scrape.md`.
4. Expand Playwright smoke to assert Control Centre seed + one persona login.
