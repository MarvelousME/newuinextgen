# 3D Visual Regression Report

**Date:** 2026-09-11  
**Status:** COMPLETE WITH LIMITATIONS

## Scope

Homepage NGT3D showcase mapping vs prior theme-only motion. Showcase references probed for identity/technique; home visual pack incomplete.

## Capture status

| Viewport | Screenshots | Notes |
|----------|-------------|-------|
| Desktop ≥1024 | Not captured this pass | Browser MCP unavailable |
| Tablet 768–1023 | Not captured this pass | — |
| Mobile &lt;768 | Not captured this pass | Seeded mobile modes: reduced/disabled per map |

## Reference showcase probe (live)

| Preset | HTTP | Visual/identity note | Home use |
|--------|------|----------------------|----------|
| doublescroll | 200 | Double Scroll Effect | `#tutoring-story` |
| 4kvideo | 200 | Crystal Clear 4K media emphasis | `#video-story` |
| wiper | 200 | Wiper clip-path | `#image-hover` |
| dark-veles | 200 | Landing motion language | `#platform-highlights` |
| scroll-mask | 200 | Scroll Mask | `#pathways` |
| onscroll | 200 | Illustrations showcase | `#subjects` |
| zoom | 200 | Zoom Entrance | `#hero` |
| swag-card | 404 | Unavailable | Not applied |
| transforms | 200 | Content is Transform9 medical — REFERENCE_CHANGED | Not applied |

## Level C transform sampling

**Limitation:** tables are approximate from content/title evidence + HTML technique hints, **not** full DevTools sampling at 0/10/25/50/75/90/100% scroll.

| Preset | Primary transform language (approx.) |
|--------|--------------------------------------|
| zoom | `scale` / opacity entrance |
| doublescroll | dual `y` tracks, opposite direction, pin |
| 4kvideo | media `scale` + pin (no currentTime scrub) |
| wiper | `clip-path` coverage |
| dark-veles | stagger + scale + y (grammar only) |
| scroll-mask | mask/clip progress |
| onscroll | staggered y/scale entrances |
| carousel-depth / parallax-slow / depth-scroll | legacy theme-adjacent |

## Regression risk notes

- Effect density controlled by leaving several sections unmapped.
- Theme kinetic/bi-3d still active — combined motion may stack; monitor hero and tutors.
- Pin presets (`doublescroll`, `4kvideo`, `scroll-mask`) disabled on mobile via seed modes.

## Verdict

Visual identity of **references** documented; **homepage** visual regression pack **not** fully captured this pass. Status remains COMPLETE WITH LIMITATIONS until viewport screenshots exist.
