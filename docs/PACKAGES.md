# NextGen Tutors — Package Reference

Complete reference for all deployable packages in the monorepo.

**Last updated:** 2026-07-27

---

## Package map

```
newuinextgen/                          ← monorepo root
├── (theme files)                      ← BeyondInfinity theme
├── NextGenTutors-Companion/           ← domain plugin
├── NextGenTutors-BeyondMeasure/       ← Control Plane admin OS (Beyond Measure)
├── NextGenTutors-Mission-Control/     ← ops control plane (coexists; strangler)
├── NextGenTutors-Plugin-Manager/      ← fleet operator console
├── NextGenTutors-Html-Importer/       ← HTML migration tool
├── ui-library/                        ← shared Magic UI (deploy separately)
├── content/
│   ├── page-map.json                  ← launch page registry
│   ├── nextgen-workflow-pack.json     ← executable workflow actions
│   └── _extracted/
│       ├── nextgen-command-center-v1.0/
│       └── nextgen-completion-suite/
├── docker/                            ← local dev stack
├── docs/                              ← this documentation suite
└── e2e/                               ← Playwright blueprint tests
```

---

## 1. BeyondInfinity theme

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/themes/nextgentutors-beyondinfinity` |
| **Source** | Workspace root (not a subfolder) |
| **Parent theme** | Hello Elementor |
| **Version** | `style.css` 1.9.3 · `BI_VERSION` 1.9.9 |
| **Text domain** | `beyondinfinity` |
| **Responsibility** | Presentation only — templates, design tokens, page defaults, UI Library partials, theme workflow fallback |

### Entry points

| File | Purpose |
|------|---------|
| `style.css` | Theme header, design tokens |
| `functions.php` | Bootstrap, `BI_VERSION`, asset enqueue |
| `inc/` | ~100 PHP modules (config, defaults, admin, workflows, UI library) |
| `templates/` | Header/footer variants, full-width, tutor calendar |
| `template-parts/` | Section and UI Library component partials |
| `page-templates/` | Named page templates (home, dashboards, forms) |
| `assets/` | CSS, JS, NGT chrome, motion system, kinetic homepage |

### Sacred contracts (theme must NOT own)

- Custom DB tables (`ngc_*`)
- Payment settlement logic
- Tutor matching scoring
- CRM/booking adapter calls
- AI model registry / BYOK keys

### Consumes from Companion

- `[ngc_*]` shortcodes
- `ngc/v1` REST API
- `class_exists('NGC_Plugin')` gate
- Section CMS (`NGC_Section_CMS`)
- Data providers via `ngc_ui_render_component` filter

### Key modules

| Module | Path | Purpose |
|--------|------|---------|
| Config schema | `inc/config/options-schema.php` | SmartHead-style `bi_*` options |
| Page registry | `inc/pages-registry.php` | Launch pages from `content/page-map.json` |
| Workflows | `inc/workflows.php` | `bi_workflow_*` JSON pack runner |
| Companion bridge | `inc/companion.php` | Shortcode/REST integration |
| UI Library | `inc/ui-library/` | `ng_ui_component()` loader |
| NGT assets | `inc/ngt-assets.php` | Design system enqueue gate |

---

## 2. NextGenTutors-Companion

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/plugins/NextGenTutors-Companion` |
| **Main file** | `nextgencompanion.php` |
| **Version** | `NGC_VERSION` 1.9.0 |
| **Responsibility** | Domain layer — business rules, persistence, REST, workflows, AI, integrations |

### Bootstrap chain

```
nextgencompanion.php
  → ngc_autoload (PSR-4)
  → NGC_Loader::boot()
  → NGC_Plugin_Bootstrap::init()  (52+ modules)
  → NGC_Database::create_tables() (44 custom tables)
```

### Subsystems

| Subsystem | Classes / paths |
|-----------|-------------------|
| **Matching** | `NGC_Matching`, `NGC_Smart_Matching`, `includes/matching/` |
| **Bookings** | `NGC_Bookings`, Amelia adapter |
| **Payments** | `NGC_Payments`, `NGC_PayFast`, `NGC_Invoices`, `NGC_Wallet` |
| **Workflows** | `NGC_Workflow_Orchestrator`, `NGC_Workflows`, `NGC_Workflow_Integrate_Executor` |
| **Integrations** | `includes/integrations/` — FluentCRM, Amelia, AutomatorWP, content packs |
| **AI Suite** | `NGC_AI_*` — BYOK models, agents, chat |
| **Automation Studio** | `NGC_Studio_*` — visual orchestration |
| **Section CMS** | `NGC_Section_CMS` — homepage section content |
| **Gamification** | `NGC_Gamification` — GamiPress bridge |
| **REST** | `includes/rest/` — `ngc/v1` namespace |
| **Admin** | `includes/admin/` — workflow admin, platform admin, AI admin |

### Database tables (prefix `wp_ngc_`)

Matches, bookings, invoices, wallet ledger, payouts, reviews, ratings, tutor applications, workflow runs, analytics events, visitor profiles, gamification, studio, child learners, audit, and more. Full list: `docs/database/database-documentation.md`.

### Integrate pack

| Path | Contents |
|------|----------|
| `integrate/workflow-01..10.json` | Business workflow specs (WF-01–WF-10) |
| `integrate/catalog/v2/` | Command Center v2 definitions (12 JSON) |
| `integrate/catalog/completion/` | Completion Suite definitions (6 JSON) |
| `integrate/README.md` | Runtime wiring + WP-CLI |

### WP-CLI commands

```bash
wp ngc verify
wp ngc integrate import
wp ngc integrate execute --event=tutor.approved
```

### Admin menus

- **NextGen** — health, platform, AI suite
- **Workflows** — triggers, FluentCRM, Amelia, logs, integrate specs
- **Automation Studio** — visual workflow designer

---

## 3. NextGenTutors-Plugin-Manager

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/plugins/NextGenTutors-Plugin-Manager` |
| **Main file** | `NextGenTutors-Plugin-Manager.php` |
| **Version** | Header 1.3.0 · `NGCPM_VERSION` 1.3.3 |
| **Responsibility** | Fleet management — install/activate stack plugins, health, offline zips |

### Does NOT own

- Tutor data or business logic
- Companion database tables
- Workflow execution

### Registry plugins (required stack)

WooCommerce, Elementor, FluentCRM, FluentSMTP, MasterStudy LMS, GamiPress, AutomatorWP, User Role Editor, Amelia (manual zip), PayFast gateway (manual zip).

### Local zip directory

| Host | Container |
|------|-----------|
| `docker/ngcpm-packages/` | `/var/www/html/wp-content/ngcpm-packages` |

Override: `NGCPM_LOCAL_ZIP_DIR` in `wp-config.php` or Docker env.

### Key classes

| Class | Purpose |
|-------|---------|
| `NGCPM_Registry` | Plugin dependency definitions |
| `NGCPM_Installer` | Zip install (direct ZipArchive + upgrader fallback) |
| `NGCPM_Local_Packages` | Auto-install from local zip dir |
| `NGCPM_Scanner` | Installed/active detection |
| `NGCPM_Queue` | Install queue UI |

---

## 4. NextGenTutors-Mission-Control

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/plugins/NextGenTutors-Mission-Control` |
| **Main file** | `nextgentutors-mission-control.php` |
| **Responsibility** | Operational control plane — configure, repair, seed, verify, overrides, plugin matrix |

Shares orchestrator state with `wp ngt system` (`ngt_system_orchestrator_state`). Does **not** duplicate domain logic — delegates to Companion.

Open **WP Admin → Mission Control** (top of menu).

---

## 5. ui-library (shared)

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/ngt-ui-library` |
| **Source** | `ui-library/` at repo root |
| **Bootstrap** | `bootstrap/class-ngt-ui-bootstrap.php` |
| **Responsibility** | Magic UI catalog — components, tokens, builder adapters |

**Not bundled in the theme zip.** Deploy `dist/ngt-ui-library.zip` to `wp-content/ngt-ui-library` or bind-mount in Docker.

Theme integration: `inc/ui-library/` + `template-parts/ui-library/`. Companion bridge: `NGC_UI_Library_Bridge`.

---

## 6. NextGenTutors-Html-Importer

| Field | Value |
|-------|--------|
| **WordPress path** | `wp-content/plugins/NextGenTutors-Html-Importer` |
| **Main file** | `revamp-html-importer.php` |
| **Version** | Header 1.0.0 · `RHI_VERSION` 1.0.1 |
| **Responsibility** | One-time HTML → WP page migration (dry-run, rollback) |

### Source HTML

`webpages-content/` — static HTML mapped to launch pages.

### Does NOT touch

- `ngc_*` database tables
- Companion business logic
- Runtime workflow hooks

---

## 7. NextGen Command Center (content pack)

| Field | Value |
|-------|--------|
| **Source zip** | `content/nextgen-command-center-v1.0.zip` |
| **Extracted** | `content/_extracted/nextgen-command-center-v1.0/` |
| **WordPress path** | `wp-content/plugins/nextgen-command-center` |
| **Version** | `NGCC_VERSION` 1.0.0 |
| **Doc** | [content-packs/COMMAND-CENTER.md](content-packs/COMMAND-CENTER.md) |

Mission Control dashboards, RTM staff chat rooms, Jitsi links, workflow v2 JSON catalog.

---

## 8. NextGen Completion Suite (content pack)

| Field | Value |
|-------|--------|
| **Source zip** | `content/nextgen-completion-suite-v1.0.zip` |
| **Extracted** | `content/_extracted/nextgen-completion-suite/` |
| **WordPress path** | `wp-content/plugins/nextgen-completion-suite` |
| **Version** | 1.0.0 |
| **Doc** | [content-packs/COMPLETION-SUITE.md](content-packs/COMPLETION-SUITE.md) |

Operational MVP pages: progress reports, lesson notes, matching queue, payouts, learning resources.

---

## Version alignment

Run after any release:

```bash
php NextGenTutors-Companion/scripts/verify-versions.php
```

Align `style.css` Version, `BI_VERSION`, `NGC_VERSION`, and Plugin Manager header with release notes.

---

## Build release zips

```powershell
powershell -File scripts/build-release.ps1
```

Output: `dist/*.zip` — theme, Companion, AI Integration, Html Importer, Plugin Manager, **Mission Control**, **ngt-ui-library**.

Extract `ngt-ui-library.zip` to `wp-content/ngt-ui-library` on the host (not inside the theme folder).
