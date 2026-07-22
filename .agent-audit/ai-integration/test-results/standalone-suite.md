# AI Integration — Test results

**Date:** 2026-07-21  
**Command:** `php NextGenTutors-AI-Integration/tests/run.php`

## Outcome

12-file standalone suite — **PASS** (162 assertions, includes `test-result-versioning.php` for `agent_run_id:result_version` uniqueness).

## Runtime surface ([d92317b2](d92317b2-90b6-420c-a5e4-2ebb2e9d2024))

- Bootstrap `1.1.0`, REST `ngtai/v1`, admin under `ngc-operations`, CLI `wp ngtai *` — present in repo.
- Docker container `newuinextgen-wordpress-1` **does not currently mount** `NextGenTutors-AI-Integration` (plugin absent from `wp-content/plugins/`). Recreate stack with `docker compose up -d` from `docker/` to pick up compose volume.
- `GET /wp-json/ngtai/v1/health` → **404** until plugin activated in running container.

## Test matrix ([ed98473a](ed98473a-0b79-4eb8-b2b2-c940126121f7))

Subagent [f40f6874](f40f6874-f719-4791-8189-2bf46b2130c5) delivered the full matrix; legacy `tests/test-signatures.php` removed (superseded by `test-signature.php`).
