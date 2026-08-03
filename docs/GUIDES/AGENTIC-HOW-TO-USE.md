# How to use the agentic code

**Last Updated:** 2026-08-03  
**Staging URL:** http://localhost:8890  
**Gateway:** http://localhost:8787/health

This platform is **governed workers + allowlisted tools**, not free-running LLM swarms. Use the layers below in order.

## 1. Start staging (Docker :8890)

```powershell
cd docker
Copy-Item .env.example .env -ErrorAction SilentlyContinue
# Ensure WP_PORT=8890 and NGT_AGENT_GATEWAY_URL=http://agent-gateway:8787
docker compose up -d --build agent-gateway wordpress db
.\scripts\seed-mcp-staging.ps1
```

| Surface | URL / path |
|---------|------------|
| Site | http://localhost:8890 |
| WP Admin | http://localhost:8890/wp-admin (`admin` / from `.env`) |
| Agent Ops | NEXT GEN TUTORS → Agent Ops |
| Agentic / MCP | NEXT GEN TUTORS → Agentic → MCP Servers |
| Gateway health | http://localhost:8787/health |

## 2. Domain agents (control plane)

**Code:** `includes/agents/class-ngc-agent-control-plane.php`

```php
NGC_Agent_Control_Plane::request_action(
  'fraud-detection',
  'agent.case.create',
  [ 'reason' => 'velocity spike', 'booking_id' => 123 ]
);
```

Strict path:

1. Agent must exist in registry  
2. Action must be in that agent’s `tools[]`  
3. `NGC_Agent_Policy_Engine::evaluate` → ALLOW / LIMITS / APPROVAL / DENY / ESCALATE  
4. Task row in `wp_ngc_agent_tasks`  
5. If approval → Agent Ops human decide → then execute  

Kill switches: global pause + per-agent pause in Agent Ops.

## 3. Product tools (marketing / CRM / social / leads)

**Code:** `includes/agentic/class-ngc-tool-gateway.php`

```php
NGC_Tool_Gateway::invoke(
  'crm.upsert_tutor_lead',
  [ 'email' => 'tutor@example.com', 'name' => 'Alex' ],
  [ 'human_approved' => true, 'agent_id' => 'content-marketing' ]
);
```

Mutating tools with `approval => true` refuse without `human_approved` / `approval_id`.  
No SQL, shell, or browser-login tools exist on this gateway.

Tutor lead discovery must use ethical criteria only (`NGC_Lead_Criteria` rejects protected traits).

## 4. Agent Gateway (A2A + MCP)

**Code:** `services/ngt-agent-gateway` + `NGC_Agent_Gateway_Client`

```php
// Diagnostics agent (first-party only)
NGC_Agent_Gateway_Client::submit_task([
  'agent_id' => 'ngt.firstparty.diagnostics',
  'message'  => 'health check',
  'idempotency_key' => wp_generate_uuid4(),
]);

// MCP via gateway (allowlisted tools only)
NGC_Agent_Gateway_Client::mcp_discover( 'https://example.com/mcp' );
NGC_Agent_Gateway_Client::mcp_execute(
  'https://example.com/mcp',
  'ping',
  [],
  true // capability approved
);
```

HMAC headers: `X-NGT-Timestamp` + `X-NGT-Signature` over `ts.POST.path`.

## 5. Cursor IDE MCP (developer only)

See [MCP-SERVERS-FREE-CONFIG.md](MCP-SERVERS-FREE-CONFIG.md) and `.cursor/mcp.json`.  
These servers help **you** build/test; they are **not** registered into WordPress MCP inventory.

## 6. Do / Don’t

| Do | Don’t |
|----|-------|
| Approve high-impact actions in Agent Ops | Enable remote MCP without capability review |
| Keep product MCP allowlist tiny | Register filesystem/shell/playwright into WP |
| Use OAuth for social | Store social passwords |
| Label demo data | Fabricate gateway/audit evidence |

## Related

- [Agentic codemap](../CODEMAPS/agentic.md)  
- [Free MCP config](MCP-SERVERS-FREE-CONFIG.md)  
- [Architecture skills](ai-agents-architect patterns: policy gate, tool registry, supervisor ≠ unconstrained autonomy)
