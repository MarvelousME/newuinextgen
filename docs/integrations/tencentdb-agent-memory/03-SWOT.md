# 03 — SWOT (TencentDB Agent Memory × Bridge)

## Strengths

- Layered L0–L3 memory with retrieval budgets
- Team/Agent/Task scoping + ACL visibility model
- Skills as versioned reusable assets
- Wiki + CodeGraph as on-demand tools (`/v3/tools/*`)
- TypeScript/Python SDKs and HTTP gateway
- MIT license; Docker Hub images
- Aligns with Bridge gap: durable agent memory does not exist today

## Weaknesses

- Default **SQLite + single-node volumes** — HA/scale risk for production SaaS
- Dual auth (`user_key` + optional Bearer) with documented Proxy/Bearer friction
- Independent identity/roles vs WordPress/Bridge IAM
- Heavy **LLM dependency** for extraction (cost + latency)
- Async derived memory → eventual consistency complexity
- Rapid API evolution (v2/v3, beta Team Memory)
- Proxy-centric DX (Claude Code pickers) differs from Bridge WP agent runtime
- Knowledge OpenAPI port docs inconsistent (8424 vs 8421 examples)

## Opportunities

- Supercharge Companion chat + ops agents with cross-session recall
- Cold-start for tutoring support / ops / architecture agents
- MCP tools: `memory_search`, `wiki_search`, `code_impact` under existing MCP permissions
- CodeGraph for impact analysis on theme/plugin changes (opt-in)
- Skills consolidation behind Bridge capability registry
- Progressive feature flags fitting RAD pillars
- Replace brittle client-only history without rewriting business domains

## Threats

- Memory poisoning / prompt injection via stored content
- Tenant contamination if `service_id`/team mapping wrong
- PII retention (minors/tutoring platform — POPIA/GDPR sensitive)
- Vendor coupling if domain imports Tencent DTOs
- Duplicate RAG/Wiki with future agents-api
- Cost runaway from async extraction
- Making Proxy mandatory → Bridge loses LLM authority
- Outage cascading if memory treated as transaction-critical

## SWOT conclusion

High **opportunity** against a real Bridge gap, with material **ops/security** conditions. Integration is justified **only** as a replaceable provider behind Bridge contracts, with SQLite/HA and identity mapping treated as first-class risks.
