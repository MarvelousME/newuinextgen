# Privacy & Observability Operations Guide

**Audience:** ops, compliance, SRE  
**Covers:** PRIV-001 (`NGC_Privacy`), OBS-001 (`NGC_Metrics`)  
**Updated:** 2026-07-20

---

## 1. Privacy / minor PII (PRIV-001)

### 1.1 What it does

| Capability | How |
|------------|-----|
| Subject access export | WordPress **Tools → Export Personal Data** + `NGC_Privacy` exporter (`ngc-minor-pii`) |
| Erasure / anonymize | **Tools → Erase Personal Data** + eraser; or Platform admin anonymize by user ID |
| Retention | Optional daily cron `ngc_privacy_retention_tick` |
| Admin | **Platform → Privacy / Consent / Minor PII** |

Child learner rows are anonymized (identifiers cleared, `status=anonymized`) rather than hard-deleting FKs blindly.

### 1.2 Recommended settings

| Setting | Default guidance |
|---------|------------------|
| Minor PII retention days | ≥ 30 (floor); default 2555 (~7y education ceiling) |
| Analytics / consent days | ≥ 7; typical 365 |
| System log days | ≥ 7; typical 90 |
| Auto-purge | Off until staging verifies sweep |

### 1.3 Operator checklist

1. Confirm WP privacy policy page assigned.
2. Enable auto-purge only after dry-run **Run retention sweep now**.
3. For a DSAR: use WP export tools first; admin JSON export is for privileged ops.
4. Never store unmasked ID/bank numbers in safeguarding notes (engine redacts common patterns).

### 1.4 Code entry points

- `NextGenTutors-Companion/includes/class-ngc-privacy.php`
- Admin handlers in `includes/admin/class-ngc-platform-admin.php`

---

## 2. Observability / metrics (OBS-001)

### 2.1 What it does

| Capability | How |
|------------|-----|
| Prometheus scrape | `GET /wp-json/ngc/v1/metrics` |
| JSON snapshot | `/ngc/v1/metrics/json` |
| Webhook push | Option URL + hourly cron / Push now |
| Error alert | `do_action( 'ngc_metrics_alert', $snapshot, $threshold )` (15m cooldown) |

### 2.2 Admin

**Platform → Observability / Metrics**

- Enable export
- Copy / rotate Bearer token
- Set push URL (Datadog/Grafana Agent/custom collector)
- Set errors/hour alert threshold

### 2.3 Scrape example

```bash
curl -sH "Authorization: Bearer $NGC_METRICS_TOKEN" \
  "http://localhost:8900/wp-json/ngc/v1/metrics"
```

### 2.4 Code entry points

- `includes/diagnostics/class-ngc-metrics.php`
- `includes/rest/class-ngc-rest-metrics.php`
- System log bridge: `do_action( 'ngc_system_log_written', ... )`

---

## 3. Safety notes

- Metrics token is a shared secret — rotate from admin UI; do not commit.
- Demo mode forces PayFast sandbox and sandboxes `wp_mail` — see Phase 14 demo docs.
- phpMyAdmin must remain loopback-bound in compose.

---

## 4. Related

- [api-reference-ngc-v1.md](./api-reference-ngc-v1.md) § Metrics
- [platform-architecture.md](./platform-architecture.md) § Cross-cutting
- `.agent-audit/15-remediation-backlog.md` (PRIV-001 / OBS-001 FIXED)
