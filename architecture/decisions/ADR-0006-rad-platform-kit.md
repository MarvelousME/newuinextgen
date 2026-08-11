# ADR-0006 — Adopt RAD Platform Kit

## Context

The monorepo has sacred package boundaries (`ARCHITECTURE.md`) and partial platform pieces (policy engine, adapters, platform kernel) but lacked machine-enforced subsystem manifests, a unified capability registry, and CI architecture fitness gates.

## Problem

Agents and humans could introduce cross-package coupling and duplicate implementations without automated failure.

## Decision

Adopt the portable `rad-platform/` kit with Architecture-as-Code under `architecture/`, Cursor RAD rule/skill, and Companion binder registries. Enforce via `node rad-platform/cli/gate.mjs` in CI.

## Alternatives

- Documentation-only ADRs (rejected — not enforceable)
- Full microservices extraction (rejected — premature)
- Rewrite Companion as capability mesh (rejected — big-bang)

## Consequences

- New cross-package work must declare capabilities/manifests
- Gate may false-positive on heuristic scans — use dependency-rules allowlist carefully
- Control-plane UI remains minimal (Platform Kernel admin section)

## Risks

Heuristic PHP boundary scans; dual admin surfaces.

## Migration impact

Additive only; no behaviour change to booking/payment paths beyond policy bridge hook.

## Rollback

Remove CI job and binder init; manifests remain documentation.
