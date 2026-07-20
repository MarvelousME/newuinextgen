# Bespoke Guide — BeyondInfinity Theme & NextGenTutors-Companion

**Audience:** implementers customising NextGen Tutors for a client brand or market  
**Code roots:** `NextGenTutors-BeyondInfinity/` (theme) · `NextGenTutors-Companion/` (domain plugin)  
**Updated:** 2026-07-20  

> Naming: the theme package is **NextGenTutors-BeyondInfinity** (not “BeyondIdentity”).  
> Companion owns business logic; the theme owns presentation. Keep that split when you bespoke.

**Master automation prompt alignment:** Four phases × four steps (16 total) match the autonomous setup prompt. Execute them via:

```bash
wp ngt system run-all --force-safe
```

Business SSOT: [`config/nextgentutors-business-profile.json`](config/nextgentutors-business-profile.json) (mirrored in Companion `config/`). Gap matrix: [`docs/SYSTEM-SETUP-GAP-MATRIX.md`](docs/SYSTEM-SETUP-GAP-MATRIX.md).

| Phase | Steps | Orchestrator |
|-------|-------|----------------|
| 1 Foundations | 1–4 | `preflight` → `configure` → `install` → pages via theme sync |
| 2 Theme / brand | 5–8 | BeyondInfinity + skin + defaults-production |
| 3 Companion / plugins | 9–12 | `seed` (business + Phase 14 demo) |
| 4 Verify / ship | 13–16 | `verify` → `repair` → `export-report` + headed e2e |

**Related runbooks**

| Doc | Use when |
|-----|----------|
| [docs/tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md](docs/tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md) | First-time site bring-up after plugins |
| [docs/SYSTEM-SETUP-GAP-MATRIX.md](docs/SYSTEM-SETUP-GAP-MATRIX.md) | Master-prompt coverage status |
| [documentation/platform-architecture.md](documentation/platform-architecture.md) | System map |
| [documentation/tutorials/01-phase14-demo-walkthrough.md](documentation/tutorials/01-phase14-demo-walkthrough.md) | Demo seed / headed e2e |
| [documentation/ui-library-guide.md](documentation/ui-library-guide.md) | UI components / tokens |

---

## How to use this guide

| Phase | Steps | Outcome |
|-------|-------|---------|
| A — Foundations | 1–3 | Safe local stack + mental model |
| B — Theme look & content | 4–8 | Brand, skins, pages, copy |
| C — Companion behaviour | 9–13 | Domain, REST, admin, integrations |
| D — Verify & ship | 14–16 | Tests, demo, packaging |

Do **not** fork domain tables into the theme. Do **not** put booking/money logic in page templates.

---

## Step 1 — Confirm packages and ownership

1. Open the solution root and locate:

| Package | Path | Own |
|---------|------|-----|
| Theme | `NextGenTutors-BeyondInfinity/` | Pages, defaults, CSS tokens, prototypes, Elementor-friendly chrome |
| Companion | `NextGenTutors-Companion/` | `wp_ngc_*` tables, REST `ngc/v1`, matching, bookings, PayFast, agents, demo, privacy, metrics |
| UI library | `ui-library/` | Shared components (theme + Companion admin can consume) |
| Docker | `docker/` | Local WordPress on port **8900** |

2. Theme is a **child of Hello Elementor** (`Template: hello-elementor` in `style.css`). Parent theme must stay installed.
3. Companion bootstrap entry: `NextGenTutors-Companion/nextgencompanion.php` → `NGC_Plugin_Bootstrap` modules in `includes/class-ngc-plugin.php`.

**Gate:** You can name both packages and state one sentence each for “theme vs plugin.”

---

## Step 2 — Bring up a local site

```powershell
cd docker
copy .env.example .env   # if needed
.\start.ps1              # or your usual compose up
```

Defaults (see `docker/.env.example`):

- URL: `http://localhost:8900` (use **localhost**, not `127.0.0.1`, for cookies)
- Admin: `admin` / `NextGenAdmin!2026`

Volumes mount Companion and BeyondInfinity from the repo, so edits on disk appear in the container without rebuilding images.

**Gate:** `/wp-admin` loads; **Appearance → Themes** shows BeyondInfinity active; Companion is Active under Plugins.

---

## Step 3 — Decide the bespoke scope

Write a short checklist for the client before coding:

| Area | Theme | Companion | Example |
|------|:-----:|:---------:|---------|
| Logo, colours, fonts | ✓ | | New skin CSS |
| Home / Find a Tutor copy | ✓ | | `inc/defaults-production/*.php` |
| Page layout / Elementor | ✓ | | Page templates + builder |
| Matching rules / subjects | | ✓ | Matching / forms / options |
| Booking lifecycle | | ✓ | `NGC_Bookings` |
| PayFast / invoices | | ✓ | Payment modules |
| Roles / dashboards data | | ✓ | Domain + REST |
| Demo personas | | ✓ | Phase 14 Demo Control Centre |
| Privacy retention | | ✓ | Privacy + Observability |

**Gate:** Scope document lists files you expect to touch (theme skins + defaults; Companion only if behaviour changes).

---

## Phase B — Bespoke the theme

### Step 4 — Brand tokens (preferred: visual preset / skin)

Prefer a **skin** over editing every component. Skins swap CSS variables only.

1. Copy an existing skin:

```text
NextGenTutors-BeyondInfinity/assets/css/skins/beyond-infinity.css
→ NextGenTutors-BeyondInfinity/assets/css/skins/client-brand.css
```

2. Change tokens under `html[data-bi-skin="client-brand"]` (primary, secondary, accent, fonts).

3. Register the preset in `inc/config/visual-presets.php` via the `bi_filter_visual_presets` filter (or add an entry next to `beyond-infinity` / `ngt-marketing` / `classic-indigo`).

4. Select the preset in theme options / BeyondInfinity admin (or set the stored option your site already uses for `bi_resolve_visual_preset_id()`).

5. Hard-refresh the front site; confirm `html[data-bi-skin="…"]` in DevTools.

**Also update** root design tokens in `style.css` `:root` if you rely on non-skinned pages.

**Gate:** Homepage and one inner page use client colours without changing PHP templates.

---

### Step 5 — Logo, favicon, and site identity

1. **Settings → General** — site title / tagline.
2. Replace logo assets under `NextGenTutors-BeyondInfinity/assets/images/` (see that folder’s README).
3. Wire logo in header templates:

```text
templates/header/default.php
templates/header/transparent.php
header.php
```

4. Customizer / BeyondInfinity options: favicon, social links, WhatsApp (`inc/openwa.php` / options schema).

**Gate:** Header shows client logo on desktop and mobile.

---

### Step 6 — Page copy and production defaults

Content defaults live in two layers:

| Layer | Path | When |
|-------|------|------|
| Staging / generic | `inc/defaults/*.php` | Dev / fallback |
| Production copy | `inc/defaults-production/*.php` | Client-facing SA copy |

Typical pages: `home`, `find-a-tutor`, `become-a-tutor`, `pricing`, `about`, `support`, dashboards.

1. Edit **production** files for client wording (subjects, provinces, guarantees, POPIA phrasing).
2. Keep keys / section IDs stable so sync and Elementor bindings still resolve.
3. Re-run page sync from BeyondInfinity / Companion admin if your environment uses “Sync Pages” (see sequential setup Step 2).

**Gate:** Find a Tutor and Become a Tutor show client language; shortcodes/forms still submit.

---

### Step 7 — Templates, prototypes, and builders

| Need | Where |
|------|--------|
| Full PHP page templates | `page-templates/` |
| Reusable sections | `template-parts/`, `prototypes/*-body.php` |
| Layout manager | `inc/layout-manager.php` |
| Elementor / WPBakery | `inc/page-builders.php` + docs under `documentation/elementor-guide.md` |

Rules:

- Call Companion via shortcodes, REST, or existing `bi_*` / `ngc_*` helpers — do not duplicate SQL.
- Prefer `ui-library` components over one-off markup when a component already exists.

**Gate:** One customised page template loads with no PHP fatals; forms still hit Companion.

---

### Step 8 — Theme admin chrome (optional)

BeyondInfinity admin UI:

- `inc/admin.php`
- `inc/admin-beyondinfinity.php`
- Assets: `assets/css/nextgen-beyond-infinity-ui.css`, `assets/js/nextgen-beyond-infinity-ui.js`

Use for operator UX only. Do not move bookings or wallets here.

**Gate:** WP Admin still loads; BeyondInfinity settings save.

---

## Phase C — Bespoke the Companion plugin

### Step 9 — Extension map (where to plug in)

| Concern | Typical location |
|---------|------------------|
| New module | `includes/class-ngc-*.php` + add class to `$modules` in `includes/class-ngc-plugin.php` |
| REST routes | `includes/rest/` |
| Admin screens | `includes/admin/` |
| Integrations (Amelia, LMS, CRM) | `includes/integrations/` |
| Matching / forms | `includes/matching/`, forms registry |
| Demo / Phase 14 | `includes/demo/` |
| Privacy / metrics | privacy + `includes/diagnostics/` |
| Autoload paths | `$paths` in `nextgencompanion.php` |

Class naming: `NGC_Something` → file `class-ngc-something.php`.

**Gate:** You know which folder owns your change before writing code.

---

### Step 10 — Configuration and feature flags

Prefer options / filters / `wp-config` constants over hard-coding:

- Demo: `NGC_ALLOW_DEMO_SEED`, Demo Control Centre (Platform → Demo Control Centre)
- PayFast: sandbox vs live (never leave live keys in repo)
- Privacy retention: Platform privacy screens
- Metrics: `/wp-json/ngc/v1/metrics` (see `documentation/ops-privacy-observability.md`)

Document any new client-specific constants in this file’s “Client notes” section at the bottom (or your private runbook).

**Gate:** Staging can toggle demo/sandbox without code edits.

---

### Step 11 — Domain behaviour changes (only if required)

Examples of legitimate Companion bespoke work:

1. **Subjects / provinces / grades** — lists used by matching and forms (theme lists + Companion validators must stay aligned).
2. **Matching weights** — `NGC_Matching` / smart matching; keep deterministic and testable.
3. **Booking statuses / reminders** — `NGC_Bookings`, session reminders; never skip conflict checks.
4. **New REST fields** — version carefully; document in `documentation/api-reference-ngc-v1.md`.
5. **Roles** — `NGC_Roles`; avoid inventing parallel capability systems.

Schema changes go through `NGC_Database::create_tables()` / explicit migrators (see amelia_booking_id nullable pattern). Always migrate existing Docker DBs, not only fresh installs.

**Gate:** Unit suite still passes: `php NextGenTutors-Companion/tests/run.php`

---

### Step 12 — Admin UX for operators

Companion admin (Platform menu) can be skinned with shared BeyondInfinity admin CSS/JS under Companion `assets/`. Keep:

- Demo Control Centre for relational demos
- Fraud / Safeguarding queues if client ops need them
- Privacy export / erase for POPIA

Add `data-testid` attributes on new critical controls so Playwright can cover them.

**Gate:** Admin can complete seed → verify without WP-CLI.

---

### Step 13 — Theme ↔ Companion contracts

When the theme needs data:

1. Prefer existing shortcodes / REST (`ngc/v1/...`).
2. Theme helper: `inc/companion.php` (soft dependency if Companion inactive).
3. Workflows: Companion `NGC_Workflows::dispatch()` ↔ theme `bi_workflow_dispatch` when present.

Never query `wp_ngc_*` from theme templates directly.

**Gate:** Deactivating Companion shows a controlled fallback (no white screen).

---

## Phase D — Verify and ship

### Step 14 — Automated checks

```powershell
# Companion unit tests
php NextGenTutors-Companion/tests/run.php

# Headed Phase 14 demo walkthrough (site must be up)
cd e2e
$env:BASE_URL='http://localhost:8900'
npm run test:phase14
```

Smoke manually:

1. Home + Find a Tutor + Become a Tutor  
2. Parent registration / login (or Phase 14 parent persona)  
3. One booking path in sandbox  
4. Platform → Demo Control Centre → Verify = PASS (staging only)

**Gate:** Units green; critical path smoke signed off.

---

### Step 15 — Package for the client environment

1. Theme folder as zip **or** git deploy of `NextGenTutors-BeyondInfinity`.
2. Companion zip / deploy of `NextGenTutors-Companion`.
3. Confirm Hello Elementor parent is installed on target.
4. Run sequential setup: [docs/tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md](docs/tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md).
5. On production: **disable demo mode**; set `NGC_ALLOW_DEMO_SEED` false; use live PayFast only with explicit ops approval.

**Gate:** Staging clone matches brand; production has demo off.

---

### Step 16 — Handover checklist

- [ ] Skin / preset name documented  
- [ ] Logo + favicon paths listed  
- [ ] Production defaults files changed (list paths)  
- [ ] Companion modules / options changed (list classes)  
- [ ] New env constants documented  
- [ ] Test commands that must pass  
- [ ] Known limitations (CRM/LMS partial sync, etc.)

---

## Quick reference — “edit here first”

### Theme

```text
style.css                          → base tokens / theme header
assets/css/skins/*.css             → brand skins
inc/config/visual-presets.php      → register skins
inc/defaults-production/*.php      → client copy
page-templates/                    → page shells
templates/header/*.php             → chrome
functions.php                      → load order (rarely)
```

### Companion

```text
nextgencompanion.php               → constants + autoload paths
includes/class-ngc-plugin.php      → module list
includes/class-ngc-database.php    → schema / migrations
includes/class-ngc-bookings.php    → booking lifecycle
includes/class-ngc-matching.php    → match create/score
includes/rest/                     → public API
includes/admin/                    → Platform UI
includes/demo/                     → Phase 14 seed
```

---

## Anti-patterns (do not)

1. Copy Companion booking SQL into theme templates.  
2. Hard-code production PayFast credentials in git.  
3. Leave Phase 14 demo mode on in production.  
4. Rename `ngc_*` REST namespaces without a migration plan.  
5. Edit only `127.0.0.1` bookmarks — cookie host must match `localhost:8900` in local Docker.  
6. Add a second “source of truth” for child learners / wallets outside Companion.

---

## Client notes (fill per engagement)

| Field | Value |
|-------|--------|
| Client / brand name | |
| Skin preset id | |
| Primary / accent hex | |
| Go-live URL | |
| Demo allowed? | Staging only / No |
| Custom Companion modules | |
| Owner engineer | |
| Sign-off date | |

---

*End of bespoke guide. For architecture depth, continue in `documentation/platform-architecture.md`.*
