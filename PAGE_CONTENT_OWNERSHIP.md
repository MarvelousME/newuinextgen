# PAGE CONTENT OWNERSHIP

Date: 2026-09-11  
Authority: restored pre-flatten architecture (git `5afb189`)

## Global chrome

| Concern | Canonical source |
|---|---|
| Header | `header.php` → Elementor Theme Builder location **or** `templates/header/{transparent\|default\|minimal}.php` |
| Navigation | `bi_render_primary_nav_menu()` → location `primary` → menu **NextGen Primary Grouped** (HEAD behaviour; do not rename until visual verify) |
| Footer | `footer.php` → Elementor location **or** `templates/footer/{default\|minimal}.php` |
| Page shell | `bi_render_page_template()` in `inc/page-wrapper.php` |
| Body blend | `inc/prototype-blend.php` (default ON) + `inc/production-content.php` fallback |

Do **not** treat `template-parts/pages/*` as ownership — those are flatten artifacts, unwired.

---

## Page: Home

- **Slug:** home (front page)
- **Canonical route:** `front-page.php`
- **Router:** `inc/defaults/home.php`
- **Content source:** `bi_render_page_default('home', 'index-body.php')` → prototype `prototypes/index-body.php` and/or production kinetic via blend/production helpers
- **Header / Footer:** via wrapper (`get_header` / `get_footer`)
- **CSS/JS:** kinetic home / 3D / filmstrip enqueues (existing `inc/kinetic-home.php`, motion, bi-3d)
- **Do not inline:** `index-body.php`

## Page: About

- **Slug:** about
- **Canonical route:** `page-about.php` (Template Name: About)
- **Router:** `inc/defaults/about.php`
- **Content source:** `prototypes/about-body.php` (blend) / production default when blend off
- **Header / Footer:** wrapper
- **Do not inline:** `about-body.php`

## Page: Find a Tutor

- **Slug:** find-a-tutor / find-tutor
- **Canonical route:** `page-find-tutor.php`
- **Router:** `inc/defaults/find-a-tutor.php`
- **Content source:** prototype `find-a-tutor-body.php` and/or production marketplace + Companion shortcodes
- **Do not inline:** body prototype

## Pages: Become a Tutor, Blog, Contact, Support, Guarantee, Pricing, Tutor Vetting, Safety Guide, Privacy, Terms, Onboarding, WordPress Setup

- **Canonical route:** matching `page-*.php`
- **Router:** matching `inc/defaults/{slug}.php`
- **Content source:** matching `prototypes/*-body.php` when blend active; else `inc/defaults-production/{slug}.php`
- **Shortcodes:** as registered in Companion / pages-registry (render via existing helpers — do not duplicate into static PHP)

## Pages: Login, Register, Parent Checkout, Thank You, Child Safety

- **Canonical route:** matching `page-*.php`
- **Router:** `inc/defaults/*`
- **Content source:** primarily production defaults + Companion forms
- **Do not replace** `the_content()` / shortcode output with static-only markup

## Dashboards: Parent / Student / Tutor / Admin

- **Canonical route:** matching `page-*-dashboard.php` / `admin-dashboard.php`
- **Chrome:** minimal header/footer via registry (exception)
- **Content source:** production shortcode bodies / Companion dashboards

## Generic fallback

- **Canonical route:** `page.php`
- **Behaviour:** wrapper → theme default when forced, else builder / `the_content()`

---

## Ownership rules (recovery)

1. One body source of truth per slug under active blend/production resolution — **not** duplicated into `page-*.php`.
2. WordPress DB / Elementor / shortcodes remain authoritative where the wrapper chooses builder content.
3. `template-parts/pages/*` is evidence only until proven equivalent and deliberately adopted.
4. Assets stay on existing enqueue paths — do not load every CSS/JS globally to “fix” styling.
