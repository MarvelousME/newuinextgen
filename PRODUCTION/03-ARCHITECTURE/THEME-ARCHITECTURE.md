# Theme architecture

Hello Elementor child. Entry: `style.css` + `functions.php` (`BI_VERSION` 1.9.29).

- Shell: `header.php` / `footer.php` → `templates/header|footer/*` or Elementor Theme Builder locations.
- Nav: `inc/nav-menu.php` builds **NextGen Primary Grouped**.
- Pages: `page-*.php` routers → `inc/defaults/*` → `prototypes/*-body.php` via blend (`inc/prototype-blend.php`) or `inc/defaults-production/*`.
- Do not treat `template-parts/pages/*` as SSOT.
- Motion: `inc/ngt-assets.php` registers GSAP; `inc/bi-3d.php` / kinetic modules render presentation 3D.
- Companion interop: `inc/companion.php`. Shortcode fallbacks: `inc/shortcodes-fallback.php` (only if `NGC_Plugin` missing).