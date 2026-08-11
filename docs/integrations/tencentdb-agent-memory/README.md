# TencentDB Agent Memory × Bridge AI OS

| Stage | Status |
|-------|--------|
| Stage 1 — Analysis | COMPLETE |
| Stage 2 — Core retrieve/write | IMPLEMENTED (flags default OFF) |

Upstream: https://github.com/TencentCloud/TencentDB-Agent-Memory (`feat/server_team`)

## Docs

| Doc | Title |
|-----|-------|
| [01](01-UPSTREAM-ANALYSIS.md) | Upstream analysis |
| [02](02-CURRENT-BRIDGE-MEMORY-ANALYSIS.md) | Current Bridge memory |
| [03](03-SWOT.md) | SWOT |
| [04](04-GAP-ANALYSIS.md) | Gap analysis |
| [05](05-TARGET-ARCHITECTURE.md) | Target architecture |
| [06](06-IDENTITY-MAPPING.md) | Identity mapping |
| [07](07-DATA-AND-TENANT-BOUNDARIES.md) | Data/tenant boundaries |
| [08](08-CAPABILITY-MAPPING.md) | Capability mapping |
| [09](09-SECURITY-MODEL.md) | Security model |
| [10](10-DEPLOYMENT-PLAN.md) | Deployment plan |
| [11](11-MIGRATION-PLAN.md) | Migration plan |
| [12](12-TEST-PLAN.md) | Test plan |
| [13](13-ROLLBACK-PLAN.md) | Rollback plan |
| [14](14-IMPLEMENTATION-PLAN.md) | Implementation plan |
| [FINAL](FINAL-INTEGRATION-EVIDENCE-REPORT.md) | Stage 2 evidence |

## Approved conditions (enforced)

1. Default SQLite ≠ multi-node HA without approved persistence plan (`sqlite_ha_acknowledged`).
2. Bridge IAM primary; `user_key` behind identity map + `NGC_Secret_Vault`.
3. Proxy never Bridge LLM gateway (`proxy_enabled` forced false).
4. Explicit write policy before long-term tutoring/PII/minors memory.
5. Core → retrieve → write first; Skills/Wiki/CodeGraph separate flags (off).
6. Memory optional: DISABLED/DEGRADED must not break bookings/payments.
