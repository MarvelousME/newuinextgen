# Changed Files — Frontend Audit 2026-08-14

| Path | Original responsibility | Problem | Root cause | Change | Why safe | Pages | Builders | Tests | Result |
| ---- | ---------------------- | ------- | ---------- | ------ | -------- | ----- | -------- | ----- | ------ |
| `inc/page-builders.php` (+ theme mirror) | Elementor/WPBakery detect + template_include | Home blank | Empty Elementor shells + Hello page.php | Widget-aware `bi_elementor_has_content`; ignore prototype markers; `template_include` **9999** | Edit/canvas unchanged | Home | Elementor | HTTP+CDP | PASS |
| `inc/page-wrapper.php` | Theme vs builder body | Opaque which path rendered | — | Theme fallback uses widget check; HTML debug comments | Comments only + logic align | All `bi_render_page_template` | All | HTTP | PASS |
| `assets/css/nav-menu.css` | Nav/offcanvas | Invisible links; under-footer | Token/stacking | Navy hard bg, white links, elevated header token | Scoped to mobile | All | — | CDP | PASS |
| `assets/css/bi-sticky-ui.css` | Sticky z | Menu z trapped | Stacking | Elevated header token | Scoped | Mobile | — | CDP | PASS |
| `assets/css/ngt-wp-bridge.css` | Skin bridge | Light links on drawer | Token navy | Hard `#092746` / white | Mobile only | Marketing | — | Prior CDP | PASS |
| `assets/css/tokens/unified.css` | Z tokens | Ad-hoc 1200 | No overlay tier | Semantic overlay/drawer/modal scale | Additive | Global | — | CSS review | PASS |
| `assets/js/nav-menu.js` | Offcanvas | Scroll lock on resize | Missing MQ | Close on desktop breakpoint / orientation | Additive listeners | Mobile | — | Code | PASS |
| `style.css` (theme + root) | Container | Potential overflow | Fixed padding width | `min(100%, …)` + nav max-width 100% | Progressive | Global | Elementor compatible | Partial | PARTIAL |
| `NextGenTutors-BeyondInfinity/functions.php` | Asset versions | Cache | — | `BI_VERSION` **1.9.33** | Cache bust | Global | — | HTML ver= | PASS |
| `inc/tags/partials.php` | Sticky CTA | Duplicate menu | Always printed | Skip marketing chrome | Prior fix | Marketing | — | HTTP sticky=0 | PASS |

**Docker note:** Live PHP/CSS/JS for `inc/` and `assets/` come from **repo root overlays**, not solely the theme package tree.
