# Platform Kernel Runbook

## Flags

| Option | Purpose |
|--------|---------|
| `ngc_workflow_authority_v1` | `1` = producers enqueue; authority executes |
| `ngc_workflow_authority_kill` | `1` = force legacy dual-fire path |
| `ngc_platform_db_version` | Schema version (`1.0.0`) |
| `ngc_audit_hmac_key` | HMAC for audit chain |

## CLI

```bash
wp ngc queue work --max=50
wp ngc queue stats
wp ngc queue dlq-replay <id>
wp ngc audit verify
wp ngc audit worm-export --legal-hold
```

## Cron

- `ngc_queue_tick` — every minute when CLI worker transient absent
- `ngc_reconciliation_daily` — ledger vs Woo/cash drift
- `ngc_safeguarding_sla_tick` — SLA breach → escalate + queue

## REST (admin)

- `GET /ngc/v1/platform/queue/stats`
- `POST /ngc/v1/platform/queue/work`
- `GET /ngc/v1/platform/queue/dlq`
- `POST /ngc/v1/platform/queue/dlq/{id}/replay`
- `GET /ngc/v1/platform/audit/verify`
- `POST /ngc/v1/platform/recon/run`

## Admin UI

WP Admin → Platform Kernel (`ngc-platform-kernel`): queue stats, DLQ replay, recon run, WORM export.

## Incident: DLQ growth

1. `wp ngc queue stats` — note `dlq_open`
2. Inspect DLQ in admin or REST
3. Fix handler / payload
4. `wp ngc queue dlq-replay <id>`
5. Confirm Intelligence alert `queue_dlq_growth` cleared

## Incident: audit chain fail

1. `wp ngc audit verify`
2. Do **not** UPDATE/DELETE `ngc_audit_chain`
3. Export WORM bundle for legal hold
4. Rotate HMAC only with documented key ceremony (re-sign not supported; new chain epoch)

## RTO / RPO (Docker)

See `docker/scripts/backup-restore.md`.
