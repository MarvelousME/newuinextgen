# NextGen Tutors — Docker (newuinextgen)

Local WordPress with the **parent workspace folder** bind-mounted as the active theme (`nextgentutors-beyondinfinity`), plus the **Agent Gateway** for A2A/MCP staging.

## Quick start

```powershell
cd docker
Copy-Item .env.example .env
.\start.ps1
# Or explicitly:
docker compose up -d --build db agent-gateway wordpress
.\scripts\seed-mcp-staging.ps1
```

| Service | URL |
|---------|-----|
| WordPress | http://localhost:8890 |
| Admin | http://localhost:8890/wp-admin |
| Agent Gateway | http://localhost:8787/health |
| phpMyAdmin | http://localhost:8082 |

**Login:** `admin` / `NextGenAdmin!2026` (override in `.env`)

## Agent Gateway + MCP

| Env | Default |
|-----|---------|
| `WP_PORT` | `8890` |
| `NGT_AGENT_GATEWAY_URL` | `http://agent-gateway:8787` (in-container DNS) |
| `NGT_GATEWAY_SHARED_SECRET` | `staging-local-secret` (local only) |
| `NGT_GATEWAY_HOST_PORT` | `8787` |

- Seed product MCP inventory: `.\scripts\seed-mcp-staging.ps1`
- Docs: [MCP free config](../docs/GUIDES/MCP-SERVERS-FREE-CONFIG.md), [How to use agentic](../docs/GUIDES/AGENTIC-HOW-TO-USE.md)

**Do not** register Cursor filesystem/shell/playwright MCPs into WordPress.

## What gets mounted

| Host path | Container path |
|-----------|----------------|
| Theme + plugins (see compose) | `wp-content/...` |
| `../config/` | `/var/www/config` (includes `mcp-staging-servers.json`) |
| `docker/mu-plugins/` | `wp-content/mu-plugins` |

## Commands

```powershell
docker compose up -d --build
docker compose down
docker compose logs -f wordpress agent-gateway
docker compose --profile setup run --rm wpcli
.\scripts\seed-mcp-staging.ps1
```

## Ports

Defaults use **8890** (WordPress), **8787** (Agent Gateway), and **8082** (phpMyAdmin).

## Companion plugin

**NextGenTutors-Companion** provides forms, dashboards, smart matching, agentic control plane, and MCP registry.

```powershell
cd docker
.\scripts\install-companion.ps1
```
