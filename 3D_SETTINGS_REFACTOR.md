# 3D Settings Refactor

**Plugin:** `nextgen-3d-scroll-manager` v1.1.0  
**Status:** COMPLETE WITH LIMITATIONS

## What changed (1.1.0)

1. **Frontend runtime added** — previously PHP registry + admin + REST existed without a working browser engine. Added:
   - `assets/js/ngt-3d-compat.js`
   - `assets/js/ngt-3d-runtime.js`
   - `assets/css/ngt-3d-engine.css`
   - `assets/js/presets/*`, `assets/css/presets/*`
2. **Showcase presets registered** in `class-ngt3d-animation-registry.php` with status/reference metadata.
3. **Asset loader** enqueues preset CSS for presets used by active page rules (`preset_asset`).
4. **Runtime** dynamically loads showcase preset JS/CSS by id from `pluginUrl`.
5. **Kill switch** wired through `settings.engine_enabled`.
6. **Homepage migration** `migrate_showcase_home_map()` with option `ngt_3d_showcase_home_map_v1=2026-09-11`.

## Settings model (post-refactor)

```
ngt_3d_settings (WP option)
  engine_enabled | debug_mode | lenis_* | default_perspective | fps_*

ngt3d_rules (DB table via NGT3D_Schema)
  page + selector + animation_names + animation_options JSON + device modes

NGT3D_Animation_Registry
  whitelist names → defaults, modes, preset_asset, status
```

Admin and REST remain the write path; runtime consumes only sanitized `window.NGT3D`.

## Deliberate non-goals

- No invented `swag-card` or `transforms` motion.
- Theme bi-3d / kinetic-home / motion pack left active (not replaced).
- Admin JSON options editor retained; full schema-driven dynamic forms deferred (partial).

## Follow-ups (limitations)

| Item | Priority |
|------|----------|
| Expose `engine_enabled` on Settings UI | High |
| Schema-driven option fields from registry `options` | Medium |
| Prove full admin→visual matrix with live edits | Medium |
| FPS safeguard end-to-end verification | Low |
