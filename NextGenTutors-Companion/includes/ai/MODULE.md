# AI module

Companion domain module for the multi-model BYOK AI suite and governed agentic tooling.

Registry id: `ai` → `includes/ai/bootstrap.php`  
Autoload: `includes/ai/`, `includes/agentic/` (+ subdirs) via `ngc_autoload` in `nextgencompanion.php`.

## Owns

### `includes/ai/` — AI suite

| Class | Role |
|-------|------|
| `NGC_AI_Models` | BYOK multi-model registry (encrypted keys) |
| `NGC_AI_Agents` | Supervised agent registry (skills, caps) |
| `NGC_AI_Chat` | Chat / inference orchestration against registered models |
| `NGC_Crypto` | Key material helpers for BYOK storage |
| `BIA_Policy` | Policy chokepoint (caps, egress allowlist, PII, audit) |

### `includes/agentic/` — agentic surface (same module; files stay here)

| Area | Classes (examples) |
|------|--------------------|
| Gateway / vault | `NGC_Tool_Gateway`, `NGC_Agent_Gateway_Client`, `NGC_Secret_Vault` |
| MCP / A2A | `NGC_MCP_Registry`, `NGC_MCP_SSRF`, `NGC_A2A_Gateway` |
| Social | `NGC_Social_Connections`, `NGC_Social_OAuth` |
| Leads / outreach | `NGC_Lead_Criteria`, `NGC_Tutor_Leads`, `NGC_Outreach_Engine` |
| Content | `NGC_Publish_Worker` |

Agentic code remains under `includes/agentic/` (including subdirs). Do not move it into `includes/ai/`.

## Does not own

- **Provider HTTP transport** — lives in the **AI-Integration** plugin (Companion registers models/policy; Integration performs outbound provider calls).
- **Matching / scoring** — `includes/matching/` (Agent G).
- **Payments / payouts** — `includes/payments/` (Agent H).
- **Amelia / PayFast / LMS / CRM bridges** — `includes/integrations/` + `includes/adapters/` (Agent J), except agentic CRM/social tool *allowlists* which stay here.
- **Agent control plane** — `includes/agents/` (`NGC_Agent_Control_Plane`, safeguarding, fraud, policy engine, event envelope). Adjacent governance; not the AI/agentic suite tree.
- Automation Hub domain registration, Docker, or architecture plan docs.

## Boundaries (quick)

```
Companion AI module          AI-Integration plugin
─────────────────            ─────────────────────
Models, agents, chat         Provider HTTP clients
BIA_Policy / crypto          Transport & credentials glue
Tool gateway / MCP / A2A     (none — Companion owns)
Social OAuth + leads         (none — Companion owns)
```

Consumers may call AI/agentic classes after `ngc_autoload` is registered; this bootstrap is intentionally a no-op for runtime behavior.
