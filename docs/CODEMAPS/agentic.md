# Agentic Platform Codemap

**Last Updated:** 2026-08-03  
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
 Policy Engine (ALLOW / LIMITS / APPROVAL / DENY / ESCALATE)
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
                                              │
                                              ├─ A2A firstparty.diagnostics
                                              └─ MCP discover/execute (allowlist)
```

## Key Modules

| Module | Purpose | Exports | Dependencies |
|--------|---------|---------|--------------|
| `NGC_Agent_Control_Plane` | Seed/registry, kill switches, tasks, approvals | `request_action`, `registry`, pause APIs | Policy Engine, DB tables |
| `NGC_Agent_Policy_Engine` | Decision matrix by action_id | `evaluate` | Control Plane pause flags |
| `NGC_Tool_Gateway` | Least-privilege app tools | `catalogue`, `invoke` | Leads, Social, Schedule |
| `NGC_Mcp_Registry` | MCP server inventory + SSRF | `upsert`, `health_check`, `store_discovery` | `NGC_Mcp_Ssrf`, Secret Vault |
| `NGC_Agent_Gateway_Client` | Signed WP→Gateway HTTP | `health`, `submit_task`, `mcp_discover`, `mcp_execute` | mu-plugin constants |
| `ngt-agent-gateway` | Node A2A + MCP proxy | `/health`, `/v1/tasks`, `/v1/mcp/*` | `@a2a-js/sdk`, first-party executor |

## Seeded control-plane agents (16)

`system-audit`, `security-ops`, `fraud-detection`, `financial-reconciliation`, `tutor-verification`, `tutor-matching`, `scheduling`, `customer-support`, `notification`, `content-marketing`, `compliance`, `observability`, `quality-assurance`, `remediation`, `release-governance`, `safeguarding`

## Data Flow (strict)

1. Unknown agent / tool → reject  
2. Kill switch (global or per-agent) → deny  
3. Policy DENY tools (`shell.unrestricted`, secret exfil, audit disable) → never run  
4. High-impact money/tutor/deploy/delete → `awaiting_approval`  
5. MCP enable requires `capabilities_approved`  
6. Gateway MCP execute requires `tool_approved` + allowlist (`ping`, `business.profile.get`, `health.summary`)

## External Dependencies

- `@a2a-js/sdk` ^1.0 — Agent Gateway A2A mode  
- Docker Compose service `agent-gateway` — staging on host port 8787  
- FluentCRM (optional) — live tutor-lead sync via tool gateway  

## Related Areas

- [MCP free config guide](../GUIDES/MCP-SERVERS-FREE-CONFIG.md)  
- [How to use](../GUIDES/AGENTIC-HOW-TO-USE.md)  
- [INDEX](INDEX.md)
