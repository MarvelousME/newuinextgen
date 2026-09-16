# TECHNICAL-DEBT-REGISTER

| ID | Debt | Impact | Exit criteria | Status |
|----|------|--------|---------------|--------|
| TD-RAD-001 | Capabilities not yet wrapping all domain services | Parallel call paths | Privileged paths use Policy Bridge | Open |
| TD-RAD-002 | No Connection Designer / Workflow Designer UI | Ops visibility gap | Deferred beyond D+3 horizon | Deferred |
| TD-RAD-003 | Static dep graph incomplete for Composer/npm | Blind spots | Expand discover scanners | Open |
| TD-RAD-004 | Hub overlap | Dual finance risk | Hub disabled when Companion authority | **Mitigated** — quiet-domain when `NGC_Plugin` active (matching/finance skipped); smoke `nextgen-automation-hub/tests/run-delegate-smoke.php` |
| TD-RAD-005 | Secrets not in external secret manager | ARCH-012 gap | Integrate vault/env secret store | **Partial** — compose requires env for MySQL/gateway secrets; `scripts/check-no-default-secrets.php`; external vault still pending |
| TD-RAD-006 | Full OpenTelemetry export | Observability gap | OTEL exporter when justified | Open |

## Remediation campaign notes (2026-09)

- Ecosystem compose paths: `../../ecosystem-platform` from `docker/`.
- Companion internal modules: matching, payments, ai, integrations, platform (`NGC_Module_Registry`).
- Agent Gateway: SQLite durable task store (`node:sqlite`, Node ≥22).
- Ecosystem API: Bearer fail-closed; production empty token exits.
- CI: `rad-architecture` job runs `validate.mjs` + `gate.mjs`.

## SWOT (summary)

**Strengths:** Sacred contracts, adapters, agent policy engine, platform kernel (queue/DLQ/audit), ADRs, RAD-in-CI.  
**Weaknesses:** Capability SSOT incomplete; Hub still installable (quiet when Companion on); no external secret manager yet.  
**Opportunities:** Full capability extraction, unified admin via Platform Kernel, vault integration.  
**Threats:** Schema breakage, agent over-privilege, silent architecture decay if RAD gate bypassed.
