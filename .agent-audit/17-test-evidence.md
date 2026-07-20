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

Mark: **NOT VERIFIED** where not run.
