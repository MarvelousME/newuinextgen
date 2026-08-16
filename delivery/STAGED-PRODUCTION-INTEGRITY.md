# Staged production — integrity completion (agent-closable)

**Date:** 2026-08-16  
**Scope:** Integrity only — no new product surface, no invented ITN settlement, no enterprise claim.

## Done in repo (working code)

| Item | Evidence |
|---|---|
| Port drift `:8900` → `:8890` / `WP_PORT` | `docker/start.ps1`, `setup.sh`, Playwright/CI wait & `BASE_URL`, `check-prod-integrity.mjs` **18/18 PASS** |
| Demo seed off on public hosts | `docker-compose.production.yml` + `mu-plugins/ngt-environment.php` + `NGC_Demo_Env::seed_allowed` honors env `0` |
| Hub strangler when Companion owns domain | `domain_writes_blocked()`; Hub match local write returns `ngt_match_companion_authority`; payouts already skip |
| CI gates | `check-prod-integrity.mjs` + `run-prod-integrity-unit.php` wired in `.github/workflows/ci.yml` |
| Honest limitations | Root `KNOWN-LIMITATIONS.md` updated (single-node, ITN, billing, POPIA) |

## Still human / infra (not agent-closable)

- Unlock GitHub billing; merge PR #1 only (no mega-merge)
- Recreate compose so Prometheus scrape + SMTP_HOST actually run
- Public HTTPS staging for PayFast ITN → ledger → recon same booking
- POPIA counsel (SEC-01); IN-018 contacts; restore drill; vault for PayFast

## Commands

```bash
node scripts/check-prod-integrity.mjs
php NextGenTutors-Companion/tests/run-prod-integrity-unit.php
node rad-platform/cli/gate.mjs
```

**Verdict:** Staged private-beta **integrity bar improved**. Enterprise bar **not met**. Paying-customer beta still blocked on HTTPS ITN + counsel + restore + billing.
