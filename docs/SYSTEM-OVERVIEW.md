# NextGen Tutors REVAMP — System Overview

**Last updated:** 2026-07-06  
**Stack version:** Theme `BI_VERSION` 1.9.0 · Companion `NGC_VERSION` 1.9.0  
**Local dev:** Docker WordPress @ http://localhost:8899

This document is the canonical **whole-system map** of the REVAMP monorepo: what each package does, how data flows, what is verified, and what remains partial.

---

## 1. Executive summary

NextGen Tutors is a **four-package WordPress solution** for a South African tutoring marketplace:

| Package | Role |
|---------|------|
| **BeyondInfinity** (theme) | Presentation, page shells, design system, kinetic homepage, UI Library partials |
| **Companion** (plugin) | Business logic, 44 custom DB tables, REST API, workflows, AI suite, integrations |
| **Html-Importer** | One-time static HTML → WP pages (dry-run, rollback) |
| **Plugin-Manager** | Operator console for stack plugin install (WooCommerce, Amelia, FluentCRM, etc.) |

**Architecture principle:** Theme renders; Companion owns data. No tutor names, prices, ratings, or dashboard KPIs are hardcoded in UI Library partials — they flow through **data providers** (`NGC_UI_*_Data_Provider`).

**Verification status (2026-07-06):**

| Layer | Status |
|-------|--------|
| PHP lint + companion `validate.php` | PASS |
| UI Library hardcode scan | PASS (28 files) |
| Playwright E2E | 28 blueprint-aligned tests (8 specs) |
| Flow audit (25 SVG workflows) | 8+ full · partial reduced via WF-09 + integrate specs |
| Live production UAT | Environment-dependent |

---

## 2. Repository layout

```
REVAMP/
├── NextGenTutors-BeyondInfinity/     # Theme (presentation)
├── NextGenTutors-Companion/          # Plugin (domain + API)
├── NextGenTutors-Html-Importer/      # Migration tool
├── NextGenTutors-Plugin-Manager/     # Fleet manager
├── docker/                           # Local WP 6.7 + MySQL stack
├── e2e/                              # Playwright workflow tests
├── scripts/                          # Repo tooling (audit, release, UI scan)
├── docs/                             # Documentation suite (this file)
├── automations/                      # DEPRECATED AutomatorWP JSON
├── diagrams/                         # Enterprise SVG diagrams
└── ARCHITECTURE.md                   # Four-package SOLID contract
```

---

## 3. Theme: BeyondInfinity

### 3.1 Bootstrap (`functions.php`)

Loads ~25 `inc/` modules: companion bridge, security, tutor-data, config (SmartHead-style options), kinetic home, 3D/motion, SEO, pages registry, **UI Library** (`inc/ui-library/`), workflows, admin.

Constants: `BI_VERSION`, `BI_DIR`, `BI_URI`.

### 3.2 Pages (23 launch pages)

Registry: `content/page-map.json` + `inc/pages-registry.php` + `inc/defaults/{slug}.php`.

| Category | Slugs |
|----------|-------|
| Marketing | `home`, `about`, `pricing`, `guarantee`, `blog`, `contact`, `support` |
| Tutor journey | `find-a-tutor`, `become-a-tutor`, `tutor-vetting`, `safety-guide`, `child-safety` |
| Auth | `register`, `login`, `thank-you`, `onboarding` |
| Dashboards | `parent-dashboard`, `student-dashboard`, `tutor-dashboard`, `admin-dashboard` |
| Legal | `privacy-policy`, `terms` |
| Ops | `wordpress-setup` (admin-only) |

**Home** uses kinetic layout (`inc/defaults/home.php`) with 11 CMS-driven sections when Companion `NGC_Section_CMS` is active.

### 3.3 UI Library (presentation layer)

| Path | Purpose |
|------|---------|
| `inc/ui-library/bootstrap.php` | `ng_ui_component()`, per-page asset enqueue |
| `inc/ui-library/builders.php` | Elementor widget + WPBakery `vc_map` |
| `template-parts/ui-library/*.php` | 11 components (hero, tutor-card, pricing-card, …) |
| `assets/css/ng-ui-*.css` | Design tokens + component CSS |

**Shortcodes (Companion):** `[ng_ui_component slug="…"]` → theme partial via `ngc_ui_render_component` filter.

### 3.4 Design system

- Legacy tokens: `assets/ngt/css/tokens.css` (navy/lime brand)
- Normalized tokens: `assets/css/ng-ui-tokens.css` (`--ng-*` variables)
- Components: `assets/css/components.css`, `kinetic-home.css`, motion system

### 3.5 Tutor presentation

- CPT single: `single-tutors.php` → `bi_render_tutor_profile()`
- Carousel: `bi_get_carousel_tutors()` / `[bi_tutors_carousel]`
- Fallback roster: `ngt_get_tutors()` in `inc/helpers.php` (demo only when CPT empty — **not used in UI Library partials**)

### 3.6 Enterprise blueprint

`docs/enterprise-blueprint/` — WF-01…WF-25 markdown + SVG diagrams, RBAC matrix, user journeys. Source of truth for Playwright blueprint IDs.

---

## 4. Companion plugin

### 4.1 Bootstrap

`nextgencompanion.php` → PSR-4 autoload → `NGC_Loader` → `NGC_Plugin_Bootstrap` loads **52 modules** on `plugins_loaded`.

### 4.2 Data layer

**44 tables** (`wp_ngc_*`): matches, bookings, invoices, wallet, reviews, audit, workflow_runs, page_sections, studio_*, gamification_*, child_learners, analytics, etc.

**CPTs:** `tutors`, `testimonials`, `resources`  
**Taxonomies:** `subject`, `province`, `grade`, `learning_format`

### 4.3 Core subsystems

| Subsystem | Classes | Responsibility |
|-----------|---------|----------------|
| Registration | `NGC_Registration`, `NGC_Forms`, `NGC_Child_Learners` | Parent/student/tutor forms → `admin-post.php` |
| Matching | `NGC_Matching`, `NGC_Smart_Matching` | WF-08 manual assign; WF-09 scoring only (auto-assign deferred) |
| Bookings | `NGC_Bookings`, `NGC_Amelia` | Session lifecycle, Amelia sync |
| Finance | `NGC_Payments`, `NGC_Invoices`, `NGC_Wallet` | Payouts WF-16, invoices WF-12 |
| Reviews | `NGC_Reviews` | Parent ratings → `ngc_reviews` table |
| Section CMS | `NGC_Section_CMS` | 11 homepage blocks in `ngc_page_sections` |
| Workflows | `NGC_Workflow_Orchestrator`, `NGC_Workflows` | Event dispatch, integrate pack |
| Studio | `NGC_Studio_*` | Visual workflow builder, forms, emails, dashboards |
| AI Suite | `NGC_AI_*`, `BIA_*` | BYOK multi-model chat, agents, diagnostics |
| Gamification | `NGC_Gamification` | GamiPress bridge, achievements, leaderboards |
| UI Library | `NGC_UI_Library`, providers, import admin | Data providers + Import & Merge |

### 4.4 REST API

Namespace **`ngc/v1`** (mirrored to legacy **`ngt/v1`**).

Major groups: dashboards, matches, bookings, wallet/invoices, reviews, admin tutor lifecycle, platform analytics, section CMS, AI suite, Studio runtime, **`GET /ui-library/verify`**.

Public calendar: **`nextgen/v1/tutors/{id}/calendar`**.

### 4.5 Shortcodes (12 core)

`ngc_find_tutor_form`, `ngc_become_tutor_form`, `ngc_contact_support_form`, `ngc_parent_register_child_form`, `ngc_student_register_form`, `ngc_login_form`, `ngc_forgot_password_form`, `ngc_parent_dashboard`, `ngc_student_dashboard`, `ngc_tutor_dashboard`, `ngc_admin_dashboard`, `nextgen_tutor_calendar`

Plus: `ng_ui_component`, `ngc_tutor_carousel`, Studio forms/dashboards.

### 4.6 UI Library data providers

| Provider | Source |
|----------|--------|
| `company` | `ngc_company_profile` option, WP bloginfo |
| `page_content` | `NGC_Section_CMS` |
| `tutor` | `tutors` CPT / `ngt_get_tutors()` |
| `subject` | `subject` taxonomy |
| `pricing` | WooCommerce products OR `ngc_get_pricing_tiers()` (CMS rates) |
| `review` | `ngc_reviews` table |
| `dashboard` | `NGC_Studio_Dashboards` / `ngc_ui_dashboard_payload` filter |
| `booking` | `NGC_Bookings::query*` |
| `calendar` | `NGC_Tutor_Calendar_Service::get_calendar()` |
| `analytics` | `NGC_Platform_Tracking` + tutor count |
| `gamification` | `NGC_Gamification::get_user_badges()` |

Admin: **NGC Operations → UI Import & Merge** — scan artifacts, duplicate detection, CMS seed.

---

## 5. Integration architecture

```
Browser → Theme template → [ngc_*] shortcode OR ng_ui_component
                ↓
         Companion form handler / REST
                ↓
         ngc_* tables + CPT meta
                ↓
    Adapters: Amelia, WooCommerce, FluentCRM, MasterStudy, GamiPress
```

**Docker offline zips:** `docker/ngcpm-packages/` (WooCommerce, Elementor, FluentCRM, Amelia, GamiPress, …).

**Integrate pack:** `NextGenTutors-Companion/integrate/` — workflow JSON + executor.

---

## 6. Testing & verification

### 6.1 Automated (run locally)

```powershell
# Companion validation (lint, versions, integrate pack, unit tests)
php NextGenTutors-Companion/scripts/validate.php

# UI Library hardcode + provider wiring
php NextGenTutors-Companion/scripts/verify-ui-library.php

# Workflow SVG → runtime gap report
powershell -File scripts/run-flow-audit.ps1

# Playwright E2E (requires Docker @ :8899)
powershell -File scripts/run-playwright.ps1
```

### 6.2 Playwright coverage (28 tests)

| Spec | Blueprint WFs |
|------|---------------|
| `blueprint-wf01-wf02-wf19-registration.spec.ts` | WF-01, 02, 19 |
| `blueprint-wf03-tutor-registration.spec.ts` | WF-03 |
| `blueprint-wf07-wf10-booking-payment.spec.ts` | WF-07, 10 |
| `blueprint-wf08-wf12-wf14-ops.spec.ts` | WF-08, 12, 14 |
| `blueprint-wf11-payment.spec.ts` | WF-11 |
| `blueprint-wf16-wf17-wf18-reviews-pricing.spec.ts` | WF-16, 17, 18 |
| `blueprint-wf24-reminders-homepage.spec.ts` | WF-24 |
| `blueprint-wf25-dashboards.spec.ts` | WF-25 |

Forms submit → `admin-post.php` → redirect `?ngc_submitted={form_id}`.

### 6.3 Flow audit snapshot

- **25** blueprint SVG workflows parsed to `docs/workflows/flow-manifest.json`
- **8** full runtime binding · **17** partial · **0** missing
- WF-09 automated matching: **DEFERRED** (scoring only)

---

## 7. Documentation index

| Document | Purpose |
|----------|---------|
| `ARCHITECTURE.md` | Four-package SOLID contract |
| `docs/SYSTEM-OVERVIEW.md` | **This file** — whole-system map |
| `docs/CODE-REVIEW-2026-07-06.md` | Latest review findings + fixes |
| `docs/NEXT-STEPS.md` | Prioritized backlog |
| `docs/ui-library/` | UI extraction, page matrix, verification |
| `docs/workflows/` | Workflow catalog + gap report |
| `docs/apis/openapi-nextgen.yaml` | REST OpenAPI |
| `NextGenTutors-BeyondInfinity/PAGES-AUDIT-REPORT.md` | Page touchpoint matrix |
| `NextGenTutors-BeyondInfinity/COMPARE-DIFFERENCES.md` | Legacy theme merge notes |

---

## 8. Known gaps & risks

| Area | Status | Notes |
|------|--------|-------|
| WF-09 auto-assign | **IMPLEMENTED** | `NGC_Matching::maybe_auto_accept()` + `ngc_auto_assign_enabled` |
| 17/25 workflows | PARTIAL | 5 new integrate specs (workflow-06…10); trigger normalization ongoing |
| `ngt_get_tutors()` static fallback | **GATED** | `ngt_demo_tutors_enabled()` only |
| WooCommerce/Amelia live E2E | PARTIAL | `install-phase2-stack.ps1` installs Woo+Elementor+FluentCRM+GamiPress |
| PayFast gateway | **IN REPO** | `NGC_PayFast_Gateway` + ITN handler |
| Elementor native UI widgets | **DONE** | `NG_UI_Elementor_Hero_Widget`, Tutor, Pricing + generic |
| PAGES-AUDIT runtime smoke | STALE | Filesystem verified; re-run on deploy |
| POPIA / WAF | SECURITY_REVIEW | See security docs |

---

## 9. Deployment quick reference

```powershell
cd docker
Copy-Item .env.example .env
.\start.ps1
# → http://localhost:8899
# Activate theme + companion via WP admin or scripts/activate-beyondinfinity.ps1
```

Release build: `powershell -File scripts/build-release.ps1`  
Pre-release check: `powershell -File scripts/verify-solution.ps1`

---

## 10. Data flow example: Homepage tutor card

```
1. User visits /
2. Theme home.php → kinetic section "tutors"
3. bi_get_carousel_tutors() OR ng_ui_component('tutor-card')
4. NGC_UI_Tutor_Data_Provider::list()
5. WP_Query tutors CPT → meta _ngc_rating, _ngc_hourly_rate
6. map_to_component('tutor-card') → template-parts/ui-library/tutor-card.php
7. Delegates to template-parts/components/tutor-card.php for markup
```

**CMS copy** (headings, FAQ, pricing teaser) flows from `NGC_Section_CMS` → `ngc_home_section()` — editable in **Home Sections** admin without code deploy.
