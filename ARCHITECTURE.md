# NextGenTutors — Solution Architecture

One product, **six deployable packages** (plus ops consoles). Each package has a single responsibility; cross-package contracts are explicit and versioned.

## Packages (canonical source only)

| Package | Folder | WordPress path | Responsibility |
|---------|--------|----------------|----------------|
| **BeyondInfinity** | `NextGenTutors-BeyondInfinity/` | `themes/nextgentutors-beyondinfinity` | Presentation: templates, design tokens, page defaults, theme workflows (fallback), `[bi_*]` / consumed `[ngc_*]` |
| **Companion** | `NextGenTutors-Companion/` | `plugins/NextGenTutors-Companion` | Domain: data layer, CPTs, `[ngc_*]` shortcodes, `ngc/v1` REST, matching, bookings, AI suite, integrations |
| **Beyond Measure** | `NextGenTutors-BeyondMeasure/` | `plugins/NextGenTutors-BeyondMeasure` | Control Plane admin OS: React SPA in `wp-admin`, RBAC matrix, metadata-driven CRUD, health/audit; does **not** own domain scoring/payments |
| **AI-Integration** | `NextGenTutors-AI-Integration/` | `plugins/NextGenTutors-AI-Integration` | Transport/security/governance bridge for approved AI integrations. No Companion domain ownership and no direct LLM or model runtime. |
| **Html-Importer** | `NextGenTutors-Html-Importer/` | `plugins/NextGenTutors-Html-Importer` | One-time / ops: static HTML → WP pages (dry-run, rollback). No runtime business logic. |
| **Plugin-Manager** | `NextGenTutors-Plugin-Manager/` | `plugins/NextGenTutors-Plugin-Manager` | Operator console: install/activate stack plugins, health, offline zips. Does not own tutor data. |

**Do not duplicate** these folders elsewhere in the repo for active development. Legacy paths (`beyondinfinity/`, `nextgencompanion/`, `revamp-html-importer/`, `docker/wp-content/themes/beyondinfinity/`) are retired.

## SOLID mapping

### Single Responsibility
- **Theme** — render UX; no custom DB tables; no payment/matching business rules.
- **Companion** — business rules, persistence, APIs, AI policy (`BIA_Policy`).
- **AI-Integration** — authenticated, redacted, idempotent transport between Companion contracts and approved external AI services; no domain or model-runtime ownership.
- **Html-Importer** — content migration only.
- **Plugin-Manager** — fleet management only.

### Open/Closed
- Extend via **adapters** (`NGC_*_Adapter`), **workflow hooks**, `ngc_ai_skills` filter, and REST — not by copying code into the theme.
- New integrations = new adapter class in Companion; theme stays unchanged.

### Liskov Substitution
- Integration adapters implement `NGC_Integration_Adapter`; booking/calendar adapters are swappable (Amelia vs internal).
- AI calls go through `NGC_AI_Models::complete()` — any OpenAI-compatible endpoint.

### Interface Segregation
- Theme talks to Companion via **narrow contracts**: `class_exists('NGC_Plugin')`, `NGC_VERSION`, `[ngc_*]` shortcodes, `ngc/v1` REST.
- Plugin-Manager does not import Companion classes; it shells WP-CLI and reads filesystem.

### Dependency Inversion
- Theme depends on **abstractions** (shortcodes/REST), not concrete Companion files.
- Companion depends on adapter interfaces, not theme internals.
- High-level modules (dashboards, matching) do not depend on low-level SQL in the theme.

## Sacred contracts

```
Theme  ──consumes──►  [ngc_*] shortcodes, ngc/v1 REST
Companion  ──owns──►  ngc_* namespace, NGC_* tables, NGC_AI_*
Plugin-Manager  ──orchestrates──►  wp plugin install/activate (never duplicates Companion logic)
Html-Importer  ──writes──►  post_content / pages only (never touches ngc_* tables)
AI-Integration  ──bridges──►  governed event/callback transport (never owns Companion domain state or invokes model runtimes directly)
```

### Version constants
- Theme: `BI_VERSION` in `functions.php` + `style.css`
- Companion: `NGC_VERSION` in `nextgencompanion.php`
- Keep in sync via `NextGenTutors-Companion/scripts/verify-versions.php`

### AI (single stack in Companion)
- **BYOK models + agents**: `NGC_AI_Models`, `NGC_AI_Agents`, `NGC_AI_Chat` — admin: **NextGen → AI Suite**
- **Health LLM assist**: `NGC_Ai_Diagnostics` uses the same model registry (no separate key store)
- Preset provider list: `NGC_Ai_Provider_Registry::providers()` — catalog only

## What lives where (anti-duplication)

| Concern | Owner | Not in |
|---------|-------|--------|
| Tutor CPT, earnings, payouts | Companion | Theme |
| Smart matching scoring | Companion | Theme |
| Multi-model BYOK | Companion AI Suite | Theme, Plugin-Manager |
| AI transport signing, redaction, callback authentication | AI-Integration | Companion domain layer, Theme |
| Page HTML import | Html-Importer | Companion |
| WooCommerce/Elementor install | Plugin-Manager | Companion |
| `bi_workflow_*` JSON fallback | Theme | Companion (when active, Companion owns hooks) |
| Enterprise blueprint docs | Theme `docs/` | — (reference only) |
| UI Library (providers, CMS copy) | Companion `includes/ui-library/` | Theme owns partials/CSS only |
| UI component markup | Theme `template-parts/ui-library/` | Companion (no HTML in providers) |

## UI Library (v1.9.0+)

Presentation components with **data providers** — no hardcoded tutor/pricing/review values in partials.

- Companion: `NGC_UI_*_Data_Provider`, `NGC_Section_CMS`, `ngc_get_pricing_tiers()`
- Theme: `ng_ui_component()`, `inc/ui-library/`, `assets/css/ng-ui-*.css`
- Verify: `php NextGenTutors-Companion/scripts/verify-ui-library.php`
- Docs: `docs/SYSTEM-OVERVIEW.md`, `docs/ui-library/`

## Docker (local dev)

All mounts point at **canonical folders** at repo root:

```yaml
../NextGenTutors-BeyondInfinity  → themes/nextgentutors-beyondinfinity
../NextGenTutors-Companion       → plugins/NextGenTutors-Companion
../NextGenTutors-BeyondMeasure   → plugins/NextGenTutors-BeyondMeasure
../NextGenTutors-AI-Integration  → plugins/NextGenTutors-AI-Integration
../NextGenTutors-Html-Importer   → plugins/NextGenTutors-Html-Importer
../NextGenTutors-Plugin-Manager  → plugins/NextGenTutors-Plugin-Manager
```

## Verify (all five packages)

```powershell
powershell -File scripts/verify-solution.ps1
```

Runs structure checks, Docker mount audit, Companion + Plugin-Manager `validate.php`, Html-Importer PHP lint, and `verify-versions.php`.

## Build

```powershell
powershell -File scripts/build-release.ps1
```

Produces `dist/*.zip` for all five packages with WordPress-correct folder roots.

## Out of scope (archived)

- `React to WordPress Theme Conversion/` — historical unified experiment; do not deploy alongside canonical packages.
- `_deploy-staging/` — staging snapshots only.
