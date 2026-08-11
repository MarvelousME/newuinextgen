# 02 — Current Bridge Memory Analysis

**Platform name in-repo:** NextGen Tutors / Companion “Bridge AI OS” (RAD + agent platform)  
**Mode:** BROWNFIELD  
**Evidence date:** 2026-08-11  

## Runtime stack (actual)

| Layer | Technology |
|-------|------------|
| App | WordPress 6.x, PHP 8.x theme + plugins |
| Domain | `NextGenTutors-Companion` |
| AI transport | `NextGenTutors-AI-Integration` (HMAC outbox ↔ external agents-api) |
| Agent gateway | Node `services/ngt-agent-gateway` (:8787) |
| DB | MySQL 8 (Docker); `ngc_*`, `ngtai_*` tables |
| Queues | MySQL durable queue + DLQ + NGTAI outbox + event outbox |
| Cache/vector | **No Redis / no vector DB in-repo** |
| Models | Companion BYOK OpenAI-compatible (`NGC_AI_Models`) |
| AuthZ | `NGC_Agent_Policy_Engine`, `NGC_Authz_Matrix`, `NGC_Policy_Bridge`, `BIA_Policy` |
| Governance | `rad-platform/` + `architecture/` manifests/capabilities |
| Observability | `NGC_Platform_Observability`, Metrics, Intelligence, NGTAI health |
| MCP / A2A | Companion registries + gateway execution |
| External (docs only) | Coolify `agents-api` / LiteLLM / RAGFlow / Qdrant — **not implemented in this monorepo** |

## Memory-related inventory

| Capability | Location | Storage | Decision |
|------------|----------|---------|----------|
| Chat history (admin UI) | `assets/js/ngc-ai.js` + `NGC_AI_Chat` / `NGC_Rest_AI` | Browser / request body only | **ENHANCE** — wrap with server memory |
| BYOK chat runner | `class-ngc-ai-chat.php` | Ephemeral | **KEEP** + inject memory context |
| Supervised agents + skills catalogue | `NGC_AI_Agents` (WP options) | Options | **KEEP**; map Tencent Skills via adapter later |
| Ops agents / tasks | `NGC_Agent_Control_Plane` | MySQL tasks/runs | **KEEP** |
| Tool gateway | `NGC_Tool_Gateway` | Domain services | **KEEP** |
| MCP registry | `NGC_MCP_Registry` + gateway | Options + gateway | **KEEP**; expose memory tools later |
| A2A pins/tasks | `NGC_A2A_Gateway` | WP options (cap 200) | **ENHANCE** durability separately |
| Gateway task store | `task-store.js` | In-memory Map | **ENHANCE** (not semantic memory) |
| Tutoring sessions | `includes/session/*` | `wp_ngc_sessions` | **KEEP** — domain booking, not LLM memory |
| AI transport outbox | NGTAI | `wp_ngtai_deliveries` | **KEEP** — reuse for async memory jobs |
| Durable queue | Platform kernel | MySQL | **KEEP** — preferred async fabric |
| Cursor MCP `memory` | Dev tooling | Local | **KEEP** (dev-only) — do not productize |
| Assumed RAGFlow/Qdrant | External docs | N/A in repo | **WRAP** only if agents-api owned; else Tencent Wiki/CodeGraph optional |

## Abstractions found

**No `IMemoryProvider` (or equivalent) exists.**  
Closest patterns: request-scoped chat history; WP-option agent config; MySQL agent tasks; external RAG assumed.

## Non-duplication stance

| Existing | Vs Tencent | Action |
|----------|------------|--------|
| Client chat history | Chat Memory L0 | ENHANCE — persist via Bridge memory capability |
| `NGC_AI_Agents` skills list | Tencent Skills | WRAP/ADAPT — Bridge skill registry remains authority; Tencent is provider backend |
| External RAG (if present) | Wiki | Federate / selective; do not dual-ingest blindly |
| Agent Gateway / BYOK models | Proxy | **KEEP Bridge gateway**; Proxy NOT primary |
| Policy / control plane | Upstream ACLs | Bridge policy primary; upstream ACL defense-in-depth |
| Tutoring session state | Team/Task memory | KEEP separate domains |

## Operating modes required (Phase 0)

Bridge must support memory provider modes: `DISABLED | LOCAL | REMOTE | DEGRADED | HEALTHY | MAINTENANCE`.  
Unrelated subsystems (bookings, payments, theme) must not fail when memory is offline.
