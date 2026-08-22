# 10 — Data Model

## Companion-owned tables (planned)

| Table | Purpose |
|-------|---------|
| `wp_ngc_comm_messages` | Outbound/inbound message records, status, idempotency_key |
| `wp_ngc_comm_templates` | Channel templates + variables |
| `wp_ngc_comm_channel_prefs` | User/tenant channel preferences |
| `wp_ngc_wa_sessions` | Mapped provider session ids + normalized state |
| `wp_ngc_wa_webhook_events` | Idempotent inbound event log |

## Reuse

- `NGC_Durable_Queue` / DLQ for send jobs
- `NGC_Immutable_Audit` / audit service for security events
- FluentCRM contact id link column on messages where resolved

## OpenWA-Dev storage

Provider-side SQLite/Postgres/Redis/S3 remain **provider infrastructure**, not WordPress tables. Prefer Postgres profile when deploying beside NGT if Redis/MySQL already exist — do not invent duplicate Redis “just because.”

## Phone

Single `NGC_Phone` helper: SA local → E.164 → provider chat id.
