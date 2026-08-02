# 05 — Tutorials

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

Longer sequential guides also live under `docs/tutorials/` (e.g. `BEYONDINFINITY-SEQUENTIAL-SETUP.md`). This file is the release-pack tutorial index.

## Tutorial A — Local Docker bring-up

| Step | Action |
|------|--------|
| 1 | Start stack from `docker/` compose (see repo Docker docs) |
| 2 | Open http://localhost:8900 |
| 3 | Activate BeyondInfinity + Companion (+ first-party plugins) |
| 4 | `wp ngt system inspect` then `preflight` |
| 5 | `wp ngt provision catalogue` — confirm 32 steps |
| 6 | `wp ngt provision run --dry-run` then `run --force-safe` |
| 7 | Enter SMTP/PayFast/AI only in settings if testing those paths |

## Tutorial B — Setup Wizard (UI)

1. WP Admin → **Setup Wizard** (`ngc-setup-wizard`).
2. Review catalogue and last results.
3. Run provision (wizard does not write secrets).
4. Clear lock only if a run stalled (`clear-lock` / UI control).
5. Open INPUTS-REQUIRED and complete OPEN rows before any production claim.

## Tutorial C — Phase 14 demo (staging)

| Step | Command / UI |
|------|----------------|
| Enable | Demo Control Centre or demo flags |
| Seed | `wp ngt system seed --allow-demo` or `wp ngc demo_seed` |
| Verify | `wp ngc demo_verify` |
| Journeys | `wp ngc demo_run_all_journeys` |
| Evidence | `wp ngc demo_export_evidence` |
| Reset | `wp ngc demo_reset --yes` (demo mode required) |

Status: **COMPLETE WITH LIMITATIONS** — do not claim full evaluator completeness for every journey trigger/notification pack.

## Tutorial D — Parent happy path (headed)

1. Open public site → register as parent.
2. Add learner / grade / mode (Online / In Person / Hybrid).
3. Run matching → shortlist.
4. Book → checkout (sandbox PayFast if configured).
5. Confirm booking visible to tutor/parent dashboards.

UNVERIFIED on production until operator signs off.

## Tutorial E — Tutor approval

1. Submit tutor application.
2. Ops opens approval / verification queue.
3. Approve → role `ngt_tutor`.
4. Tutor sets availability; appears in matching pool.

## Related

- Live demo runbook: `.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md`
- Bespoke guide: `BESPOKE-THEME-AND-COMPANION-GUIDE.md`
