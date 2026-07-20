# Developer Manual — Automation Studio

## Extension points

### Filters

- `ngc_studio_trigger_catalog` — add trigger types
- `ngc_studio_trigger_hook_map` — map triggers to WP hooks
- `ngc_studio_node_types` — add canvas node types
- `ngc_studio_compiled_workflow` — modify compiled plan
- `ngc_studio_step_handler` — custom step execution
- `ngc_studio_templates` — add prebuilt templates

### Actions

- `ngc_studio_runtime_reloaded` — after hot-reload
- `ngc_studio_workflow_executed` — after run completes
- `ngc_studio_api_step` / `ngc_studio_ai_step` — custom nodes

## Emit events from code

```php
NGC_Studio_Event_Bus::emit( 'TUTOR_APPROVED', [ 'user_id' => 42 ] );
```

## React build

```bash
cd NextGenTutors-Companion/build-src
npm install && npm run build:copy
```

## Phase 2 — Form, Email, Notification Designers

### REST endpoints

| Resource | Base route |
|----------|------------|
| Forms | `GET/POST /ngc/v1/studio/forms`, `GET/PUT/DELETE /studio/forms/{id}`, `POST .../publish`, `POST .../forms/{key}/submit` |
| Emails | `GET/POST /ngc/v1/studio/emails`, merge-fields catalog, publish, test send |
| Notifications | `GET/POST /ngc/v1/studio/notifications`, channels catalog, publish, test dispatch |

### PHP classes

- `NGC_Studio_Forms` — field catalog (24 types), shortcodes `[ngc_studio_form key="..."]`, submit + workflow binding
- `NGC_Studio_Email` — merge fields, sync to `ngc_email_templates`, test send
- `NGC_Studio_Notifications` — 8 channels (email, SMS, WhatsApp, push, dashboard, toast, Slack, Teams)

### Filters (Phase 2)

- `ngc_email_template_override` — Studio email overrides workflow templates
- `ngc_studio_form_fields` — extend form field catalog
- `ngc_studio_notification_channels` — add notification channels

## Phase 3 — Dashboard Designer, path highlighting, live monitor

### Dashboard Designer

| Resource | Route |
|----------|-------|
| Dashboards | `GET/POST /ngc/v1/studio/dashboards`, CRUD, `/widgets`, `/roles`, `/publish` |

- `NGC_Studio_Dashboards` — 14 widget types, role targeting, shortcode `[ngc_studio_dashboard key="..."]`
- Filter `ngc_studio_role_dashboard` merges published layouts into role dashboards

### Branch / loop execution

- Compiler stores `edge_meta` with `sourceHandle` (`true`/`false`, `loop`/`exit`)
- `NGC_Studio_Engine::execute_graph()` follows branch and loop edges; path entries include `branch`, `edge_to`, `loop_iteration`
- React applies path highlight after simulate or from Monitor → **Highlight**

### Live monitor

- `NGC_Studio_Stream` — ring buffer + `ngc_studio_step_executed` / `ngc_studio_workflow_executed` hooks
- `GET /ngc/v1/studio/live?since={id}` — JSON poll (fallback)
- `GET /ngc/v1/studio/live?sse=1&since={id}` — **Server-Sent Events** (~25s per connection, auto-reconnect in React)
- Monitor tab shows transport badge (`SSE` / `Poll`); click **Highlight** to paint path on canvas

## Platform REST (v1.6.0 gap closure)

| Resource | Route | Auth |
|----------|-------|------|
| Wallet | `GET /ngc/v1/wallet`, `GET /wallet/ledger` | Logged-in (admin may pass `user_id`) |
| Invoices | `GET /ngc/v1/invoices`, `GET /invoices/{id}` | Owner or admin |
| Matches | `GET /ngc/v1/matches`, `GET /matches/{id}` | Role-scoped list |

## Compliance & integrations

- **POPIA:** `NGC_Platform_Tracking::consent_granted()` gates `ngc_ref` cookies; pending referral stored until consent accept.
- **Amelia:** `amelia_booking_id` on `ngc_bookings`; `NGC_Bookings::sync_from_amelia()` on booking hooks.

## Validation & E2E

```bash
php scripts/validate.php
php scripts/run-phpunit.php   # after: composer install (optional)
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar eval-file \
  /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/e2e-docker.php --allow-root
```

### PHPUnit (optional)

```bash
cd NextGenTutors-Companion
composer install
vendor/bin/phpunit
```

Release builds: `powershell -File scripts/build-release.ps1` (repo root) — runs Studio `npm run build:copy` before zipping.

### P3 — Catalog, payouts, validation

- **WooCommerce CSV** — `Categories` column mapped to `product_cat` via `NGC_WooCommerce_Catalog::assign_product_categories()`
- **PayFast export** — `NGC_Payout_Export`, `wp ngc export_payouts`, `wp ngc confirm_payout {id}`; see `docs/operations/payout-export.md`
- **Unit tests** — `php tests/run.php` (Companion + Html-Importer)
