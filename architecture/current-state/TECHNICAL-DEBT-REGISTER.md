# TECHNICAL-DEBT-REGISTER

| ID | Debt | Impact | Exit criteria |
|----|------|--------|---------------|
| TD-RAD-001 | Capabilities not yet wrapping all domain services | Parallel call paths | Privileged paths use Policy Bridge |
| TD-RAD-002 | No Connection Designer / Workflow Designer UI | Ops visibility gap | Deferred beyond D+3 horizon |
| TD-RAD-003 | Static dep graph incomplete for Composer/npm | Blind spots | Expand discover scanners |
| TD-RAD-004 | Hub overlap | Dual finance risk | Hub disabled when Companion authority |
| TD-RAD-005 | Secrets not in external secret manager | ARCH-012 gap | Integrate vault/env secret store |
| TD-RAD-006 | Full OpenTelemetry export | Observability gap | OTEL exporter when justified |

## SWOT (summary)

**Strengths:** Sacred contracts, adapters, agent policy engine, platform kernel (queue/DLQ/audit), ADRs.  
**Weaknesses:** Coupling/duplication (Hub), incomplete capability SSOT historically, no architecture CI.  
**Opportunities:** RAD kit enforcement, incremental capability extraction, unified admin via Platform Kernel.  
**Threats:** Cascading finance bugs, schema breakage, agent over-privilege, silent architecture decay.
