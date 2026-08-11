# RAD LOOP — Discover → Design → Implement → Gate → Evidence

Mandatory operating procedure for agents working under the RAD Platform Kit.

## Before any substantial change

1. Read `MASTER-ARCHITECTURE-PROMPT.md`.
2. Determine BROWNFIELD vs GREENFIELD (this repo: **BROWNFIELD**).
3. Search for existing capabilities, adapters, registries, and services — **reuse before invent**.

## Loop

### 1. Discover

```bash
node rad-platform/cli/discover.mjs
```

Review `architecture/current-state/` and `ARCHITECTURE.md`. Identify blast radius.

### 2. Design

- Map change to subsystem(s) and capability IDs.
- Update or propose manifests / capabilities / dependency-rules / contracts.
- Record intentional duplication justification if any.
- Prefer Strangler Fig / ACL over rewrite.

### 3. Implement

- Scaffold from `rad-platform/templates/subsystem/` only when creating a new boundary.
- Wire through capabilities and policy bridge; no cross-package internal requires.
- Preserve existing behaviour unless fixing a defect/security/architecture violation.

### 4. Gate

```bash
node rad-platform/cli/validate.mjs
node rad-platform/cli/gate.mjs
node rad-platform/cli/gate.mjs --fixtures
```

**Do not claim done if gate fails.** Fix findings or update allowlisted dependency-rules with justification + ADR.

### 5. Evidence

```bash
node rad-platform/cli/graph.mjs
node rad-platform/cli/evidence.mjs
```

Attach or cite `architecture/reports/gate-report.json` and compliance report paths in the completion summary.

## Done checklist

Use `checklists/subsystem-dod.md`. For architecture work, also require:

- [ ] Gate PASS (or documented BLOCKED with evidence)
- [ ] Manifests/capabilities updated if surface area changed
- [ ] No new direct cross-subsystem implementation coupling
- [ ] Policy path for privileged operations
- [ ] Honest compliance verdict (never invent PRODUCTION READY)

## Complementary policy

This loop complements `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md` (demo Phase 14, security, finance). RAD owns architecture governance; the master directive owns product audit/demo readiness.
