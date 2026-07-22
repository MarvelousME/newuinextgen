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

---

# Assessment 2026-07-22 — Enterprise-grade SWOT gap gate (post UX Phase 6 / UX-CRO-006)

**Scope note:** This entry evaluates distance to *enterprise-grade*, a stricter bar than the staged-rollout conditions above. Prior closed items (GIT-001 … OBS-001) are not re-litigated; statuses below reflect evidence available in this session only.

## Per-capability status

| Capability | Status | Evidence / missing evidence |
|---|---|---|
| UX Phases 1–6 code gates | `VERIFIED` | PHP lint clean (touched files); `validate.php` green (211 files, 64 assertions); catalog snapshots 28/28; integration smoke green; versions BI 1.9.16 / NGC 1.9.5 synchronized (17-test-evidence 2026-07-21 rows) |
| UX Phase 6 runtime behavior | `NOT VERIFIED` | No browser render, keyboard walk, reduced-motion smoke, mobile sticky/FAB collision test, or find→book ≤3-click audit executed. Docker binds root `inc/`+`assets/` over the theme path; those dirs are junctions to the same files (not dual-tree drift) |
| PayFast payment integrity | `VERIFIED` | Docker sandbox e2e 14/14 (2026-07-20): redirect, ITN, amount tamper, replay |
| Authorization / IDOR | `PARTIAL` | 7 NGC_Access unit tests pass; no full cross-role authorization matrix executed |
| Privacy & minor protection | `PARTIAL` | Exporters/erasers/retention implemented (PRIV-001); consent versioning, masking, and deletion flows not demonstrated end-to-end this session |
| Safeguarding | `PARTIAL` | Case tables, SLA, moderator queue exist; no evidence a live signal→case→escalation chain was executed |
| Fraud engine | `PARTIAL` | 15 rules wired + review UI; thresholds firing into reviewable cases with real signals not demonstrated |
| Financial reconciliation | `PARTIAL` | Payout scheduler/export code present; ledger-ready journals and reconciliation reports not produced from executed transactions |
| Event reliability (outbox/DLQ/replay) | `PARTIAL` | Outbox + event bridge exist; idempotency, dead-letter, and authorized-replay behavior not exercised under test |
| Observability | `PARTIAL` | `/ngc/v1/metrics` Prometheus + system log exist; no collector wired, no alert fired in test, no tracing |
| AI-agent governance | `PARTIAL` | Policy engine + kill switch + 20-case eval harness pass; autonomy remains Level 0–1 (observe/recommend/case) — prompt-injection suite minimal |
| Testing depth | `PARTIAL` | 64 unit assertions + snapshots; PHPUnit suite SKIPPED (no composer install); Playwright e2e NOT RUN; no coverage measurement |
| Accessibility | `NOT VERIFIED` | No axe/manual audit executed any session |
| Release reproducibility | `VERIFIED` | Disk reclaimed; worktree committed `955d1ad`; `build-release.ps1` green 2026-07-22; SHA-256 hashes in `.agent-audit/evidence/release/SHA256-BI-1.9.16-NGC-1.9.5-2026-07-22.txt`; versions BI 1.9.16 / NGC 1.9.5 synchronized |
| Demo environment (Phase 14) | `PARTIAL` | Register: `COMPLETE WITH LIMITATIONS`; full trigger/notification/reconciliation evidence packs incomplete |

## SWOT (enterprise-grade lens)

- **Strengths:** hardened PayFast path with real e2e evidence; escaped presentation layer reusing one token system; governance scaffolding (policy engine, kill switch, eval harness, audit registers) that most WordPress stacks lack.
- **Weaknesses:** all 2026-07-21/22 verification outside the release build is static — zero runtime/browser evidence; PHPUnit and Playwright unexecuted; no accessibility or performance evidence.
- **Opportunities:** analytics plumbing (`NGC_Platform_Analytics`, conversions table) enables real CRO measurement; demo seed + journey catalogue can be extended into executable e2e evidence packs cheaply.
- **Threats:** minors + payments domain means unproven safeguarding/reconciliation controls are audit-fail items, not nice-to-haves; disk pressure remains chronic (~1.4 GB free after build) and can block future rebuilds.

## Enterprise-grade exit criteria (ordered)

1. **Release integrity:** ✅ COMPLETE 2026-07-22 — disk freed, commit `955d1ad`, junction audit (root ≡ packaged), `build-release.ps1` green, SHA-256 archive filed. Tag `release/bi-1.9.16-ngc-1.9.5` applied.
2. **Runtime verification:** deploy 1.9.16/1.9.5 to Docker/staging; execute Playwright journeys (find→book ≤3 clicks), keyboard walk, reduced-motion smoke, axe scan, Lighthouse ≥95 targets.
3. **Test depth:** `composer install` + PHPUnit in CI; authorization matrix + webhook-replay + negative payment tests executed, not listed.
4. **Safeguarding/fraud/reconciliation proofs:** execute one full chain each (signal→case→escalation; rule→case→review; transactions→journal→reconciliation report) and file evidence packs.
5. **Observability activation:** wire a collector, fire one real alert in staging, document runbook.
6. **Phase 14 completion:** journey evidence packs + live demonstration runbook to `COMPLETE`.

## Decision (this entry)

```text
APPROVED WITH CONDITIONS
```

**Staged/limited rollout:** condition (7) rebuild+hash ZIPs is **closed**. Remaining gate before broader traffic: (8) runtime verification of UX Phase 6 on staging/Docker. **The enterprise-grade bar is NOT met** until exit criteria 2–6 hold; unrestricted public production approval today remains `NOT APPROVED FOR PRODUCTION`.

### REL-001 closed

| ID | Evidence |
|----|----------|
| REL-001 | Commits `955d1ad` + `3bde122`; tag `release/bi-1.9.16-ngc-1.9.5`; five `dist/*.zip` artifacts; SHA-256 file under `.agent-audit/evidence/release/` |
