# NextGen Intelligence Platform

Central event-driven reporting for the NextGenTutors ecosystem. All plugins publish structured events through a single SDK; Mission Control consumes live dashboards via REST + SSE.

## Architecture

See [ADR-003](../../../documentation/adr/ADR-003-intelligence-platform.md) for full enterprise architecture.

```
Plugin / Theme / Service
        │
        ▼
  NGC_Intelligence::emit()
        │
        ▼
  NGC_Intelligence_Event_Bus (validate → enrich → persist)
        │
        ├── NGC_Intelligence_Kpi_Engine (KPIs, drill-down)
        ├── NGC_Intelligence_Alerts → NGC_Intelligence_Notifications
        └── NGC_Intelligence_Stream (SSE ring buffer)
                │
                ▼
        Mission Control Intelligence tab
```

## SDK — emit events

```php
NGC_Intelligence::emit( [
    'event_key'   => 'booking.created',
    'plugin_slug' => 'companion',
    'module'      => 'bookings',
    'feature'     => 'checkout',
    'severity'    => 'info',
    'outcome'     => 'success',
    'user_id'     => get_current_user_id(),
    'message'     => 'Booking confirmed',
    'payload'     => [ 'booking_id' => 123 ],
    'duration_ms' => 42,
] );
```

Required: `event_key`. All other fields are optional and normalized by `NGC_Intelligence_Schema`.

## SDK — register plugin metadata

```php
add_action( 'ngc_intelligence_register_plugins', function () {
    NGC_Intelligence::register_plugin( [
        'slug'        => 'my-plugin',
        'label'       => 'My Plugin',
        'version'     => '1.0.0',
        'features'    => [ 'checkout', 'reports' ],
        'health_check'=> 'my_plugin_health',
    ] );
} );
```

Or fire `do_action( 'ngc_intelligence_register', $definition )`.

## Auto-collectors

When intelligence is enabled, collectors bridge:

- System log (`ngc_system_log_written`)
- Workflows (`ngc_workflow_dispatched`)
- Auth (`wp_login`, `user_register`)
- REST API (`rest_pre/post_dispatch` for ngc/ngt routes)
- Plugin activation/deactivation
- Domain events (`ngc_domain_event`)

## REST API (`ngc/v1/intelligence/*`)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Full health matrix (plugins, cron, AI, security) |
| `/events/export` | GET | CSV export (audited) |
| `/audit` | GET | Intelligence admin audit log |
| `/dashboard` | GET | Executive KPIs, series, health |
| `/events` | GET | Paginated event grid |
| `/notifications` | GET | Open alerts |
| `/notifications/{id}/ack` | POST | Acknowledge alert |
| `/stream` | GET | SSE live updates |
| `/registry` | GET | Registered plugins/features |
| `/drill/{level}` | GET | Drill-down (domain, plugin, event) |
| `/config` | GET/POST | Hot-reload settings |
| `/emit` | POST | Admin test emit |
| `/insights` | GET | AI executive brief |
| `/insights/ask` | POST | Natural language Q&A |

Requires `manage_options` or `ngc_admin_operations`.

## Configuration

Option: `ngc_intelligence_config`

- `enabled`, `retention_days`, `refresh_interval_ms`
- `sse_enabled`, `sampling_rate`
- `collect_rest`, `collect_auth`, `mask_pii`
- Alert thresholds: `alert_error_threshold`, `alert_booking_failures`

Changes apply without restart.

## Database tables

- `wp_ngc_intel_events` — normalized operational events
- `wp_ngc_intel_kpi_hourly` — hourly metric buckets
- `wp_ngc_intel_notifications` — in-app alert center

Created via `NGC_Database::create_tables()` (Repair stack / plugin activation).

## Mission Control

Open **Mission Control → Intelligence** for live KPI cards, Chart.js trends, notification center, event grid, and NL insights.

## Extensibility guidelines

1. Never build duplicate reporting inside plugins — emit events only.
2. Use consistent `event_key` prefixes (`booking.`, `payment.`, `auth.`).
3. Include `correlation_id` for multi-step workflows.
4. Set `severity` and `outcome` for alert evaluation.
5. Register plugin metadata so Mission Control discovers your package automatically.

Administrator guide: [ADMIN.md](./ADMIN.md)

## Components

| Class | Role |
|-------|------|
| `NGC_Intelligence` | SDK facade |
| `NGC_Intelligence_Event_Bus` | Ingestion + query + export |
| `NGC_Intelligence_Kpi_Engine` | KPIs + drill-down |
| `NGC_Intelligence_Health` | Plugin/service health matrix |
| `NGC_Intelligence_Dispatch` | Email + webhook notifications |
| `NGC_Intelligence_Retention` | Daily purge cron |
| `NGC_Intelligence_Audit` | Immutable admin audit |
| `NGC_Intelligence_Ai` | Executive briefs + NL Q&A |
| `NGC_Intelligence_Collectors` | Auto-instrumentation |

## Testing

```bash
php NextGenTutors-Companion/scripts/validate.php
cd e2e && npm run test:mission-control-headed
```

Emit a test event (logged in as admin):

```bash
curl -X POST /wp-json/ngc/v1/intelligence/emit \
  -H "X-WP-Nonce: …" \
  -H "Content-Type: application/json" \
  -d '{"event_key":"test.ping","plugin_slug":"companion","message":"Hello"}'
```
