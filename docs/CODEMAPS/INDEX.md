# Codemaps Index

**Last Updated:** 2026-08-03  
**Scope:** NextGen Tutors agentic platform (Companion + Agent Gateway + Docker :8890)

## Maps

| Area | File | Purpose |
|------|------|---------|
| Agentic / MCP / A2A | [agentic.md](agentic.md) | Control plane, tool gateway, MCP, Agent Gateway |
| How to use | [../GUIDES/AGENTIC-HOW-TO-USE.md](../GUIDES/AGENTIC-HOW-TO-USE.md) | Operator + developer runbook |
| Free MCP config | [../GUIDES/MCP-SERVERS-FREE-CONFIG.md](../GUIDES/MCP-SERVERS-FREE-CONFIG.md) | Cursor vs product MCP inventory |
| Delivery registry | [../../delivery/MCP-SERVER-REGISTRY.md](../../delivery/MCP-SERVER-REGISTRY.md) | Verified MCP behaviour |

## Runtime entry points

| Layer | Entry |
|-------|-------|
| WordPress staging | http://localhost:8890 |
| Agent Gateway | http://localhost:8787/health |
| Compose project | `docker/` (`COMPOSE_PROJECT_NAME=newuinextgen`) |
| Cursor MCP | `.cursor/mcp.json` |
| Product MCP seed | `config/mcp-staging-servers.json` |
