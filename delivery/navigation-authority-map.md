# Navigation Authority Map

**Audit date:** 2026-08-14

| Context | Authoritative implementation | Markup | Behaviour | Must NOT appear |
| ------- | ---------------------------- | ------ | --------- | --------------- |
| Desktop primary | `bi_render_primary_nav_menu()` / `wp_nav_menu(primary)` | `.ngt-nav__menu` in `templates/header/{transparent,default}.php` | Horizontal links + dropdowns | Second Elementor nav, chrome.js `#nav` |
| Mobile primary | **Same** `.ngt-nav__menu` | Offcanvas via `nav-menu.js` | Toggle `.is-open` + `body.ngt-offcanvas-open` | Sticky CTA bar, `#drawer`, footer-as-drawer |
| Nested Compliance | SSOT children in `bi_nav_menu_structure()` | `.sub-menu` in-flow on mobile | Dropdown trigger + Escape | Hover-only on touch |
| Footer Explore/Company/Legal | `bi_footer_structure()` + `templates/footer/default.php` | `.ngt-footer` columns | Static links | Competing Elementor footer without location handoff |
| Sticky CTA | `bi_sticky_mobile_cta()` | `.bi-sticky-cta` | Skipped when `bi_uses_marketing_header_chrome()` | Visible duplicate of Find a Tutor / Pricing on marketing pages |
| Float dock | `bi_float_dock()` | `#float-dock` | `bi-sticky-ui.js` + `floating.js` | Second WhatsApp FAB |
| Account CTA | Header tools `.ngt-nav__signin` / Dashboard | Header only | Role-based URL | Extra sticky Login |

## Verified (390px mobile, 2026-08-14)

| Assertion | Result |
| --------- | ------ |
| Drawer count | **1** |
| Sticky CTA | **absent** |
| Primary links | Find a Tutor, Pricing, Become a Tutor, About, Contact, Compliance, Blog |
| Drawer bg / link color | `rgb(9, 39, 70)` / `rgb(255, 255, 255)` |
| Header elevated z when open | `500` (`--ngt-z-header-elevated`) |
| chrome.js `#drawer` | **absent** |
| Duplicate IDs (sampled) | **none** |

## Schema

`bi_nav_public_schema_version()` → `2026-08-14-nav-footer-ssot-v1`
