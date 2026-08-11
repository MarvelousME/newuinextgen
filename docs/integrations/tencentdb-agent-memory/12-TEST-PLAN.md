# 12 — Test Plan

## Unit

- Memory DTO mappers (Bridge ↔ adapter) — no Tencent types leak
- Classification / redaction rules
- Identity map idempotency
- Policy deny default for `memory.*`

## Provider / contract

- Adapter against recorded HTTP fixtures from `/v3/*` + `/health`
- SDK version pin compatibility test
- OpenAPI Knowledge contract smoke (when Phase E/F)

## Integration (real services, staging profile)

- Core up; health PASS
- Provision mapping user/team/agent
- L0 write → query
- Retrieve L2/L3 after pipeline (async wait with timeout)
- Delete L0/L1 and confirm absence

## Security E2E

- User A cannot read User B private memory
- Tenant A cannot access Tenant B service_id data
- Agent without capability denied
- DISABLED provider: booking + PayFast paths still green
- Logs scrubbed of user_keys / LLM keys

## Failure

- Stop Core → retrieve returns empty, chat still works, circuit opens
- Stop Knowledge only → code/wiki flags degrade; chat memory OK
- Malformed responses → typed errors, no PHP fatals

## Persistence

- Restart Core container; mapped data + L0 survive volume

## Architecture / RAD

- Manifest validates; `node rad-platform/cli/gate.mjs` PASS
- No theme→memory-core coupling

## Product E2E (Definition of Done subset)

1. Bridge user + agent  
2. Task/chat turn  
3. Persist useful memory  
4. New session retrieve  
5. Agent prompt contains labeled memory (not secrets)  

Skills/Wiki/CodeGraph E2E only when respective flags enabled.
