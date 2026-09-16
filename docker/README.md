# NextGen Tutors — Docker (newuinextgen)

Local WordPress with the **parent workspace folder** bind-mounted as the active theme (`nextgentutors-beyondinfinity`), plus the **Agent Gateway** for A2A/MCP staging.

## Quick start

```powershell
cd docker
Copy-Item .env.example .env
# Edit .env: replace every CHANGE_ME_* with strong unique secrets (required).
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

**Login:** use `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` from your local `docker/.env` (never commit `.env`).

## Secrets (required)

Compose **does not** ship default values for:

| Env | Purpose |
|-----|---------|
| `MYSQL_PASSWORD` | WordPress DB user password |
| `MYSQL_ROOT_PASSWORD` | MySQL root password |
| `NGT_GATEWAY_SHARED_SECRET` | Shared secret between WordPress and Agent Gateway |

Copy `.env.example` → `.env` and replace `CHANGE_ME_*` placeholders. Non-secret names (`MYSQL_DATABASE`, `MYSQL_USER`) may keep example defaults.

### Rotate secrets

If a secret was ever committed, shared in chat, or matches an old known default (`wordpress`, `rootpass`, `staging-local-secret`):

1. Generate new values (password manager or `openssl rand -hex 32`).
2. Update `docker/.env` with the new `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, and/or `NGT_GATEWAY_SHARED_SECRET`.
3. Recreate affected services so containers pick up env:
   ```powershell
   docker compose up -d --force-recreate db agent-gateway wordpress
   ```
4. **MySQL passwords:** changing `MYSQL_*` after the volume already exists does **not** update the live DB. Either:
   - Local disposable data: `docker compose down -v` then `up` (destroys DB volume), or
   - Keep data: connect as root and `ALTER USER` / set WordPress DB password to match `.env`.
5. Confirm gateway calls work (Companion / MCP smoke) with the new shared secret.
6. Run the repo check: `php ../scripts/check-no-default-secrets.php` from `docker/` (or `php scripts/check-no-default-secrets.php` from the `newuinextgen` root).

`.env` is gitignored under `docker/`. Never commit real secrets.

## Agent Gateway + MCP

| Env | Notes |
|-----|-------|
| `WP_PORT` | Default `8890` |
| `NGT_AGENT_GATEWAY_URL` | Default `http://agent-gateway:8787` (in-container DNS) |
| `NGT_GATEWAY_SHARED_SECRET` | **Required** via `.env` — no compose/PHP hardcoded default |
| `NGT_GATEWAY_HOST_PORT` | Default `8787` |

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
php ../scripts/check-no-default-secrets.php
```

## Ports

Defaults use **8890** (WordPress), **8787** (Agent Gateway), and **8082** (phpMyAdmin).

## Ecosystem platform overlay

With `docker compose -f docker-compose.yml -f docker-compose.ecosystem.yml up -d`:

| Service | URL | Notes |
|---------|-----|-------|
| Control Center / API | http://localhost:8790 | Loopback only (`ECOSYSTEM_BIND=127.0.0.1`) |
| RabbitMQ management | http://localhost:15672 | Loopback only |
| Odoo | http://localhost:8069 | Loopback only |

Set a strong `ECOSYSTEM_API_TOKEN` in `.env` before using tenant/provision APIs. The Control Center prompts for this token at runtime (session storage). Do **not** use weak values like `platform-super-admin` or `admin`.

On LAN/VPS deployments: override all default passwords, keep `ECOSYSTEM_BIND=127.0.0.1`, and never expose `:8790` without Bearer auth.

## Companion plugin

**NextGenTutors-Companion** provides forms, dashboards, smart matching, agentic control plane, and MCP registry.

```powershell
cd docker
.\scripts\install-companion.ps1
```
