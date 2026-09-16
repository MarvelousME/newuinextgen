# Codemaps Index

**Last Updated:** 2026-09-16  
**Scope:** NextGen Tutors (`newuinextgen/`) — WordPress Companion domain + Agent Gateway + RAD governance

## Maps

| Area | File | Purpose |
|------|------|---------|
| Platform / domain | [platform.md](platform.md) | Theme → Companion → sidecars; modules; Policy Bridge |
| Agentic / MCP / A2A | [agentic.md](agentic.md) | Control plane, tool gateway, MCP, Agent Gateway |
| How to use | [../GUIDES/AGENTIC-HOW-TO-USE.md](../GUIDES/AGENTIC-HOW-TO-USE.md) | Operator + developer runbook |
| Free MCP config | [../GUIDES/MCP-SERVERS-FREE-CONFIG.md](../GUIDES/MCP-SERVERS-FREE-CONFIG.md) | Cursor vs product MCP inventory |
| Delivery registry | [../../delivery/MCP-SERVER-REGISTRY.md](../../delivery/MCP-SERVER-REGISTRY.md) | Verified MCP behaviour |
| Debt register | [../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md](../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md) | TD-RAD status |
| Workspace SoT | [../../../SOURCE-OF-TRUTH.md](../../../SOURCE-OF-TRUTH.md) | Canonical vs feedstock (WeTransfer root) |

## Runtime entry points

| Layer | Entry |
|-------|-------|
| WordPress staging | http://localhost:8890 |
| Agent Gateway | http://localhost:8787/health |
| Ecosystem API | http://localhost:8790/health (overlay) |
| Compose project | `docker/` (`COMPOSE_PROJECT_NAME=newuinextgen`) |
| RAD CLI | `node rad-platform/cli/{discover,validate,gate}.mjs` |
| Cursor MCP | `.cursor/mcp.json` |
| Product MCP seed | `config/mcp-staging-servers.json` |

## Edit roots

| Concern | Canonical path |
|---------|----------------|
| Theme PHP/CSS | `NextGenTutors-BeyondInfinity/` (TutorFabulous brand; text domain `beyondinfinity`) |
| Domain | `NextGenTutors-Companion/` |
| Control plane SaaS | workspace-root `ecosystem-platform/` |
| Page build | workspace-root `elementor-page-build/` |
