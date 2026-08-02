# Cinematic video hero integration — summary report

**Date:** 2026-07-26  
**Theme:** BeyondInfinity 1.9.17  
**Scope:** Background video heroes, design tokens, performance / a11y controls

---

## 1. Video → page mapping

| Asset | Page / section | Integration |
| --- | --- | --- |
| `hero-mother-daughter-online.mp4` | Home hero | `bi_get_hero_video_url()` → cinematic full-viewport `.ngi-hero--cinematic` |
| `about-learning.mp4` | About hero | `bi_tutoring_video_url( 'about' )` → `.ng-page-hero--cinematic` |
| `become-tutor-classroom.mp4` | Become a Tutor hero | `bi_tutoring_video_url( 'become-a-tutor' )` |
| `find-tutor-session.mp4` | Find a Tutor hero | `bi_tutoring_video_url( 'find-a-tutor' )` |
| `story-online-class.mp4` | Home `#video-story` (Our Story band) | `bi_tutoring_video_url( 'video-story' )` → `.bi-cinematic-band` |

Slug aliases also registered: `our-story`, `journey` → `story-online-class.mp4` (ready if a dedicated page is added later).

**Posters (static fallbacks):** theme image registry keys — `home_video`, `about_feature`, `become_tutor`, `hero_bg`.

---

## 2. Architecture

### PHP
- `inc/motion.php` — `bi_tutoring_video_map()`, `bi_tutoring_video_url()`, `bi_tutoring_video_poster_url()`, updated `bi_get_hero_video_url()`
- `inc/page-composer.php` — cinematic hero markup (scrim, poster, no raw `autoplay`), enqueues cinematic CSS/JS
- `inc/defaults-production/home.php` — home hero + story band
- `functions.php` (+ BeyondInfinity copy) — enqueue cinematic assets on kinetic home

### CSS / JS
- `assets/css/bi-cinematic-hero.css` — full-viewport cover, scrims, glass stats, fade-in, reduced-motion
- `assets/js/bi-cinematic-video.js` — IntersectionObserver play/pause, Save-Data / 2G poster-only, `requestIdleCallback`, visibility pause
- Tokens expanded in `assets/ngt/css/tokens.css` and `assets/css/tokens/unified.css`
- `page-composer.css` / `kinetic-image-hover.css` consume hero opacity / z-index tokens

Interior heroes already called `bi_tutoring_video_url()` but the function was **undefined** — wiring it activates About / Become / Find without new page templates.

---

## 3. Performance behaviour

| Control | Behaviour |
| --- | --- |
| `preload="metadata"` | Default on all cinematic videos |
| Poster image | Holds paint; no CLS when video fades in |
| IntersectionObserver | Plays only when ~15%+ in view; pauses when off-screen |
| `requestIdleCallback` | Boot deferred |
| Save-Data / slow-2G | Poster-only (`bi-cinematic--poster-only`) |
| `prefers-reduced-motion` | Video hidden; poster/photo remains |
| GPU | `transform: translateZ(0)`, `backface-visibility: hidden` |
| No blocking JS | Deferred footer scripts; no sync XHR |

**Note:** Only one bitrate exists per clip (~2.9–5.5 MB). True “mobile lower bitrate” needs re-encoded 720p/480p variants (see recommendations).

---

## 4. Accessibility

- Videos are decorative: `muted`, `playsinline`, `aria-hidden="true"`, no controls
- Dark brand + vignette scrims for WCAG AA text contrast over motion
- Reduced-motion users get static posters only
- Keyboard / focus: existing CTA focus rings via unified tokens; story trailer button retained (`#ngiOpenVideo`)
- Semantic `<section aria-labelledby>` on story band; page heroes keep H1

---

## 5. `!important` inventory (intentional only)

| Location | Rule | Rationale |
| --- | --- | --- |
| `assets/ngt/css/tokens.css` | `.lenis.lenis-smooth { scroll-behavior: auto !important; }` | Defeat UA/theme smooth scroll that fights Lenis |
| `assets/ngt/css/tokens.css` | `[data-reveal*] { opacity/transform !important }` under reduced-motion | Beat JS inline styles so content stays visible |
| `assets/css/bi-cinematic-hero.css` | hide cinematic `<video>` under reduced-motion | Guarantee no decorative motion media for a11y preference |

No new `!important` was added to override Elementor / WPBakery / WooCommerce. Prefer specificity + later cascade (`bi-cinematic-hero` after page-composer / kinetic).

---

## 6. CSS conflicts resolved

- Hard-coded hero video opacities (`0.28` / `0.35`) → `--bi-hero-video-opacity` / `--bi-home-hero-video-opacity`
- Ad-hoc z-index on video/mesh → `--bi-z-hero-*` / `--ngt-z-hero-*`
- Missing `bi_tutoring_video_url()` left interior video CSS dead → function defined; heroes activate
- Homepage used legacy `hero-loop.mp4` / CDN → prefers `assets/videos/hero-mother-daughter-online.mp4`

---

## 7. Files modified / added

**Added**
- `assets/css/bi-cinematic-hero.css`
- `assets/js/bi-cinematic-video.js`
- `documentation/ux-redesign/07-cinematic-video-integration.md` (this report)

**Modified**
- `inc/motion.php`
- `inc/page-composer.php`
- `inc/defaults-production/home.php`
- `functions.php`, `NextGenTutors-BeyondInfinity/functions.php`
- `assets/ngt/css/tokens.css`
- `assets/css/tokens/unified.css`
- `assets/css/page-composer.css`
- `assets/css/kinetic-image-hover.css`

(`inc/` and `assets/` are junctions into the BeyondInfinity package.)

---

## 8. Remaining recommendations

1. **Re-encode mobile variants** (e.g. `*-720.mp4` / `*-480.mp4`) and serve via `<source media="(max-width:…)"` or JS `matchMedia`.
2. **Extract true frame posters** with ffmpeg from each MP4 (current posters are thematic JPEGs, not exact first frames).
3. **Optional dedicated `/our-story` page** — slug map already supports it.
4. **Full NGT CSS dead-code audit** (`components.css` / `pages.css` / `content.css` / `floating.css`) — deferred; tokens expanded and consumers migrated for heroes only to avoid risky wholesale restyles.
5. **Lighthouse** on staging after deploy (videos inflate network weight; poster-only path should keep Perf ≥ target on throttled mobile).
6. **CDN / cache headers** for `assets/videos/*` (long `Cache-Control`, immutable).

---

## 9. QA checklist (manual)

- [ ] Home hero loops muted, full-bleed, text readable
- [ ] About / Become / Find heroes show mapped videos
- [ ] `#video-story` band uses `story-online-class.mp4`
- [ ] Scroll away → video pauses; return → resumes
- [ ] OS reduced-motion → poster only, no console errors
- [ ] Chrome DevTools Network → Save-Data → poster only
- [ ] 320 / 768 / 1280 / 1920 layouts without overflow
- [ ] Keyboard tab through hero CTAs and story “Watch trailer”
