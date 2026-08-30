# ecosystem-platform

Sovereign Multi-Tenant Agentic **Ecosystem-as-a-Service** control plane for the NextGen monorepo.

Odoo Community is a **replaceable business engine** behind provider contracts — not the product UI.

## Tiers

| Tier | Path | Role |
|------|------|------|
| Experience | `apps/control-center`, `packages/nextgen-ui` | Next.js + Kinetic design system |
| Control plane | `services/platform-api`, `packages/platform-core` | Tenants, blueprints, IAM, provisioning |
| Business | `packages/odoo-adapter`, `platforms/odoo` | CRM/sales/invoicing via adapters |
| Digital | `bridges/wordpress` | WordPress/Companion boundary |
| Automation | `packages/event-bus` | Canonical events (+ RabbitMQ when configured) |
| Intelligence | agent-gateway (repo `services/ngt-agent-gateway`) | MCP/tools — tenant-scoped |

## Quick start

```bash
cd ecosystem-platform
npm install
npm test
npm run dev:api
```

API default: `http://localhost:8790`

**Authentication:** all `/api/v1/*` routes require `Authorization: Bearer <ECOSYSTEM_API_TOKEN>`. Client `X-User-Id` is ignored for privilege. Set `ECOSYSTEM_API_TOKEN` to a strong secret (weak sentinels are rejected). The Control Center stores the token in browser session storage only.

With full stack (Odoo, Postgres, Redis, RabbitMQ):

```bash
cd docker
docker compose -f docker-compose.yml -f docker-compose.ecosystem.yml up -d
```

## API (platform-owned)

- `GET /health` — no auth
- `GET /api/v1/platform/overview` — Bearer platform token
- `GET /api/v1/platform/tenants` — Bearer platform token
- `POST /api/v1/platform/tenants` — create tenant
- `POST /api/v1/platform/tenants/:id/provision` — run blueprint provisioner
- `GET /api/v1/tenants/:tenantId/customers` — Bearer platform token (tenant header + membership)

See `.agent-audit/ECOSYSTEM-PLATFORM-VERIFICATION-MASTER-PROMPT.md` for verification gates.
