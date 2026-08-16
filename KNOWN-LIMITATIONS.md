# KNOWN-LIMITATIONS.md

Honest risk register — updated **2026-08-16**.  
This is **not** a production-readiness certificate.

Green health checks are **presence/configuration signals only** — not proof of bookings, payments, POPIA compliance, live PayFast settlement, or restore drills.

---

## Production posture (honest)

| Bar | Score | Status |
|-----|-------|--------|
| Staged private beta (conditioned) | ~6.0 | Almost met after billing + PR merge + HTTPS ITN |
| Enterprise | 8.5 | **Not met** — HA, restore, counsel, CI green, vault |

**Do not claim enterprise-grade.** Do not mega-merge the dirty working tree.

---

## Environment / ops

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| ENV-01 | Live WordPress UAT / paying-customer host not proven here | High | **NOT VERIFIED** |
| ENV-02 | Rate limiter uses single-site **transients** (fails under multi-node) | Medium | **PARTIAL** — single-node only until Redis/object-cache |
| ENV-03 | Compose default `WP_PORT=8890`; CI wait/BASE_URL must follow `.env` | High | **VERIFIED** in repo (`check-prod-integrity.mjs`) |
| ENV-04 | Local compose may set `NGC_ALLOW_DEMO_SEED=1` | Critical if shipped | **VERIFIED** — production overlay + mu-plugin force off |
| ENV-05 | GitHub Actions blocked when org billing locked | Critical | **HUMAN** — unlock billing before CI claims |
| ENV-06 | No backup → restore drill with timed RTO/RPO | High | **NOT VERIFIED** — blocks paying-customer beta |
| ENV-07 | Single MySQL; no object-cache; `tenant_id` mostly default `1` | High | **BLOCK multi-node** until object-cache + tenant tests |
| ENV-08 | Prometheus + `SMTP_*` wired in compose; stack recreate operator-owned | Medium | **PARTIAL** — recreate compose to activate |

## Security / legal

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| SEC-01 | POPIA lawful basis unsigned | Critical | **SECURITY_REVIEW_REQUIRED** — human/counsel |
| SEC-02 | PayFast merchant keys still commonly in WP options | High | **PARTIAL** — move to env/vault before prod |
| SEC-03 | Recording marketing copy must stay **out** | Medium | **VERIFIED** product stance — do not re-add |
| SEC-04 | Safeguarding contacts (IN-018) OPEN | High | **HUMAN** — not agent-closable |

## Money path

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| FIN-01 | PayFast ITN cannot reach `localhost:8890` | Critical | **BLOCKED** until public HTTPS staging |
| FIN-02 | Settled ITN → ledger → recon same booking | Critical | **UNVERIFIED** as one film |
| FIN-03 | Hub vs Companion dual finance risk | High | **MITIGATED in code** — Hub payout/match writes blocked when Companion authority |

## Dual trees / authority

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| AUTH-01 | Automation Hub coexists with Companion | High | **STRANGLER** — Companion domain SSOT; Hub finance/match gated |
| AUTH-02 | Root `assets`/`inc` bind-mount can shadow theme | Medium | Keep **asset-parity** gate |
| AUTH-03 | Dual UI (CMS library vs Magic UI) | Low | Documented; not a second booking engine |

## Agent-closable vs human-only

| Closable in repo | Not agent-closable |
|---|---|
| Port alignment 8890 / `WP_PORT` | GitHub billing unlock |
| Demo seed forbid on production overlay | POPIA counsel sign-off |
| Hub domain write strangler | Live PayFast merchant + public ITN |
| Integrity checker in CI | Restore drill on real backups |
| Characterization tests | IN-018 phone tree / IN-020 deploy auth |

---

## Commands (integrity)

```bash
node scripts/check-prod-integrity.mjs
node scripts/check-asset-parity.mjs
node rad-platform/cli/gate.mjs
php NextGenTutors-Companion/tests/run-prod-integrity-unit.php
php NextGenTutors-Companion/scripts/run-journey-fitness.php
```

Production host must use:

```bash
docker compose -f docker-compose.yml -f docker-compose.production.yml up -d
```

Never leave `NGC_ALLOW_DEMO_SEED=1` on a public host.
