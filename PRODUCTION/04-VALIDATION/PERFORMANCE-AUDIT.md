# Performance audit (packaging pass)

- GSAP/ScrollTrigger share one handle pair; 3D plugin reuses them.
- 3D Scroll Manager loads engine JS only when the page has rules.
- Kinetic CSS/JS stay page-gated via existing `bi_page_needs_*` helpers.
- Companion ZIP no longer includes ~66 MB `build-src`.
- Theme ZIP must not include nested plugins, `node_modules`, or `release.zip`.