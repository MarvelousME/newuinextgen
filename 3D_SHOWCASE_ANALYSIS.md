# 3D Showcase Analysis

**Probe date:** 2026-09-11  
**Status:** COMPLETE WITH LIMITATIONS  
**Plugin:** `nextgen-3d-scroll-manager` v1.1.0

## Method

HTTP status + page identity from live Framer showcase URLs. Browser MCP unavailable this session — technique notes come from content/title evidence and HTML hints, not full DevTools scroll sampling (0/10/25/50/75/90/100%).

## Showcase matrix

| Preset ID | URL | HTTP | Identity | Status | Technique (evidence-level) |
|-----------|-----|------|----------|--------|----------------------------|
| `doublescroll` | https://doublescroll.framer.website/ | 200 | Double Scroll Effect | READY | Dual opposite vertical tracks in sticky scene |
| `4kvideo` | https://4kvideo.framer.website/ | 200 | Crystal Clear 4K | READY | Media scale/pin; **no** `video.currentTime` scrub |
| `wiper` | https://wiper.framer.website/ | 200 | Wiper Effect | READY | `clip-path` wipe |
| `dark-veles` | https://dark-veles.framer.website/ | 200 | DARK VELES landing | READY | Motion **language** only (sticky pacing, stagger, scale) — not content clone |
| `scroll-mask` | https://scroll-mask.framer.website/ | 200 | Scroll Mask by Framer Institute | READY | Mask/clip typographic or media reveal |
| `swag-card` | https://swag-card.framer.website/ | 404 | — | REFERENCE_UNAVAILABLE | Stub only; no invented behavior |
| `onscroll` | https://onscroll.framer.website/ | 200 | OnScroll / Illustrations Showcase | READY | Staggered media/image entrances |
| `transforms` | https://transforms.framer.website/ | 200 | Transform9 AI medical site | SHOWCASE_CHANGED / REFERENCE_CHANGED | Stub disabled; no invented transforms |
| `zoom` | https://zoom.framer.website/ | 200 | Zoom Entrance | READY | Scale-based zoom entrance |

## Implementation policy

- READY → registry + preset JS/CSS + homepage mapping where coherent.
- REFERENCE_UNAVAILABLE / REFERENCE_CHANGED → registered stubs that refuse apply; not mapped on home.
- Homepage uses a **coherent subset** of showcases plus legacy presets (`carousel-depth`, `parallax-slow`, `depth-scroll`). `swag-card` and `transforms` are not applied.

## Limitations

- Level C transform sampling tables are approximate (no Browser MCP scroll sampling).
- Visual regression screenshots not captured for all viewports in this pass.
