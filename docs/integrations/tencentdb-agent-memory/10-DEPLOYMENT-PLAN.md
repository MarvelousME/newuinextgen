# 10 — Deployment Plan

## Fit to current stack

Extend `docker/docker-compose.yml` with an **optional profile** `memory` (not default):

| Service | Image | Publish | Network |
|---------|-------|---------|---------|
| `tdai-memory-core` | `agentmemory/memory-core:${TAG}` | **internal only** (no host publish in staging/prod prefer) | bridge docker net |
| `tdai-memory-hub` | `agentmemory/memory-hub:${TAG}` | optional admin | internal + optional bind |
| `tdai-knowledge` | (hub combined or separate KS) | internal | bridge net |
| `tdai-proxy` | omit by default | — | — |

Do **not** blindly copy `start-all.sh`; translate to Compose with Bridge secrets, healthchecks, resource limits, named volumes, restart policies.

## Configuration (Bridge-owned)

```text
memory.enabled
memory.mode = DISABLED|LOCAL|REMOTE|...
memory.core_base_url
memory.knowledge_base_url
memory.service_id_strategy
memory.gateway_bearer_secret_ref
memory.timeout_ms
memory.retry
memory.feature.skills|wiki|codegraph|proxy
memory.llm.*  (extraction LLM — separate from BYOK chat if desired)
```

Ports are config values.

## Secrets

Map `MEMORY_LLM_API_KEY`, gateway bearer, provisioned user_keys → `NGC_Secret_Vault` / Docker secrets / env — never commit `.admin-key`.

## Persistence condition

| Environment | Acceptable store |
|-------------|------------------|
| Local/dev | SQLite volume OK |
| Staging | SQLite volume + backup snapshots OK with data-loss disclaimer |
| Production HA | **BLOCKED** until approved plan: dedicated volume backups + single-writer topology **or** migrate to Tencent VDB/external store per upstream scripts |

## Health

Bridge polls `memory.health` → Core `/health` (+ Knowledge). Surface on Platform Kernel / Memory Center. Circuit breaker opens → DEGRADED.

## Network allowlist

```text
wordpress/companion → memory-core
companion → knowledge (if enabled)
memory-core → approved LLM endpoint
knowledge → approved LLM endpoint
Deny: theme → memory-*; public internet → 8420/8424
```
