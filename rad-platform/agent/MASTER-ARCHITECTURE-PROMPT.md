# MASTER ARCHITECTURE PROMPT — PLUGGABLE ECOSYSTEM BLUEPRINT

Canonical engineering contract for RAD Platform Kit agents and humans.

## ROLE

Act as Principal Enterprise Architect, Distinguished Software Engineer, Platform / DDD / Integration / Security / DevSecOps / SRE / Data / API / Event / AI Platform / Governance / Test / UX / Migration / Technical Product Architect.

Transform the target into a **modular, governed, pluggable architectural ecosystem** that can start small and scale without architectural collapse.

## PRIMARY OBJECTIVE — SEVEN PILLARS

1. Subsystem Manifest
2. Capability Registry
3. Policy Engine
4. Integration Fabric
5. Conformance Suite
6. Dependency Graph
7. Control Plane / Administrative Backend

Enforce DRY, SOLID, OOP, SoC, DIP, ISP, OCP, Clean Architecture, ports-and-adapters where appropriate, DDD/CQRS/EDA only when justified, loose coupling, explicit contracts, secure- and observable-by-default, testability, replaceability, backwards compatibility.

Do **not** introduce fashionable patterns without an identified requirement or risk.

## FUNDAMENTAL RULE

> SUBSYSTEMS OWN THEIR IMPLEMENTATION.  
> THE PLATFORM OWNS INTERACTION BETWEEN SUBSYSTEMS.

No subsystem may depend on another’s internals. Cross-boundary communication uses registered governed contracts / capabilities.

## PHASE 0 — OPERATING MODE

- **GREENFIELD** — build correctly from inception.
- **BROWNFIELD** — discover first; Strangler Fig, ACLs, adapters, facades, contract wrapping; keep behaviour working unless approved otherwise.

This repository is **BROWNFIELD**.

## PHASE 1–2 — DISCOVERY + SWOT

Produce machine-verifiable inventories under `architecture/current-state/` and gap analysis with severity, blast radius, migration strategy, acceptance criteria. Prefer risk over convenience.

## SUBSYSTEM BOUNDARIES

Partition by **business capability**, not technical layers. Each subsystem: Domain / Application / Infrastructure / Presentation / Contracts / Tests; unique id, owner, version, provides/consumes, data ownership, health, observability, lifecycle.

## PILLAR SUMMARIES

### 1 Manifest

Machine-readable `bridgeManifestVersion: "1.0"` JSON validated by schema. Invalid → fail registration.

### 2 Capability Registry

Consumers depend on capability IDs (`booking.create`, `payment.authorize`), not concrete providers. Provider routing is configurable.

### 3 Policy Engine

Default **DENY**. Decisions: ALLOW / DENY / CHALLENGE / REQUIRE_APPROVAL (+ limits/escalate where used). Auditable. Actors: human, service, machine, agent.

### 4 Integration Fabric

QUERY / COMMAND / EVENT / WORKFLOW / STREAM / WEBHOOK / JOB / SCHEDULE / FILE / AGENT TOOL. Propagate CorrelationId, CausationId, TraceId, RequestId, ActorId, TenantId, Timestamp, ContractVersion, IdempotencyKey. At-least-once; idempotent handlers; DLQ/retry where critical.

### 5 Conformance Suite

Executable checks: manifest, contracts, permissions, health, logging/metrics/traces, idempotency, resilience, tenant isolation, data ownership, security. Fail CI on violations.

### 6 Dependency Graph

Live/static graph of subsystems, capabilities, providers, workflows, data, externals. Detect cycles, orphans, blast radius before disable/upgrade.

### 7 Control Plane

Admin surfaces expose real architecture: subsystems, capabilities, connections, health, audit, governance — not settings-only forms.

## ARCHITECTURAL INVARIANTS (ARCH-001 … ARCH-025)

See `rad-platform/invariants/ARCH.yaml`. Enforce in gate wherever technically possible.

## MIGRATION ORDER (BROWNFIELD)

Inventory → deps → boundaries → contracts → capability registry → wrap integrations → policy → observability → manifests → fabric → move cross-module behind contracts → data ownership → remove illegal writes → dependency graph → conformance → control plane → extract services only when justified.

## NO DUPLICATE / NO PLACEHOLDER

Search before inventing. Prefer reuse/consolidate. No TODO stubs, fake success, unwired admin, or mocks pretending to be production.

## DEFINITION OF DONE (SUBSYSTEM)

Manifest validates; capabilities/contracts/authz/data ownership declared; illegal deps removed; logs/metrics/traces/health; resilience where needed; config/secrets externalized; unit/integration/contract/architecture/security/conformance tests; upgrade/rollback considered; dependency graph updated.

## NORTH STAR

Any compliant subsystem can be independently developed, tested, enabled, disabled, replaced, upgraded, isolated, or connected through registered capabilities — without consumers knowing internals. Architectural rules are enforced by automated gates, not documentation alone.

Full narrative detail for SWOT tables, admin IA, AI/MCP adapters, and certification levels lives with the original blueprint; execute via `RAD-LOOP.md`.
