# 08 — Asset Mapping

## CSS

| Reference | Target | Notes |
|-----------|--------|-------|
| `assets/css/tokens.css` | `assets/ngt/css/tokens.css` | Design tokens ported |
| `assets/css/components.css` | `assets/ngt/css/components.css` | Nav, buttons, cards |
| `assets/css/pages.css` | `assets/ngt/css/pages.css` | Page-specific |
| `assets/css/content.css` | `assets/ngt/css/content.css` | Typography / prose |
| `assets/css/floating.css` | `assets/ngt/js/floating.js` + BI FAB styles | WhatsApp FAB |
| `assets/css/admin.css` | `assets/css/options-admin.css` | Admin surfaces |
| `assets/css/elementor.css` | `inc/ui-library/elementor-widget*.php` | BI Elementor bridge |
| — | `assets/css/kinetic-*.css` | BI-only motion home |
| — | `assets/css/bi-*.css` | BI enterprise / cinematic layers |
| — | `assets/css/skins/beyond-infinity.css` | Canonical skin |
| — | `assets/css/nextgen-beyond-infinity-ui.css` | Unified UI bridge |

## JavaScript

| Reference | Target | Notes |
|-----------|--------|-------|
| `assets/js/chrome.js` | `assets/ngt/js/chrome.js` | Get Started removed; Sign In only |
| `assets/js/home.js` | `assets/ngt/js/home.js` + `assets/js/kinetic-home.js` | Split: ref + kinetic |
| `assets/js/tutors.js` | Companion marketplace JS | Domain moved to NGC |
| `assets/js/pricing.js` | `assets/ngt/js/pricing.js` | Kept |
| `assets/js/onboarding.js` | `assets/ngt/js/setup.js` | Setup/onboarding |
| `assets/js/dashboard.js` | `assets/ngt/js/dashboard.js` | Shell |
| `assets/js/dashboard-api.js` | `assets/js/dashboard-rest.js` | REST to Companion |
| `assets/js/tutor-dash.js` | `assets/ngt/js/tutor-dash.js` | Kept |
| `assets/js/admin-dash.js` | `assets/ngt/js/admin-dash.js` | Kept |
| `assets/js/chat.js` | `assets/ngt/js/chat.js` | Kept |
| `assets/js/static.js` | `assets/ngt/js/static.js` | Static pages |
| `assets/js/preloader.js` | `assets/js/bi-loader.js` | BI loader |
| `assets/js/floating.js` | `assets/ngt/js/floating.js` | FAB |
| `assets/js/vendor/lenis.min.js` | `assets/ngt/js/vendor/lenis.min.js` | Smooth scroll |
| `assets/js/vendor/lucide.min.js` | Loaded via BI enqueue | Icons |
| — | `assets/js/nav-menu.js` | WP nav dropdown behaviour |
| — | `assets/js/bi-scheme.js` | Dark/light toggle |

## Images & media

| Reference | Target |
|-----------|--------|
| `assets/img/logo.svg` | `assets/ngt/img/logo.png`, `logo-dark.png` |
| Prototype hero images | `assets/images/hero-bg.jpg`, `assets/videos/hero-mother-daughter-online.mp4` |
| — | `assets/brand/nextgen-tutors-hero-mark.png` (retired mark, reference only) |

## Root shared assets (workspace)

| Path | Role |
|------|------|
| `assets/ngt/js/chrome.js` | Shared chrome copy (Get Started removed) |
| `assets/js/dashboard-rest.js` | Cross-theme dashboard REST client |

## Not ported (reference-only / superseded)

| Asset | Reason |
|-------|--------|
| `dist/*` bundles | Build artifacts; not deployed |
| `assets/js/data.js` demo tutors | Companion CPT |
| `assets/js/export.js`, `reports.js` | Admin prototype; Companion admin |
| `assets/js/setup-wizard-admin.js` | BI options admin replaces |
| `assets/js/workflows-admin.js` | Companion workflow registry |
| Ref social SVGs in footer | Placeholder links; omitted |

## Enqueue strategy (target)

| Layer | Handler |
|-------|---------|
| NGT base | `inc/enqueue.php` → `assets/ngt/*` |
| BI extensions | Conditional per template / page registry |
| Companion | Plugin enqueue for shortcodes + REST |
