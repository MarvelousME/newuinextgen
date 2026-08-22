# JavaScript Source Map

**Audit date:** 2026-08-14

| Feature | Handle | File | Idempotent? | Notes |
| ------- | ------ | ---- | ----------- | ----- |
| Mobile offcanvas | `bi-nav-menu` | `assets/js/nav-menu.js` | Yes (single IIFE) | Escape, backdrop click, **resize/orientation close** added |
| Sticky header + FAB | `bi-sticky-ui` | `assets/js/bi-sticky-ui.js` | Yes | Sole sticky scroll owner |
| Float panels | `bi-ngt-floating` | `assets/ngt/js/floating.js` | Yes | Does not rebuild dock |
| Kinetic home | `bi-kinetic-home` | `assets/js/kinetic-home.js` | Page-scoped | Home only |
| Focus trap | `bi-focus-trap` | `assets/js/bi-focus-trap.js` | Utility | Used by drawers/modals |
| Booking drawer | `bi-booking-drawer` | `assets/js/bi-booking-drawer.js` | Conditional | `--ngt-z-drawer` |
| chrome.js | — | `assets/ngt/js/chrome.js` | N/A | **Not enqueued**; static prototypes only |
| Bridge | `bi-ngt-wp-bridge` | `assets/js/ngt-wp-bridge.js` | Yes | Explicitly does not inject chrome |

## Scroll lock

`nav-menu.js` sets `document.body.style.overflow` on open/close. Breakpoint change to desktop clears lock. Multi-overlay scroll-lock manager is **not yet unified** across booking drawer + Elementor popups (documented limitation).
