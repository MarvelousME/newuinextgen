# Demo environment (Phase 14)

**Status:** IMPLEMENTED (seed + control centre + CLI + journeys + evidence)  
**Seed version:** `14.0.0`  
**Master directive:** [../AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md](../AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md) §27

## Quick start (Docker WordPress)

```bash
# Enable + seed + verify
docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_seed --allow-root

docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_verify --allow-root

# Journeys + evidence
docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_run_all_journeys --allow-root

docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_export_evidence --allow-root

# Clock / queues / reset
docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_advance_time --seconds=86400 --allow-root

docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_process_queues --allow-root

docker compose -f docker/docker-compose.yml exec -T wordpress \
  wp ngc demo_reset --yes --allow-root
```

Host PHPUnit-less tests:

```bash
php NextGenTutors-Companion/tests/run.php
```

## Admin UI

**Platform → Demo Control Centre** (`admin.php?page=ngc-demo-control`)

- Enable demo mode
- Seed / verify / run journeys / export evidence
- Advance clock / process schedulers
- Reset demo data
- Persona directory + switch-user (demo mode only)

## Credentials

Shared demo password is generated per site (`ngc_demo_password` option) or set via `NGC_DEMO_PASSWORD` / env.  
Visible only in Demo Control Centre while demo mode is on.

Stable IDs: `NGT-DEMO-P0001`, `NGT-DEMO-T0001`, … (see registry).

## Safety

| Control | Behavior |
|---------|----------|
| `NGC_DEMO_MODE` / Platform demo flag | Required for ops |
| Email | Sandbox capture (`NGC_Demo_Notifications`) |
| PayFast | Forced sandbox filter |
| Payouts | Blocked when external side effects false |
| Reset | Requires demo mode + `allow_reset` |

## Code map

| Class | Path |
|-------|------|
| `NGC_Demo` | `includes/demo/class-ngc-demo.php` |
| `NGC_Demo_Env` | `includes/demo/class-ngc-demo-env.php` |
| `NGC_Demo_Clock` | `includes/demo/class-ngc-demo-clock.php` |
| `NGC_Demo_Registry` | `includes/demo/class-ngc-demo-registry.php` |
| `NGC_Demo_Seeder` | `includes/demo/class-ngc-demo-seeder.php` |
| `NGC_Demo_Verifier` | `includes/demo/class-ngc-demo-verifier.php` |
| `NGC_Demo_Reset` | `includes/demo/class-ngc-demo-reset.php` |
| `NGC_Demo_Evidence` | `includes/demo/class-ngc-demo-evidence.php` |
| `NGC_Demo_Journeys` | `includes/demo/class-ngc-demo-journeys.php` |
| `NGC_Demo_Admin` | `includes/admin/class-ngc-demo-admin.php` |

Entities are created via `NGC_Child_Learners`, `NGC_Matching`, `NGC_Bookings`, `NGC_Wallet`, `NGC_Reviews`, `NGC_Fraud_Engine`, `NGC_Safeguarding`, `NGC_Agent_Control_Plane` — not raw dashboard JSON.
