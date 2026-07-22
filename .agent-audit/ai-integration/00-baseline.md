# AI Integration — Baseline

**Date:** 2026-07-21
**Branch:** `master`
**HEAD:** `eba25743ce15cb6d298798291b755d1776ae0e69`
**Working tree:** 19 modified/untracked paths at start of this pass (prior session already added the initial `NextGenTutors-AI-Integration` package, Companion `ngc_match_requested` hook, `ngc_dashboard_html` filter, and five-package tooling).

## State before this implementation pass

| Area | State |
|------|-------|
| `NextGenTutors-AI-Integration/` | Initial version present (35 files): bootstrap, config, signature (legacy 7-field canonical), redactor, outbox bridge on Companion `ngc_event_outbox`, REST callbacks, admin pages, CLI, 5 test files |
| Canonical signature | Legacy `METHOD\nPATH\nTS\nNONCE\nTENANT\nIDEM\nBODY_SHA256` — superseded by directive §7 |
| Durable stores | Nonce + idempotency tables existed; deliveries/approvals tables did **not** |
| Companion | `ngc_match_requested` action emitted post-persist; domain event bridge maps `MatchRequested`; policy engine `NGC_Agent_Policy_Engine` available |
| Tooling | `verify-solution.ps1` / `build-release.ps1` five-package aware; Docker mounts + activation wired |
| agents-api | **Not reachable/configured in this environment** — external delivery evidence cannot be fabricated |

## Directive applied

`CURSOR AUTONOMOUS IMPLEMENTATION DIRECTIVE — IMPLEMENT NextGenTutors-AI-Integration COMPLETELY IN ONE EXECUTION PASS` (user message 2026-07-21). This rebuilds the package to the §3 structure with §5 tables, §7 canonical signature, §8 dual-layer replay, §9 business idempotency, §14 policy gate, §16 delivery processor, §17 callback routes, §19 approvals, §20 cron, §21 CLI, §22 admin, §25 tests, §26 MATCH-001 slice.
