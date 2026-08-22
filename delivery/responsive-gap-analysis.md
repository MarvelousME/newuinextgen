# Responsive Gap Analysis

**Audit date:** 2026-08-14

## Mobile-first status

| Area | Status | Notes |
| ---- | ------ | ----- |
| Nav breakpoint | Desktop-first `@media (max-width: 1099px)` | Functional; full mobile-first rewrite deferred |
| Container | Improved to `width: min(100%, …)` + gutters | `style.css` |
| Kinetic home | Separate bundle | Needs dedicated 320–430 matrix |
| Forms | Present on home match wizard | Not fully keyboard/a11y audited this pass |
| Tables | Dashboard/Woo | UNVERIFIED this pass |

## Viewport matrix (executed where noted)

| Width | Header | Menu | Content | Overflow | Notes |
| ----: | ------ | ---- | ------- | -------- | ----- |
| 390 | PASS | PASS (open/close, white on navy) | PASS (kinetic main restored) | PARTIAL | Emulation `iw`/`cw` mismatch |
| 320–375 | UNVERIFIED | — | — | — | Scheduled |
| 768 | UNVERIFIED | — | — | — | — |
| 1024+ | UNVERIFIED | desktop nav expected | — | — | — |
| 1366–1920 | UNVERIFIED | — | — | — | — |

## Gaps remaining

1. Full Playwright screenshot matrix (320→1920)  
2. Orientation change headed proof on physical device  
3. WooCommerce checkout mobile  
4. Dashboard mission CSS at 320px
