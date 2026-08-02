# NextGen Intelligence Platform — Administrator Guide

## Overview

Mission Control **Intelligence** is the single pane of glass for operational reporting across NextGenTutors. All plugins publish events to Companion; the dashboard updates live via REST polling and SSE.

## Access

**WP Admin → Mission Control → Intelligence**

Requires `manage_options`.

## Views

| View | Purpose |
|------|---------|
| **Overview** | Executive KPIs, AI brief, health pills, charts, notifications |
| **Events** | Server-paged event grid with filters and CSV export |
| **Plugins** | Plugin health matrix and cron queue status |
| **Settings** | Hot-reload configuration and intelligence audit log |

## Live updates

- Dashboard refreshes every 5 seconds (configurable)
- SSE stream pushes instant updates when events or alerts are ingested
- No manual refresh required

## Configuration

Settings apply immediately without restart:

- **Retention** — daily cron purges events older than N days
- **Sampling** — reduce volume under load (0.01–1.0)
- **Alert email** — critical/error alerts emailed to admin
- **Webhook** — JSON POST with HMAC signature (`X-NGC-Signature`)
- **Mask PII** — email/phone redaction in stored payloads
- **SSE** — disable if proxy blocks long-lived connections

## Notifications

In-app alerts appear in Overview. Acknowledge to clear. External channels fire for errors/critical by default.

Dedupe prevents duplicate alerts within 1 hour for the same title.

## Drill-down

Click any KPI card to filter the Events view by domain, severity, or event key. Use breadcrumb/back to reset.

Hierarchy: Executive → Domain → Plugin → Module → Feature → Event → Diagnostics (REST `/drill/{level}`).

## Export

Events view → **Export CSV** (max 5000 rows per export, audited).

## Repair / first run

1. Activate **NextGenTutors-Companion**
2. Mission Control → Configure → **Repair stack** (creates `wp_ngc_intel_*` tables)
3. Open Intelligence tab

## Troubleshooting

| Symptom | Action |
|---------|--------|
| Empty KPIs | Run repair; emit test event via REST `/intelligence/emit` |
| SSE not connecting | Check `sse_enabled`; use polling fallback (automatic) |
| High DB size | Lower retention days; run retention cron manually: `wp cron event run ngc_intelligence_retention_sweep` |
| Missing plugin | Ensure plugin calls `NGC_Intelligence::register_plugin()` on boot |

## Security

- All REST routes require admin capability
- Webhook signatures use `webhook_secret` from settings
- Intelligence admin actions logged to immutable audit option + Companion audit table
- PII masking enabled by default

## Related docs

- Developer SDK: `NextGenTutors-Companion/docs/intelligence/README.md`
- Architecture: `documentation/adr/ADR-003-intelligence-platform.md`
