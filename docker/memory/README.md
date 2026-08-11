# Docker memory profile (optional)

**Status:** Stage 2 — Core only  
**HA:** Default upstream persistence is **SQLite + local volumes**. Do **not** treat this as multi-node HA without an approved persistence plan (Tencent Vector DB migration or equivalent).

## What this profile starts

| Service | Role | Bridge usage |
|---------|------|--------------|
| `memory-core` | Memory Core gateway (`agentmemory/memory-core`) | **REQUIRED** when `memory.enabled` |
| Memory Hub | Not included by default | ADMIN-ONLY deep-link later |
| Memory Proxy | **Not included** | **Forbidden** as Bridge LLM gateway |

## Start

From `docker/`:

```bash
docker compose --profile memory -f docker-compose.yml -f memory/docker-compose.memory.yml up -d memory-core
```

Point Companion Memory Center `core_base_url` at `http://memory-core:8420` (in-compose) or `http://127.0.0.1:8420` (host).

## Secrets

- Set `TDAI_GATEWAY_API_KEY` / bearer via env or Docker secrets.
- Store the same value in Companion via **Memory Center → Gateway bearer** (`NGC_Secret_Vault`).
- Map `user_key` values through identity map + vault — never log plaintext keys.

## Flags (Companion)

Enable in order:

1. Master `enabled` + mode `REMOTE` or `LOCAL`
2. `retrieve_enabled`
3. `write_enabled`
4. Skills / Wiki / CodeGraph remain **off** until separate approval

## Rollback

```bash
docker compose --profile memory -f docker-compose.yml -f memory/docker-compose.memory.yml stop memory-core
```

Then disable flags in Memory Center (or leave DISABLED). Bookings/payments continue unaffected.
