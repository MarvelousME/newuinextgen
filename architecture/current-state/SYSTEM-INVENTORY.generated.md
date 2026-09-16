# SYSTEM-INVENTORY (discover)

Generated: 2026-09-16T18:53:23.928Z

## Operating mode

**BROWNFIELD** — existing WordPress tutoring platform with Companion domain plugin and BeyondInfinity theme.

## Packages

| ID | Folder | Present |
|----|--------|---------|
| beyondinfinity | `NextGenTutors-BeyondInfinity/` | yes |
| companion | `NextGenTutors-Companion/` | yes |
| ai-integration | `NextGenTutors-AI-Integration/` | yes |
| html-importer | `NextGenTutors-Html-Importer/` | yes |
| plugin-manager | `NextGenTutors-Plugin-Manager/` | yes |

## Dependency locks (Composer / npm)

Scanned manifests: **10** (npm 7, composer 3; lockfile present 5).

| Ecosystem | Manifest | Name | Lockfile | Direct deps |
|-----------|----------|------|----------|-------------|
| npm | `e2e/package.json` | nextgentutors-e2e | yes | 2 |
| npm | `nextgen-tutors-google-ai-studio-1st-one/package.json` | react-example | yes | 25 |
| composer | `NextGenTutors-AI-Integration/composer.json` | nextgentutors/ai-integration | no | 0 |
| composer | `NextGenTutors-BeyondMeasure/composer.json` | nextgentutors/beyond-measure | no | 1 |
| npm | `NextGenTutors-BeyondMeasure/package.json` | nextgentutors-beyond-measure | no | 11 |
| npm | `NextGenTutors-Companion/build-src/package.json` | ngc-automation-studio | yes | 8 |
| composer | `NextGenTutors-Companion/composer.json` | nextgentutors/companion | no | 1 |
| npm | `rad-platform/package.json` | rad-platform | no | 0 |
| npm | `scripts/gsapify-extract/package.json` | gsapify-extract | yes | 1 |
| npm | `services/ngt-agent-gateway/package.json` | ngt-agent-gateway | yes | 1 |

## Audit feeders

- OK `.agent-audit/01-repository-inventory.md`
- OK `.agent-audit/02-architecture-current-state.md`
- OK `.agent-audit/11-functional-capability-matrix.md`
- OK `ARCHITECTURE.md`

## Notes

- Discovery refreshes machine snapshot only; curated inventories in `architecture/current-state/*.md` remain authoritative narrative.
- Lockfile scan inventories `composer.json` / `package.json` (+ lock presence); it does not resolve full transitive graphs.
- Run `node rad-platform/cli/gate.mjs` after design/implement changes.
