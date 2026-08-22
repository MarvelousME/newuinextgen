---
name: rad-platform
description: Enforce Rapid Application Development via the seven-pillar pluggable ecosystem kit (manifests, capabilities, policy, fabric, conformance, dependency graph, control plane). Use when changing architecture, adding subsystems/capabilities, cross-package integration, platform governance, or when the user mentions RAD, architecture gate, manifests, or capability registry.
---

# RAD Platform Skill

## When to use

- Adding or changing subsystems, capabilities, contracts, or cross-package dependencies
- Platform / architecture / governance / policy work
- User asks for RAD loop, architecture compliance, or seven pillars

## Instructions

1. Read `rad-platform/agent/RAD-LOOP.md` and follow it strictly.
2. Reference `rad-platform/agent/MASTER-ARCHITECTURE-PROMPT.md` for invariants.
3. Search the codebase for existing capabilities/adapters before creating new ones.
4. Update `architecture/manifests/`, `architecture/capabilities/`, and `architecture/dependency-rules/` when surface area changes.
5. Run gates:

```bash
node rad-platform/cli/discover.mjs
node rad-platform/cli/validate.mjs
node rad-platform/cli/gate.mjs
node rad-platform/cli/evidence.mjs
```

6. Complete `rad-platform/agent/checklists/subsystem-dod.md` for new/changed subsystems.
7. Cite evidence paths (`architecture/reports/gate-report.json`, compliance report) in the final summary.
8. Never bypass policy for privileged operations; use `NGC_Policy_Bridge` when invoking capabilities from agents/services.
