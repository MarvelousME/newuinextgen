# Headed system verification (E2E)

Full-stack Playwright checks against the local Docker WordPress site (`http://localhost:8900` by default). Runs **headed** (visible Chrome) so you can watch navigation, forms, and admin.

## What it verifies

| Step | Check |
|------|--------|
| 01 | Site reachable (HTTP under 500) |
| 02 | Public pages: home, about, find, become, pricing, login, register, contact, guarantee |
| 03 | Homepage landmarks + primary CTA |
| 04 | Booking / match CTA |
| 05 | Login role cards + parent form |
| 06 | Register + find-a-tutor intake forms |
| 07 | Tutor archive (or find-a-tutor fallback) |
| 08 | Axe scan on home (recorded; does not fail on a11y debt) |
| 09 | wp-admin login + Companion surfaces |
| 10 | Demo Control Centre verify when available |
| 11 | Logout |

Evidence is written to:

- `.agent-audit/evidence/runtime/system-verification-latest.json`
- `.agent-audit/evidence/runtime/system-verification-{timestamp}.json`

## Prerequisites

1. Docker stack up (`docker/start.ps1` or compose) on port **8900**.
2. Theme + Companion active; admin defaults: `admin` / `NextGenAdmin!2026` (override with `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD`).
3. Chrome installed (Playwright `channel: 'chrome'` locally).

## Run

From repo root:

```powershell
powershell -File scripts/run-playwright.ps1 -System -Headed
```

From `e2e/`:

```powershell
$env:BASE_URL = 'http://localhost:8900'
npm run test:system-headed
```

Headless (CI-style):

```powershell
npm run test:system
```

## Related suites

| Command | Scope |
|---------|--------|
| `npm run test:system-headed` | This full-system verify pack |
| `npm run test:phase14` | Relational demo seed / journeys |
| `npm run test:all-headed` | Every workflow spec, headed, serial |
| `npm run test:workflows-headed` | Same as all under `workflows/` |

Open the HTML report after a run:

```powershell
cd e2e
npm run report
```
