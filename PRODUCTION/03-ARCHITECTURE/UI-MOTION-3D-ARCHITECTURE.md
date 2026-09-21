# UI, motion, 3D

- Theme kinetic home + `bi-3d` + NGT skin.
- 3D Scroll Manager: rules table `wp_*ngt_3d_scroll_rules`, REST `ngt3d/v1`, shared handles `bi-ngt-gsap` / `bi-ngt-scrolltrigger`.
- 3D Filmstrip: `[ngt_filmstrip]` / `[nextgen_filmstrip]`.
- UI library: theme `ui-library/` and/or `wp-content/ngt-ui-library`. Vendor loader will not fetch a second GSAP if `window.gsap` or the theme script tag exists.
- Reduced motion: existing `motion.js` / token durations. Primary content is in PHP prototypes — visible without JS.