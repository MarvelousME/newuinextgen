# Home 3D Preview

**URL:** `/home-3d/`  
**Template:** `page-home-3d.php`  
**Live home:** unchanged (`/`)

## UX / architecture intent

Curated preset map (not a dense “every effect” dump):

| Role | Sections | Presets |
|------|----------|---------|
| Primary | `#hero`, `#video-story` | `zoom`, `4kvideo` |
| Secondary | `#subjects` (3D filmstrip), `#tutoring-story`, `#platform-highlights`, `#image-hover`, `#cursor-reveal`, `#reviews` | `scale-depth`, `doublescroll`, `dark-veles`, `wiper`, `scroll-mask`, `horizontal-scroll` |
| Micro | `#trust`, `#journey`, `#tutors`, `#pricing`, `#cta` | `stagger-depth`, `perspective-reveal`, `scale-depth`, `parallax-slow+depth-scroll` |
| Text / interactive | headings, leads, CTAs, card grids | `gsapify-text-*`, `fade-up`, `stagger-children`, `magnetic-button` |
| Breathing room | `#faq`, `#ng-ui-stats`, `#learning-proof` | theme motion only |

## Filmstrip swaps (home-3d only)

- `#subjects` → `nextgen-3d-filmstrip` (`source=subjects`) instead of subject tabs
- `#tutors` → `nextgen-3d-filmstrip` (`source=tutors`) instead of kinetic tutors carousel
- Live `/` keeps the original tabs + carousel

Mobile: heavy pin/mask effects disabled or reduced. Reduced-motion handled by NGT3D runtime.

## Lab extras

Full Framer-style demos + text/entrance rules live on `/3d-scroll-test/` (`swag-card`, `transforms`, GSAPify text presets).

Live `/` is unchanged.