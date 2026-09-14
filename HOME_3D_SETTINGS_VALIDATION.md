# HOME 3D Settings Validation

**Date:** 2026-09-11  
**Status:** COMPLETE WITH LIMITATIONS

## Validation scope

Prove that homepage NGT3D settings reach the browser runtime and that stubs/kill paths are honest. Full admin→visual matrix deferred to live WP admin edits.

## Chain under test

```
ngt_3d_settings + DB rules
  → Page_Resolver (engine_enabled)
  → Rule_Repository::get_for_page
  → Asset_Loader (GSAP handles, runtime, preset CSS)
  → Runtime_Config::build → window.NGT3D
  → ngt-3d-runtime.js → preset.apply / stub refuse
```

## Checks

| # | Check | Expected | Result |
|---|-------|----------|--------|
| 1 | Plugin version | 1.1.0 | Pass (header + `NGT3D_VERSION`) |
| 2 | Runtime assets exist | compat, runtime, engine.css, presets | Pass |
| 3 | Registry showcase entries | 9 IDs with status/reference | Pass |
| 4 | Migration flag | `ngt_3d_showcase_home_map_v1=2026-09-11` | Pass (code) |
| 5 | Home map selectors | Matches DOM IDs in `HOME_3D_MAP.md` | Pass |
| 6 | swag-card / transforms | READY on lab `/3d-scroll-test/`; not in home seed map | Pass |
| 7 | `engine_enabled` gate | Blocks enqueue when empty | Pass (code path) |
| 8 | Preset CSS enqueue | Only for used `preset_asset` | Pass (loader) |
| 9 | Theme stack coexistence | bi-3d / kinetic / motion remain; home.js skip when kinetic | Pass (theme code) |
| 10 | Config payload shape | `enabled`, `rules[]`, `perspective`, `debug` | Pass (Runtime_Config) |
| 11 | Admin JSON options | Edit form shared textarea | Pass with limitation |
| 12 | Admin→visual matrix | Live edit each option, screenshot | **Not fully proven** |
| 13 | Level C scroll sampling | DevTools at scroll % | **Approximate only** (no Browser MCP) |
| 14 | Multi-viewport screenshots | Desktop/tablet/mobile pack | **Incomplete** this pass |

## Settings validation verdict

**Partially proven:** config payload + runtime present; registry/migration/home map consistent with implementation.

**Not fully proven:** interactive admin save → front-end visual change for every option key.

## Recommendation

Before claiming PRODUCTION READY settings UX: flip `engine_enabled` and one rule option live, confirm asset absence / transform change, and capture viewport evidence.
