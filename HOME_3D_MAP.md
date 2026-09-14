# HOME 3D Map

**Date:** 2026-09-11  
**Migration flag:** `ngt_3d_showcase_home_map_v1` = `2026-09-11`  
**Status:** COMPLETE WITH LIMITATIONS

## Actual DOM section IDs

`#hero`, `#trust`, `#ng-ui-stats`, `#subjects`, `#how-it-works`, `#tutoring-story`, `#platform-highlights`, `#learning-proof`, `#video-story`, `#image-hover`, `#pathways`, `#tutors`, `#pricing`, `#testimonials`, `#faq`, `#cta` (`.bi-parallax-cta`)

## Applied NGT3D preset map (coherent subset)

| Selector | Preset(s) | Notes | Mobile mode (seed) |
|----------|-----------|-------|--------------------|
| `#hero` | `zoom` | Showcase Zoom Entrance | reduced |
| `#platform-highlights` | `dark-veles` | Motion language only | disabled |
| `#tutoring-story` | `doublescroll` | Dual track sticky | disabled |
| `#subjects` | `onscroll` | Staggered media | reduced |
| `#video-story` | `4kvideo` | Scale/pin; no video time scrub | disabled |
| `#image-hover` | `wiper` | clip-path wipe | reduced |
| `#pathways` | `scroll-mask` | Mask/clip reveal | disabled |
| `#tutors` | `carousel-depth` | Legacy | full |
| `#cta` | `parallax-slow`,`depth-scroll` | Legacy combo | disabled |

**Not applied on home seed:** `swag-card`, `transforms` (READY on lab `/3d-scroll-test/` — sticky stack + 3D gallery).

## Unmapped sections (theme motion only)

`#trust`, `#ng-ui-stats`, `#how-it-works`, `#learning-proof`, `#pricing`, `#testimonials`, `#faq`

These rely on existing bi-3d / kinetic-home / motion pack behavior — no NGT3D showcase rule.

## Theme stack still active

- bi-3d, kinetic-home, motion pack remain.
- NGT theme `home.js` GSAP skipped when kinetic home on.
- NGT3D reuses `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.
