# Phase 0 — Repository & Evidence Baseline

**Generated:** 2026-08-03T00:20:00Z (local session)  
**Branch:** `master`  
**Commit:** `249ffac0ee4a7dabbc461166d957b0424c7887f5`  
**Working tree:** dirty (agentic delivery + many prior uncommitted changes; deleted historical evidence files present)

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.20 (cli) |
| Node | v24.18.0 |
| Free disk (C:) | ~2.14 GB (constrained) |
| WordPress `:8890` | **DOWN** (connection refused) |
| Docker | Not verified healthy this pass |

## Re-run commands (this pass)

| Command | Exit | Result |
| --- | --- | --- |
| `php NextGenTutors-Companion/tests/agentic-governance.php` | 0 | **12/12 PASS** |
| `php NextGenTutors-Companion/tests/agentic-completion.php` | 0 | **10/10 PASS** |
| `php NextGenTutors-Companion/tests/run.php` | 255 | **FAIL** — `trailingslashit()` missing outside WP bootstrap |
| `cd services/ngt-agent-gateway && npm test` | 0 | **12/12 PASS** (includes HTTP E2E) |
| Gateway `/health` | 0 | `a2a_mode=official-a2a-js-sdk`, `a2a_sdk_loaded=true` |
| Full `scripts/build-release.ps1` | **NOT RUN** | Disk risk / WP down |
| Companion lean ZIP | 0 | `delivery/installable-packages/NextGenTutors-Companion-v1.9.5-agentic.zip` + SHA-256 |
| Headed Playwright | **NOT RUN** | WordPress runtime unavailable |

## Inherited claims treated as UNVERIFIED until reproduced

- Education placeholders fully replaced → still **PARTIAL** workflows
- Agentic maturity 22%→48% → **estimate only**
- Prior Pass C headed E2E green → not re-executed this pass
- Production packages from 2026-08-02 → exist on disk as prior artifacts; **not rebuilt** this pass for full suite

## Correct release decision entering this pass

**STAGING ONLY**
