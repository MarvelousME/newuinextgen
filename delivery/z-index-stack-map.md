# Z-Index Stack Map

**Audit date:** 2026-08-14  
**Canonical tokens:** `assets/css/tokens/unified.css`

| Layer | Token | Value | Expected context |
| ----- | ----- | ----: | ---------------- |
| Base | `--ngt-z-base` | 0 | Page |
| Raised | `--ngt-z-raised` | 2 | Cards / local |
| Sticky / nav (idle) | `--ngt-z-nav` / `--bi-z-header` | 40 | Fixed header |
| FAB | `--bi-z-fab` | 30 | Float dock closed chrome |
| Overlay / backdrop | `--ngt-z-overlay` | 450 | Offcanvas backdrop |
| Header elevated / drawer | `--ngt-z-header-elevated` / `--ngt-z-drawer` | 500 | Open mobile nav stacking context |
| Modal | `--ngt-z-modal` | 600 | Dialogs |
| Popover | `--ngt-z-popover` | 700 | Popovers |
| Toast | `--ngt-z-toast` | 800 | Toasts |
| Loader | `--ngt-z-loader` | 900 | Page loader |

## Stacking-context rule (mandatory)

`.ngt-nav__menu` is a **child** of `.ngt-nav` (`z-index: 40`). Raising only the menu’s z-index cannot escape the header context. When offcanvas opens, elevate **`.ngt-nav`** via `body.ngt-offcanvas-open .ngt-nav { z-index: var(--ngt-z-header-elevated) }`.

## Verified

Open mobile nav: `navZ=500`, `menuZ=500`, hit-test returns primary links (not footer).

## Remaining exceptions (document, do not escalate blindly)

| File | Hardcoded z | Reason |
| ---- | ----------: | ------ |
| `ngt-toast.css` | ~10050 | Historical toast ceiling — migrate to `--ngt-z-toast` |
| `theme-switcher.css` | 99990 | Admin-ish floater |
| `nbi-infinity.css` | 9998–10000 | Legacy infinity overlays |
