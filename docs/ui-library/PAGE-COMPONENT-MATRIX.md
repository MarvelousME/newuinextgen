# Page → Component Mapping Matrix

Required page targets mapped to current BeyondInfinity stack.  
**Implementation Status:** `done` = partial/provider exists; `cms` = editable via Section CMS; `phase2` = planned.

---

## Home

| | |
|---|---|
| **Existing Current Sections** | Kinetic 11-block CMS (`inc/kinetic-home.php`), hero search, marquee, how-it-works, subjects, tutors carousel, reviews, pricing teaser, FAQ, CTA |
| **Extracted Candidate Sections** | Hero canvas, Trending Subjects, Featured/All Tutors, Testimonials, Flat Monthly Packages, FAQ, Blog, Become CTA (final.html + v2) |
| **Selected Enhancements** | `hero`, `stats-band`, `subject-card`, `tutor-card`, `review-card`, `pricing-card` via UI library |
| **Source File** | `nextgentutors_final.html`, `nextgen_tutors_full_redesign_v2.html`, old `template-parts/home/*` |
| **Data Source** | `NGC_Section_CMS`, tutors CPT, `ngc_reviews`, WooCommerce, `NGC_Platform_Tracking` |
| **Backend Mapping** | `home.hero_*`, `home.featured_tutors`, `home.pricing_teaser`, `home.faq_teaser` |
| **Component Mapping** | `[ng_ui_component slug="hero" page_key="home"]`, tutor-card, stats-band |
| **Duplicate Risk** | High — pricing/FAQ headings duplicated across 3 HTML files |
| **Implementation Status** | **done** (library + CMS); wire shortcodes in defaults optional |

## Find a Tutor

| | |
|---|---|
| **Existing** | `page-find-tutor.php`, `[ngc_find_tutor_form]`, intake workflow |
| **Extracted** | Grade/subject/area/mode selectors, parent trust copy, matching promise |
| **Enhancements** | Hero + form wrapper (preserve `ngc_find_tutor_form`) |
| **Source** | old `find-a-tutor/*`, complete.html |
| **Data Source** | `NGC_Forms`, taxonomies `subject`, `grade`, `province` |
| **Backend** | Form registry + page default `inc/defaults/find-a-tutor.php` |
| **Components** | `hero` (static labels only) |
| **Duplicate Risk** | Low |
| **Status** | **cms** — form preserved; hero via CMS |

## Become a Tutor

| | |
|---|---|
| **Existing** | `page-become-a-tutor.php`, `[ngc_become_tutor_form]`, vetting workflow WF-03 |
| **Extracted** | Benefits, earnings, requirements, vetting timeline |
| **Enhancements** | `stats-band`, benefits accordion, FAQ |
| **Source** | old `become-a-tutor/content.php`, final CTA band |
| **Data Source** | CMS + `NGC_Tutor_Lifecycle` status |
| **Backend** | `become-a-tutor.*` defaults + form |
| **Components** | hero, stats-band |
| **Duplicate Risk** | Medium — CTA copy overlaps home |
| **Status** | **cms** |

## Tutor Marketplace

| | |
|---|---|
| **Existing** | Marketplace shortcode, smart matching filters |
| **Extracted** | Filter bar, tutor cards, availability chips |
| **Enhancements** | `tutor-card` grid + filter UI |
| **Source** | complete.html, old `find-a-tutor/directory.php` |
| **Data Source** | `NGC_UI_Tutor_Data_Provider`, `NGC_Smart_Matching` |
| **Backend** | CPT + REST filters |
| **Components** | tutor-card |
| **Duplicate Risk** | Low |
| **Status** | **done** provider; filters **phase2** |

## Tutor Profile

| | |
|---|---|
| **Existing** | `single-tutors.php`, profile meta, calendar REST |
| **Extracted** | Verification badges, reviews, booking CTA |
| **Enhancements** | review-card, achievement-badge, calendar-grid |
| **Source** | old `tutor/content-single.php`, `profile-*` |
| **Data Source** | CPT, `NGC_Reviews`, `NGC_Tutor_Calendar_Service` |
| **Backend** | Post meta `_ngc_*` |
| **Components** | review-card; calendar **phase2** |
| **Duplicate Risk** | Low |
| **Status** | **partial** |

## Tutor Calendar

| | |
|---|---|
| **Existing** | Calendar service + REST |
| **Extracted** | Public availability grid |
| **Enhancements** | calendar-grid component |
| **Data Source** | `NGC_UI_Calendar_Data_Provider` |
| **Status** | **phase2** partial |

## Parent / Student / Tutor / Admin Dashboard

| | |
|---|---|
| **Existing** | Studio dashboards, KPI strips in defaults |
| **Extracted** | KPI cards, upcoming lessons, notifications |
| **Enhancements** | dashboard-kpi, booking-list |
| **Data Source** | `NGC_UI_Dashboard_Data_Provider`, `NGC_Bookings` |
| **Backend** | `NGC_Studio_Dashboards`, filter `ngc_ui_dashboard_payload` |
| **Components** | dashboard-kpi, booking-list |
| **Status** | **partial** — KPI provider; booking partial phase2 |

## Pricing

| | |
|---|---|
| **Existing** | `page-pricing.php`, WooCommerce packages |
| **Extracted** | Tier cards, commitment discounts, trust section |
| **Enhancements** | pricing-card grid |
| **Data Source** | `NGC_UI_Pricing_Data_Provider` |
| **Backend** | WooCommerce + pricing manager |
| **Components** | pricing-card, hero |
| **Duplicate Risk** | High — same heading in 3 HTML files |
| **Status** | **done** |

## Subjects (+ Mathematics, Physical Science, Accounting, English, Life Sciences)

| | |
|---|---|
| **Existing** | Subject taxonomy, subject page templates |
| **Extracted** | SEO hero, grade coverage, tutor listing, FAQ |
| **Enhancements** | subject-card, tutor-card, hero |
| **Data Source** | `subject` taxonomy, tutor provider filtered by slug |
| **Backend** | Term meta + CMS per subject page |
| **Components** | subject-card, tutor-card |
| **Status** | **done** provider; per-subject CMS **cms** |

## About Us / Our Tutors / Tutor Vetting / Safety Guide

| | |
|---|---|
| **Existing** | Page defaults + trust copy |
| **Extracted** | Stats, vetting timeline, safety badges |
| **Enhancements** | stats-band, trust badges |
| **Data Source** | CMS + analytics provider |
| **Status** | **cms** |

## Blog / Matric Past Papers / Study Tips

| | |
|---|---|
| **Existing** | `page-blog.php`, posts CPT |
| **Extracted** | Blog grid from final.html |
| **Enhancements** | Use native WP loop (no duplicate blog engine) |
| **Data Source** | `post` CPT |
| **Status** | **done** (WP native) |

## FAQ / Contact / Legal (Privacy, Terms, Cookie)

| | |
|---|---|
| **Existing** | Defaults merged from prototypes |
| **Extracted** | FAQ accordion, contact form, POPIA badges |
| **Enhancements** | accordion component CSS; contact hero |
| **Data Source** | CMS / page defaults; `[ngc_contact_support_form]` |
| **Components** | hero; company provider for contact info |
| **Duplicate Risk** | Medium for FAQ headings |
| **Status** | **cms** |

---

## Frontend → Backend mapping (summary)

| Component | Backend fields / source |
|-----------|-------------------------|
| hero | `NGC_Section_CMS` → `{page}.hero_title`, `hero_subtitle`, `hero_cta_*`, `hero_image_id` |
| tutor-card | `tutors` CPT → `display_name`, thumbnail, taxonomies, `_ngc_rating`, `_ngc_hourly_rate` |
| pricing-card | WooCommerce product / `ngc_get_pricing_tiers()` |
| review-card | `ngc_reviews` table |
| stats-band | `NGC_Platform_Tracking` + tutor count |
| dashboard-kpi | `NGC_Studio_Dashboards` / `ngc_ui_dashboard_payload` filter |

---

## Usage in templates

```php
<?php ng_ui_component( 'tutor-card', [ 'limit' => 6 ] ); ?>
```

```text
[ng_ui_component slug="hero" page_key="home"]
```
