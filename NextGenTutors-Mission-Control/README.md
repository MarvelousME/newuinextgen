# NextGenTutors Mission Control

Master admin panel to **configure, override, repair, and orchestrate** the entire NextGen Tutors stack.

Mission Control is the **default landing page** under the unified **NEXT GEN TUTORS** admin shell (Companion `NGC_Admin_Shell`). It does **not** create its own top-level WordPress menu when Companion is active.

See: `NextGenTutors-Companion/docs/admin/UNIFIED-ADMIN.md`

## Activate

```powershell
# Docker
docker compose --profile setup run --rm --entrypoint wp wpcli plugin activate NextGenTutors-Mission-Control --allow-root
```

Open **WP Admin → NEXT GEN TUTORS → Mission Control** (or `admin.php?page=ngt-admin`).

## Tabs

| Tab | Purpose |
|-----|---------|
| **Status** | Theme, Companion, business profile, demo mode, AI pause, plugin matrix, cron observability |
| **Intelligence** | Live KPIs, Chart.js trends, event grid, notifications, AI briefs (Companion `ngc/v1/intelligence/*`) |
| **Configure** | Configure identity, repair, seed, verify, export, full pipeline |
| **Overrides** | Maintenance mode, force demo/AI flags, force support contact |
| **Control Map** | Links to Business Profile, Demo, NGCPM, Hub, AI, Agent Ops, Page Registry |

## CLI parity

Pipeline actions mirror `wp ngt system` (shared state option `ngt_system_orchestrator_state`).

## Headed e2e

```powershell
cd e2e
$env:BASE_URL='http://localhost:8900'
npm run test:unified-admin-headed
npm run test:mission-control-headed
```

## Release zips

`scripts/build-release.ps1` produces seven packages including this plugin and `ngt-ui-library.zip` (extract to `wp-content/ngt-ui-library`).
