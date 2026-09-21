# 3D Implementation Report

**Date:** 2026-09-11  
**Plugin:** `nextgen-3d-scroll-manager` **1.1.0**  
**Status:** COMPLETE WITH LIMITATIONS

## Summary

Frontend runtime was missing from an otherwise present PHP registry/admin/REST plugin. v1.1.0 adds runtime, compat shim, engine CSS, showcase preset assets, registry metadata, preset CSS enqueue, kill switch, and an idempotent homepage showcase map migration.

## Deliverables

| Item | Location |
|------|----------|
| Runtime | `assets/js/ngt-3d-runtime.js` |
| Compat | `assets/js/ngt-3d-compat.js` |
| Engine CSS | `assets/css/ngt-3d-engine.css` |
| Presets | `assets/js/presets/*`, `assets/css/presets/*` |
| Registry | `includes/class-ngt3d-animation-registry.php` |
| Migration | `NGT3D_Rule_Repository::migrate_showcase_home_map()` |
| Flag | `ngt_3d_showcase_home_map_v1=2026-09-11` |
| Docs | Root `3D_*.md` / `HOME_3D_*.md` |

## Final preset table

| ID | Status | Reference | Home section | Technique |
|----|--------|-----------|--------------|-----------|
| `zoom` | READY | zoom.framer.website | `#hero` | Scale zoom entrance |
| `dark-veles` | READY | dark-veles.framer.website | `#platform-highlights` | Motion language (not clone) |
| `doublescroll` | READY | doublescroll.framer.website | `#tutoring-story` | Dual opposite scroll tracks |
| `onscroll` | READY | onscroll.framer.website | `#subjects` | Staggered media entrances |
| `4kvideo` | READY | 4kvideo.framer.website | `#video-story` | Media scale/pin; no video.currentTime scrub |
| `wiper` | READY | wiper.framer.website | `#image-hover` | clip-path wipe |
| `scroll-mask` | READY | scroll-mask.framer.website | `#pathways` | Mask/clip reveal |
| `carousel-depth` | Legacy READY | — | `#tutors` | Legacy depth carousel |
| `parallax-slow` + `depth-scroll` | Legacy READY | — | `#cta` | Legacy parallax/depth |
| `swag-card` | REFERENCE_UNAVAILABLE | 404 | — | Stub only |
| `transforms` | REFERENCE_CHANGED | URL now Transform9 medical | — | Stub disabled |

## Theme coexistence

bi-3d, kinetic-home, and motion pack remain active. NGT `home.js` GSAP skipped when kinetic home is on. NGT3D reuses `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.

## Limitations

1. Browser MCP unavailable — Level C scroll sampling approximate.
2. `transforms` / `swag-card` intentionally unimplemented beyond stubs.
3. Visual regression screenshots not captured for all viewports.
4. Admin schema-driven dynamic option fields partial (shared JSON).
5. Settings validation chain partially proven; full admin→visual matrix needs live edits.
6. Settings UI does not yet expose `engine_enabled` checkbox (code kill switch exists).

## Verdict

**COMPLETE WITH LIMITATIONS** — runtime + coherent home map + evidence-based READY presets shipped; stubs honest; residual admin UX and visual evidence remain.
