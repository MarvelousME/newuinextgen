# SECURITY-BOUNDARIES

| Boundary | Control | Status |
|----------|---------|--------|
| Agent actions | `NGC_Agent_Policy_Engine` (deny-by-default for prohibited) | EXISTS |
| Platform admin | `NGC_Authz_Matrix` + caps | EXISTS |
| Capability invoke | `NGC_Policy_Bridge` | ADDED (RAD) |
| AI transport | AI-Integration policy gate | EXISTS |
| Tenant | `NGC_Tenant_Context` | PARTIAL |
| Secrets | WP options / env — not full secret manager | GAP |
| Theme → Companion | contracts only | ENFORCED (static gate) |

Default privileged decision: **DENY** unless policy allows.
