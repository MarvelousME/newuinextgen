# TECHNICAL-DEBT-REGISTER

| ID | Debt | Impact | Exit criteria | Status |
|----|------|--------|---------------|--------|
| TD-RAD-001 | Capabilities not yet wrapping all domain services | Parallel call paths | Privileged paths use Policy Bridge | **Mitigated** — `matching.propose`, `booking.create`, `payment.authorize` authorize via `NGC_Policy_Bridge::authorize_domain()`; tests in `tests/run.php` |
| TD-RAD-002 | No Connection Designer / Workflow Designer UI | Ops visibility gap | Deferred beyond D+3 horizon | Deferred |
| TD-RAD-003 | Static dep graph incomplete for Composer/npm | Blind spots | Expand discover scanners | **Done** — `rad-platform/cli/lib/scan-lockfiles.mjs` + `discover.mjs` emits `dependencyLocks` |
| TD-RAD-004 | Hub overlap | Dual finance risk | Hub disabled when Companion authority | **Mitigated** — quiet-domain when `NGC_Plugin` active; smoke `nextgen-automation-hub/tests/run-delegate-smoke.php` |
| TD-RAD-005 | Secrets not in external secret manager | ARCH-012 gap | Integrate vault/env secret store | **Mitigated** — `NGC_Secret_Vault` supports `env:NAME` refs + encrypted option vault; compose secrets required; `scripts/check-no-default-secrets.php`. External HashiCorp/AWS SM still optional ops upgrade |
| TD-RAD-006 | Full OpenTelemetry export | Observability gap | OTEL exporter when justified | **Partial** — `NGC_Platform_Observability::export_span()` + `ngc_otel_span` hook when `OTEL_EXPORTER_OTLP_ENDPOINT` set; full OTLP SDK remains ops/Tempo (ADR-005) |

## Remediation campaign notes (2026-09)

- Ecosystem compose paths: `../../ecosystem-platform` from `docker/`.
- Companion internal modules: matching, payments, ai, integrations, platform (`NGC_Module_Registry`).
- Agent Gateway: SQLite durable task store (`node:sqlite`, Node ≥22).
- Ecosystem API: Bearer fail-closed; production empty token exits.
- CI: `rad-architecture` job runs `validate.mjs` + `gate.mjs`.
- Discover inventories npm/composer manifests + lockfile presence.
- Privileged domain entrypoints call Policy Bridge; WC settlement uses `trusted_system` when no interactive user.

## SWOT (summary)

**Strengths:** Sacred contracts, adapters, agent policy engine, platform kernel, ADRs, RAD-in-CI, Policy Bridge on core mutations, env+option vault.  
**Weaknesses:** Not every REST helper is bridge-wrapped; Hub still installable (quiet when Companion on); OTLP not in-process.  
**Opportunities:** Full capability extraction on remaining surfaces; external secret manager; Tempo collector wiring.  
**Threats:** Schema breakage, agent over-privilege, silent architecture decay if RAD gate bypassed.
