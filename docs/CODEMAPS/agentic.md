# Agentic Platform Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `NextGenTutors-Companion/includes/agents/class-ngc-agent-control-plane.php`
- `NextGenTutors-Companion/includes/agentic/class-ngc-tool-gateway.php`
- `services/ngt-agent-gateway/src/server.js`
- `docker/mu-plugins/ngt-agent-gateway-bridge.php`

## Architecture

```
Trigger (admin / fraud / event)
        │
        ▼
 Agent Control Plane (16 registry agents)
        │  tools[] + autonomy L0–L3
        ▼
 Policy Engine + Policy Bridge (capability ids)
        │
        ├─ awaiting_approval → Agent Ops UI
        └─ queued → execute_task

Agentic Admin / workers
        │
        ▼
 Tool Gateway (allowlisted app tools only)
        │
        ├─ CRM / leads / social / schedule (PHP domain services)
        └─ Agent Gateway Client (HMAC) ──► Node Gateway :8787
                                              │  SQLite durable tasks
                                              ├─ A2A firstparty.diagnostics
                                              └─ MCP discover/execute (allowlist)
```

## Key Modules

| Module | Purpose | Exports | Dependencies |
|--------|---------|---------|--------------|
| `NGC_Agent_Control_Plane` | Seed/registry, kill switches, tasks, approvals | `request_action`, `registry`, pause APIs | Policy Engine, DB tables |
| `NGC_Agent_Policy_Engine` | Decision matrix by action_id | `evaluate` | Control Plane pause flags |
| `NGC_Policy_Bridge` | Capability → allow/deny | `decide`, `authorize_invoke`, `authorize_domain` | Capability Registry, Authz |
| `NGC_Secret_Vault` | Server-side secrets | `store`, `reveal` (`env:` + option) | NGC_Crypto |
| `NGC_Tool_Gateway` | Least-privilege app tools | `catalogue`, `invoke` | Leads, Social, Schedule |
| `NGC_Mcp_Registry` | MCP server inventory + SSRF | `upsert`, `health_check`, `store_discovery` | `NGC_Mcp_Ssrf`, Secret Vault |
| `NGC_Agent_Gateway_Client` | Signed WP→Gateway HTTP | `health`, `submit_task`, `mcp_discover`, `mcp_execute` | mu-plugin constants |
| `ngt-agent-gateway` | Node A2A + MCP proxy | `/health`, `/v1/tasks`, `/v1/mcp/*` | `node:sqlite`, `@a2a-js/sdk` |

## Seeded control-plane agents (16)

`system-audit`, `security-ops`, `fraud-detection`, `financial-reconciliation`, `tutor-verification`, `tutor-matching`, `scheduling`, `customer-support`, `notification`, `content-marketing`, `compliance`, `observability`, `quality-assurance`, `remediation`, `release-governance`, `safeguarding`

## Data Flow (strict)

1. Unknown agent / tool → reject  
2. Kill switch (global or per-agent) → deny  
3. Policy DENY tools (`shell.unrestricted`, secret exfil, audit disable) → never run  
4. High-impact money/tutor/deploy/delete → `awaiting_approval`  
5. MCP enable requires `capabilities_approved`  
6. Gateway MCP execute requires `tool_approved` + allowlist (`ping`, `business.profile.get`, `health.summary`)  
7. Gateway tasks persist in SQLite under `NGT_GATEWAY_DATA_DIR` (survive restart)

## External Dependencies

- Node ≥22 for gateway (`node:sqlite`)
- HMAC shared secret via env (`NGT_GATEWAY_SHARED_SECRET`) — no compose defaults
- Optional `OTEL_EXPORTER_OTLP_ENDPOINT` for span hook export

## Related Areas

- [platform.md](platform.md) — domain modules + Policy Bridge  
- [INDEX.md](INDEX.md) — map index  
