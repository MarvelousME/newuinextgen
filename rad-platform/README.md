# RAD Platform Kit

Portable **Rapid Application Development** architecture kit that enforces the seven-pillar pluggable ecosystem blueprint.

## Pillars

1. Subsystem Manifest
2. Capability Registry
3. Policy Engine
4. Integration Fabric
5. Conformance Suite
6. Dependency Graph
7. Control Plane / Administrative Backend

## RAD loop

```text
discover → design → implement → gate → evidence
```

| Command | Purpose |
|---------|---------|
| `node rad-platform/cli/discover.mjs` | Refresh current-state inventory snapshot |
| `node rad-platform/cli/validate.mjs` | Schema-validate manifests + capabilities |
| `node rad-platform/cli/graph.mjs` | Emit dependency graph JSON + DOT |
| `node rad-platform/cli/gate.mjs` | Architecture fitness gate (CI) |
| `node rad-platform/cli/gate.mjs --fixtures` | Prove invalid manifests fail |
| `node rad-platform/cli/evidence.mjs` | Compliance report + scorecard |

Requires **Node 18+**. No npm install required for the CLI.

## Drop into another repo

1. Copy `rad-platform/` into the target repository.
2. Create `architecture/{manifests,capabilities,contracts,policies,dependency-rules,current-state,reports,decisions}/`.
3. Author subsystem manifests conforming to `schemas/subsystem-manifest.schema.json`.
4. Wire CI to `node rad-platform/cli/gate.mjs`.
5. Point agents at `agent/RAD-LOOP.md` and `agent/MASTER-ARCHITECTURE-PROMPT.md`.

## WordPress binder

See `adapters/wordpress/README.md` and Companion classes:

- `NGC_Subsystem_Registry`
- `NGC_Capability_Registry`
- `NGC_Policy_Bridge`

## Agent enforcement

- Cursor rule: `.cursor/rules/rad-platform-ecosystem.mdc`
- Cursor skill: `.cursor/skills/rad-platform/SKILL.md`
