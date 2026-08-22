# Responsive Test Report

**Date:** 2026-08-14 · Host `http://localhost:8890/`

## Automated HTTP structure checks

| URL | main | ngt-nav | sticky CTA | bi-prototype | HTTP body |
| --- | ----: | ------: | ---------: | -----------: | --------: |
| `/` | 1 | 8 | 0 | 0 | ~212KB |
| `/find-a-tutor/` | 1 | 8 | 0 | 0 | ~238KB |
| `/pricing/` | 1 | 8 | 0 | 0 | ~232KB |
| `/about/` | 1 | 8 | 0 | 0 | ~225KB |
| `/contact/` | 1 | 8 | 0 | 0 | ~227KB |
| `/login/` | 1 | 8 | 0 | 0 | ~217KB |

## CDP mobile 390×844 (home)

| Check | Result |
| ----- | ------ |
| Theme fallback comment | Present |
| `main.site-main` | Present |
| Drawer open | PASS |
| Links white on navy | PASS |
| Drawer count | 1 |
| Sticky CTA | absent |
| Dup IDs | none |
| Horizontal overflow detector | PARTIAL fail (emulation `clientWidth` 390 vs `innerWidth` 473) |

Screenshot: `delivery/evidence/frontend-audit-2026-08-14/mobile-offcanvas-390.png`
