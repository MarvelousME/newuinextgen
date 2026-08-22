# Frontend Codebase Inventory

**Audit date:** 2026-08-14  
**Runtime:** `http://localhost:8890/`  
**Active theme:** Hello Elementor parent + child `nextgentutors-beyondinfinity` (`NextGenTutors-BeyondInfinity/`)  
**Companion:** `NextGenTutors-Companion`  
**Docker mounts:** theme package + overlay `../assets` + overlay `../inc` (overlay wins for CSS/JS/PHP under those paths)

| Area | File(s) | Implementation | Owner | Active? | Duplicate? | Risk |
| ---- | ------- | -------------- | ----- | ------- | ---------- | ---- |
| Theme shell | `NextGenTutors-BeyondInfinity/{header,footer,front-page,page-*}.php` | PHP templates | BeyondInfinity | Yes | Root mirror exists | Drift if root≠theme for non-overlaid files |
| Asset enqueue | `functions.php` (`BI_VERSION` **1.9.33**) | `wp_enqueue_*` | BeyondInfinity | Yes | Root `functions.php` older (1.9.28) unused | Low — theme file is live |
| Tokens | `assets/css/tokens/{base,unified,brand-semantic}.css` | CSS variables | Theme | Yes | — | Medium — multiple token layers |
| Nav SSOT | `inc/nav-menu.php` `bi_nav_menu_structure()` | PHP structure → WP menus | Theme | Yes | Overlay sync required | Low if synced |
| Desktop/mobile nav | `templates/header/*` + `nav-menu.js` + `nav-menu.css` | Same `.ngt-nav__menu` offcanvas | Theme | Yes | chrome.js not enqueued | Was CRITICAL (home blank) — fixed |
| Sticky CTA | `inc/tags/partials.php` `bi_sticky_mobile_cta()` | Mobile quick actions | Theme | Conditional | Competes with primary nav | Hidden on marketing header |
| Float dock | `bi_float_dock()` + `bi-sticky-ui.js` + `floating.js` | FAB / support / WhatsApp | Theme | Yes | Legacy WhatsApp FAB no-op | Collision with drawer if z wrong |
| Kinetic home | `inc/defaults-production/home.php` + kinetic CSS/JS | PHP sections | Theme | Yes when `home_layout=kinetic` | Elementor page.php path | CRITICAL if template_include loses |
| Prototype blend | `inc/prototype-blend.php` | Marker + shortcodes | Theme | Option-gated | Marker ≠ content | Home skips blend when kinetic |
| Elementor bridge | `inc/page-builders.php`, `integrations/elementor.css` | Locations + widgets | Theme | Yes | Theme Builder vs kinetic | High without kinetic force |
| UI widgets | `inc/ui-library/*`, Companion UI library | Elementor/VC/shortcode | Shared | Yes | Multiple tutor-card paths | Medium drift |
| Gutenberg | `theme.json` + `integrations/gutenberg.css` | Tokens only | Theme | Yes | No custom blocks in theme | Low |
| WPBakery | `integrations/wpbakery.css` + `vc_map` for `ng_ui_component` | Adapter | Theme | Conditional | — | Legacy pages |
| chrome.js | `assets/ngt/js/chrome.js` | Static HTML injector | Prototypes | **Not enqueued on WP** | Guard skips if `.ngt-nav` | Low on WP |
| Dashboards | Companion shortcodes `ngc_*_dashboard` | Plugin templates | Companion | Yes | Theme fallback stubs | Medium |

## CSS ownership (canonical handles)

See also `css-source-map.md`. Runtime resolves via `BI_URI` → theme directory with Docker `assets/` overlay.

## JavaScript ownership

See `javascript-source-map.md`. Primary interaction owners: `bi-nav-menu`, `bi-sticky-ui`, `bi-ngt-floating`, `bi-kinetic-home`.

## Evidence

- Homepage before fix: nav + only `<!-- bi-prototype:home -->` + footer (`site-main: 0`)
- Homepage after fix: `<!-- bi-render:theme-fallback -->` + `main.site-main` + `.ngi-home`
- Artifact: `delivery/evidence/frontend-audit-2026-08-14/`
