# Responsive Verification

**Status:** UNVERIFIED (no automated capture in this pass)

## Token-based layout

- `--ngt-container-gutter: clamp(1rem, 4vw, 2rem)`
- `--ngt-container-default: 1280px`
- `--ngt-container-narrow: 720px`
- Fluid typography via `clamp()` in `brand-semantic.css`

## Breakpoints to validate

| Viewport | Pages |
|----------|-------|
| 375px | Home, Find a Tutor filters, login |
| 768px | Nav drawer, subject tabs |
| 1280px | Marketplace grid, footer |
| 1440px+ | Wide container, hero |

## Known scoped systems

- Kinetic homepage (`.ngi-home`) — separate CSS bundle
- Dashboard mission control — `dashboard-mission.css`

Run Playwright screenshot matrix before claiming PASS.
