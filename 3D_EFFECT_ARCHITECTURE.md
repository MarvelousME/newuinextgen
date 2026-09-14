# 3D Effect Architecture

**Plugin:** `nextgen-3d-scroll-manager` v1.1.0  
**Status:** COMPLETE WITH LIMITATIONS

## Stack layers (coexistence)

| Layer | Package | Role |
|-------|---------|------|
| Theme bi-3d | BeyondInfinity `bi-3d.js` / `inc/bi-3d.php` | Legacy 3D attributes, kinetic carousel, tilt |
| Kinetic home | `kinetic-home.js` / `kinetic-home.css` | Homepage motion surface |
| Motion pack | Theme motion classes / assets | General reveal / hover motion |
| NGT home.js | Theme NGT bundle | **Skipped** when kinetic home is on (`inc/ngt-assets.php`) |
| NGT3D engine | Plugin runtime | Rule-driven ScrollTrigger presets; reuses `bi-ngt-gsap` / `bi-ngt-scrolltrigger` |

NGT3D does **not** replace the theme motion stack. It adds admin-configurable scroll effects on selected selectors.

## Plugin components

```
nextgen-3d-scroll-manager/
├── nextgen-3d-scroll-manager.php   # boot, v1.1.0, migrate_showcase_home_map()
├── includes/
│   ├── class-ngt3d-animation-registry.php  # whitelist + showcase presets
│   ├── class-ngt3d-rule-repository.php     # DB rules + home map migration
│   ├── class-ngt3d-page-resolver.php       # kill switch engine_enabled
│   ├── class-ngt3d-runtime-config.php      # window.NGT3D payload
│   ├── class-ngt3d-asset-loader.php        # conditional enqueue + preset CSS
│   ├── class-ngt3d-rest-api.php
│   └── …
├── admin/                                  # rules, settings, diagnostics
└── assets/
    ├── js/ngt-3d-compat.js                 # legacy attr bridge
    ├── js/ngt-3d-runtime.js                # strategy runtime (added in 1.1.0)
    ├── css/ngt-3d-engine.css
    └── {js,css}/presets/ngt-3d-preset-*.{js,css}
```

## Data flow

1. `NGT3D_Page_Resolver::can_load_engine()` — false if admin/builder/AJAX/CLI or `settings.engine_enabled` empty.
2. `NGT3D_Rule_Repository::get_for_page()` — enabled rules for current page.
3. `NGT3D_Dependency_Resolver` — GSAP / ScrollTrigger / Three / Atropos needs.
4. `NGT3D_Asset_Loader` — enqueue compat + runtime + engine CSS; enqueue preset CSS for used `preset_asset` keys.
5. `NGT3D_Runtime_Config::build()` → `wp_localize_script( …, 'NGT3D', $config )`.
6. Runtime loads preset JS dynamically for showcase IDs; applies via `NGT3DPresets[id].apply`.

## Kill switch

- Option key: `ngt_3d_settings.engine_enabled` (default `true`).
- Empty → `can_load_engine()` false (no assets) and runtime config `rules: []` / `enabled: false`.

## Homepage migration

- `NGT3D_Rule_Repository::migrate_showcase_home_map()` on `plugins_loaded`.
- Idempotent flag: option `ngt_3d_showcase_home_map_v1` = `2026-09-11`.
- Soft-disables prior front-page rules, inserts coherent showcase map. Does not invent `swag-card` / `transforms` instances.

## Preset contract

Registry entry fields used by architecture: `status`, `reference`, `reference_status`, `preset_asset`, `options`, device modes, `deps`.

Stub presets (`swag-card`, `transforms`) register but mark unavailable/disabled and do not invent motion.

## Shared GSAP handles

Asset loader prefers existing theme handles `bi-ngt-gsap` and `bi-ngt-scrolltrigger`; CDN fallback only if unregistered.
