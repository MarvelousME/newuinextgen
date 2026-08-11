# 04 — Gap Analysis

| Capability | Bridge Current | Tencent | Gap | Decision |
| ---------- | -------------- | ------- | --- | -------- |
| conversation memory | Client-only history | L0 Chat Memory | No durable L0 | **ADD** via `memory.chat.*` → Tencent adapter |
| long-term memory | None | L1/L2/L3 | Missing | **ADD** `memory.retrieve` / persona |
| agent memory | WP options config only | Agent-scoped assets | No semantic agent memory | **ADD** mapped agents |
| team memory | None | Teams + visibility | Missing | **ADD** tenant→team mapping |
| user preferences | Admin prefs ≠ LLM prefs | L1/L3 | Missing LLM prefs | **ADD** classified writes |
| persona | Demo login personas only | L3 core | Missing | **ADD** `memory.persona.*` |
| skills | Declarative `NGC_AI_Agents` list | Versioned Skill assets | Parallel catalogues risk | **ADAPT** Bridge owns catalogue; Tencent stores/search |
| knowledge / RAG | Documented external RAGFlow | Wiki | Optional duplication | **OPTIONAL** selective Wiki; federate later |
| document indexing | None in-repo | Wiki ingest async | Gap | Phase E optional |
| code intelligence | None | CodeGraph | Gap | Phase F optional |
| memory search | None | BM25/hybrid | Gap | **ADD** |
| memory lifecycle | N/A | Async pipeline states | Gap | **ADD** job states |
| access control | Policy engine + authz | ACL visibility | Dual models | Bridge primary + map |
| retention | None for chat | Implicit local store | Gap | Bridge retention policies |
| audit | Immutable audit / NGTAI | Limited product audit | Gap | Bridge audit wrappers |
| memory correction/delete | N/A | L0/L1 delete APIs verified | Partial L2/L3 | **ADD** where API supports; verify cascade |
| tenant isolation | `NGC_Tenant_Context` partial | `service_id` + team | Mapping required | **ADD** mapping store |
| agent loadout | Manual agent config | Hub bindings | Gap | Admin Memory Center |
| LLM gateway | BYOK + Agent Gateway | Proxy | Must not replace | **KEEP** Bridge; Proxy optional/dev |
| MCP memory tools | No product memory tools | Knowledge tools API | Gap | Expose via Bridge MCP later |
| HA persistence | MySQL platform | SQLite default | **BLOCKER for prod HA** | Condition: volume HA plan or migrate store |

## Target Bridge capability (summary)

Introduce Bridge-owned memory capabilities with Tencent as default remote provider and a local/no-op provider for `DISABLED`/`DEGRADED`.
