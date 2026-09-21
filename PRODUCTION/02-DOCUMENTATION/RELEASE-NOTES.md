# Release notes — 2026.09.12

## Why this train exists

Previous `delivery/` packages were marked STAGING ONLY and mixed theme 1.9.17 with plugins 1.9.5. `dist/release-manifest.json` stamped Plugin Manager as 1.9.19 while the header is 1.3.5. This distribution uses **actual headers**.

## Changes

- Junction-free BeyondInfinity ZIP copied from monorepo theme files (not the whole repo).
- Companion ZIP excludes `build-src/` and `tests/`.
- Plugin Manager ZIP is slim (no `offline-packages`).
- Theme `bi_companion_active()` requires `NGC_Plugin` — no fake Companion via version constants.
- GSAP/ScrollTrigger registered once as `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.
- Plugin Manager first-party matrix includes 3D + Subjects as required visual stack.
- UI library bundled in the theme and also as a drop-in zip.
- `ngt/v1` remains Companion’s legacy alias of `ngc/v1`. There is no `ngtbi_*` API in this tree.

## Not in this train

- Moving OpenWA or theme Woo fallbacks into Companion.
- Flattening `prototypes/*-body.php`.
- Bundled Amelia / Elementor / WooCommerce / MasterStudy / FluentCRM (licensed separately).