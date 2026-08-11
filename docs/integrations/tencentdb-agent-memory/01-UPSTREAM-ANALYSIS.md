# 01 — Upstream Analysis (TencentDB Agent Memory)

**Stage:** 1 (analyze only)  
**Upstream:** https://github.com/TencentCloud/TencentDB-Agent-Memory  
**Branch inspected:** `feat/server_team` (also exists: `main`, `feat/server`)  
**Evidence date:** 2026-08-11  
**License:** MIT  

## Verified product claim

TencentDB Agent Memory is a **team-level memory hub** that turns conversations, docs, and code into four reusable **memory assets**:

| Asset | Verified |
|-------|----------|
| Chat Memory (L0–L3) | Yes — MemoryCore README + INSTALL |
| Skills | Yes — `/v3/skill/*`, Skill clients in SDK |
| LLM-Wiki | Yes — MemoryKnowledge OpenAPI + Hub |
| CodeGraph | Yes — MemoryKnowledge OpenAPI; based on colbymchenry/codegraph |

Agents are **callers and metadata entities** — MemoryCore does **not** host/schedule/execute agents.

## Service topology (verified)

| Service | Default port | Repo path | Classification for Bridge |
|---------|--------------|-----------|---------------------------|
| Memory Core (Gateway) | 8420 | `MemoryCore/` | **REQUIRED** (data + meta plane) |
| Memory Hub / Panel UI | 8125 | `MemoryPanel/` (+ combined hub image) | **ADMIN-ONLY** (ops UX; Bridge Memory Center preferred for day-to-day) |
| Knowledge Service | 8424 (docs); OpenAPI samples `8421` | `MemoryKnowledge/` | **OPTIONAL** (Wiki/CodeGraph phases) |
| Proxy | 8096 | `MemoryProxy/` | **NOT NEEDED** for Bridge runtime LLM path; **DEVELOPMENT-ONLY** if coding-agent enrichment wanted |

Ports must be Bridge configuration — never hard-coded in domain code.

## Runtime requirements (verified)

- **Node.js ≥ 22.16**
- Docker Hub images: `agentmemory/memory-core`, `memory-hub`, `memory-proxy`
- **LLM API required** for extraction/aggregation (OpenAI-compatible or Anthropic protocol)
- Embeddings optional; BM25 works without remote embeddings
- **Default persistence: SQLite + local files + in-process pipeline state** (`TDAI_DATA_DIR`, Docker volumes `tdai-memory-core-data`, `tdai-panel-data`)
- Migration scripts exist: SQLite → Tencent Vector DB (`migrate-sqlite-to-tcvdb`), v2→v3 data migrate
- TypeScript SDK: `@tencentdb-agent-memory/memory-sdk-ts-v2` (v3 isolation)
- Python Hermes plugin + OpenClaw plugin adapters

## Auth model (verified)

| Mechanism | Purpose |
|-----------|---------|
| `sk-mem-…` **user_key** | End-user / panel / proxy auth (`x-tdai-user-key`) |
| Gateway `Authorization: Bearer` (`TDAI_GATEWAY_API_KEY` / `MEMORY_CORE_GATEWAY_API_KEY`) | Machine gate; **empty by default** in local deploy so Proxy can talk to Core |
| `x-tdai-service-id` | Memory instance / tenant routing (usually `default` locally) |
| Roles | System Admin vs Team Admin / Member; asset Owner |
| Visibility | `private` / `team` / `restricted` / `agent` ACLs |

**Critical:** local `.env.example` documents that non-empty gateway API key currently breaks Proxy auth/sessionInit until Proxy sends Bearer — production auth story is immature.

## API surface (verified)

### Memory Core

| Plane | Paths | Notes |
|-------|-------|-------|
| Health | `GET /health` | Public; includes pipeline worker stats |
| Compat | `/capture`, `/recall`, `/search/*` | Legacy |
| v2 | `/v2/conversation|atomic|scenario|core/*` | Stable |
| v3 data | `/v3/conversation|atomic|scenario|core/*` | **Recommended**; requires `team_id` + `agent_id` + `user_id` |
| Skills | `/v3/skill/*` | Create/search/version/extract |
| Meta | `/v3/meta/*` | Users, teams, agents, tasks, ACLs, user-keys (~54 actions) |
| Knowledge meta | `/v3/knowledge/*` | Metadata only (not content search) |

### Knowledge Service

- OpenAPI `MemoryKnowledge/openapi.yaml` — Wiki + CodeGraph (~28 endpoints)
- Multi-tenant via `x-tdai-service-id`
- Tools discovery: `/v3/tools/list`, `/v3/tools/call` (README)
- Async ingest → `ready` status

### Proxy

- OpenAI + Anthropic dual protocol
- Pipeline: auth → sessionInit (team/agent/task pick) → injection → upstream LLM
- **Not** Bridge’s model authority

## Layered memory (verified)

| Layer | Content | Use |
|-------|---------|-----|
| L0 | Raw conversation | Exact wording / audit source |
| L1 | Atoms (facts, prefs, events) | Precise recall |
| L2 | Scenario/project context | Working context restore |
| L3 | Persona/core profile | Cold-start identity |

Async pipeline extracts L1→L2→L3; retrieval uses budgets (items/chars/timeout) + BM25/vector/RRF when configured.

## Deployment modes (verified)

1. Full stack: `deploy/global-images/start-all.sh` (core + hub + proxy)
2. Hub-only against existing Core
3. From-source MemoryCore gateway
4. Combined panel+knowledge image under `deploy/panel-knowledge-combined/`

## Known upstream limitations (verified)

- Wiki/CodeGraph async — not instantly ready
- CodeGraph prioritizes public HTTPS repos; private/SSH still refining
- Hub manual binding; automated routing still iterating
- Hermes/OpenClaw require `x-task-id` (and static `x-conversation-id`) in current version
- Product evolving rapidly (v2.0.0 / Team Memory Beta)
- Default SQLite unsuitable as-is for multi-node HA Bridge production without migration/HA plan

## SDKs to prefer for adapter

- TypeScript `@tencentdb-agent-memory/memory-sdk-ts-v2` — `MemoryClient` + `MetadataClient`
- Prefer HTTP/SDK behind Bridge adapter; do **not** vendor Node services into PHP domain

## Upstream docs consulted

- `README.md`, `INSTALL.md`, `MemoryCore/README.md`
- `deploy/global-images/.env.example`, `start-*.sh`
- `MemoryKnowledge/openapi.yaml`
- `sdk/memory-core/typescript/README.md`
- Recursive tree of `feat/server_team` (MemoryCore, MemoryKnowledge, MemoryPanel, MemoryProxy, sdk/, deploy/)
