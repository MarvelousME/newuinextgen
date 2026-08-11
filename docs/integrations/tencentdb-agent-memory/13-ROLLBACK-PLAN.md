# 13 — Rollback Plan

## Instant rollback (no data loss of WP)

1. Set `memory.enabled=false` (and write/retrieve flags false).
2. Confirm chat/agents operate without provider (noop path).
3. Optionally `docker compose --profile memory stop`.

## Partial rollback

| Symptom | Action |
|---------|--------|
| Write storms / cost | Disable `memory.write.enabled` only |
| Bad retrieval | Disable `memory.retrieve.enabled`; keep health |
| Skills noise | Disable `memory.skills.enabled` |
| Wiki/CodeGraph errors | Disable wiki/codegraph flags |

## Hard rollback (remove subsystem)

1. Flags off  
2. Remove compose profile services  
3. Keep volumes for forensics **or** purge with explicit approval  
4. Leave mapping table in place (orphaned OK) or migrate archive  
5. Remove capability registrations from active registry if needed (manifests can remain disabled)

## What must never be rolled back destructively

- Companion bookings, payments, sessions, NGTAI outbox  
- BYOK model keys  

## Verification after rollback

- `memory.health` reports DISABLED  
- Existing PHPUnit / `tests/run.php` / booking smoke green  
- RAD gate still PASS (manifest may stay registered as disableable)
