# 05 — Target Architecture

## North star

```text
                     BRIDGE AI OS (Companion + RAD)
                              |
                   Capability / Policy Layer
                              |
                    Bridge Memory Abstraction
                         (IMemoryProvider)
                              |
              +---------------+----------------+
              |                                |
   TencentDbAgentMemoryProvider      Noop / FutureProvider
              |
     +--------+--------+-----------+
     |                 |           |
 Memory Core        Knowledge   (Proxy optional/dev)
  :configurable      :optional
```

## Non-negotiables

1. Bridge owns IAM, policy, capabilities, secrets, audit, observability, LLM gateway.
2. Application code depends on **capabilities** (`memory.retrieve`), never Tencent URLs/DTOs.
3. Memory offline ⇒ degraded enrichment only; bookings/payments/theme unaffected.
4. Tencent Proxy is **not** the Bridge model router.
5. Preferred enrichment flow:

```text
Agent Request → Policy → memory.retrieve → Context Budget → Prompt Assembly
→ Bridge AI Gateway / NGC_AI_Models → Model
→ conversation.completed → Outbox → memory.write (async)
```

## Subsystem id

`bridge.memory.tencentdb` (RAD manifest under `architecture/manifests/`).

## Provider interface (conceptual)

```text
IMemoryProvider
  write / search / retrieve / forget / correct / list / health
  chat.write / chat.search
  persona.get / persona.update
  skill.search / skill.assign (via Bridge skill façade)
  knowledge.search / knowledge.ingest (optional)
  code.search / code.impact (optional)
```

## Components to add (Stage 2 — planned only)

| Component | Package | Role |
|-----------|---------|------|
| `NGC_Memory_Contract` / interface | Companion `includes/memory/` | Ports |
| `NGC_Memory_Service` | Companion | Capability façade + policy + classification |
| `NGC_Tencent_Memory_Adapter` | Companion or small Node sidecar client | HTTP/SDK adapter |
| Mapping store table | Companion DB | Bridge↔Tencent IDs |
| Feature flags + settings | Companion options / secrets vault | Config |
| Admin Memory Center | Platform Kernel / AI admin | Read health, flags, mappings |
| Docker compose overlay | `docker/` | Optional profile `memory` |
| RAD manifest + capabilities | `architecture/` | Gate registration |
| MCP tool wrappers | Companion MCP | Opt-in tools |

## Service classification reminder

| Service | Bridge need |
|---------|-------------|
| Memory Core | REQUIRED when `memory.enabled` |
| Knowledge | OPTIONAL feature flags |
| Hub UI | ADMIN-ONLY deep-link optional |
| Proxy | NOT NEEDED for WP agents |

## Resilience

- Timeouts, retries (transient), circuit breaker via existing patterns
- Write failures → durable queue / NGTAI-style outbox; do not fail HTTP chat
- Retrieve timeout → empty memory context + metric
- Modes: DISABLED / DEGRADED / HEALTHY / MAINTENANCE
