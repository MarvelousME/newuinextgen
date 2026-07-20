# Execution Manifest

| Field | Value |
|-------|-------|
| **Execution ID** | `NGT-AA-2026-07-20-T1341Z` |
| **Start timestamp** | 2026-07-20T13:41:00+02:00 |
| **Repository root** | `c:\Users\marvi\Downloads\wetransfer_newuinextgen_2026-07-07_1929\newuinextgen` |
| **Current branch** | `NOT VERIFIED` — workspace is **not a git repository** (no `.git`) |
| **Current commit hash** | `NOT VERIFIED` — no VCS |
| **Operating system** | Windows 10.0.26200 (win32) |
| **PHP (host CLI)** | 8.4.20 VERIFIED (`php -v`) |
| **PHP (Docker WP)** | 8.2 (image `wordpress:6.7-php8.2-apache`) VERIFIED (compose) |
| **Node** | v24.18.0 VERIFIED |
| **npm** | 11.18.0 VERIFIED |
| **Docker Engine** | 29.4.0 VERIFIED |
| **Docker Compose** | v5.1.1 VERIFIED |
| **Database** | MySQL 8.0 (container `newuinextgen-db-1`, healthy) VERIFIED |
| **Application framework** | WordPress 6.7 image; Companion `NGC_VERSION` 1.9.1; Hub 2.0.0 |
| **Package managers** | Composer present in Companion (`composer.json`); root has no `composer.json` |
| **Detected services** | wordpress (:8900), db, phpmyadmin (:8082) — all running |
| **Detected containers** | `newuinextgen-wordpress-1`, `newuinextgen-db-1`, `newuinextgen-phpmyadmin-1` |
| **Environment files** | `docker/.env` (present — contents NOT logged), `docker/.env.example` |
| **CI/CD** | `.github/workflows/ci.yml`, `docs-lint.yml` |
| **Test projects** | Companion `tests/run.php` + PHPUnit; `ui-library/tests/*`; `e2e/` Playwright |
| **Known constraints** | No git baseline; Kirki fatal blocks full WP-CLI without `--skip-plugins`; dual theme tree (root + BeyondInfinity mirror); Automation Hub parallel to Companion |
| **Unavailable tooling** | `git` history/branching; production secrets management; Stripe; APM vendors |
| **Initial risk statement** | High blast radius: financial (PayFast + dual payout crons), privacy (minors), incomplete fraud/safeguarding modules, AI agents currently Level 0–1 only (chat/manage, no autonomous tool loops). Secret-bearing `docker/.env` must never be committed or echoed. |

## Baseline commands executed

```
git rev-parse --show-toplevel  → fatal: not a git repository
php -v                         → PHP 8.4.20
node --version                 → v24.18.0
npm --version                  → 11.18.0
docker version                 → 29.4.0
docker compose ps              → db healthy, wordpress up, phpmyadmin up
php NextGenTutors-Companion/tests/run.php → OK — 20 unit tests passed
php ui-library/tests/integration-smoke.php → OK — integration smoke passed
```

## Application code change status at Phase 0 start

**No application files modified before this manifest was written.**

## Secret exposure risks

| Path | Risk | Mitigation |
|------|------|------------|
| `docker/.env` | Contains local WP credentials | Do not commit; do not print contents in audit |
| PayFast / Amelia API keys in DB options | Runtime secrets | Masked via `NGC_Crypto` / admin UI |
| `BIA_ALLOW_RAW_SHELL` | Shell escape | Must remain unset in production |

## Protected production resources

Agent autonomy **must not** autonomously: production deploy, payouts, refunds, account closures, permanent suspensions, destructive DB ops, secret rotation, firewall changes.
