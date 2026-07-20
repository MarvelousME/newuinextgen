# COMPARE-DIFFERENCES.md

**Artifact:** `nextgentutors-beyondinfinity` (workspace folder: `beyondinfinity/`)  
**Phase:** 0 → 0b — Inventory updated with canonical full theme  
**Date:** 2026-06-26  
**Evidence run:** Filesystem scan of `WP-THEME-NEXT-GEN` + `beyondinfinity/` — **NOT PRODUCTION-VERIFIED** (no live WP install)

---

## Executive summary

| Artifact | Location | Counts | Status |
|----------|----------|--------|--------|
| **Canonical `nextgen-tutors` theme** | `Desktop/WP-THEME-NEXT-GEN/nextgen-tutors/` | **143 PHP, 20 JS, 9 CSS** | **Present** — full SmartHead-style build with `inc/`, integrations, prototypes |
| Subset `nextgen-tutors-theme` | `Desktop/nextgentutors-full-website/.../nextgen-tutors-theme` | 15 PHP, 1 JS, 1 CSS | **Stale subset** — do not use as port source |
| `nextgentutors-beyondinfinity` | `Pictures/infinity/beyondinfinity` | 52 PHP, 1 JS, 3 CSS | **Present** — v1.2.1; presentation + POPIA pages + builder compat |
| `nextgencompanion` plugin | Expected `nextgencompanion/nextgencompanion.php` | — | **MISSING** — no `NGC_VERSION`, no `ngc_*` anywhere in `WP-THEME-NEXT-GEN` |
| `nextgentutors-workflows` | `WP-THEME-NEXT-GEN/plugins/` + theme `bundled-plugins/` | 1 plugin | **Present** — early bootstrap for FluentCRM/Forms/Woo/Amelia |
| Desktop substitute stack | `nextgentutors-full-website/public_html/plugins/` | 3 plugins | **Present** — `nextgen_*` / overlapping `ngt/v1`; not wire-compatible with BI `ngc_*` |

**Canonical source for Phase 1 ports:** `C:\Users\marvi\Desktop\WP-THEME-NEXT-GEN\nextgen-tutors\`

**Critical gap:** BeyondInfinity consumes `[ngc_*]` shortcodes (master spec). Neither the canonical NGT theme nor any plugin in `WP-THEME-NEXT-GEN` registers them — NGT uses `[ngt_*]` + Fluent Forms + REST instead.

---

## 0. `WP-THEME-NEXT-GEN` repository layout

| Path | Role | Phase 1 action |
|------|------|----------------|
| `nextgen-tutors/` | Full WordPress theme (canonical) | **Primary port source** |
| `plugins/nextgentutors-workflows/` | Standalone + bundled workflow bootstrap | **Evaluate merge into companion** — overlaps theme `inc/workflows.php` |
| `tests/` | `wp-stubs.php`, `run-tests.php` | **Reference only** — not packaged with theme |
| `smarthead/` | Reference theme architecture | **Drop from product** — inspiration only |
| `dist/`, `extracted/` | Build artifacts | **Drop from zips** |
| `*.html` (root) | Static React→WP prototypes | **Superseded** by theme `prototypes/` + `template-parts/` |
| `*.zip` | Historical packages | **Do not deploy** — use live `nextgen-tutors/` folder |
| `.wp-env.json` | Local WP env config | **Exclude from production zips** |

---

## 1. Theme identity

| Field | Canonical `nextgen-tutors` | `beyondinfinity` (target) |
|-------|---------------------------|---------------------------|
| Theme Name | NextGen Tutors | BeyondInfinity |
| Folder slug | `nextgen-tutors` | `beyondinfinity` → **rename to `nextgentutors-beyondinfinity` in Phase 1** |
| Version | 1.0.0 (`style.css` + `NGT_VERSION`) | 1.2.1 (`BI_VERSION`) |
| Parent | Standalone (not Hello Elementor child) | hello-elementor child |
| Architecture | SmartHead-style `inc/storage.php` bootstrap, 9 integration bridges | Flat `inc/` (8 modules) + 18 default partials |
| Data layer | **In theme** (CPTs, DB tables, REST, chat) | **Presentation only** — expects companion |

---

## 2. Page templates

Canonical NGT uses `page-templates/*.php` (registered templates) + `dashboard/*.php` + `single-tutors.php`. BeyondInfinity uses root `page-*.php` files.

| Page / slug | Canonical NGT | BeyondInfinity | Action | Notes |
|-------------|---------------|----------------|--------|-------|
| Home | `front-page.php` + 10 `template-parts/home/*` sections | `front-page.php` + `inc/defaults/home.php` | **merge** | Port NGT home sections (marquee, tutors-carousel, stats-guarantee); keep BI builder wrapper |
| Find a tutor | `page-templates/find-a-tutor.php` + directory/booking-steps | `page-find-tutor.php` + `ngc_find_tutor_form` intake | **merge** | NGT = searchable directory + Amelia; BI = parent intake form — both UX layers |
| Become a tutor | `page-templates/become-a-tutor.php` | `page-become-a-tutor.php` + duplicate `page-become-tutor.php` | **dedupe** | Single slug; drop `page-become-tutor.php`; NGT uses Fluent Form via `ngt_fluentform_shortcode` |
| About | `page-templates/about.php` | `page-about.php` | **merge** | Keep BI POPIA tone; port NGT layout sections |
| Contact | `page-templates/contact.php` | `page-contact.php` | **merge** | BI shortcode block; NGT has support portal shortcode |
| Pricing | `page-templates/pricing.php` + WooCommerce cards | `page-pricing.php` + JS calculator | **merge** | Port `[ngt_pricing_cards]` / WC integration; keep BI ZAR calculator |
| Blog | `page-templates/blog.php` | — | **port** | New in consolidated theme |
| Guarantee | `page-templates/guarantee.php` | — | **port** | New page + default partial |
| Onboarding | `page-templates/onboarding.php` + `assets/js/onboarding.js` | — | **port** | Role-based onboarding flow |
| Support | `page-templates/support.php` + `[ngt_support_portal]` | — | **port** | FluentSupport integration |
| Privacy | `page-templates/privacy.php` | `page-privacy-policy.php` | **keep (BI)** | BI POPIA-oriented copy wins |
| Terms | `page-templates/terms.php` | `page-terms.php` | **keep (BI)** | BI copy wins |
| Safety guide | `page-templates/safety-guide.php` | `page-safety-guide.php` | **merge** | BI in-person safeguards + NGT layout |
| Tutor vetting | `page-templates/tutor-vetting.php` | `page-tutor-vetting.php` | **keep (BI)** | BI trust badges; port NGT vetting profile parts if unique |
| Register | — (onboarding covers signup) | `page-register.php` | **keep (BI)** | `ngc_*` forms — blocked until companion |
| Login | — | `page-login.php` | **keep (BI)** | `ngc_*` — blocked until companion |
| Thank you | — | `page-thank-you.php` | **keep (BI)** | Post-form confirmation |
| Child safety | — | `page-child-safety.php` | **keep (BI)** | Minors POPIA — BI only |
| Parent dashboard | — (student dashboard template exists) | `page-parent-dashboard.php` | **keep (BI)** | NGT has no dedicated parent shell |
| Student dashboard | `dashboard/student-dashboard.php` | `page-student-dashboard.php` | **merge** | Port NGT REST-driven dashboard JS; BI auth shell |
| Tutor dashboard | `dashboard/tutor-dashboard.php` | `page-tutor-dashboard.php` | **merge** | Port `tutor-dash.js`, earnings REST |
| Admin dashboard | `dashboard/admin-dashboard.php` | `admin-dashboard.php` | **merge** | Port NGT KPI REST widgets; BI capability gates |
| Single tutor | `single-tutors.php` + `template-parts/tutor/*` | — | **port** | Required for `tutors` CPT archive UX |
| WooCommerce | `woocommerce/archive-product.php` | — | **port (optional)** | Session packages; depends on WC active |
| Elementor canvas | `page-templates/elementor.php` | via `inc/page-builders.php` | **keep (BI)** | BI empty-meta fix (v1.2.1) is authoritative |

**BI-only pages (no NGT equivalent):** register, login, thank-you, child-safety, parent-dashboard — **retain without duplication**.

---

## 3. Shortcodes

Verified registrations in canonical theme (`inc/shortcodes.php` only):

| Shortcode | Canonical NGT | BeyondInfinity | Action |
|-----------|---------------|----------------|--------|
| `[ngt_tutor_search]` | Registered — hero search / Fluent Form fallback | — | **port** — replaces spec's `[ngt_tutors]` (not on disk) |
| `[ngt_stats]` | Registered — REST-driven counters | Partial in home defaults | **port** |
| `[ngt_pricing_cards]` | Registered — WooCommerce `session-package` tag | — | **port** |
| `[ngt_demo_accounts]` | Registered — gated by `ngt_demo_mode` | — | **port (dev/staging only)** |
| `[ngt_support_portal]` | Registered — FluentSupport bridge | — | **port to companion** |
| `[ngt_tutor_directory]` | Registered — loads `find-a-tutor/directory` | — | **port** |
| `[ngt_booking]` | Registered — Amelia `[ameliabooking]` wrapper | — | **port** |
| `[ngt_earnings_calculator]` | **Not found** | — | **drop** — was spec/subset assumption |
| `[ngt_tutors]` / `[ngt_tutor_grid]` / `[ngt_tutor_card]` | **Not found** | — | **drop** — use `[ngt_tutor_directory]` |
| `[ngc_find_tutor_form]` | **Not in NGT repo** | Consumed | **companion owns** |
| `[ngc_become_tutor_form]` | Not in NGT repo | Consumed | **companion owns** |
| `[ngc_contact_support_form]` | Not in NGT repo | Consumed | **companion owns** |
| `[ngc_parent_register_child_form]` | Not in NGT repo | Consumed | **companion owns** |
| `[ngc_student_register_form]` | Not in NGT repo | Consumed | **companion owns** |
| `[ngc_login_form]` / `[ngc_forgot_password_form]` | Not in NGT repo | Consumed | **companion owns** |
| `[ngc_*_dashboard]` (4) | Not in NGT repo | Consumed | **companion owns** — NGT uses REST + JS dashboards instead |
| `[nextgen_*]` (platform plugins) | Desktop stack only | — | **alias or deprecate** in Phase 2 |

**Dedupe rule:** Theme renders shells; companion registers `ngc_*`; NGT `ngt_*` shortcodes become companion aliases or theme template-parts — **never both owning the same UX**.

---

## 4. Custom Post Types & taxonomies

| Slug | Canonical NGT | BeyondInfinity | Action |
|------|---------------|----------------|--------|
| `tutors` | Registered (`inc/custom-post-types.php`) | — | **move to companion** — single slug; not `ngt_tutor` |
| `testimonials` | Registered | — | **move to companion** |
| `resources` | Registered | — | **move to companion** |
| Taxonomies | `subject`, `province`, `curriculum`, `grade`, `format` on tutors/resources | — | **move to companion** — includes seeded CAPS/IEB terms |

| Roles | Canonical NGT | BeyondInfinity | Action |
|-------|---------------|----------------|--------|
| `tutor` | Registered + caps `ngt_view_earnings`, `ngt_manage_sessions` | `tutor` via `inc/roles.php` | **dedupe** — align caps |
| `student` | Registered + `ngt_book_sessions`, `ngt_view_courses` | `student` | **dedupe** |
| `parent_guardian` | Registered + `ngt_view_children` | `parent` | **dedupe** — rename to one canonical role |

---

## 5. Custom database tables

All created in canonical theme `inc/database.php` on `after_switch_theme`:

| Table | Purpose | Action |
|-------|---------|--------|
| `{prefix}ngt_earnings` | Tutor payouts per booking | **move to companion** |
| `{prefix}ngt_ratings` | Post-session ratings | **move to companion** |
| `{prefix}ngt_payouts` | Period payout batches | **move to companion** |
| `{prefix}ngt_referrals` | Referral rewards | **move to companion** |
| `{prefix}ngt_session_logs` | Attendance/notes | **move to companion** |
| `{prefix}ngt_chat_messages` | In-platform chat (not in subset theme) | **move to companion** |

**Theme must not create tables after Phase 1** — companion owns migrations + `ngt_db_version`.

---

## 6. REST / AJAX routing

Canonical theme registers **`ngt/v1`** in `inc/rest-api.php` (22 routes):

| Route | Methods | Owner today | Phase 2 action |
|-------|---------|-------------|----------------|
| `/stats`, `/tutors`, `/tutors/{id}`, `/subjects` | GET | Theme | **companion** — public tutor discovery |
| `/ratings`, `/referral/apply`, `/notifications` | POST/GET | Theme | **companion** |
| `/chat`, `/chat/contacts`, `/chat/messages` | GET/POST | Theme | **companion** — POPIA audit required |
| `/dashboard/student`, `/dashboard/tutor`, `/dashboard/admin` | GET | Theme | **companion** — align with `ngc_*_dashboard` shells |
| `/tutor/earnings`, `/admin/stats` | GET | Theme | **companion** |
| `/admin/tutors/{id}/approve`, `/reject` | POST | Theme | **companion** |
| `/workflows`, `/workflows/{id}`, `/sync` | GET/POST | Theme + workflows plugin | **companion or workflows plugin** |
| `/onboarding/users`, `/system-status` | GET/POST | Theme | **companion** |
| `admin-ajax.php` (`ngt_setup_*`, chat fallback) | AJAX | Theme | **companion** |
| `ngc/v1` | — | Expected companion | **create Phase 2** — canonical public API |
| `ngt/v1` (platform plugins) | Overlap | Desktop plugins | **deprecate → alias `ngc/v1`** |

**Target:** Theme keeps **zero** REST routes; companion absorbs all data endpoints.

---

## 7. `inc/` modules — canonical NGT vs BeyondInfinity

| Module | Canonical NGT (`inc/`) | BeyondInfinity | Action |
|--------|------------------------|----------------|--------|
| bootstrap.php + storage.php | Central module loader | — | **do not port to theme** — companion pattern |
| database.php | 6 tables | — | **companion** |
| custom-post-types.php, taxonomies.php, tutor-meta.php | CPT + meta | — | **companion** |
| rest-api.php | 22 routes | — | **companion** |
| shortcodes.php | 7 `ngt_*` shortcodes | template-tags (consume `ngc_*` only) | **dedupe** |
| ajax-handlers.php, chat-messages.php | Chat + wizard AJAX | — | **companion** |
| dashboard-data.php, tutor-lifecycle.php | Dashboard + vetting pipeline | — | **companion** |
| activation-pipeline.php, setup-wizard.php | First-run wizard | — | **port UX to companion admin** |
| demo-importer.php, block-patterns.php | Demo content | — | **optional Phase 1** |
| elementor.php, elementor-widgets.php | Builder widgets | — | **port widgets if Elementor kept** |
| fluentcrm-hooks.php, amelia-hooks.php, gamipress-hooks.php, woocommerce.php | Integration hooks | — | **companion** |
| workflow-registry.php, workflows.php | Workflow orchestration | — | **workflows plugin or companion** |
| enqueue.php | 20 JS + 9 CSS assets | 1 JS + 3 CSS | **merge assets selectively** — no duplicate bundles |
| page-content.php, prototype-hooks.php | Section renderers | `inc/page-wrapper.php`, defaults | **merge render pattern** |
| customizer.php | Theme options | `inc/customizer.php` | **merge** — BI WhatsApp/contact wins for public |
| acf-fields.php | ACF field groups | — | **companion or drop if ACF not required** |
| page-builders.php | — | Present | **keep (BI)** |
| security.php, roles.php, seo.php | Partial in NGT | Present | **keep (BI)** — POPIA + auth gates |
| admin.php | — | Sync Launch Pages | **keep (BI)** |

**Integrations folder** (`integrations/{slug}/`): amelia, elementor, fluentcrm, fluent-forms, fluent-support, gamipress, masterstudy-lms, woocommerce, workflows — **all companion scope**, not theme.

---

## 8. Assets (dedupe map)

| Asset group | Canonical NGT | BeyondInfinity | Action |
|-------------|---------------|----------------|--------|
| Design tokens / components | `assets/css/tokens.css`, `components.css`, `pages.css` | `style.css` utilities | **merge into BI CSS** — one token file |
| Home interactivity | `home.js`, `tutors.js`, `preloader.js` | `main.js` (pricing calc, province selector) | **merge into single `main.js`** |
| Dashboards | `dashboard.js`, `dashboard-api.js`, `admin-dash.js`, `tutor-dash.js` | Fallback panels only | **port when companion live** |
| Chat | `chat.js`, floating UI CSS | — | **companion + optional theme shell** |
| Onboarding / setup | `onboarding.js`, `setup-wizard-admin.js` | — | **companion admin** |
| Prototypes (`prototypes/*-body.php`) | 18 legacy monoliths | — | **drop** — superseded by `template-parts/` |

---

## 9. Security artifacts

| Item | Scan result | Action |
|------|-------------|--------|
| `application-password.txt` | **NOT FOUND** in `WP-THEME-NEXT-GEN` | No rotation needed from this repo |
| `Zuloo Holdings` string | **NOT FOUND** | Build guard in Phase 2 packaging |
| Demo mode / demo accounts | `ngt_demo_mode` option + `[ngt_demo_accounts]` | **disable in production** |
| Chat fallback (keyword AI) | `ngt_chat_fallback()` in ajax-handlers | **POPIA review before port** — not true RAG |

---

## 10. Interop contract

**Required (master spec):**

```php
defined('NGC_VERSION') || class_exists('NGC_Plugin')
```

| Check | Canonical NGT | BeyondInfinity |
|-------|---------------|----------------|
| Detects companion | **No** | **No** — Phase 1 task |
| Registers `ngc_*` | **No** | Consumes only |
| Owns data layer | **Yes (anti-pattern for target arch)** | **No (correct)** |

**Phase 2:** Create `nextgencompanion` by extracting NGT theme data modules + implementing `ngc_*` shortcodes. Map existing `ngt_*` / Fluent Form IDs to companion form registry.

---

## 11. Three-way source resolution (no duplication)

Use this precedence when the same feature appears in multiple places:

1. **`WP-THEME-NEXT-GEN/nextgen-tutors/`** — canonical for marketplace features (CPT, REST, directory, dashboards, integrations)
2. **`beyondinfinity/`** — canonical for POPIA legal pages, builder-compat wrapper, launch page sync, SEO, security gates, `ngc_*` consumption contract
3. **`nextgentutors-full-website/.../nextgen-tutors-theme`** — **ignore** (15-file stale export)
4. **Root HTML / zips in `WP-THEME-NEXT-GEN`** — **ignore** (prototypes only)

**Never duplicate:** CPT registration, table creation, REST routes, or dashboard data queries in the consolidated theme.

---

## Gate 0 checklist (updated)

| Criterion | Status | Evidence |
|-----------|--------|----------|
| Canonical full theme inventoried | **PASS** | §0–8; 143/20/9 counts |
| Subset vs canonical distinguished | **PASS** | §11 precedence |
| Companion inventoried | **PASS (absent)** | No `ngc_*` in entire `WP-THEME-NEXT-GEN` |
| Every page/shortcode/CPT/table/route listed | **PASS** | §2–6 |
| Each row has port/merge/drop/dedupe | **PASS** | Tables above |
| NOT PRODUCTION-VERIFIED labeled | **PASS** | No live WP test |

**Gate 0: PASS** — canonical source identified; Phase 1 may proceed with `WP-THEME-NEXT-GEN/nextgen-tutors/` as port baseline.

---

## Phase 1 entry criteria

1. Rename theme folder to `nextgentutors-beyondinfinity`
2. Implement `NGC_Plugin` detection + graceful degradation
3. Port from **canonical NGT**: home sections, tutor directory, single-tutor template, guarantee/blog/onboarding/support pages
4. **Dedupe:** remove `page-become-tutor.php`; merge CSS/JS bundles
5. **Do not** copy database/CPT/REST into theme — stub companion interfaces
6. Rewrite merged marketing copy (no verbatim port)

## Phase 2 entry criteria

1. Extract `inc/database.php`, `custom-post-types.php`, `rest-api.php`, `dashboard-data.php`, `chat-messages.php` into `nextgencompanion`
2. Register all `ngc_*` shortcodes + `NGC_VERSION`
3. Alias `ngt/v1` → `ngc/v1` for back-compat
4. Reconcile `parent` vs `parent_guardian` role naming
