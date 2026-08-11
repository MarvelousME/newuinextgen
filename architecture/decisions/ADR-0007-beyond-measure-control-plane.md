# ADR-0007 — Beyond Measure Control Plane

## Status

Accepted

## Context

Subsystem-specific PHP admin screens (Talent, Memory, Kernel, Mission Control) do not scale as the ecosystem grows. Each screen reinvents navigation, permissions, tables, health, and audit.

## Decision

Ship **NextGenTutors Beyond Measure** as a separate WordPress plugin that owns administration:

- React + TypeScript SPA in `wp-admin`
- WordPress/PHP remains auth, RBAC, REST, orchestration, persistence authority
- Subsystems register metadata (capabilities, resources, config, health)
- Domain logic stays in Companion / bridges

Mission Control and Companion Kernel/Talent/Memory PHP UIs coexist (strangler); Beyond Measure is the preferred entry under `ngt-admin`.

## Consequences

- New package: `NextGenTutors-BeyondMeasure/`
- REST namespace: `nextgentutors-control/v1`
- RAD manifests/capabilities/contracts updated
- No Companion internal requires from Beyond Measure (REST + public hooks only)
