# 11 — Operations Runbook

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Daily / weekly checks

| Check | Command / UI |
|-------|----------------|
| Health | Plugin Manager scan; Companion health scanner |
| Orchestrator status | `wp ngt system status` |
| Provision state | `wp ngt provision status` |
| Metrics | `/wp-json/ngc/v1/metrics` (if enabled) |
| Failed payments / ITN | Woo + Companion payment logs |
| Safeguarding queue | Safeguarding admin UI |
| SMTP failures | FluentSMTP logs |

## 2. Provisioning operations

```text
wp ngt provision catalogue
wp ngt provision status
wp ngt provision run [--dry-run] [--force-safe] [--allow-demo] [--from=<id>] [--only=<id>]
wp ngt provision rollback --step=<id>
wp ngt provision clear-lock
wp ngt system provision   # alias path into system dispatcher
```

UI: Setup Wizard `ngc-setup-wizard`. Lock TTL 900 seconds.

## 3. System orchestration

```text
wp ngt system inspect|preflight|configure|seed|verify|run-all
wp ngt system repair|export-report|reset-demo|status
```

Prefer `--force-safe` on shared environments. Use `--allow-demo` only when demo is intentional.

## 4. Recovery

| Incident | Action |
|----------|--------|
| Stuck provision lock | `wp ngt provision clear-lock` after confirming no runner |
| Bad configure | Restore DB backup; optional step `rollback` |
| Bad deploy | Redeploy previous zips + DB restore |
| Demo pollution on staging | `wp ngc demo_reset --yes` with demo mode on |
| Payment duplicate | Rely on idempotent ITN handlers; reconcile in finance UI |
| Email outage | Fix FluentSMTP; flush/retry per provider |

Diagram: `14-observability-and-recovery.svg`.

## 5. Escalation

| Severity | Example | Contact |
|----------|---------|---------|
| P1 | Checkout down, data breach | Host + admin@ / on-call (fill) |
| P2 | Matching errors, CRM sync | Ops manager role |
| Safeguarding | Child safety report | IN-018 contacts (OPEN) |

## 6. Local reference

Docker WP: http://localhost:8900 — not a production surrogate for go-live sign-off.
