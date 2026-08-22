# Accessibility Report (Frontend Audit Pass)

**Audit date:** 2026-08-14  
**Target:** WCAG 2.2 AA where feasible  
**Verdict:** **PARTIAL — improvements verified; full AA audit incomplete**

| Check | Status | Evidence |
| ----- | ------ | -------- |
| Skip link | Present | Homepage a11y tree |
| Primary nav landmark | Present | `aria-label="Primary Navigation"` |
| Menu toggle `aria-expanded` | PASS | CDP open/close |
| Escape closes drawer | Implemented | `nav-menu.js` |
| Focus restore on Escape | Implemented | Focuses toggle |
| Focus trap in offcanvas | PARTIAL | Focus trap util exists; not fully bound to primary nav drawer |
| Contrast (drawer) | PASS | White on `#092746` |
| Reduced motion | Present | `accessibility.css` / bridge |
| Touch targets | PARTIAL | Toggle ~44px; needs systematic audit |
| Heading hierarchy on home | PASS after fix | `h1` NextGen Tutors restored |

## Remaining a11y work

- Full axe/playwright scan across journeys  
- Dialog/support panel focus trap verification  
- Form label/`inputmode` audit on booking + registration
