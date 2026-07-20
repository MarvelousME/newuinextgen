# NextGen Tutors — Developer Guide

Enterprise developer onboarding for the NextGen Tutors WordPress platform.

**Audience:** Backend developers, integration engineers, solution architects  
**Prerequisites:** PHP 8.2+, WordPress 6.7+, MySQL 8.0, Docker Desktop (local), Git  
**Last updated:** 2026-07-13

---

## 1. Quick start (15 minutes)

```powershell
# 1. Clone / open workspace
cd newuinextgen

# 2. Start Docker
cd docker
copy .env.example .env    # if first run
.\start.ps1

# 3. Install registry plugins + configure stack
.\scripts\install-registry-zips.ps1

# 4. Open site
# http://localhost:8900
# Admin: admin / NextGenAdmin!2026

# 5. Verify
cd ..
powershell -File scripts/verify-solution.ps1
php NextGenTutors-Companion/scripts/validate.php
```

**Expected:** Theme active, Companion + Plugin Manager active, 23 launch pages, health checks green.

---

## 2. Mental model

### 2.1 Six packages, three layers

```
┌─────────────────────────────────────────────────────────┐
│  PRESENTATION — BeyondInfinity theme (workspace root)    │
│  Templates · UI Library · design tokens · page defaults  │
└───────────────────────────┬─────────────────────────────┘
                            │ shortcodes · REST · filters
┌───────────────────────────▼─────────────────────────────┐
│  DOMAIN — NextGenTutors-Companion                      │
│  Tables · workflows · matching · payments · AI · REST    │
└───────────────────────────┬─────────────────────────────┘
                            │ adapters
┌───────────────────────────▼─────────────────────────────┐
│  INTEGRATIONS — WooCommerce · Amelia · FluentCRM · etc.  │
└─────────────────────────────────────────────────────────┘

OPERATIONS — Plugin Manager (fleet install)
MIGRATION  — Html Importer (one-time)
OPS UI     — Command Center + Completion Suite (content packs)
```

### 2.2 Golden rules

1. **Theme renders; Companion owns data.** Never put business SQL or payment logic in the theme.
2. **Extend via adapters and hooks**, not by copying Companion code into the theme.
3. **UI Library partials are data-provider driven.** No hardcoded tutor names, prices, or ratings in `template-parts/ui-library/`.
4. **Workflows dispatch events; orchestrator executes.** Use `NGC_Workflows::dispatch()` or `NGC_Workflow_Orchestrator::run()`.
5. **Plugin Manager installs; Companion configures.** Fleet zips go in `ngcpm-packages/`; integration bootstrap runs in Companion.

---

## 3. Repository layout (developer map)

| Path | You work here when… |
|------|---------------------|
| `functions.php`, `inc/` | Theme features, page defaults, UI Library |
| `templates/`, `template-parts/` | Layout and component markup |
| `assets/css/`, `assets/js/` | Front-end styling and interactions |
| `content/page-map.json` | Adding launch pages |
| `content/nextgen-workflow-pack.json` | Theme-level workflow actions (RTM, email) |
| `NextGenTutors-Companion/includes/` | Business logic, REST, admin |
| `NextGenTutors-Companion/integrate/` | Workflow specs and catalogs |
| `NextGenTutors-Plugin-Manager/includes/` | Registry, installer, health |
| `docker/` | Local environment |
| `e2e/` | Playwright workflow tests |
| `docs/` | All documentation |

**Do not develop in** `to-discard/` — archived only.

---

## 4. Extension points

### 4.1 WordPress hooks (Companion)

| Hook | When |
|------|------|
| `ngc_workflow_dispatched` | After any `NGC_Workflows::dispatch()` |
| `ngc_workflow_completed` | After orchestrator workflow finishes |
| `ngc_integrations_bootstrapped` | After local stack configure |
| `ngc_integrate_runtime_ready` | Integrate pack loaded |
| `ngc_ai_skills` | Register AI agent skills |
| `ngc_integrate_event_bindings` | Map integrate events to handlers |
| `ngcc_mission_control_metrics` | Override Command Center metrics |
| `bi_workflow_rtm_queued` | After RTM message queued (theme pack) |

### 4.2 Filters

| Filter | Purpose |
|--------|---------|
| `ngc_workflow_vars` | Enrich workflow template variables |
| `ngc_ui_render_component` | Override UI Library component output |
| `ngc_registry` | Extend Plugin Manager dependency registry |
| `ngcpm_registry` | Same (Plugin Manager) |

### 4.3 Integration adapters

Implement `NGC_Integration_Adapter` in `NextGenTutors-Companion/includes/adapters/`:

```php
class NGC_MyService_Adapter implements NGC_Integration_Adapter {
    public function id(): string { return 'myservice'; }
    public function is_available(): bool { return class_exists( 'MyService' ); }
    // ...
}
```

Register in `NGC_Workflow_Orchestrator::adapters()`.

### 4.4 AutomatorWP

When AutomatorWP is active, Companion registers:

- **Integration:** `nextgencompanion`
- **Trigger:** `nextgencompanion_workflow_event`
- **Importer:** `NGC_AutomatorWP_Importer::import_from_v2_catalog()`

Admin: **Workflows → Integrate Specs → Seed AutomatorWP from v2 JSON**

---

## 5. Workflow system

### 5.1 Three workflow layers

| Layer | Location | Executes? |
|-------|----------|-----------|
| **Theme workflow pack** | `content/nextgen-workflow-pack.json` | Yes — `bi_workflow_dispatch()` |
| **Companion orchestrator** | `NGC_Workflow_Orchestrator` | Yes — registration lifecycles |
| **Integrate specs** | `integrate/workflow-*.json` | Documented; bound via `NGC_Workflow_Integrate_Executor` |
| **v2 catalog** | `integrate/catalog/v2/` | Definition records; AutomatorWP seed |
| **Blueprint WF-01–25** | `docs/enterprise-blueprint/` | Specification + E2E map |

### 5.2 Dispatch an event

```php
// From Companion code:
NGC_Workflows::dispatch( 'find_tutor.submitted', [
    'name'    => 'Jane Parent',
    'email'   => 'jane@example.com',
    'subject' => 'Mathematics',
    'grade'   => '10',
] );

// Runs orchestrator for registration events; fires theme pack for ngt.* events.
```

### 5.3 Run orchestrator directly

```php
NGC_Workflow_Orchestrator::run( 'TUTOR_APPROVED', [
    'user_id' => 42,
    'email'   => 'tutor@example.com',
] );
```

### 5.4 Execute integrate event (admin / CLI)

```bash
wp ngc integrate execute --event=tutor.approved --user_id=42
```

---

## 6. REST API (`ngc/v1`)

Full classification: `NextGenTutors-Companion/REST-ENDPOINTS.md`

| Family | Examples |
|--------|----------|
| Forms | `POST /ngc/v1/forms/{form_id}` |
| Matching | `GET /ngc/v1/matching/suggest` |
| Bookings | `POST /ngc/v1/bookings` |
| Dashboard | `GET /ngc/v1/dashboard/{role}` |
| Tutor calendar | `GET /ngc/v1/tutors/{id}/calendar` |

**Auth:** Cookie auth for logged-in users; application passwords for server-to-server; capability checks per endpoint.

**OpenAPI:** `docs/apis/openapi-nextgen.yaml`

---

## 7. Database

44 custom tables under `wp_ngc_*`. Created on plugin activation via `NGC_Database::create_tables()`.

**Never** write raw SQL in the theme. Use Companion services or `$wpdb` only inside Companion classes.

Schema reference: `docs/database/database-documentation.md`

---

## 8. UI Library

### Render a component

```php
// Theme:
ng_ui_component( 'tutor-card', [ 'tutor_id' => 123 ] );

// Companion shortcode:
[ng_ui_component slug="pricing-table"]
```

### Data providers

`NextGenTutors-Companion/includes/ui-library/providers/class-ngc-ui-*-data-provider.php`

Verify no hardcoded demo data:

```bash
php NextGenTutors-Companion/scripts/verify-ui-library.php
```

---

## 9. Local Docker development

| Service | URL | Notes |
|---------|-----|-------|
| WordPress | http://localhost:8900 | Port from `docker/.env` |
| phpMyAdmin | http://localhost:8082 | DB inspection |
| MySQL | `db:3306` (internal) | wordpress/wordpress |

### Bind mounts (hot reload)

| Host | Container |
|------|-----------|
| Workspace root | `themes/nextgentutors-beyondinfinity` |
| `NextGenTutors-Companion/` | `plugins/NextGenTutors-Companion` |
| `NextGenTutors-Plugin-Manager/` | `plugins/NextGenTutors-Plugin-Manager` |
| `docker/ngcpm-packages/` | `wp-content/ngcpm-packages` |
| `content/_extracted/nextgen-command-center-v1.0/` | `plugins/nextgen-command-center` |
| `content/_extracted/nextgen-completion-suite/` | `plugins/nextgen-completion-suite` |

### Useful WP-CLI (inside container)

```bash
docker exec -it <wordpress-container> bash
wp plugin list --allow-root
wp ngc verify --allow-root
wp eval-file wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php --allow-root
```

---

## 10. Testing

| Layer | Command | Location |
|-------|---------|----------|
| PHP lint | `php -l file.php` | Per file |
| Companion validate | `php NextGenTutors-Companion/scripts/validate.php` | Structural |
| UI Library scan | `php NextGenTutors-Companion/scripts/verify-ui-library.php` | Hardcode check |
| Solution verify | `powershell -File scripts/verify-solution.ps1` | Full repo |
| E2E Playwright | `npx playwright test` | `e2e/` |
| Integrate test | `php NextGenTutors-Companion/scripts/integrate-test.php` | Workflow bindings |
| Docker E2E | `scripts/e2e-docker.php` | Container smoke |

E2E maps to blueprint workflows WF-01–WF-25. See `docs/verification/testing-documentation.md`.

---

## 11. Coding standards

- **PHP:** WordPress Coding Standards; `phpcs` where configured
- **Escaping:** `esc_html()`, `esc_attr()`, `wp_kses_post()` on all output
- **Nonces:** All admin POST/AJAX actions
- **Capabilities:** `current_user_can()` before privileged operations
- **i18n:** Text domain `nextgencompanion` (plugin) / `beyondinfinity` (theme)
- **Commits:** Focused diffs; do not commit `.env`, credentials, or `node_modules`

---

## 12. Release process

1. Bump versions: `style.css`, `BI_VERSION`, `NGC_VERSION`, `NGCPM_VERSION`
2. Run `verify-versions.php` and `verify-solution.ps1`
3. Run Playwright on staging
4. `powershell -File scripts/build-release.ps1`
5. Deploy zips per [COMMERCIAL-DEPLOYMENT-GUIDE.md](COMMERCIAL-DEPLOYMENT-GUIDE.md)
6. Update `CHANGES-REGISTRY.md` and `KNOWN-LIMITATIONS.md`

---

## 13. Further reading

| Topic | Document |
|-------|----------|
| Architecture | [../ARCHITECTURE.md](../ARCHITECTURE.md) |
| Packages | [PACKAGES.md](PACKAGES.md) |
| Workflow catalog | [workflows/INTEGRATION-CATALOG.md](workflows/INTEGRATION-CATALOG.md) |
| Hands-on tutorials | [tutorials/DEVELOPER-TUTORIALS.md](tutorials/DEVELOPER-TUTORIALS.md) |
| Automation Studio | [../NextGenTutors-Companion/docs/studio/DEVELOPER-MANUAL.md](../NextGenTutors-Companion/docs/studio/DEVELOPER-MANUAL.md) |
| API reference | [apis/api-documentation.md](apis/api-documentation.md) |
| Security | [security/security-documentation.md](security/security-documentation.md) |
