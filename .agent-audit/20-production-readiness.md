# 20 — Production Readiness Decision

**Updated:** 2026-07-20 (open-items + SFG/FRD/EVT/GIT pass)

## Scores (0–10, evidence-based)

| Area | Score | Notes |
|------|-------|-------|
| Architecture | 7 | Modular Companion + Hub; event bridge now envelopes Hub/Companion hooks |
| Functional completeness | 6 | Core booking/match/pay flows; ops UIs added |
| Security | 6 | PayFast ITN hardened; IDOR gates; phpMyAdmin loopback |
| Privacy | 7 | WP export/erase for child learners; retention cron + admin tools |
| Safeguarding | 6 | Cases + SLA + moderator queue + escalate/resolve |
| Fraud controls | 6 | 15 rules wired to hooks; review UI + resolve |
| Financial controls | 6 | PayFast e2e 14/14; Hub payout defers to Companion |
| Reliability | 5 | Outbox table; retries/DLQ still light |
| Observability | 7 | System log + Prometheus scrape `/ngc/v1/metrics` + optional webhook push |
| AI-agent governance | 7 | Policy + kill switch + eval harness VERIFIED |
| AI-agent autonomy | 3 | Observe/recommend/case only — not unsupervised |
| UI/UX | 6 | Safeguarding + fraud admin under Operations |
| Accessibility | NOT VERIFIED | Not audited this run |
| Testing | 7 | 51 unit tests; PayFast Docker e2e 14/14 |
| Production readiness | 6 | Git baseline; PRIV-001 + OBS-001 closed |

## Decision

```text
APPROVED WITH CONDITIONS
```

### Conditions (must hold before public production traffic)

1. **Secrets:** Never commit `docker/.env`; rotate any credentials that lived only in local compose.
2. **Safeguarding ops:** Staff trained on moderator queue; SLA cron `ngc_safeguarding_sla_tick` verified in staging.
3. **Fraud:** Tune thresholds per environment; confirm payout-meta key names match live tutor profiles.
4. **Privacy:** Retention/export/deletion for minor PII implemented (PRIV-001) — verify staff runbooks on staging.
5. **Observability:** Prometheus scrape + webhook push available (OBS-001) — wire collector before high volume.
6. **Restore drill:** Backup/restore NOT VERIFIED this run — run once on staging.

### Closed since prior NOT APPROVED

| ID | Evidence |
|----|----------|
| GIT-001 | Git repo initialized with `.gitignore` baseline |
| SEC-001 | phpMyAdmin `PMA_BIND=127.0.0.1` |
| FIN-001 | Hub payout defers when Companion scheduler present |
| PAY-001 | Unit + Docker sandbox e2e 14/14 |
| AGT-001 | `tests/agent-evaluation.php` 20 cases |
| IDOR-001 | `NGC_Access` + match reject ownership |
| SFG-001 | Moderator UI + SLA hours + escalate/resolve |
| FRD-001 | Expanded rule set + hook wiring + review UI |
| EVT-001 | `NGC_Domain_Event_Bridge` + outbox |
| PRIV-001 | WP personal-data exporters/erasers + retention sweep + Platform privacy UI |
| OBS-001 | `/ngc/v1/metrics` Prometheus + JSON + webhook push + alert hook |

### Still open (non-blocking for staged rollout)

- Accessibility audit  
- Full Playwright e2e suite  
- UI Ops Centre polish 
