# 04 — Component Inventory

## Shared chrome components

| Component | Reference | Target | Status |
|-----------|-----------|--------|--------|
| Primary nav | `chrome.js` buildNav | `bi_render_primary_nav_menu()` | ✓ Migrated |
| Nav dropdown (Compliance) | JS hover + drawer sub-links | PHP fallback + `nav-menu.js` | ✓ |
| Sign In CTA | `.btn--ghost` → login/dashboard | `bi_render_header_sign_in_cta()` | ✓ |
| Get Started CTA | `.btn--primary` | — | ✓ Removed |
| Footer grid | `buildFooter()` | `templates/footer/default.php` | ✓ |
| Page transitions | `#page-transition` overlay | `assets/js/bi-loader.js` / kinetic bridge | Partial (BI motion layer) |
| Smooth scroll | Lenis in chrome.js | `assets/ngt/js/vendor/lenis.min.js` | ✓ Ported |
| Lucide icons | `data-lucide` | Same pattern in templates | ✓ |

## Home page sections

| Section | Reference (`template-parts/home/`) | Target | Action |
|---------|-----------------------------------|--------|--------|
| Hero | `hero.php` | `template-parts/sections/hero.php` + kinetic | Merge |
| Marquee | `marquee.php` | kinetic home sections | Merge |
| How it works | `how-it-works.php` | `sections/how-it-works.php` | Merge |
| Subjects | `subjects.php` | home `#subjects` anchor | Keep |
| Tutors carousel | `tutors-carousel.php` | `sections/tutors.php` | Enhance (live CPT) |
| Stats / guarantee | `stats-guarantee.php` | trust strip + guarantee CTA | Merge |
| Pricing teaser | `pricing.php` | `sections/services.php` | Merge |
| Testimonials | `testimonials.php` | kinetic / narrative blocks | Merge |
| Become tutor band | `become-tutor.php` | `sections/final-cta.php` | Merge |
| CTA band | `cta-band.php` | `sections/final-cta.php` | Merge |

## Find a Tutor components

| Component | Reference | Target | Notes |
|-----------|-----------|--------|-------|
| Directory grid | `find-a-tutor/directory.php` | NGC marketplace shortcode shell | Companion owns data |
| Tutor card | `tutor-card.php`, `tutor/card.php` | `components/tutor-card.php`, UI library | Visual parity |
| Booking steps | `booking-steps.php` | Companion booking drawer | Domain in NGC |
| Page head | `pagehead.php` | page template header | Merge |
| CTA band | `cta-band.php` | template partial | Keep |
| Filters / search | `tutors.js` | REST + NGC matching | Enhance |

## Trust / legal components

| Component | Reference prototype | Target default |
|-----------|--------------------|--------------------|
| Safety steps table | `safety-guide-body.php` | `inc/defaults/safety-guide.php` |
| Vetting timeline | `tutor-vetting-body.php` | `inc/defaults/tutor-vetting.php` |
| Guarantee steps | `guarantee-body.php` | `inc/defaults/guarantee.php` |
| Terms sections | `terms-body.php` | `inc/defaults/terms.php` |
| Privacy sections | `privacy-body.php` | `inc/defaults/privacy-policy.php` |

## Dashboard components (Companion-owned)

| Component | Reference | Target |
|-----------|-----------|--------|
| Parent dashboard shell | `dashboard-body.php` | `page-parent-dashboard.php` + shortcode |
| Student dashboard | same | `page-student-dashboard.php` |
| Tutor dashboard | `tutor-dashboard-body.php` | `page-tutor-dashboard.php` |
| Admin dashboard | `admin-dashboard-body.php` | `admin-dashboard.php` |
| Dashboard REST | `dashboard-api.js` | `assets/js/dashboard-rest.js` (shared) |

## UI library (BI extensions, no ref equivalent)

| Component | Path |
|-----------|------|
| Pricing card | `template-parts/ui-library/pricing-card.php` |
| Booking list | `template-parts/ui-library/booking-list.php` |
| Dashboard KPI | `template-parts/ui-library/dashboard-kpi.php` |
| Status badge | `template-parts/ui-library/status-badge.php` |
| Calendar grid | `template-parts/ui-library/calendar-grid.php` |
