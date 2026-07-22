# Operations

## Delivery lifecycle

Outbox rows in `wp_ngtai_deliveries` move through:

```
pending ──claim──► processing ──2xx──► delivered
                       │
                       ├─5xx/408/429/timeout──► retry_pending (attempts 1–4)
                       │                              │ attempt 5
                       │                              ▼
                       │                         dead_letter
                       └─4xx (non-retryable)──► failed
```

- Retry backoff: 0s, 30s, 120s, 600s, 1800s (a `Retry-After` header overrides).
- Claiming is lock-based (`locked_at`/`locked_by`); `recover_locks()` returns rows stuck in `processing` longer than 5 minutes to `retry_pending`.
- The flush worker runs on the `ngtai_outbox_flush` cron hook via `NGTAI_Outbox_Bridge::dispatch_batch()` (default batch 10, max 100).

## Monitoring

- **Admin pages** — Settings, Events, Agent Ops, Approvals, and Health under the WordPress admin (capability-gated by `NGTAI_Access`).
- **Health endpoint** — `GET /wp-json/ngtai/v1/health` (manage capability required).
- **Queue counts** — `NGTAI_Delivery_Repository::counts()` per status; watch `retry_pending`, `failed`, and `dead_letter` growth.
- **Counters** — options such as `ngtai_callback_failure_total` and `ngtai_policy_denied_total` (`NGTAI_Logger::bump`).
- **Logs** — scrubbed structured entries in the `ngtai_log_ring` option (last 200) and `error_log` when `WP_DEBUG` is on.
- **Audit** — `wp_ngtai_audit` records policy decisions, deliveries, callbacks, and approval activity with correlation IDs.

## Incident response

| Situation | Action |
| --- | --- |
| Stop all agent traffic | Enable global pause (`ngtai_global_pause` option or `NGTAI_GLOBAL_PAUSE` constant). Policy gate denies everything while active. |
| Upstream outage | The circuit breaker opens automatically after 5 retryable failures (60s). Deliveries accumulate as `retry_pending` and drain when the API recovers. |
| Stuck `processing` rows | Run lock recovery (cron does this; manual: `NGTAI_Delivery_Repository::recover_locks()`). |
| `dead_letter` build-up | Inspect `last_error`/`http_status` on the rows, fix the cause, and re-enqueue if appropriate. |
| Suspected replay/abuse | Check `wp_ngtai_callback_nonces` and audit entries; rotate the signing secret and key ID. |

## Maintenance

- Expired nonce rows are purged by cron (`NGTAI_Nonce_Store::purge_expired()`); retention defaults to 30 days.
- Secret rotation: update the key ID and secret in settings (or constants) in lockstep with agents-api; in-flight callbacks signed with the old key will fail with `ngtai_unknown_key`/`ngtai_signature_mismatch` and be retried by the sender.
- WP-CLI helpers are available via `NGTAI_CLI` when WP-CLI is present.
