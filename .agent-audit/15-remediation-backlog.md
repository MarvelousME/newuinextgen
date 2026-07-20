# 15 — Remediation Backlog

## P0

| ID | Issue | Status |
|----|-------|--------|
| FIN-001 | Dual payout cron (Companion + Hub) | **FIXED** — Hub defers when `NGC_Payout_Scheduler` exists |
| SEC-001 | phpMyAdmin exposed on :8082 in compose | **FIXED** — bind `127.0.0.1` via `PMA_BIND` |
| GIT-001 | No VCS baseline | **FIXED** — git init + lean baseline commit (bulk dumps gitignored) |

## P1

| ID | Issue | Status |
|----|-------|--------|
| FRD-001 | Fraud rules incomplete vs master directive list | **FIXED** — 15 rules + hooks + review UI |
| SFG-001 | Safeguarding moderator UI / escalation SLA | **FIXED** — queue UI, SLA, escalate/resolve, cron |
| AGT-001 | Agent evaluation + prompt-injection suite | **FIXED** — `tests/agent-evaluation.php` (20 cases) |
| PAY-001 | PayFast webhook replay / idempotency tests | **FIXED** — unit + Docker sandbox e2e |
| IDOR-001 | Booking/matching object-level access | **FIXED** — `NGC_Access` + REST gates |
| EVT-001 | Wire domain events through envelope | **FIXED** — `NGC_Domain_Event_Bridge` + outbox |

## P2

| ID | Issue | Status |
|----|-------|--------|
| OBS-001 | External APM / metrics export | OPEN |
| UI-001 | Agent Ops Centre UX polish | PARTIAL |
| DOC-001 | Remaining `.agent-audit` phase files depth | PARTIAL |
| PRIV-001 | Retention / export / deletion for minor PII | OPEN |
| A11Y-001 | Accessibility audit | OPEN |
