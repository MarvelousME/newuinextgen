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
| 2026-07-22 REL-001 | Disk reclaim (unused Docker images/volumes + temp/npm cache) | Host | **OK** | 0 | 0 | C: free 0.33 GB → 1.53 GB before build; active `newuinextgen_*` volumes retained |
| 2026-07-22 REL-001 | `git commit` worktree | Host | **OK** | 0 | 0 | `955d1ad` — 282 files (UX 1–6 + AI Integration + audit docs) |
| 2026-07-22 REL-001 | Junction audit (root ↔ packaged theme) | Host | **OK** | 0 | 0 | `assets`/`inc`/`templates`/etc. under BeyondInfinity are junctions to root — content identical (not dual-tree drift) |
| 2026-07-22 REL-001 | `scripts/build-release.ps1` | Host PHP 8.4 / Node | **OK** | 0 | PHPUnit skipped | validate.php green; Studio vite build; NGCPM validate; 5 ZIPs with forward-slash entries |
| 2026-07-22 REL-001 | Release ZIP SHA-256 archive | Host | **OK** | 0 | 0 | `.agent-audit/evidence/release/SHA256-BI-1.9.16-NGC-1.9.5-2026-07-22.txt` (theme 29.0 MB, Companion 0.88 MB, AI 0.10 MB, Importer 0.03 MB, Manager 82.9 MB) |

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
- Phase 6 live keyboard/mobile/reduced-motion/CRO click-count walk on staging/Docker
- Accessibility (axe) and Lighthouse scoring

Mark: **NOT VERIFIED** where not run.
