# BeyondInfinity — Sequential Setup After Plugins Are Installed

**Product:** NextGen Tutors · BeyondInfinity theme  
**Audience:** Operators, implementers, site admins  
**Last updated:** 2026-07-13  
**Prerequisite:** All **required** stack plugins are installed and activated (see Step 0).

This guide is the **ordered runbook** to bring the theme and its features online after the plugin fleet is ready. Follow steps in order. Do not skip verification gates.

---

## How to use this document

| Stage | When |
|-------|------|
| **Steps 0–3** | Always — theme, pages, menus, branding |
| **Steps 4–6** | Always — integrations, email, commerce/booking |
| **Steps 7–8** | Recommended — content packs + AutomatorWP |
| **Steps 9–10** | Staging: demo seed · Production: real tutors |
| **Steps 11–12** | Always — health + smoke tests before go-live |

**Related docs**

- Full commercial install (from blank WP): [COMMERCIAL-DEPLOYMENT-GUIDE.md](../COMMERCIAL-DEPLOYMENT-GUIDE.md)
- Operator fleet / Docker: [OPERATOR-TUTORIALS.md](OPERATOR-TUTORIALS.md)
- Production sign-off: [PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md)
- Packages map: [PACKAGES.md](../PACKAGES.md)

---

## Step 0 — Confirm prerequisite stack

Before starting theme setup, confirm these are **Installed + Active**.

### First-party (required)

| Package | Location / note |
|---------|-----------------|
| **NextGenTutors-Companion** | Domain plugin — creates `wp_ngc_*` tables |
| **NextGenTutors-BeyondInfinity** | Child of Hello Elementor (`Template: hello-elementor`) |
| **Hello Elementor** | Parent theme (must be installed; BeyondInfinity activated) |
| **NextGenTutors-Plugin-Manager** | Recommended for health / Fix all |

### Registry fleet (required)

| Plugin | Notes |
|--------|-------|
| WooCommerce | Commerce + PayFast |
| Elementor | Page builder |
| FluentCRM | CRM |
| FluentSMTP | Transactional email transport |
| MasterStudy LMS | LMS |
| GamiPress | Achievements |
| AutomatorWP | Automations |
| User Role Editor | Roles |
| Amelia Booking | **Manual zip** — booking |
| PayFast WooCommerce Gateway | **Manual zip** — ZA payments |

### Optional

| Plugin | Notes |
|--------|-------|
| WPBakery (`js_composer`) | Optional builder |
| Ultimate Addons for Elementor | Optional; needs Elementor |
| nextgen-command-center | Content pack |
| nextgen-completion-suite | Content pack |
| NextGenTutors-Html-Importer | One-time migration only |

**WP Admin check**

1. **Plugins → Installed Plugins** — required list above Active  
2. **Appearance → Themes** — `NextGenTutors-BeyondInfinity` Active  
3. **NextGenTutors Plugins** → Scan / Verify — overall status ideally `READY` (or only optional gaps)

**WP-CLI check**

```bash
wp plugin list --status=active --allow-root
wp theme list --status=active --allow-root
wp eval 'echo class_exists("NGC_Plugin") ? "Companion OK\n" : "Companion MISSING\n";' --allow-root
```

**Gate:** Do not continue until Companion is active and BeyondInfinity is the active theme.

---

## Step 1 — Activate theme roles and rewrite rules

Theme activation registers tutoring roles and flushes permalinks.

**WP Admin**

1. If BeyondInfinity is already active: **Appearance → Themes** → switch away briefly → reactivate **NextGenTutors-BeyondInfinity**  
   *(or use Step 2 sync, which also binds pages)*  
2. **Settings → Permalinks** → ensure `Post name` (`/%postname%/`) → **Save Changes** (flush)

**WP-CLI**

```bash
wp theme activate nextgentutors-beyondinfinity --allow-root
wp rewrite structure '/%postname%/' --hard --allow-root
wp eval 'if (function_exists("bi_register_roles")) { bi_register_roles(); }' --allow-root
```

**Timezone (South Africa default)**

```bash
wp option update timezone_string 'Africa/Johannesburg' --allow-root
```

**Gate:** Permalinks work (no 404 on `/find-a-tutor/` after Step 2).

---

## Step 2 — Sync launch pages (required)

Creates/publishes all launch pages from `content/page-map.json`, assigns templates, sets the static front page, and rebuilds nav.

**WP Admin**

1. **Appearance → Sync Launch Pages** (`bi-sync-pages`)  
2. Click **Sync Launch Pages** (or equivalent primary action)  
3. Confirm shortcode health on that screen (Companion tags reported OK)

**WP-CLI**

```bash
wp eval 'if (function_exists("bi_sync_launch_pages")) { bi_sync_launch_pages(); echo "pages synced\n"; }' --allow-root
```

### Pages that must exist after sync

| Slug | Purpose |
|------|---------|
| `home` | Front page (kinetic / production home) |
| `find-a-tutor` | Marketplace + find form |
| `become-a-tutor` | Tutor application |
| `contact` / `support` | Support forms |
| `register` / `login` | Auth |
| `parent-checkout` | Checkout shortcode |
| `parent-dashboard` / `student-dashboard` / `tutor-dashboard` / `admin-dashboard` | Role dashboards |
| `onboarding` | Post-registration onboarding |
| `privacy-policy` / `terms` / trust pages | Legal |
| `wordpress-setup` | Admin-facing setup surface |

**Reading settings**

1. **Settings → Reading**  
2. Your homepage displays: **A static page**  
3. Homepage = **Home**  
4. Privacy Policy page = **Privacy Policy** (if prompted)

**Gate:** Visit `/` — BeyondInfinity home loads (not a blank Twenty* theme).

---

## Step 3 — Menus and header/footer

Sync assigns:

- Primary menu → location `primary` (typically **NextGen Primary Grouped**)  
- Footer menu → location `footer-1`

**WP Admin**

1. **Appearance → Menus**  
2. Confirm Primary and Footer assignments  
3. If empty: return to **Sync Launch Pages** and sync again, or:

```bash
wp eval 'if (function_exists("bi_sync_launch_nav")) { bi_sync_launch_nav(true); echo "nav synced\n"; }' --allow-root
```

**Gate:** Header shows Find a Tutor / Become a Tutor / Login (or grouped nav), not an empty bar.

---

## Step 4 — Theme branding and kinetic home

**WP Admin → Appearance → Customize**

| Setting | Recommended value |
|---------|-------------------|
| Visual preset | `Beyond Infinity` (`visual_preset` = `beyond-infinity`) |
| Home layout | `Kinetic` (`home_layout` = `kinetic`) |
| Color scheme | `default` (or brand choice) |
| Phone / Email / WhatsApp | Live business contacts (`bi_phone`, `bi_email`, `bi_whatsapp`, …) |
| Service area | e.g. South Africa metros |
| Theme switcher | Off for production (or admins-only) |
| Header / footer style | Per brand; leave defaults unless designed otherwise |

**WP-CLI defaults**

```bash
wp theme mod set visual_preset beyond-infinity --allow-root
wp theme mod set home_layout kinetic --allow-root
wp theme mod set color_scheme default --allow-root
```

Also complete branding on **Appearance → NextGen Operations** / Customizer contact section if present.

**Gate:** Homepage shows kinetic marketing layout with brand contact details (not placeholder-only).

---

## Step 5 — Bootstrap integrations (Companion)

Configures PayFast sandbox stubs (local), FluentCRM, GamiPress, MasterStudy, Amelia bootstrap, forms registry, parent checkout.

### Staging / Docker / local

```bash
wp eval 'NGC_Integrations_Bootstrap::configure_local_stack(true);' --allow-root
```

Option set when done: `ngc_integrations_bootstrapped`.

### Production (manual — do not rely on demo bootstrap alone)

| Integration | Where to configure |
|-------------|--------------------|
| **PayFast** | WooCommerce → Settings → Payments → PayFast — live merchant keys |
| **FluentCRM** | FluentCRM → Settings — tags/lists used by workflows |
| **FluentSMTP** | FluentSMTP → Settings — production SMTP / API |
| **Amelia** | Amelia → Settings — services, employees, calendar; API key for Companion |
| **MasterStudy** | MasterStudy → settings / courses as needed |
| **GamiPress** | Points/achievements linked to lesson completion |
| **AutomatorWP** | After Step 8 seed — review automations |

**Amelia safety**

- Ensure Amelia DB tables exist before or via Companion safe activate  
- If broken: Companion Amelia bootstrap / `amelia-safe-activate` scripts (see Operator tutorials)

**Gate:** `wp option get ngc_integrations_bootstrapped --allow-root` returns a timestamp (staging) **or** each production integration page saves without error.

---

## Step 6 — Commerce, pages & plugin wiring

1. **WooCommerce → Settings**  
   - Currency: `ZAR` (typical)  
   - Shipping/tax as required  
   - Pages: shop/cart/checkout/account — WooCommerce pages wizard if never run  
2. Confirm **PayFast** gateway enabled (sandbox on UAT, live on prod)  
3. **Elementor → Tools → Regenerate CSS & Data** (after first theme switch)  
4. **User Role Editor** — confirm tutoring roles exist (parent/student/tutor variants from Companion/theme)

**Gate:** Cart/checkout pages resolve; PayFast appears among payment methods.

---

## Step 7 — Optional content packs

Skip if packs are not licensed/deployed.

### Command Center

1. Activate plugin `nextgen-command-center`  
2. Admin → Command Center → **Run / Repair Setup**  
3. Confirm pages e.g. `/nextgen-command-center/`, `/nextgen-staff-rtm/`

### Completion Suite

1. Activate `nextgen-completion-suite`  
2. Admin → Completion Suite → **Run / Repair Setup**  
3. Confirm pages e.g. progress reports, lesson notes, tutor payouts  

Details: [content-packs/COMMAND-CENTER.md](../content-packs/COMMAND-CENTER.md), [COMPLETION-SUITE.md](../content-packs/COMPLETION-SUITE.md)

**Gate:** Pack setup wizards report success; pack URLs load for admins.

---

## Step 8 — Workflows / AutomatorWP / Integrate Specs

1. **NextGen → Workflows → Integrate Specs** (Companion)  
2. **Import content-pack catalog** (if packs installed)  
3. **Seed AutomatorWP** from platform v2 JSON / importer  
4. Open **AutomatorWP** — confirm automations Active  
5. Spot-check FluentCRM tags used by workflows  

Catalog reference: [workflows/INTEGRATION-CATALOG.md](../workflows/INTEGRATION-CATALOG.md)

**Gate:** At least core automations (registration → CRM contact, tutor approved, booking → notifications) exist and are Enabled.

---

## Step 9 — Tutors / marketplace data

### Staging only (demo)

In `wp-config.php` (local/Docker often already set):

```php
define( 'NGC_ALLOW_DEMO_SEED', true );
```

Then:

```bash
wp eval 'if (class_exists("NGC_Tutor_Seeder")) { NGC_Tutor_Seeder::ensure_seeded(true); }' --allow-root
# or
wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/seed-tutors-docker.php --allow-root
```

### Production

```php
define( 'NGC_ALLOW_DEMO_SEED', false );
```

1. Create real **Tutors** CPT entries (or approve applications from Become a Tutor)  
2. Match subjects/areas used by Find a Tutor filters  
3. Publish at least 1–3 vetted tutors before public marketing pushes

**Gate:** `/find-a-tutor/` lists tutors (demo or real); tutor single pages open.

---

## Step 10 — Forms & shortcode health

Confirm Companion shortcodes render (not theme fallbacks) on key pages:

| Page | Expected shortcodes |
|------|---------------------|
| Find a Tutor | `ngc_tutor_marketplace`, `ngc_find_tutor_form` |
| Become a Tutor | `ngc_become_tutor_form` |
| Contact / Support | `ngc_contact_support_form` |
| Register | parent/student register forms |
| Login | `ngc_login_form`, `ngc_forgot_password_form` |
| Dashboards | `ngc_*_dashboard` |

**WP Admin**

- **Appearance → Sync Launch Pages** — shortcode status  
- **Appearance → BeyondInfinity Health**  
- **NextGen → Health** → run self-healing repair if offered  

```bash
wp ngc verify --allow-root
```

**Gate:** Forms show Companion UI; submitting a test Find a Tutor / Contact does not white-screen.

---

## Step 11 — Platform verification sequence

Run in this order:

### 11.1 Plugin Manager

1. **NextGenTutors Plugins**  
2. **Scan** / **Rescan**  
3. **Fix all** only if required plugins still missing/inactive  
4. **Verify System** / **Run Health Check**  
5. Resolve MANUAL gaps: drop Amelia / PayFast zips into `wp-content/ngcpm-packages/` then Install Queue  

### 11.2 Companion Health

1. **NextGen → Health**  
2. **Run self-healing repair** if checks fail  
3. CLI: `wp ngc verify --allow-root`  
4. Optional: `wp ngc verify --integrations --allow-root`

### 11.3 Theme Health

1. **Appearance → BeyondInfinity Health**  
2. Confirm Companion present, tutors CPT, shortcodes, primary menu, assets  

**Gate:** No FATAL/critical failures; remaining warnings documented as intentional (e.g. optional Ultimate Elementor dismissed).

---

## Step 12 — End-to-end smoke test (sequential journeys)

Perform as different roles (incognito / separate browser profiles).

| # | Journey | Pass criteria |
|---|---------|---------------|
| 1 | Visit `/` | Kinetic home, nav, CTA work |
| 2 | Find a Tutor — submit form | Success state / application logged |
| 3 | Become a Tutor — submit | Application visible under NextGen |
| 4 | Register parent / student | User created; redirected sensibly |
| 5 | Login → Parent dashboard | `parent-dashboard` loads with Companion shortcode |
| 6 | Admin approves tutor | Tutor becomes public on marketplace |
| 7 | Parent Checkout (UAT) | Order created; PayFast sandbox completes |
| 8 | Amelia booking (UAT) | Booking appears in Amelia |
| 9 | FluentCRM | Contact/tag created for registration ||
| 10 | Command Center (if installed) | Metrics page loads for staff |

**Sign-off:** Attach `wp ngc verify` output + screenshot of BeyondInfinity Health (all green / accepted yellows) to the deployment ticket.

---

## Production hardening (after smoke pass)

```php
// wp-config.php
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'NGC_ALLOW_DEMO_SEED', false );
```

- Replace WP-Cron with system cron (see ops docs)  
- Enable backups (DB daily / files weekly)  
- TLS only; restrict admin users  
- Live PayFast + SMTP only after UAT payment/email proof  

Checklist: [PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md)

---

## Quick WP-CLI script (staging replay)

Run **after** required plugins are active. Adjust paths if plugins live under a different folder name.

```bash
wp theme activate nextgentutors-beyondinfinity --allow-root
wp rewrite structure '/%postname%/' --hard --allow-root
wp option update timezone_string 'Africa/Johannesburg' --allow-root

wp eval 'bi_sync_launch_pages();' --allow-root
wp theme mod set visual_preset beyond-infinity --allow-root
wp theme mod set home_layout kinetic --allow-root

wp eval 'NGC_Integrations_Bootstrap::configure_local_stack(true);' --allow-root

# Optional demo (staging only — requires NGC_ALLOW_DEMO_SEED)
# wp eval 'NGC_Tutor_Seeder::ensure_seeded(true);' --allow-root

wp ngc verify --allow-root
```

---

## Failure map (common)

| Symptom | Fix |
|---------|-----|
| Buttons stuck on `...processing` | Hard-refresh admin assets; use latest Companion `ngc-button-processing.js` |
| Install blocked while local zips exist | Plugin Manager ≥ latest — remote wporg installs must not be offline-blocked by unrelated zips |
| Home is wrong theme | Activate BeyondInfinity; Sync Launch Pages; Reading → static Home |
| Shortcodes show plain tags | Activate Companion; re-sync pages |
| Amelia fatal on activate | Use Companion Amelia safe bootstrap; confirm tables exist |
| Empty marketplace | Seed tutors (staging) or publish Tutor CPT (prod) |
| Emails never send | Configure FluentSMTP; send test |
| PayFast missing | Install gateway zip to `ngcpm-packages` / Plugin Manager Add Plugin |

---

## Document control

| Field | Value |
|-------|--------|
| Doc ID | `BEYONDINFINITY-SEQUENTIAL-SETUP` |
| Depends on | Companion 1.9.x · BeyondInfinity 1.9.x · Plugin Manager 1.3.x |
| Supersedes | Ad-hoc operator notes for post-plugin theme bring-up |
| Owner | Platform ops / implementation partners |
