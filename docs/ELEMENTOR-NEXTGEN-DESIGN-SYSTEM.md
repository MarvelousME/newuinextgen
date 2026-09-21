# NextGen Elementor Design System

Administrators can build NextGen Tutors pages in Elementor using reusable widgets in the **NextGen Tutors** category. Widgets are a presentation layer only. Domain data still comes from Companion; motion still runs through the existing 3D Scroll Manager.

## Architecture

```
Elementor
  → NextGen Elementor widgets (bi_el_*)
    → BeyondInfinity markup + tokens
      → Companion providers / shortcodes
      → NGT3D runtime (GSAP / ScrollTrigger / Three.js already registered)
```

Existing kinetic PHP templates stay in place. If a page has real Elementor content, that content renders. Otherwise the current BeyondInfinity PHP fallback renders. Theme templates are not deleted.

## How to edit pages

1. Open **Pages** and edit the page.
2. Click **Edit with Elementor**.
3. In the widget panel, open **NextGen Tutors**.
4. Drag widgets onto the canvas.
5. Publish. Do not switch the page to an empty Elementor canvas unless you intend to replace the kinetic body.

To keep the current marketing home, leave the homepage without Elementor sections (kinetic fallback). To take over the homepage, insert widgets until `_elementor_data` is non-empty.

## How to create sections

Use Elementor containers (or sections). Drop a NextGen widget in each container. Style the container with Elementor **Style** and **Advanced**. Widget-level look is controlled by **NextGen preset** (Glass, Aurora, Orbital, Neural, Spatial, Holographic, Editorial, Dark Technology, Soft Education, Premium Minimal).

## How to use the NextGen widgets

| Widget | What it shows | Data source |
|---|---|---|
| NextGen Hero | Headline, CTA, optional search | CMS hero + Elementor text |
| Find Tutor | Intake / marketplace / smart match | Companion shortcodes |
| Tutor Grid / Card | Live tutors | `NGC_UI_Provider_Registry` / `bi_get_live_tutors` |
| Subject Grid / Card | Subject catalog | Companion subjects CMS |
| Tutor Filmstrip | 3D filmstrip | `[ngt_filmstrip]` |
| Testimonials | Published reviews | review provider |
| Pricing | Tiers | `ngc_get_pricing_tiers` |
| Stats | Live KPIs | analytics provider / `bi_real_stat_cards` |
| FAQ | FAQ items | Section CMS `home/faq` |
| Booking CTA | Book button | existing booking drawer URL |
| Animated Heading / GSAP Text Reveal | Motion typography | markup for NGT3D |
| 3D Tilt Card | Tilt host | `data-bi-tilt` → NGT3D compat |
| Scroll Mask / Zoom / Horizontal / Sticky Story | Scroll scenes | NGT3D presets |
| 3D Scene / Particle / Orb / WebGL Background | Lazy WebGL | `window.THREE` from NGT3D, CSS fallback if missing |

Empty Companion data does **not** invent tutors, prices, or reviews on the public site. The editor shows a status hint.

## How to apply motion

Open the widget’s **Advanced** tab → **NextGen Motion**.

- Enable Motion
- Motion Preset (maps to existing NGT3D animation IDs such as `depth-hero`, `tilt-3d`, `scroll-mask`)
- Scroll Trigger, Start, End, Scrub
- Perspective, Depth, Translate X/Y/Z, Rotate X/Y/Z, Scale, Opacity, Stagger
- Mouse Interaction
- Reduced Motion
- Disable on Tablet / Mobile

These controls compile into `window.NGT3D.rules` via the `ngt3d_page_rules` filter. They do **not** start a second GSAP engine.

## How to configure 3D effects

1. Enable motion and pick a 3D preset (`tilt-3d`, `webgl-distortion`, `stack-3d`, …).
2. For WebGL widgets, set **Quality** to Low / Medium / High.
3. Provide a **CSS fallback image** for devices without WebGL.
4. Keep **Reduced Motion** on. The runtime already respects `prefers-reduced-motion`.
5. WebGL canvases lazy-init in view, pause off-screen, and dispose on unload.

Do not enqueue extra copies of GSAP, ScrollTrigger, or Three.js.

## How to build reusable Elementor templates

1. In Elementor → **Templates** → **Saved Templates**.
2. Insert from the **NextGen Tutors** library source (Homepage, Find a Tutor, Tutor Profile, Subjects, Pricing, Become a Tutor, About, Safety, Guarantee, Contact).
3. Save a copy under **My Templates** if you need to customize.
4. Insert the copy onto a page. Existing page content is not overwritten until you publish.

## Performance guidance

- Prefer Medium quality WebGL; Low on mobile (Disable on Mobile is on by default).
- One WebGL host per viewport is enough.
- Kinetic home JS is still dequeued in the Elementor editor to avoid conflicts.
- NGT3D assets load only when rules exist (DB rules or compiled widget motion).

## Accessibility guidance

- Headings are semantic (`h1`–`h4` controls).
- Buttons meet a 44px minimum target.
- Focus rings use `--ngt-focus-ring`.
- `prefers-reduced-motion` disables widget animation when Reduced Motion is on.
- Touch / coarse pointers skip hover tilt.
- Content remains readable with JavaScript or WebGL disabled.

## Happy Addons

Happy Addons is optional. If it is installed, kit accent variables alias to `--ngt-*` tokens. Nothing requires it.

## Fallback behaviour

```
if Elementor document has content:
    render Elementor (including NextGen widgets)
else:
    render existing BeyondInfinity PHP / kinetic template
```

See `bi_should_show_theme_fallback()` in `inc/page-wrapper.php`.
