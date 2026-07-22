# 17 — Test Evidence

| Time | Suite | Environment | Passed | Failed | Skipped | Notes |
|------|-------|-------------|--------|--------|---------|-------|
| 2026-07-20 Phase 0 | `NextGenTutors-Companion/tests/run.php` | Host PHP 8.4 | 20 | 0 | 0 | Baseline |
| 2026-07-20 Phase 0 | `ui-library/tests/integration-smoke.php` | Host | OK | 0 | 0 | Baseline |
| 2026-07-20 Phase 9 | `NextGenTutors-Companion/tests/run.php` | Host PHP 8.4 | **25** | 0 | 0 | +5 agent policy tests |
| 2026-07-20 Phase 3 | `NextGenTutors-Companion/tests/run.php` | Host PHP 8.4 | **28** | 0 | 0 | +3 PayFast ITN tests |
| 2026-07-20 Phase 3 | `tests/agent-evaluation.php` | Host PHP 8.4 | **20** | 0 | 0 | Policy + ITN abuse scenarios |
| 2026-07-20 open items | `NextGenTutors-Companion/tests/run.php` | Host PHP 8.4 | **35** | 0 | 0 | +7 NGC_Access IDOR unit tests |
| 2026-07-20 open items | `scripts/payfast-e2e-docker.php` | Docker WP `:8900` | **14/14** | 0 | 0 | Sandbox redirect + ITN + amount tamper + replay — see `payfast-e2e-run.txt` |
| 2026-07-20 SFG/FRD/EVT | `NextGenTutors-Companion/tests/run.php` | Host PHP 8.4 | **43** | 0 | 0 | +SLA helpers + fraud rule coverage asserts |
| 2026-07-21 UX-CRO-006 | PHP `-l` on 10 touched PHP files | Host PHP 8.4 | **10** | 0 | 0 | Theme/Companion Phase 6 files clean |
| 2026-07-21 UX-CRO-006 | Node `--check` on `main.js`, `ngc-marketplace.js` | Host Node 24 | **2** | 0 | 0 | Counter and marketplace scripts parse cleanly |
| 2026-07-21 UX-CRO-006 | `NextGenTutors-Companion/scripts/validate.php` | Host PHP 8.4 | **211 lint + 64 assertions** | 0 | PHPUnit skipped | Integrate, smoke, versions and unit assertions green |
| 2026-07-21 UX-CRO-006 | `ui-library/tests/catalog-snapshot.php` | Host PHP 8.4 | **28** | 0 | 0 | Catalog hashes unchanged |
| 2026-07-21 UX-CRO-006 | `ui-library/tests/integration-smoke.php` | Host PHP 8.4 | **OK** | 0 | 0 | Integration smoke green |
| 2026-07-21 UX-CRO-006 | `git diff --check` (scoped files) | Git/Windows | **OK** | 0 | 0 | Only expected LF→CRLF working-copy warnings |

## Agent policy tests added

1. Deny secret exfiltration  
2. Allow observe at L0  
3. Require approval for refund  
4. Global kill switch denies  
5. Autonomy over max requires approval  

## Not executed this session

- Full PHPUnit suite  
- Playwright e2e  
- Backup/restore  
- Phase 6 live keyboard/mobile/reduced-motion/CRO click-count walk (Docker theme bind overlays packaged `inc/` and `assets/` with dirty root mirrors)
- Phase 6 release ZIP build (C: had only 0.07 GB free)

Mark: **NOT VERIFIED** where not run.
