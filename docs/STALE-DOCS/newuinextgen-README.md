# NextGen Tutors — React → WordPress Theme

A production-ready WordPress theme converted from the uploaded **NextGen Tutors**
React application (Vite + React 19 + Tailwind v4 + Framer Motion + GSAP), with
maximum visual, structural and behavioral fidelity.

- **Marketing pages** are rebuilt as native PHP templates + template parts, styled
  by a hand-compiled stylesheet (`assets/css/theme.css`) that reproduces the app's
  Tailwind visual system — **zero build step required**; the ZIP works on activation.
- **The interactive app** (4 role dashboards, Simulation Hub, login/register,
  GamiPress widget, email-log simulator, the localStorage "database") is preserved
  by **re-mounting the original compiled React bundle** on app/dashboard routes —
  optional, behind a documented one-time build.
- Branding, colors, footer, CTAs and contact details are **Customizer-editable**;
  navigation uses **native WordPress menus**; output is fully escaped; **no plugin
  is required**, with shortcode-ready zones for Amelia / GamiPress / forms.

Theme folder to install: **`react-to-wp-theme/`**

---

## 1. Complete file tree

```
react-to-wp-theme/
├── style.css                      Theme header + a11y / admin-bar safety CSS
├── functions.php                  Bootstraps the /inc modules
├── index.php                      Blog index / fallback
├── front-page.php                 Static front page (static home OR React mount)
├── page.php                       Default page
├── single.php                     Single post
├── archive.php                    Category / tag / date / author archives
├── 404.php                        Not-found
├── searchform.php                 HTML5 search form
├── comments.php                   Threaded comments
├── screenshot.png                 1200×900 theme thumbnail
├── .gitignore
├── inc/
│   ├── setup.php                  add_theme_support, menus, image sizes, auto-create pages
│   ├── enqueue.php                theme.css / theme.js / React bundle enqueue
│   ├── customizer.php             Colors, CTA labels, footer, contact, React-mount toggle
│   ├── template-tags.php          Brand, SVG logo, menus, CTA buttons, post meta
│   └── helpers.php                Route map, tutor seed data, helpers
├── template-parts/
│   ├── content-page.php
│   ├── content-post.php
│   ├── components/
│   │   ├── section-heading.php
│   │   └── tutor-card.php
│   └── sections/
│       ├── hero.php               4-slide hero carousel (HeroCarousel)
│       ├── trust-strip.php        Stat row
│       ├── wp-banner.php          WordPress/Elementor banner
│       ├── mission.php            Mission / Vision / Purpose
│       ├── tutors.php             Vetted tutors grid (loops tutor-card)
│       ├── services.php           Services 4-up
│       ├── how-it-works.php       4-step path
│       ├── impact.php             Dark showcase + animated counters
│       └── final-cta.php          Closing CTA band
├── page-templates/
│   ├── template-home.php          "NextGen Home (Marketing)" — full home composition
│   └── template-full-width.php    "Full Width" — for sub-routes (Find/Pricing/etc.)
├── assets/
│   ├── css/
│   │   └── theme.css              Compiled visual system (the source of truth)
│   ├── js/
│   │   ├── theme.js               Vanilla interactions (menu/carousel/reveal/counters/tilt)
│   │   ├── customizer-preview.js  Live Customizer preview
│   │   └── README.md              Where the React bundle lands
│   ├── images/README.md           Imagery notes (remote Unsplash, self-host guide)
│   └── fonts/README.md            Font notes (system stacks, no webfonts)
└── build-src/                     Optional: builds the React bundle for app routes
    ├── package.json
    ├── vite.config.ts
    └── copy-to-theme.mjs
```

---

## 2. Asset mapping table

| Original React path | New WordPress path | Where it's used |
|---|---|---|
| `src/index.css` (`@import "tailwindcss"`) | `assets/css/theme.css` | Global — compiled Tailwind utility system as semantic CSS |
| `src/components/Navigation.tsx` | `header.php` + `assets/css/theme.css` (`.ngt-nav*`) | Sticky header, desktop + mobile nav |
| `<footer>` in `src/App.tsx` | `footer.php` | Footer columns + legal row |
| `src/components/HeroCarousel.tsx` | `template-parts/sections/hero.php` + `theme.js` (carousel) | Home hero |
| `src/components/WordPressHub.tsx` `NextGenLogo` (inline SVG) | `inc/template-tags.php` `ngt_brand_svg()` | Header + footer brand mark |
| Tutor avatars (remote Unsplash URLs) | Preserved as remote URLs in `inc/helpers.php` | Tutor cards (see `assets/images/README.md`) |
| `src/components/ThreeDCard.tsx` (tilt) | `theme.js` `initTilt()` + `.ngt-tilt` | Tutor cards |
| `src/components/FloatingParticles/GsapStagger/PageLoader` | Reduced to CSS transitions + `data-reveal` in `theme.js` | Scroll reveal (perf-safe replacement) |
| Framer Motion / GSAP runtime | `assets/js/theme.js` (IntersectionObserver, rAF counters) | All marketing animations |
| `src/main.tsx` / `src/App.tsx` (full SPA) | `assets/js/app.bundle.js` (built) mounted on `#root` | App/dashboard routes |
| Fonts (Tailwind system stacks) | `--ngt-sans` / `--ngt-mono` in `theme.css` | Global typography (no files) |

> Photographic imagery in the source was **remote**, so it is referenced remotely
> (faithful 1:1). Self-hosting steps: `assets/images/README.md`. Marked **UNVERIFIED**
> because no local image/font/video files existed in the uploaded project.

---

## 3. React component → WordPress mapping

| React component | WordPress equivalent | Notes |
|---|---|---|
| `Navigation` | `header.php`, `ngt_default_primary_menu()` | Native `wp_nav_menu` (location `primary`) + vanilla mobile drawer |
| `HeroCarousel` (+ `HeroAttachedLogo`) | `template-parts/sections/hero.php` | 4 slides; auto-rotate/swipe/dots in `theme.js` |
| Trust strip (inline) | `template-parts/sections/trust-strip.php` | — |
| WP/Elementor banner (inline) | `template-parts/sections/wp-banner.php` | Shortcode-ready CTA zone |
| Mission/Vision/Purpose (inline) | `template-parts/sections/mission.php` | — |
| Vetted tutors grid + `ThreeDCard` | `sections/tutors.php` + `components/tutor-card.php` | Reusable card looped over `ngt_get_tutors()` |
| Services grid (inline) | `template-parts/sections/services.php` | Inline SVG icons replace `lucide-react` |
| How-It-Works (inline) | `template-parts/sections/how-it-works.php` | — |
| Impact showcase (inline) | `template-parts/sections/impact.php` | Animated counters in `theme.js` |
| Final CTA (inline) | `template-parts/sections/final-cta.php` | Uses Customizer CTA labels |
| `NextGenLogo` (SVG) | `ngt_brand_svg()` | Themed via CSS vars |
| `Dashboards`, `SimulationHub`, `Forms`, `ProfileEditor`, `GamiPressWidget`, `EmailLogsWidget`, `TutorMiniCalendar`, `WordPressHub` | **Re-mounted React bundle** on app routes | Stateful localStorage app — preserved as-is, not re-implemented in PHP |
| `section-heading` pattern (repeated) | `components/section-heading.php` | Reused by every section |

---

## 4. Route → template mapping

| React `currentPage` route | WordPress page (slug) | Template assigned | Rendering |
|---|---|---|---|
| `home` | Home (`home`) → set as front page | `front-page.php` → `template-home.php` | Static PHP sections (default) **or** React mount (toggle) |
| `find-a-tutor` | Find a Tutor (`find-a-tutor`) | Full Width | PHP hero + content + `[ngc_find_tutor_form]` zone |
| `become-a-tutor` | Become a Tutor (`become-a-tutor`) | Full Width | PHP hero + `[ngc_become_tutor_form]` zone |
| `pricing` | Pricing (`pricing`) | Full Width | PHP hero + editor content |
| `vetting` | Tutor Vetting (`vetting`) | Full Width | PHP hero + editor content |
| `safety` | Safety Guide (`safety`) | Full Width | PHP hero + editor content |
| `about` | About (`about`) | Full Width | PHP hero + editor content |
| `contact` | Contact (`contact`) | Full Width | PHP hero + contact-form shortcode zone |
| `login`, `register`, `*-dashboard`, `simulation-hub`, `wordpress-hub` | Create a page with one of these slugs (e.g. `app`) | any | **Auto-mounts the React bundle** (see `ngt_should_mount_react()`) |

> On theme activation the marketing pages above are **created automatically** with the
> correct templates, and Home is set as the static front page (`inc/setup.php`).

---

## 5. Setup instructions

### A. Install the theme (no build needed)
1. Zip the `react-to-wp-theme/` folder (or use the provided ZIP).
2. WordPress admin → **Appearance → Themes → Add New → Upload Theme** → choose the ZIP → **Install** → **Activate**.
3. On activation the theme auto-creates the marketing pages and sets **Home** as the front page.
4. **Appearance → Menus**: create a menu, add the pages, assign it to **"Primary Menu (Header)"** (and optionally **"Footer Legal Menu"**).
5. **Appearance → Customize → NextGen — Brand Colors / CTA Labels / Footer / Contact Details** to brand the site. Set a logo under **Site Identity** (optional — falls back to the inline SVG).
6. Done — the marketing site is live and responsive.

### B. Re-mounting the React app (optional — enables the dashboards/simulation)
Requires Node 18+ once.
1. Copy the **original uploaded React source** into `react-to-wp-theme/build-src/`
   so it contains `src/`, `index.html`, `tsconfig.json` (the app code), alongside the
   provided `package.json`, `vite.config.ts`, `copy-to-theme.mjs`.
2. From `build-src/`:
   ```bash
   npm install
   npm run build:copy
   ```
   This writes `assets/js/app.bundle.js` and `assets/css/app.bundle.css` into the theme.
3. To run the **full SPA on Home**: Customize → *NextGen — CTA Labels* → enable
   **"Mount full React app on Home"**. For dashboards, create a Page with slug
   `app` (or `dashboard`, `login`, `register`, `simulation-hub`, `wordpress-hub`) —
   it auto-mounts the bundle on `#root`.

### Rebuilding the CSS (optional)
`assets/css/theme.css` is a hand-compiled, dependency-free reproduction of the
app's Tailwind v4 utility system (Tailwind v4 is config-less / CSS-first, so there
is **no `tailwind.config.js`** in the source to port). Edit it directly — it uses
plain CSS with `--ngt-*` variables and standard media queries, so no toolchain is
needed to maintain it.

---

## 6. Activation checklist

- [ ] ZIP installs via Appearance → Themes → Upload without error.
- [ ] Theme activates with **no fatal errors / no PHP notices** (WP 6.0+, PHP 7.4+).
- [ ] `screenshot.png` shows in the theme picker.
- [ ] Marketing pages were auto-created (Pages list shows Home, Find a Tutor, …).
- [ ] Home is set as the static front page (Settings → Reading).
- [ ] Primary menu assigned; header nav + mobile drawer work.
- [ ] Customizer sections appear (Brand Colors, CTA, Footer, Contact) and live-preview.
- [ ] Logo upload overrides the inline SVG; removing it restores the SVG.
- [ ] Permalinks flushed (Settings → Permalinks → Save once if any 404s).

## 7. QA checklist

- [ ] **Desktop / tablet / mobile**: hero, grids, footer reflow correctly (breakpoints at 640/768/980/1024/1100px).
- [ ] Hero carousel auto-rotates, pauses on hover/focus, supports arrows, dots, swipe, and `prefers-reduced-motion`.
- [ ] Sticky header gains shadow on scroll and clears the admin bar.
- [ ] Scroll-reveal and animated counters fire once on enter; disabled under reduced-motion.
- [ ] Tutor cards tilt on pointer (desktop only), CTA links resolve to the Find-a-Tutor page.
- [ ] All output escaped (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`); inline SVG is trusted/static.
- [ ] All assets load via `get_template_directory_uri()` — **no hardcoded absolute URLs**.
- [ ] 404, search, archive, single post, and a default Page all render with theme chrome.
- [ ] Customizer color changes propagate to hero, buttons, accents (CSS variables).
- [ ] Keyboard: skip-link, menu toggle, carousel controls, and focus styles operate.
- [ ] (If built) React bundle mounts on `#root` for app routes and the localStorage app works.

## 8. Known limitations (only where source was missing or non-portable)

- **Stateful app re-implementation**: per the chosen strategy, the dashboards /
  Simulation Hub / localStorage "database" are **preserved by re-mounting the original
  React bundle**, not rewritten in PHP. Without running the one-time build (§5B),
  those interactive routes show the static marketing fallback instead.
- **Imagery & fonts — UNVERIFIED**: the uploaded project shipped **no local image,
  font, or video files** (all photos were remote Unsplash URLs; fonts were system
  stacks). These are referenced exactly as the source did; self-hosting steps are in
  `assets/images/README.md` and `assets/fonts/README.md`.
- **Hero background video**: the React slide-0 used a remote mixkit video. The PHP hero
  uses the lighter animated "whiteboard" card visual (also present in the source) to
  avoid an external video dependency; restore the `<video>` in `hero.php` if desired.
- **Third-party widgets** (Amelia booking, GamiPress, contact forms) were *simulated*
  in React. The theme exposes **shortcode-ready zones** (page content + footer widget
  area) so the real plugins drop in without template edits — no plugin is bundled or required.
