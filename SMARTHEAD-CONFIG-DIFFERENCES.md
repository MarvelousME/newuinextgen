# SmartHead vs BeyondInfinity — Configuration Architecture

**Date:** 2026-07-01  
**Scope:** Carry SmartHead’s *flexible configuration sophistication* into `beyondinfinity` without ThemeREX/trx_addons coupling.  
**Status:** PLAN ONLY — awaiting approval before implementation.

---

## Executive summary

SmartHead is a **declarative, multi-layer configuration engine**: one options schema drives the Customizer, per-post overrides, dynamic CSS, template switching, and runtime getters with a strict precedence chain. BeyondInfinity today is **page-centric and code-centric**: launch pages live in PHP defaults + JSON map, with a thin Customizer (contact fields only) and no per-page theme controls.

The goal is not to clone SmartHead’s 1,800-line `theme.options.php` or trx_addons dependency. It is to adopt its **patterns** in a lean, NextGen-specific `bi_*` configuration layer that respects companion shortcodes, Elementor/WPBakery dual-mode pages, and POPIA constraints.

---

## Architecture comparison

| Layer | SmartHead | BeyondInfinity (today) | Gap |
|-------|-----------|------------------------|-----|
| **Runtime registry** | `$SMARTHEAD_STORAGE` + `smarthead_storage_*()` | None (scattered `get_theme_mod`, hardcoded arrays) | No unified in-memory config |
| **Option schema** | ~200+ declarative fields in `theme.options.php` | 7 contact fields in `inc/customizer.php` | No schema-driven extensibility |
| **Getter API** | `smarthead_get_theme_option($name, $default, $strict, $post_id)` | `get_theme_mod('bi_phone')` ad hoc | No cascade / override resolution |
| **Customizer** | Auto-built from schema (`theme.customizer.php`) | Hand-registered controls | Duplication risk when adding fields |
| **Per-page overrides** | Meta box `smarthead_options` + inherit locks | None | Cannot hide hero, swap header per page |
| **Dynamic CSS** | `theme.styles.php` → `wp_footer` inline CSS from colors/fonts | Static `:root` tokens in `style.css` | Rebrand requires code edit |
| **Color schemes** | Multiple schemes + `scheme_{name}` body class | Fixed NGT palette in CSS | No admin-selectable schemes |
| **Typography** | Per-tag font controls (p, h1–h6, menu…) | Google Fonts hard-enqueued | Limited typography control |
| **Template switching** | `get_template_part` from option (`header_style`, `body_style`) | Fixed `header.php` / `footer.php` | No layout variants |
| **Lists / enums** | `includes/lists.php` + filters | Inline arrays in PHP templates | Inconsistent select sources |
| **Dependencies** | `dependency` + `linked` fields in schema + JS | None | No conditional option UI |
| **Blog / archive modes** | `blog_mode` detection + mode-specific options | N/A (minimal blog) | Low priority for BI |
| **Plugin integration** | trx_addons filters everywhere | `companion.php`, shortcode_exists guards | Different model (keep BI model) |
| **Page inventory** | Implicit (WP pages + CPT) | `pages-registry.php` + `page-map.json` + admin sync | **BI strength** — extend with per-page options |
| **Content defaults** | Mixed with theme options | `inc/defaults/*.php` + builder fallback | **BI strength** — keep separate from skin config |

---

## SmartHead configuration flow (reference)

```
theme.options.php
  ├─ smarthead_storage_set('settings', …)      // internal dev flags
  ├─ smarthead_storage_set('options', …)       // declarative schema (type, std, override, dependency)
  ├─ smarthead_storage_set('schemes', …)       // color palettes
  └─ smarthead_storage_set('theme_fonts', …)   // typography defaults

Boot sequence (priorities on after_setup_theme):
  1. Filters mutate lists/options
  2. smarthead_options_create()
  3. smarthead_load_theme_options() → theme_mod → options[].val

Runtime:
  wp → smarthead_override_theme_options() → post meta smarthead_options
  Templates → smarthead_get_theme_option('header_style')
  Customizer preview → theme.customizer.preview.js + dynamic CSS
  Post editor → override metabox (inherit | custom per field)
```

---

## BeyondInfinity configuration flow (today)

```
functions.php
  └─ inc/customizer.php          // 7 contact theme_mods
  └─ inc/pages-registry.php      // slug → template/default metadata
  └─ inc/page-wrapper.php        // builder vs theme default
  └─ inc/defaults/*.php          // static section markup
  └─ content/page-map.json       // launch page sync
  └─ style.css :root tokens      // design system (fixed)
  └─ inc/kinetic-home.php        // home-specific helpers (not configurable)
```

**Pain points this causes:**
- Rebrand (colors, fonts) = developer edits CSS.
- New global toggle (e.g. hide WhatsApp FAB) = new Customizer boilerplate.
- Per-page needs (hide kinetic section, use canvas header) = Elementor only or code change.
- No single source of truth for “what can be configured.”

---

## What to adopt (mapped to BI)

### 1. Core infrastructure — `bi_storage` + `bi_get_theme_option()`

Port the *idea* of `includes/storage.php` and `smarthead_get_theme_option()` as:

| File | Purpose |
|------|---------|
| `inc/config/storage.php` | `bi_storage_get/set`, array helpers |
| `inc/config/options-schema.php` | Declarative option definitions |
| `inc/config/options-loader.php` | Load `theme_mod` into storage |
| `inc/config/options-get.php` | `bi_get_theme_option()` cascade |
| `inc/config/lists.php` | Shared enums (yes/no, header styles, home sections) |

**Getter precedence (BI-specific):**

1. Explicit `$post_id` meta (if not `inherit`)
2. Current singular post meta `bi_options`
3. Blog/context mode key (e.g. `hero_style_dashboard`)
4. `get_theme_mod( $name )`
5. Schema `std` default
6. `apply_filters( 'bi_theme_option_{$name}', $default )`

### 2. Schema-driven Customizer

Replace hand-written `bi_customize_register()` loops with a generator (like `smarthead_customizer_register_controls`) that reads `bi_storage_get('options')`.

**Initial option groups for NextGen:**

| Panel | Sections | Example fields |
|-------|----------|----------------|
| Brand | Colors, Typography | primary, secondary, accent, body font |
| Layout | Header, Footer, Body | header style, sticky nav, footer columns |
| Contact | (migrate existing) | phone, emails, WhatsApp, response hours |
| Homepage | Kinetic sections | toggle trust, subjects, tutors, testimonials, FAQ |
| Tutoring | Rates & trust | online/in-person/tertiary rates, guarantee ref |
| Integrations | Companion | show shortcode placeholders, REST dashboard mode |

### 3. Dynamic CSS layer

Port pattern from `theme.styles.php` (trimmed):

- `inc/config/theme-styles.php` — map color scheme → CSS custom properties (`--ngt-primary`, etc.)
- `bi_customizer_save_css()` — write to `uploads/beyondinfinity/custom.css` or inline on `wp_head` priority 99
- Keep kinetic CSS separate; only override tokens it inherits

### 4. Per-page override metabox

Port SmartHead override UI (simplified):

- Meta key: `bi_options` (array)
- Metabox on: `page` posts listed in `bi_pages_registry()` + `post` + `tutors` CPT
- Fields flagged with `'override' => true` in schema
- Inherit lock per field (no trx_addons metabox API — native WP)

**High-value overrides for launch pages:**

| Option | Use |
|--------|-----|
| `show_page_title` | Hide title on dashboards |
| `header_style` | Light/dark/transparent on marketing vs app pages |
| `body_style` | Full-width vs boxed |
| `use_theme_default` | Force `inc/defaults` even if builder empty (override wrapper logic) |
| `home_section_*` | Front page only: disable kinetic blocks |
| `hide_whatsapp_fab` | Per-page FAB |

### 5. Template switching (incremental)

SmartHead uses `templates/header-{style}.php`. BI equivalent:

```
templates/header/
  default.php
  transparent.php
  minimal.php   (dashboard)
footer/
  default.php
  minimal.php
```

`header.php` becomes a router:

```php
get_template_part( 'templates/header/' . bi_get_theme_option( 'header_style', 'default' ) );
```

### 6. Filters for extension (no trx_addons)

| Filter | Purpose |
|--------|---------|
| `bi_filter_options_schema` | Plugins add options |
| `bi_filter_list_header_styles` | Register header variants |
| `bi_filter_allow_override_options` | CPT allowlist |
| `bi_filter_dynamic_css` | Append CSS rules |
| `bi_filter_home_sections` | Kinetic section registry |

### 7. Keep BI strengths (do not regress)

- **`page-wrapper.php`** builder fallback logic stays; add option to force theme default.
- **`pages-registry.php` + page-map sync** — link override metabox to registry types (dashboard pages get dashboard header default).
- **`inc/defaults/*.php`** — content structure stays in defaults; config only toggles/styles sections.
- **Companion boundary** — no CPT/REST/ratings in theme config; only presentation toggles.

---

## What NOT to port

| SmartHead feature | Reason to skip |
|-------------------|----------------|
| Full trx_addons integration | BI uses NextGenCompanion + Elementor |
| TGMPA required plugins stack | Different deployment model |
| 10+ blog layout styles | Out of scope for tutoring marketplace v1 |
| Essential Grid / Revolution Slider options | Not in BI stack |
| `$smarthead_` std callbacks in schema | Over-engineering for v1 |
| Duplicate Theme Options admin page | Customizer + post overrides sufficient for v1 |
| 2,400-line generated CSS file | Generate token overrides only, not full theme CSS |

---

## Risk & compatibility notes

| Risk | Mitigation |
|------|------------|
| Elementor overrides theme options | Options affect theme shell only; Elementor canvas bypasses header/footer as today |
| Child of Hello Elementor | Config layer in child theme; parent updates won't wipe `theme_mod` |
| Performance (inline CSS) | File cache in uploads; bust cache on `customize_save_after` |
| POPIA | No PII in theme options; contact fields remain sanitized text |
| Migration | Existing `bi_*` theme_mod keys map 1:1 into schema |

---

## File footprint estimate (after implementation)

```
inc/config/
  storage.php
  lists.php
  options-schema.php
  options-loader.php
  options-get.php
  customizer-bridge.php
  override-metabox.php
  theme-styles.php
assets/js/
  bi-options-admin.js      (dependency + inherit UI, ~200 lines)
  bi-customizer-preview.js (optional phase 2)
```

**~1,200–1,800 lines PHP** (vs SmartHead’s ~4,000+ across options/customizer/styles) — deliberate subset.

---

## Success criteria

- [x] All global contact/branding toggles defined once in schema
- [x] Customizer and `bi_get_theme_option()` stay in sync automatically
- [x] At least 5 per-page overrides work on launch pages (title, header, sections)
- [x] Color scheme switch updates `--ngt-*` tokens without editing `style.css`
- [x] `bi_pages_touchpoint_audit()` reports option defaults per page type
- [x] Elementor/WPBakery dual-mode unchanged
- [x] No new hard dependency on trx_addons or jQuery beyond WP admin

**Status:** Implemented in BeyondInfinity **v1.4.0** (2026-07-01). See `CHANGES-REGISTRY.md`.

---

## Related documents

- Implementation phases: `SMARTHEAD-CONFIG-IMPLEMENTATION-PLAN.md`
- Existing merge notes: `COMPARE-DIFFERENCES.md` (§ SmartHead reference)
- Page inventory: `PAGES-AUDIT-REPORT.md`, `inc/pages-registry.php`
