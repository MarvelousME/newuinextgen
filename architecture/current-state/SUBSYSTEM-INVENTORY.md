# SUBSYSTEM-INVENTORY

**Last Updated:** 2026-09-16

| Subsystem ID | Owner | Maturity | Manifest | Notes |
|--------------|-------|----------|----------|-------|
| beyondinfinity | platform-frontend | L1 Registered | `architecture/manifests/beyondinfinity.json` | Dual tree risk (root theme overlays); edit `NextGenTutors-BeyondInfinity/` |
| companion | platform-domain | L2 Governed (partial) | `architecture/manifests/companion.json` | Modular monolith; Policy Bridge on core mutations |
| companion.matching | companion | L2 | internal module | `includes/matching/` via `NGC_Module_Registry` |
| companion.payments | companion | L2 | internal module | `includes/payments/` + PayFast |
| companion.ai | companion | L2 | internal module | BYOK suite `includes/ai/` |
| companion.integrations | companion | L2 | internal module | adapters / gateways |
| companion.platform | companion | L2 | internal module | Policy Bridge, Authz, Observability, capabilities |
| ai-integration | platform-ai-governance | L1 Registered | `architecture/manifests/ai-integration.json` | No domain ownership |
| html-importer | platform-ops | L1 Registered | `architecture/manifests/html-importer.json` | Must not touch ngc_* |
| plugin-manager | platform-ops | L1 Registered | `architecture/manifests/plugin-manager.json` | No tutor data |
| agent-gateway | platform-agents | L2 | `services/ngt-agent-gateway/` | SQLite durable tasks; HMAC |
| rad-platform | platform-governance | L2 | `rad-platform/` | discover / validate / gate in CI |

Future extractions contexts from Companion (not yet separate deployables): Identity, Bookings package, Notifications, AgentRuntime as standalone services.
