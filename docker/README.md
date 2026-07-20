# NextGen Tutors — Docker (newuinextgen)

Local WordPress with the **parent workspace folder** bind-mounted as the active theme (`nextgentutors-beyondinfinity`).

## Quick start

```powershell
cd docker
Copy-Item .env.example .env
.\start.ps1
```

| Service | URL |
|---------|-----|
| WordPress | http://localhost:8900 |
| Admin | http://localhost:8900/wp-admin |
| phpMyAdmin | http://localhost:8082 |

**Login:** `admin` / `NextGenAdmin!2026` (override in `.env`)

## What gets mounted

| Host path | Container path |
|-----------|----------------|
| `../` (theme root) | `wp-content/themes/nextgentutors-beyondinfinity` |
| `docker/hello-elementor/` | `wp-content/themes/hello-elementor` |

Edits to theme files in this repo are reflected immediately in the container.

## Commands

```powershell
docker compose up -d          # start
docker compose down           # stop
docker compose logs -f wordpress
docker compose --profile setup run --rm wpcli   # re-run bootstrap
```

## Ports

Defaults use **8900** (WordPress) and **8082** (phpMyAdmin) to avoid clashing with the REVAMP stack on 8899/8081. Change `WP_PORT` and `PMA_PORT` in `.env` if needed.

## Companion plugin

**NextGenTutors-Companion** provides forms, dashboards, smart matching, and the live tutor marketplace (`ngc_*` shortcodes, CPTs, REST API).

1. Set the plugin folder in `.env`:

```env
COMPANION_PLUGIN_PATH=C:/Users/marvi/Music/REVAMP/NextGenTutors-Companion
```

2. Install and activate (while Docker is running):

```powershell
cd docker
.\scripts\install-companion.ps1
```

The plugin is bind-mounted into `wp-content/plugins/NextGenTutors-Companion`. New stacks run companion install automatically during `start.ps1` when the path exists.
