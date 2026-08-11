# 08 — Capability Mapping

## Bridge capabilities to register (architecture/capabilities)

| Capability ID | Provider subsystem | Maps to Tencent | Phase |
|---------------|--------------------|-----------------|-------|
| `memory.health` | bridge.memory.tencentdb | `GET /health` (+ KS health) | A |
| `memory.write` | bridge.memory.tencentdb | L0 add (+ async extract) | C |
| `memory.search` | bridge.memory.tencentdb | L1 searchAtomic / conversation search | B |
| `memory.retrieve` | bridge.memory.tencentdb | L2/L3 read + budgeted search | B |
| `memory.forget` | bridge.memory.tencentdb | delete conversation/atomic (+ best-effort) | C |
| `memory.correct` | bridge.memory.tencentdb | atomic update / scenario write | C |
| `memory.list` | bridge.memory.tencentdb | query APIs | B |
| `memory.chat.write` | bridge.memory.tencentdb | `/v3/conversation/add` | C |
| `memory.chat.search` | bridge.memory.tencentdb | conversation search/query | B |
| `memory.persona.get` | bridge.memory.tencentdb | `/v3/core/read` | B |
| `memory.persona.update` | bridge.memory.tencentdb | `/v3/core/write` | C |
| `memory.skill.search` | bridge.memory.tencentdb | `/v3/skill/*` search | D |
| `memory.skill.assign` | bridge.memory.tencentdb | meta binding + Bridge agent config | D |
| `memory.knowledge.search` | bridge.memory.tencentdb | KS wiki query / tools.call | E |
| `memory.knowledge.ingest` | bridge.memory.tencentdb | wiki create+ingest | E |
| `memory.code.search` | bridge.memory.tencentdb | CodeGraph query | F |
| `memory.code.impact` | bridge.memory.tencentdb | callers/callees/impact | F |

Consumers (`NGC_AI_Chat`, Control Plane, MCP) call **capabilities** via `NGC_Memory_Service` + `NGC_Policy_Bridge`.

## Explicit non-capabilities

| Anti-pattern | Status |
|--------------|--------|
| `proxy.route_all_llm` | **Rejected** |
| Direct `http://tencent-memory:8420` from theme/domain | **Forbidden** |
| Duplicate second skills admin bypassing Bridge | **Forbidden** |

## Dependency graph edges (planned)

```text
companion → bridge.memory.tencentdb (optional)
bridge.memory.tencentdb → bridge.secrets
bridge.memory.tencentdb → bridge.policy (NGC_Policy_Bridge)
AgentRuntime → memory.retrieve
memory.write → durable queue
```
