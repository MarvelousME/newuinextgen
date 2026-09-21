# Services / Sidecars Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `services/ngt-agent-gateway/src/server.js`
- `services/ngt-talent-intelligence/` (optional NLP)
- `services/ngt-mcp-mock/` (local MCP stub)
- Workspace `ecosystem-platform/` (compose overlay from `docker/`)

## Architecture

```
 Companion (PHP)
   │  HMAC client (NGC_Agent_Gateway_Client)
   ▼
 ngt-agent-gateway :8787
   ├─ /health
   ├─ /v1/tasks          (durable SQLite)
   ├─ /v1/mcp/*          (allowlist + SSRF guards)
   └─ A2A firstparty.diagnostics

 Optional:
   Talent Intelligence  ←─ Companion talent REST
   ecosystem-platform :8790  ←─ Bearer API (fail-closed)
```

## Key Modules

| Service | Purpose | Auth | Persistence |
|---------|---------|------|-------------|
| `ngt-agent-gateway` | A2A tasks + MCP proxy | HMAC `NGT_GATEWAY_SHARED_SECRET` | SQLite under `NGT_GATEWAY_DATA_DIR` |
| `ngt-talent-intelligence` | NLP / ranking assist | service-local | service-owned |
| `ngt-mcp-mock` | Dev MCP fixture | local only | none |
| `ecosystem-platform` | SaaS tenants / BFF | Bearer token | platform DB |

## Data Flow

1. Companion tool / agent needs external execution  
2. Client signs request → Gateway  
3. Unknown / unapproved MCP tool → reject  
4. Tasks survive process restart via SQLite (`node:sqlite`, Node ≥22)

## External Dependencies

- Node ≥22 for Agent Gateway  
- No default secrets in compose — set env explicitly  
- Ecosystem path from Docker: `../../ecosystem-platform`

## Related Areas

- [agentic.md](agentic.md)  
- [platform.md](platform.md)  
- `services/ngt-agent-gateway/README.md`  
