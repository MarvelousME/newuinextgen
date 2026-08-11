# FINAL — TencentDB Agent Memory Integration Evidence Report

**Date:** 2026-08-11  
**Stage:** 2 (approved PROCEED WITH CONDITIONS)  
**Verdict:** **STAGING READY / PARTIAL**

## Conditions compliance

| Condition | Enforcement | Status |
|-----------|-------------|--------|
| Production HA: SQLite not multi-node HA | `sqlite_ha_acknowledged` setting + docker/memory README | DOCUMENTED |
| Bridge IAM primary; hide `user_key` | `NGC_Memory_Identity_Map` + vault refs; scrub plaintext from meta | IMPLEMENTED |
| Proxy not Bridge model gateway | `proxy_enabled` always forced `false` in settings get/update + admin | IMPLEMENTED |
| PII/minors write policy | `NGC_Memory_Service::classify` + `write_policy_gate` (minors/tutoring/sensitive/credentials) | IMPLEMENTED |
| Core retrieve→write first; Skills/Wiki/CodeGraph separate flags | Flags default off; no Skills/Wiki/CodeGraph adapters in Stage 2 | IMPLEMENTED |
| Memory optional; DISABLED/DEGRADED safe | Noop provider; `retrieve_safe` / `write_safe` never throw into chat; bookings/payments untouched | IMPLEMENTED |

## Deliverables

### RAD

- `architecture/manifests/bridge-memory-tencentdb.json`
- `architecture/capabilities/memory-tencentdb.json`
- `architecture/contracts/memory.v1.json`
- `architecture/policies/memory-write-policy.json`
- Companion optional consumes + dependency edges

### Companion runtime

| Component | Path |
|-----------|------|
| Interface | `includes/memory/interface-ngc-memory-provider.php` |
| Settings | `includes/memory/class-ngc-memory-settings.php` |
| Noop | `includes/memory/class-ngc-memory-noop-provider.php` |
| Service | `includes/memory/class-ngc-memory-service.php` |
| Identity map | `includes/memory/class-ngc-memory-identity-map.php` |
| Tencent adapter | `includes/memory/class-ngc-tencent-memory-adapter.php` |
| Ingestion worker | `includes/memory/class-ngc-memory-ingestion-worker.php` |
| REST | `includes/rest/class-ngc-rest-memory.php` |
| Admin | `includes/admin/class-ngc-memory-admin.php` |
| Chat hooks | `includes/ai/class-ngc-ai-chat.php` (optional retrieve inject + async write) |
| Schema | `wp_ngc_memory_identity_map` via `NGC_Database` |

### Deploy

- `docker/memory/README.md`
- `docker/memory/docker-compose.memory.yml` (Core only; **no Proxy**)
- `docker/.env.example` memory vars (commented)

### Tests

- `tests/phpunit/Memory/MemorySettingsTest.php`
- `tests/run-memory-unit.php`

## Explicitly deferred (flags off / not built)

- Skills adapter ↔ `NGC_AI_Agents` (Phase D)
- Knowledge / Wiki (Phase E)
- CodeGraph (Phase F)
- MCP memory tools (Phase G)
- Live Core E2E against pulled Docker image (requires LLM keys + image pull)
- Production HA persistence plan approval

## Limitations (honest)

1. Without a running Memory Core, remote provider degrades; noop/empty context is intentional.
2. Identity provisioning against `/v3/meta/user/create` is mapping-ready; full auto-provision loop is minimal (admin key + upsert) — ops must seed mappings for production tenants.
3. Policy Bridge DENYs unknown capabilities; memory caps load from `architecture/capabilities` when architecture root is present.
4. Not production HA-ready on default SQLite.

## Rollback

1. Memory Center: disable master switch / set mode `DISABLED`.
2. Stop `memory-core` compose profile.
3. Leave identity map table in place (non-destructive).

## Verdict rationale

Stage 2 Core path is code-complete with safe defaults and condition guards. Live Core health/E2E and HA persistence remain open → **STAGING READY / PARTIAL**, not production-complete.
