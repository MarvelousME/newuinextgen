# SECURITY-BOUNDARIES

**Last Updated:** 2026-09-16

| Boundary | Control | Status |
|----------|---------|--------|
| Agent actions | `NGC_Agent_Policy_Engine` (deny-by-default for prohibited) | EXISTS |
| Platform admin | `NGC_Authz_Matrix` + caps | EXISTS |
| Capability invoke / domain mutate | `NGC_Policy_Bridge` (`authorize_domain` / `authorize_invoke`) | **ACTIVE** on matching / bookings / payments core paths |
| AI transport | AI-Integration policy gate | EXISTS |
| Tenant | `NGC_Tenant_Context` | PARTIAL |
| Secrets | `NGC_Secret_Vault` — `env:NAME` + encrypted options; compose secrets required | **MITIGATED** (external HashiCorp/AWS SM optional) |
| Payment settle | WC hook / cron / explicit `trusted_system` only — not bare uid=0 | **HARDENED** |
| MCP / Gateway | HMAC + SSRF + tool allowlist | EXISTS |
| Theme → Companion | contracts only (shortcode / REST) | ENFORCED (static gate) |
| Legacy Core dual-activation | `NGC_Legacy_Plugin_Guard` + Plugin-Manager deny-list | EXISTS |

Default privileged decision: **DENY** unless policy allows.

See also: [ARCHITECTURE-RISKS.md](ARCHITECTURE-RISKS.md), [CAPABILITY-INVENTORY.md](CAPABILITY-INVENTORY.md), [docs/architecture/SYSTEM-ARCHITECTURE-REFERENCE.md](../../docs/architecture/SYSTEM-ARCHITECTURE-REFERENCE.md).
