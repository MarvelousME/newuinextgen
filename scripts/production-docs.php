<?php
/**
 * Write PRODUCTION documentation from live inventory + known architecture.
 *
 * Usage: php scripts/production-docs.php
 */

$root = dirname( __DIR__ );
$prod = $root . '/PRODUCTION';
$val  = $prod . '/04-VALIDATION';
$docs = $prod . '/02-DOCUMENTATION';
$arch = $prod . '/03-ARCHITECTURE';
$mig  = $prod . '/05-MIGRATION';
$diag = $arch . '/diagrams';

foreach ( [ $docs, $arch, $mig, $diag, $val ] as $d ) {
	if ( ! is_dir( $d ) ) {
		mkdir( $d, 0755, true );
	}
}

$meta = [];
$meta_file = $val . '/inventory-meta.json';
if ( is_file( $meta_file ) ) {
	$meta = json_decode( (string) file_get_contents( $meta_file ), true ) ?: [];
}
$versions = $meta['versions'] ?? [];
$v = function ( $id, $fallback ) use ( $versions ) {
	return $versions[ $id ] ?? $fallback;
};

function wfile( $path, $body ) {
	if ( ! is_dir( dirname( $path ) ) ) {
		mkdir( dirname( $path ), 0755, true );
	}
	file_put_contents( $path, $body );
}

$theme_v = $v( 'NextGenTutors-BeyondInfinity', '1.9.29' );
$comp_v  = $v( 'NextGenTutors-Companion', '1.9.19' );
$train   = '2026.09.12';

$stack = <<<MD
| Package | Version | Role | Required |
|---|---|---|---|
| Hello Elementor | {$v('Hello-Elementor','3.5.1')} | Parent theme | Yes |
| NextGenTutors-BeyondInfinity | $theme_v | Presentation | Yes |
| NextGenTutors-Companion | $comp_v | Domain / persistence / REST | Yes |
| NextGenTutors-Plugin-Manager | {$v('NextGenTutors-Plugin-Manager','1.3.5')} | Install order / health | Yes |
| NextGenTutors-Mission-Control | {$v('NextGenTutors-Mission-Control','1.0.0')} | Onboarding / ops | Yes |
| nextgen-3d-scroll-manager | {$v('nextgen-3d-scroll-manager','1.2.0')} | GSAP/3D motion | Yes |
| nextgen-3d-filmstrip | {$v('nextgen-3d-filmstrip','1.0.0')} | Filmstrip UI | Yes |
| nextgen-subjects-widget | {$v('nextgen-subjects-widget','1.1.0')} | Subjects grid | Yes |
| NextGenTutors-Html-Importer | {$v('NextGenTutors-Html-Importer','1.0.0')} | One-time HTML import | Optional |
| NextGenTutors-AI-Integration | {$v('NextGenTutors-AI-Integration','1.1.0')} | Agents-api bridge | Optional |
| NextGenTutors-BeyondMeasure | {$v('NextGenTutors-BeyondMeasure','1.0.0')} | Control-plane SPA | Optional |
| nextgen-automation-hub | {$v('nextgen-automation-hub','2.0.0')} | Legacy overlap — leave off | Optional |
MD;

wfile( $docs . '/README.md', <<<MD
# NextGen Tutors — Production distribution

Release train **$train**. Package versions are **WordPress headers**, not ZIP filename fiction.

This folder is the client-facing production kit. Install from `../01-INSTALLABLE-PACKAGES/` onto a **brand-new WordPress** instance. Do not unzip the monorepo root as a theme.

## What ships

$stack

`drop-ins/ngt-ui-library.zip` is **not** a plugin. Copy it to `wp-content/ngt-ui-library` **or** rely on the copy bundled inside the BeyondInfinity theme (`ui-library/`). Companion resolves both.

## Install order

1. WordPress 6.0+ / PHP 8.0+
2. Upload and activate **Hello Elementor**
3. Upload and activate **Companion**
4. Upload and activate Plugin Manager, Mission Control, 3D Scroll Manager, 3D Filmstrip, Subjects Widget
5. Optionally Html Importer (deactivate after import)
6. Upload and activate **BeyondInfinity**
7. Appearance → Menus: confirm **NextGen Primary Grouped** is assigned to Primary
8. Settings → Reading: Home page
9. Create Parent / Tutor / Student users as needed

See [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md). Do not call this production-ready until [../04-VALIDATION/RELEASE-ACCEPTANCE.md](../04-VALIDATION/RELEASE-ACCEPTANCE.md) is signed from a ZIP-upload clean-room run.
MD
);

wfile( $docs . '/INSTALLATION-GUIDE.md', <<<MD
# Installation guide

## Prerequisites

- WordPress 6.2+ recommended (6.0 minimum for Companion)
- PHP 8.0+ (8.2 recommended)
- MySQL 8 / MariaDB 10.6+
- Parent theme **Hello Elementor** (bundled as `Hello-Elementor-v*.zip`)
- `upload_max_filesize` / `post_max_size` ≥ 64M for theme ZIP

## WordPress Upload path (customer)

1. **Appearance → Themes → Add New → Upload** `Hello-Elementor-v*.zip` → Activate.
2. **Plugins → Add New → Upload** `NextGenTutors-Companion-v*.zip` → Activate first.
3. Upload remaining required plugins (Plugin Manager, Mission Control, 3D Scroll Manager, 3D Filmstrip, Subjects Widget). Activate Companion **before** the theme.
4. Upload Html Importer only if you are migrating static HTML; deactivate after import.
5. **Appearance → Themes → Upload** `NextGenTutors-BeyondInfinity-v$theme_v.zip` → Activate.
6. If Magic UI catalog is missing, extract `drop-ins/ngt-ui-library.zip` to `wp-content/ngt-ui-library`.

Activation must produce no fatals. Companion creates `wp_ngc_*` tables and registers CPT `tutors`. The theme must not crash if Companion is later deactivated — forms fall back to theme shortcode shells.

## Clean-room Docker (engineering)

From `docker/`:

```powershell
.\\clean-install.ps1
```

This compose file bind-mounts **only the PRODUCTION ZIPs**, not the monorepo. That is the acceptance path. The developer stack (`start.ps1`) mounts source and is **not** a production proof.

## After activation

- Mission Control / Companion provisioning creates required pages from [`inc/pages-registry.php`](../../inc/pages-registry.php).
- Menu **NextGen Primary Grouped** is synced by [`inc/nav-menu.php`](../../inc/nav-menu.php).
- Roles `parent`, `parent_guardian`, `tutor`, `student` are installed by Companion `NGC_Roles` (theme also registers read-only stubs if Companion is absent).

Do not install **nextgen-automation-hub** alongside Companion unless you have a documented reason. Hub registers overlapping CPTs and REST.
MD
);

wfile( $docs . '/PRODUCTION-DEPLOYMENT-GUIDE.md', <<<MD
# Production deployment guide

Target site: `https://www.nextgentutors.co.za` (timezone Africa/Johannesburg, currency ZAR).

1. Take a full backup of any existing site before overlaying packages.
2. Deploy to staging first using the ZIP upload path in INSTALLATION-GUIDE.md.
3. Inject secrets **only in wp-admin / wp-config** after install: PayFast merchant id/key/passphrase, SMTP, AI BYOK keys, gateway HMAC. None of these ship in ZIPs.
4. Disable `WP_DEBUG_DISPLAY` on production. Keep `WP_DEBUG_LOG` until the first week is clean.
5. Replace WP-Cron with system cron.
6. TLS, daily DB backups, and object cache are host concerns — see BACKUP-RESTORE.md and SECURITY-GUIDE.md.
7. External plugins (WooCommerce, Elementor, Amelia, MasterStudy, FluentCRM, PayFast gateway) are licensed separately. Companion adapters fail closed if they are absent.

Do not use `docker/.env` values (`NextGenAdmin!2026`, `staging-local-secret`) in production.
MD
);

wfile( $docs . '/CLIENT-HANDBOOK.md', <<<MD
# Client handbook

NextGen Tutors is a South African tutoring marketplace: parents find vetted tutors, book lessons, pay in ZAR, and follow progress. Students attend. Tutors teach and get paid. Administrators run matching, safeguarding, and content.

The public website (Home, Find a Tutor, Become a Tutor, Pricing, Safety, About) is the BeyondInfinity theme. Bookings, wallets, subjects, and tutor records live in the Companion plugin so they survive a theme switch.

Start here:

- Parents — [PARENT-GUIDE.md](PARENT-GUIDE.md)
- Students — [STUDENT-GUIDE.md](STUDENT-GUIDE.md)
- Tutors — [TUTOR-GUIDE.md](TUTOR-GUIDE.md)
- Site operators — [ADMINISTRATOR-GUIDE.md](ADMINISTRATOR-GUIDE.md)
MD
);

wfile( $docs . '/ADMINISTRATOR-GUIDE.md', <<<MD
# Administrator guide

- WP-Admin → NextGen Companion / Mission Control for domain data, provisioning, and health.
- Plugin Manager shows first-party stack status (Companion required; 3D/subjects required for visual parity; Html Importer optional).
- Pages: keep slugs in PAGE-MATRIX.csv. Do not delete Home, Find a Tutor, dashboards, login, register.
- Menu: Primary location must use **NextGen Primary Grouped**.
- Users: assign roles `parent` / `tutor` / `student` (Companion caps). Do not rely on theme read-only stubs in production.
- Integrations: Woo + PayFast for checkout; Amelia for booking calendars; MasterStudy for LMS; FluentCRM for lifecycle email. Missing plugins must not fatal the site.
- Html Importer: dry-run, import, verify, deactivate.
MD
);

wfile( $docs . '/TUTOR-GUIDE.md', <<<MD
# Tutor guide

1. Apply via **Become a Tutor**.
2. After approval, open **Tutor Dashboard** (`/tutor-dashboard`, shortcode `ngc_tutor_dashboard`).
3. Keep subjects, grades, province, and availability current — they power matching and the Find a Tutor marketplace.
4. Join lessons from the dashboard when the session window opens.
5. Earnings and payouts are Companion wallet/ledger data, not theme options.
MD
);

wfile( $docs . '/PARENT-GUIDE.md', <<<MD
# Parent guide

1. Register at `/register` (child learner + parent account).
2. Browse `/find-a-tutor` or request a match.
3. Book from the tutor profile or booking drawer.
4. Pay via checkout (`/parent-checkout`, `ngc_parent_checkout`) when WooCommerce/PayFast is configured.
5. Track lessons on `/parent-dashboard` (`ngc_parent_dashboard`).
MD
);

wfile( $docs . '/STUDENT-GUIDE.md', <<<MD
# Student guide

1. A parent usually creates the student login.
2. `/student-dashboard` (`ngc_student_dashboard`) lists upcoming sessions.
3. Join when the lesson is open. Content remains visible if JavaScript fails; motion/3D is progressive enhancement.
MD
);

wfile( $docs . '/TROUBLESHOOTING.md', <<<MD
# Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| “The theme is missing the style.css stylesheet” | ZIP used Compress-Archive / backslash names / data-descriptor flag | Use packages from this PRODUCTION tree (Python zip writer) |
| White screen after theme activate | Parent Hello Elementor missing | Install Hello Elementor first |
| Empty dashboards | Companion inactive; theme fallbacks are shells | Activate Companion; confirm `NGC_Plugin` exists |
| Duplicate GSAP in Network | Old vendor loader hitting jsDelivr while theme loads cdnjs | This release registers `bi-ngt-gsap` once; vendor loader reuses `window.gsap` |
| Prototype SQL mentioning `ngt_earnings` | Stale labels in `prototypes/*-body.php` | Live tables are `wp_ngc_*`. Do not flatten prototypes |
| Magic UI missing | `wp-content/ngt-ui-library` absent | Theme-bundled `ui-library/` or drop-in zip |
| Hub + Companion both active | Overlapping CPTs/REST | Deactivate Automation Hub |
MD
);

wfile( $docs . '/BACKUP-RESTORE.md', <<<MD
# Backup and restore

- **Database:** all customer records live in WordPress core tables plus `wp_ngc_*` (Companion). Theme switch does not drop them.
- **Deactivate Companion:** tables remain. Uninstall drops tables **only** if `NGC_DROP_TABLES` is defined true in Companion `uninstall.php`.
- **Files:** `wp-content/uploads`, theme, plugins.
- Restore: import SQL, restore uploads, re-upload the same PRODUCTION ZIPs, activate Companion before the theme.
- Docker developer backups: `docker/scripts/backup-wp.ps1` — not a substitute for host backups.
MD
);

wfile( $docs . '/SECURITY-GUIDE.md', <<<MD
# Security guide

- REST `ngc/v1` routes use `permission_callback` (login, `manage_options`, or role caps). Do not expose Universal API (`nuapi/v1`) on production.
- Forms/AJAX: Companion and theme fallbacks must send nonces (`bi_nonce`, `wp_rest`).
- Secrets never belong in ZIPs. Review `04-VALIDATION/inventory-meta.json` `secret_hits` before each ship. Exclude `docker/.env` and `config/ngt-onepass-eval.php`.
- Output escaped (`esc_html`, `esc_url`); input sanitized (`sanitize_text_field`, `sanitize_key`).
- POPIA: Companion consent log + `[ngc_popia_withdraw]`.
- phpMyAdmin in Docker binds to 127.0.0.1 — keep it that way.
MD
);

wfile( $docs . '/UPGRADE-GUIDE.md', <<<MD
# Upgrade guide

1. Backup DB + files.
2. Replace plugin/theme ZIPs via WP upload (or overwrite folders keeping `wp-content/uploads`).
3. Activate Companion first so `NGC_Database::create_tables()` / dbDelta can run (idempotent).
4. Visit wp-admin once, then flush permalinks.
5. Do not delete `wp_ngc_*` tables between versions.
6. Html Importer is not an upgrade tool.
7. Version headers in this train: theme $theme_v, Companion $comp_v. Ignore older `release/` ZIPs tagged 1.9.5 / 1.9.17 / 1.9.34.
MD
);

wfile( $docs . '/INTEGRATIONS-GUIDE.md', <<<MD
# Integrations guide

Companion adapters (`includes/adapters/`, `includes/integrations/`):

| System | Class | Behaviour if absent |
|---|---|---|
| WooCommerce / PayFast | `NGC_PayFast`, `NGC_Parent_Checkout`, Woo catalog | `{ ok: false }` / skip; no fatal |
| Amelia | `NGC_Amelia_Adapter` | `is_available()` false |
| MasterStudy LMS | `NGC_Masterstudy_Adapter` | notice + early return |
| FluentCRM | `NGC_Fluentcrm_Adapter` | try/catch |
| GamiPress / AutomatorWP / Fluent Support | bootstrap | inactive reasons |

Theme Woo hooks in `inc/workflows.php` no-op when `NGC_Plugin` exists.

WhatsApp OpenWA REST (`bi/v1`) remains theme-owned in this release (`inc/openwa.php`).
MD
);

wfile( $docs . '/RELEASE-NOTES.md', <<<MD
# Release notes — $train

## Why this train exists

Previous `delivery/` packages were marked STAGING ONLY and mixed theme 1.9.17 with plugins 1.9.5. `dist/release-manifest.json` stamped Plugin Manager as 1.9.19 while the header is 1.3.5. This distribution uses **actual headers**.

## Changes

- Junction-free BeyondInfinity ZIP copied from monorepo theme files (not the whole repo).
- Companion ZIP excludes `build-src/` and `tests/`.
- Plugin Manager ZIP is slim (no `offline-packages`).
- Theme `bi_companion_active()` requires `NGC_Plugin` — no fake Companion via version constants.
- GSAP/ScrollTrigger registered once as `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.
- Plugin Manager first-party matrix includes 3D + Subjects as required visual stack.
- UI library bundled in the theme and also as a drop-in zip.
- `ngt/v1` remains Companion’s legacy alias of `ngc/v1`. There is no `ngtbi_*` API in this tree.

## Not in this train

- Moving OpenWA or theme Woo fallbacks into Companion.
- Flattening `prototypes/*-body.php`.
- Bundled Amelia / Elementor / WooCommerce / MasterStudy / FluentCRM (licensed separately).
MD
);

$sys = <<<MD
# System architecture

Release train $train. Live code roots: BeyondInfinity theme $theme_v, Companion $comp_v.

```mermaid
flowchart TB
  WP[WordPress]
  Hello[Hello Elementor]
  Theme[BeyondInfinity presentation]
  Comp[Companion domain]
  Support[PluginManager MissionControl Subjects Filmstrip Scroll3D]
  WP --> Hello --> Theme
  WP --> Comp
  WP --> Support
  Theme -->|"shortcodes REST providers"| Comp
  Comp -->|"CPTs wp_ngc tables ngc/v1"| Domain[(Tutors Bookings Payments CRM LMS AI)]
```

## Ownership

| Concern | Owner |
|---|---|
| Templates, header/footer, kinetic, GSAP chrome | Theme |
| CPT `tutors`, `testimonials`, `resources` | Companion |
| `wp_ngc_*` tables | Companion |
| REST `ngc/v1` (+ alias `ngt/v1`) | Companion |
| REST `ngt3d/v1` | 3D Scroll Manager |
| REST `bi/v1` OpenWA | Theme (this release) |
| Roles/caps | Companion `NGC_Roles` (theme stubs if Companion absent) |

Narrow contracts: shortcodes `ngc_*`, REST `ngc/v1`, UI providers, `bi_companion_active()`.
MD;
wfile( $arch . '/SYSTEM-ARCHITECTURE.md', $sys );
wfile( $diag . '/system.mmd', "flowchart TB\n  WP[WordPress] --> Theme[BeyondInfinity]\n  WP --> Comp[Companion]\n  Theme --> Comp\n" );

wfile( $arch . '/THEME-ARCHITECTURE.md', <<<MD
# Theme architecture

Hello Elementor child. Entry: `style.css` + `functions.php` (`BI_VERSION` $theme_v).

- Shell: `header.php` / `footer.php` → `templates/header|footer/*` or Elementor Theme Builder locations.
- Nav: `inc/nav-menu.php` builds **NextGen Primary Grouped**.
- Pages: `page-*.php` routers → `inc/defaults/*` → `prototypes/*-body.php` via blend (`inc/prototype-blend.php`) or `inc/defaults-production/*`.
- Do not treat `template-parts/pages/*` as SSOT.
- Motion: `inc/ngt-assets.php` registers GSAP; `inc/bi-3d.php` / kinetic modules render presentation 3D.
- Companion interop: `inc/companion.php`. Shortcode fallbacks: `inc/shortcodes-fallback.php` (only if `NGC_Plugin` missing).
MD
);

wfile( $arch . '/COMPANION-ARCHITECTURE.md', <<<MD
# Companion architecture

Bootstrap: `nextgencompanion.php` → `NGC_Loader` → `NGC_Plugin` → `NGC_Plugin_Bootstrap` modules.

- Persistence: `NGC_Database::create_tables()` (dbDelta, version option `ngc_db_version`).
- CPTs: `NGC_Post_Types`.
- REST: `NGC_Rest` namespace `ngc/v1`; `NGC_Rest_Legacy_Alias` mirrors to `ngt/v1`.
- Shortcodes: `NGC_Shortcodes` plus marketplace, matching, checkout, builder, studio.
- Integrations boot with `is_available()` gates.
- Uninstall does **not** drop tables unless `NGC_DROP_TABLES`.
MD
);

wfile( $arch . '/DATA-ARCHITECTURE.md', <<<MD
# Data architecture

Canonical prefix: `wp_ngc_*` (see DATABASE-SCHEMA.md). WordPress posts hold CPT `tutors` and taxonomies `subject`, `province`, `grade`, `learning_format`.

Theme demo arrays in `inc/tutor-data.php` are presentation fallbacks; live roster is Companion CPT + `NGC_Tutor_Cpt_Source`.

Do not treat prototype comments about `ngt_earnings` as schema.
MD
);

wfile( $arch . '/REST-API-ARCHITECTURE.md', <<<MD
# REST API architecture

Canonical: `ngc/v1` (`NGC_Rest::NAMESPACE`). Legacy alias: `ngt/v1` (same handlers). **No `ngtbi_*` routes exist.**

Theme `bi_rest_namespace()` returns `ngc/v1` when `NGC_Plugin` exists, else `ngt/v1` for old clients (those routes will 404 without Companion).

Other namespaces: `ngt3d/v1` (motion), `ngtai/v1` (optional AI plugin), `bi/v1` (OpenWA theme), `nextgentutors-control/v1` (optional BeyondMeasure).

See REST-ENDPOINTS.csv for the scanned `register_rest_route` inventory.
MD
);

wfile( $arch . '/AUTH-RBAC-ARCHITECTURE.md', <<<MD
# Auth and RBAC

Companion `NGC_Roles` installs `parent`, `parent_guardian`, `tutor`, `student` with `ngc_*` capabilities. Theme `inc/roles.php` only adds `read` if the role is missing.

Dashboard REST requires login. Admin routes require `manage_options` or Companion admin caps. Theme `inc/security.php` redirects role dashboards.
MD
);

wfile( $arch . '/BOOKING-ARCHITECTURE.md', <<<MD
# Booking architecture

Companion: `NGC_Bookings`, `NGC_Session_Orchestrator`, `NGC_Meetings`, REST `class-ngc-rest-bookings.php`, Amelia adapter when present.

Theme: booking drawer chrome (`inc/booking-drawer.php`) and tutor profile CTAs. No booking tables in the theme.
MD
);

wfile( $arch . '/LMS-ARCHITECTURE.md', <<<MD
# LMS architecture

Companion `NGC_Lms` + `NGC_Masterstudy_Adapter`. If MasterStudy is absent, LMS sync is skipped. Theme dashboards still render Companion session data.
MD
);

wfile( $arch . '/CRM-ARCHITECTURE.md', <<<MD
# CRM architecture

Companion FluentCRM adapter bootstraps contacts/tags from domain events. Failures are caught; the public site stays up. FluentSMTP is the mail transport (third-party).
MD
);

wfile( $arch . '/COMMERCE-PAYMENTS-ARCHITECTURE.md', <<<MD
# Commerce and payments

Companion owns checkout (`NGC_Parent_Checkout`), invoices, wallet ledger, payouts, PayFast ITN. WooCommerce is optional. Theme workflow Woo hooks return immediately when Companion is active.
MD
);

wfile( $arch . '/AI-ARCHITECTURE.md', <<<MD
# AI architecture

Companion BYOK models/agents (`includes/ai/`, REST `/ai/*`) stay in Companion. Optional plugin NextGenTutors-AI-Integration is a signed outbox to agents-api (`ngtai/v1`) and must not hold domain tables. Do not put API keys in packages.
MD
);

wfile( $arch . '/UI-MOTION-3D-ARCHITECTURE.md', <<<MD
# UI, motion, 3D

- Theme kinetic home + `bi-3d` + NGT skin.
- 3D Scroll Manager: rules table `wp_*ngt_3d_scroll_rules`, REST `ngt3d/v1`, shared handles `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.
- 3D Filmstrip: `[ngt_filmstrip]` / `[nextgen_filmstrip]`.
- UI library: theme `ui-library/` and/or `wp-content/ngt-ui-library`. Vendor loader will not fetch a second GSAP if `window.gsap` or the theme script tag exists.
- Reduced motion: existing `motion.js` / token durations. Primary content is in PHP prototypes — visible without JS.
MD
);

wfile( $arch . '/DEPLOYMENT-ARCHITECTURE.md', <<<MD
# Deployment architecture

Two Docker stories:

1. **Developer bind-mount** (`docker/docker-compose.yml` + `start.ps1`) — source mounted; Windows junctions not followed in Linux, so extra binds exist for `assets/`, `inc/`, `prototypes/`. Port **8890**.
2. **Clean-install** (`docker/docker-compose.clean-install.yml` + `clean-install.ps1`) — vanilla WordPress, ZIPs from PRODUCTION only. This is the acceptance path.

Production hosts use WP upload, not Docker mounts.
MD
);

wfile( $arch . '/DEPENDENCY-MAP.md', <<<MD
# Dependency map

See `../04-VALIDATION/DEPENDENCY-MATRIX.csv`.

```mermaid
flowchart LR
  Hello[Hello Elementor] --> Theme[BeyondInfinity]
  Comp[Companion] --> Theme
  Comp --> PM[Plugin Manager]
  Comp --> MC[Mission Control]
  Comp --> Subj[Subjects Widget]
  Comp --> Film[Filmstrip]
  Theme --> Scroll[3D Scroll Manager]
  Woo[WooCommerce optional] --> Comp
  Amelia[Amelia optional] --> Comp
```

Required runtime: WordPress + Hello Elementor + Companion + BeyondInfinity + visual plugins. Optional: Woo/Amelia/LMS/CRM, AI Integration, BeyondMeasure. Avoid Automation Hub when Companion is active.
MD
);

wfile( $val . '/DATABASE-SCHEMA.md', <<<MD
# Database schema (Companion)

Created by `NGC_Database::table_names()` / `create_tables()` via dbDelta. Prefix is `{wpdb->prefix}ngc_`.

Matches, bookings, sessions, invoices, wallet_ledger, payouts, reviews, audit_log, tutor_applications, session_logs, earnings, ratings, workflow_runs, analytics_events, visitor_profiles, user_profiles, acquisition_sources, affiliate_clicks, attribution_links, user_sessions, device_profiles, conversion_events, metric_snapshots, demo_seed_log, consent_log, gamification_*, leaderboard_entries, export_*, repair_snapshots, ai_diagnostics_log, referrals, reminder_schedules, studio_*, child_learners, page_sections, builder_*, system_log, intel_*, memory_identity_map, talent_*.

Additional: platform kernel (`NGC_Platform_Schema`), safeguarding/fraud/agent control-plane tables, 3D plugin `ngt_3d_scroll_rules`.

Theme creates **no** custom tables. Migrations are idempotent dbDelta + `ngc_db_version`.
MD
);

wfile( $val . '/SECURITY-AUDIT.md', <<<MD
# Security audit (packaging pass)

- Companion uninstall is fail-safe (no drop unless `NGC_DROP_TABLES`).
- Theme no longer defines `NGC_VERSION`; Companion detection is `class_exists('NGC_Plugin')`.
- Secret scan results: see `inventory-meta.json` → `secret_hits` (review; many hits are password *generators* or docs).
- Do not ship `docker/.env`, PayFast live keys, or `config/ngt-onepass-eval.php`.
- REST permission callbacks remain the runtime control; this document does not replace a pentest.
MD
);

wfile( $val . '/PERFORMANCE-AUDIT.md', <<<MD
# Performance audit (packaging pass)

- GSAP/ScrollTrigger share one handle pair; 3D plugin reuses them.
- 3D Scroll Manager loads engine JS only when the page has rules.
- Kinetic CSS/JS stay page-gated via existing `bi_page_needs_*` helpers.
- Companion ZIP no longer includes ~66 MB `build-src`.
- Theme ZIP must not include nested plugins, `node_modules`, or `release.zip`.
MD
);

wfile( $val . '/CONTENT-PARITY.md', <<<MD
# Content parity

Authority: `PAGE_CONTENT_OWNERSHIP.md` in the theme root.

- Keep `prototypes/*-body.php` as body source when blend is on.
- Production defaults live in `inc/defaults-production/`.
- `template-parts/pages/*` are flatten artifacts — unwired.
- Stale prototype SQL (`ngt_session_logs`) is documentation debt, not live schema.
MD
);

wfile( $val . '/INSTALLATION-TEST.md', <<<MD
# Installation test

Procedure: `docker/clean-install.ps1` using ZIPs in `01-INSTALLABLE-PACKAGES`.

Checklist:

- [ ] Hello Elementor installs
- [ ] Companion activates without fatal
- [ ] Support plugins activate
- [ ] BeyondInfinity uploads and activates
- [ ] `debug.log` has no fatals from our code
- [ ] CPT `tutors` exists
- [ ] Shortcodes registered by Companion
- [ ] NextGen Primary Grouped assigned
- [ ] Home renders body content with JS disabled
- [ ] Theme deactivate/switch does not drop `wp_ngc_*`

Fill results after the clean-room run. Until then this train is a **clean-install candidate**, not a signed production pass.
MD
);

wfile( $val . '/REGRESSION-TESTS.md', <<<MD
# Regression tests

Reuse existing Playwright specs under `e2e/` against the clean-install URL (default http://localhost:8891):

- homepage display
- login role paths
- tutor profile / booking
- five-minute booking journey (needs Woo/PayFast to fully pass)

`scripts/run-playwright.ps1` is the host runner. Bind-mount developer Docker is not a substitute.
MD
);

wfile( $val . '/RELEASE-ACCEPTANCE.md', <<<MD
# Release acceptance

| Gate | Status |
|---|---|
| ZIP upload install | Pending clean-room run |
| No activation fatals | Pending |
| Companion theme-independent | Pending (CPT/tables after theme switch) |
| Theme without Companion degrades | Code path present (`bi_companion_active`) |
| Honest versions / checksums | Produced by `scripts/build-production-release.ps1` |
| Secrets excluded | Packaging excludes `.env`; review secret_hits |
| Docs match packages | This generator uses inventory versions |

Signed off by: ________________  Date: ________________

Do not set recommendation to PRODUCTION PASS until the clean-room script completes successfully.
MD
);

wfile( $mig . '/MIGRATION-GUIDE.md', <<<MD
# Migration guide

From a running NextGen site or static HTML:

1. Backup.
2. Install PRODUCTION packages (Companion first).
3. Html Importer: point at `webpages-content` / uploaded HTML, dry-run, import, rollback if needed, deactivate.
4. Confirm pages in PAGE-MATRIX still resolve via theme routers.
5. Map any leftover `ngt_*` options to Companion — live schema is `ngc_*`.
MD
);

wfile( $mig . '/LEGACY-TO-PRODUCTION-MAPPING.md', <<<MD
# Legacy to production mapping

| Legacy | Production |
|---|---|
| `ngt_*` REST | Companion `ngc/v1` (alias `ngt/v1`) |
| `ngtbi_*` | Does not exist — do not invent shims |
| Theme Tutor CPT | Companion `tutors` CPT |
| Theme DB tables | None in theme; Companion `wp_ngc_*` |
| Theme business APIs | Companion services |
| Theme UI | BeyondInfinity |
| `*-body.php` | Keep as authoritative body when blend on |
| Automation Hub CPTs | Do not migrate onto Hub if Companion is active |
| Monorepo-as-theme ZIP | Invalid — use materialized BeyondInfinity ZIP |
MD
);

wfile( $mig . '/DATABASE-MIGRATIONS.md', <<<MD
# Database migrations

Companion uses versioned dbDelta (`ngc_db_version`, `ngc_platform_db_version`). Re-activation is safe. There is no destructive default uninstall.

3D Scroll Manager has its own schema version (`NGT3D_SCHEMA_VERSION`).

AI Integration uses `NGTAI_Migrator` on its own `wp_ngtai_*` tables (optional plugin).
MD
);

wfile( $mig . '/COMPATIBILITY-MATRIX.md', <<<MD
# Compatibility matrix

| Component | Min | Notes |
|---|---|---|
| WordPress | 6.0 (6.2 for 3D Scroll) | |
| PHP | 8.0 | Plugin Manager header still says 7.4; run 8.0+ |
| Hello Elementor | 3.5.x | Bundled 3.5.1 |
| WooCommerce | latest stable | Optional |
| Elementor | optional | Theme Builder chrome |
| Amelia / MasterStudy / FluentCRM | optional | Graceful skip |
MD
);

fwrite( STDOUT, "Documentation written under $prod\n" );
