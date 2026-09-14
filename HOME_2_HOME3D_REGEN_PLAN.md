# Home 2 → Home 3D Regeneration Plan

**Date:** 2026-09-12  
**Live URL:** `/home-3d/` (preview only — live `/` unchanged)  
**Template:** `page-home-3d.php` → `template-parts/pages/home.php`  
**Motion:** NextGen 3D Scroll Manager curated map (`NGT3D_Home_3d_Page`)

---

## Intent (UX)

Create **Home 2**: a second homepage composition that keeps the kinetic information pyramid of the live home, but adopts the **3D lab visual system** (photography + navy/cyan cinematic palette) so scroll-linked 3D rules have real visual anchors.

| Layer | Source | Home 2 / home-3d |
|-------|--------|------------------|
| Structure / copy / shortcodes | Kinetic home | Reuse |
| Color tokens | 3D demo + kinetic navy/cyan | Home-3d skin override |
| Photography | `assets/images/*` (same pack as `/3d-scroll-test/`) | Section backgrounds |
| Motion | Admin NGT3D curated map | Unchanged selectors |

## Color scheme (from 3D home / kinetic)

| Token | Value | Role |
|-------|-------|------|
| Navy | `#07172f` / `#092746` | Surfaces, banner |
| Midnight | `#031126` | Deep hero wash |
| Cyan accent | `#28c7f7` | CTAs, links, focus |
| Gold | `#ffb703` | Sparse accent (stats/eyebrows) |
| Soft ink | `#e8f3ff` / `#10213f` | Text on dark / light bands |

Avoid purple-gradient “AI slop”; keep NextGen navy→cyan identity.

## Image pack (from 3D demo `media_pack`)

| Key | File | Home 2 section |
|-----|------|----------------|
| hero | `hero-bg.jpg` | `#hero` cinematic plate |
| video | `home-video.jpg` | `#video-story` / poster |
| about | `about-feature.jpg` | `#platform-highlights` / proof band |
| become | `become-tutor.jpg` | pathways side / become panel |
| pricing | `pricing-bg.jpg` | `#pricing` depth plate |
| cta | `cta-bg.jpg` | `#cta` parallax plate |
| guarantee | `guarantee-bg.jpg` | trust/safety accent |

## Information pyramid (ui-page)

1. **Hero** — brand + one CTA + photo/video plane  
2. **Trust / subjects filmstrip** — proof + browse  
3. **Journey / story / video** — how it works + cinematic story  
4. **Pathways / tutors filmstrip / pricing** — choose mode + people + rates  
5. **Reviews / FAQ / CTA** — social proof + close  

One job per section; filmstrips only on home-3d (not live `/`).

## Implementation steps

1. Theme helper `bi_home_3d_media_pack()` — URIs for the image pack.  
2. `assets/css/home-3d-v2.css` — scoped under `body.ngt-home-3d-preview`.  
3. Enqueue skin + CSS variables on `/home-3d/` only.  
4. Annotate home body sections with image CSS vars when `bi_is_home_3d_preview()`.  
5. Keep NGT3D selectors: `#hero`, `#journey`, `#cursor-reveal`, `#reviews`, `#cta`, filmstrip `#subjects`/`#tutors`.  
6. Verify HTTP + image presence + `NGT3D` runtime on `/home-3d/`.

## Non-goals

- Do not replace live `/`.  
- Do not invent new photography.  
- Do not load every 3D preset (curated map only).  
- Do not flatten `*-body` / production defaults as part of this work.

## Acceptance

- `/home-3d/` shows navy/cyan skin + ≥5 theme photos in DOM/CSS.  
- Live `/` visual unchanged.  
- NGT3D rules still resolve (selectors present).  
- Reduced-motion: banner static; heavy pins already disabled in map.
