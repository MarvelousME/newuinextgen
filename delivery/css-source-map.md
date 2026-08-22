# CSS Source Map

**Audit date:** 2026-08-14

| Layer | Handle(s) | File | Role |
| ----- | --------- | ---- | ---- |
| 01 Tokens | `bi-tokens-base`, `bi-tokens-unified`, `bi-tokens-brand` | `assets/css/tokens/*` | Colors, type, z-index, gutters |
| 02 Theme base | `bi-style` | `style.css` | Reset-ish utilities, `.ngt-container`, buttons |
| 03 Components | `bi-components`, `bi-sections`, `bi-hero-brand` | `assets/css/components.css`, `sections.css` | Marketing UI |
| 04 Nav / sticky | `bi-nav-menu`, `bi-sticky-ui` | `nav-menu.css`, `bi-sticky-ui.css` | Header, offcanvas, FAB z-stack |
| 05 NGT bridge | `bi-ngt-bridge`, `bi-ngt-*` | `ngt-wp-bridge.css`, `assets/ngt/css/*` | Skin bridge + floating |
| 06 Builder adapters | `bi-page-builders`, `bi-integration-*` | `page-builders.css`, `integrations/*` | Elementor/Gutenberg/WPBakery/Woo |
| 07 Kinetic | `bi-kinetic-*`, `bi-cinematic-hero` | `kinetic-*.css` | Home surfaces |
| 08 Motion / 3D | `bi-motion-pack`, `bi-3d`, … | `motion/*`, `bi-3d.css` | Progressive enhancement |
| 09 Dynamic | `bi-dynamic-theme` | Generated | Scheme overrides |

## Breakpoints observed (project CSS)

`414, 480, 560, 600, 620, 640, 760, 767, 768, 782, 860, 900, 960, 980, 1000, 1024, 1099, 1100, 1920`  
**Nav authority breakpoint:** `1099` / `1100`.

## Specificity / !important policy

| Pattern | Why present | Replacement direction |
| ------- | ----------- | --------------------- |
| Mobile drawer `color/#092746 !important` | Midnight scheme + `--navy`→cyan token bug | Keep hard hex until token alias fixed |
| Header elevated `z-index: var(--ngt-z-header-elevated) !important` | Beats sticky `!important` z-40 | Semantic token (done) |
| Builder edit hides sticky | Avoid overlaying Elementor UI | Keep scoped |

## Cascade recommendation

Keep current enqueue order; do not introduce `@layer` until browser matrix confirmed. Prefer tokens over new `!important`.
