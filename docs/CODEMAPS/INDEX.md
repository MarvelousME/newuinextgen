# Codemaps Index

**Last Updated:** 2026-09-17  
**Scope:** NextGen Tutors (`newuinextgen/`) — WordPress Companion domain + Agent Gateway + RAD governance  
**Stack versions:** Theme `BI_VERSION` **2.1.1** · Companion `NGC_VERSION` **1.9.22**

## Maps

| Area | File | Purpose |
|------|------|---------|
| Platform / domain | [platform.md](platform.md) | Theme → Companion → sidecars; modules; Policy Bridge |
| Companion domain | [companion-domain.md](companion-domain.md) | Matching, bookings, payments, REST, data ownership |
| Session commerce | [session-commerce.md](session-commerce.md) | Booking → Woo → session → MasterStudy → launch |
| Agentic / MCP / A2A | [agentic.md](agentic.md) | Control plane, tool gateway, MCP, Agent Gateway |
| Theme / presentation | [theme.md](theme.md) | TutorFabulous edit root (BeyondInfinity = legacy alias), UI Library, Elementor |
| Services / sidecars | [services.md](services.md) | Agent Gateway, Talent NLP, MCP mock |
| RAD / governance | [rad.md](rad.md) | Discover, validate, gate, sacred packages |
| How to use | [../GUIDES/AGENTIC-HOW-TO-USE.md](../GUIDES/AGENTIC-HOW-TO-USE.md) | Operator + developer runbook |
| Free MCP config | [../GUIDES/MCP-SERVERS-FREE-CONFIG.md](../GUIDES/MCP-SERVERS-FREE-CONFIG.md) | Cursor vs product MCP inventory |
| Delivery registry | [../../delivery/MCP-SERVER-REGISTRY.md](../../delivery/MCP-SERVER-REGISTRY.md) | Verified MCP behaviour |
| Debt register | [../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md](../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md) | TD-RAD status |
| System architecture | [../architecture/SYSTEM-ARCHITECTURE-REFERENCE.md](../architecture/SYSTEM-ARCHITECTURE-REFERENCE.md) | Long-form technical reference |
| System overview | [../SYSTEM-OVERVIEW.md](../SYSTEM-OVERVIEW.md) | Whole-system map |
| Workspace SoT | [../../../SOURCE-OF-TRUTH.md](../../../SOURCE-OF-TRUTH.md) | Canonical vs feedstock (WeTransfer root) |

## Runtime entry points

| Layer | Entry |
|-------|-------|
| WordPress staging | http://localhost:8890 |
| Agent Gateway | http://localhost:8787/health |
| Ecosystem API | http://localhost:8790/health (overlay) |
| Compose project | `docker/` (`COMPOSE_PROJECT_NAME=newuinextgen`) |
| RAD CLI | `node rad-platform/cli/{discover,validate,gate}.mjs` |
| Companion tests | `php NextGenTutors-Companion/tests/run.php` |
| Session unit | `php NextGenTutors-Companion/tests/run-session-unit.php` |
| Cursor MCP | `.cursor/mcp.json` |
| Product MCP seed | `config/mcp-staging-servers.json` |

## Edit roots

| Concern | Canonical path |
|---------|----------------|
| Theme PHP/CSS | `NextgenTutors-TutorFabulous/` (text domain `beyondinfinity`; `NextGenTutors-BeyondInfinity/` = legacy alias only) |
| Domain | `NextGenTutors-Companion/` |
| Control plane SaaS | workspace-root `ecosystem-platform/` |
| Page build | workspace-root `elementor-page-build/` |
| Architecture contracts | `architecture/` + `rad-platform/` |

## Reading paths

| Audience | Start |
|----------|-------|
| New developer | [../SYSTEM-OVERVIEW.md](../SYSTEM-OVERVIEW.md) → [platform.md](platform.md) → [companion-domain.md](companion-domain.md) → [session-commerce.md](session-commerce.md) |
| Architect | [../architecture/SYSTEM-ARCHITECTURE-REFERENCE.md](../architecture/SYSTEM-ARCHITECTURE-REFERENCE.md) → [rad.md](rad.md) |
| Ops / agents | [agentic.md](agentic.md) → [../GUIDES/AGENTIC-HOW-TO-USE.md](../GUIDES/AGENTIC-HOW-TO-USE.md) |
| Frontend / theme | [theme.md](theme.md) → `ARCHITECTURE.md` (repo root) |
