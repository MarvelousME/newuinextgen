# ADR-003: Enterprise Operational Intelligence Platform

## Status

Accepted — Phase 1 + 2 implemented (2026-07-27)

## Context

NextGenTutors spans Companion (domain), Mission Control (ops), Automation Hub, AI Integration, theme, and Plugin Manager. Duplicate reporting in each package creates drift and blind spots.

## Decision

Centralize operational intelligence in **Companion** (`NGC_Intelligence_*`) with **Mission Control** as the dashboard consumer.

### Architecture

```
┌─────────────────────────────────────────────────────────┐
│ Producers: plugins, collectors, SDK emit()              │
└───────────────────────────┬─────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────┐
│ NGC_Intelligence_Event_Bus                              │
│  validate → enrich → persist → KPI → alerts → SSE     │
└───────────────────────────┬─────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────┐
│ Storage: intel_events, intel_kpi_hourly, notifications  │
└───────────────────────────┬─────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────┐
│ REST ngc/v1/intelligence/* + Mission Control UI         │
└─────────────────────────────────────────────────────────┘
```

### Principles

- **Event-driven** — no plugin-local report generation
- **CQRS-lite** — writes via event bus; reads via KPI engine + REST
- **Hot config** — `ngc_intelligence_config` option, no restart
- **Graceful degradation** — polling if SSE unavailable
- **Auditability** — config/export/ack actions logged

### Collectors (automatic)

Auth, REST, workflows, bookings, audit, exceptions, security, plugins, domain events, health checks, notifications, matching.

### Extension

Plugins register via `NGC_Intelligence::register_plugin()` and emit via `NGC_Intelligence::emit()`.

## Consequences

**Positive**

- Single SSOT for operational metrics
- Mission Control gains live visibility without duplicating Companion data
- Webhook/email alert channels for ops teams

**Negative / deferred**

- Full enterprise grid (pivot, millions of rows) — server-paged table today
- External channels (Teams, Slack, SMS) — webhook adapter pattern ready
- BYOK LLM forecasting — heuristic AI layer; plug NGTAI for advanced models
- Multi-tenant isolation — site-scoped tables; network meta future work

## Compliance

- WCAG 2.2 AA: semantic tables, ARIA tabs, keyboard KPI drill
- PII masking configurable
- Admin-only REST with nonce
