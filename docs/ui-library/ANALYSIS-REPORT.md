# NextGen Tutors — UI Research & Theme Analysis Report

Generated: 2026-07-06  
Stack: BeyondInfinity v1.9.0 + NextGenTutors-Companion v1.9.0  
Inventory: `docs/ui-library/inventories/` (run `node scripts/analyze-ui-artifacts.mjs` to refresh)

---

## Executive summary

Four HTML research artifacts and the legacy **nextgen-tutors-theme-original-top-ui** (181 files) were scanned. Best patterns were consolidated into a **NextGen UI Library** with:

- Normalized `--ng-*` design tokens (mapped to existing `--navy` / `--lime` brand)
- 11 data providers (no hardcoded tutor/pricing/review data in new partials)
- 8 PHP template partials + component registry
- Admin **Import & Merge** tool + duplicate heading detection
- Elementor widget + WPBakery `vc_map` bridge

HTML artifacts contain **hardcoded tutor/pricing data** — these are **reference-only**; live site uses CPT, `ngc_*` tables, WooCommerce, and CMS fields.

---

## HTML artifact analysis

| File | Page Type | Best Sections | Reusable Components | Unique Content | Dynamic Data Fields | Assets | Risks | Recommendation |
|------|-----------|---------------|---------------------|----------------|--------------|--------|-------|----------------|
| `nextgentutors_final.html` (72KB) | Home mega | Hero canvas, Trending Subjects, How It Works, Featured/All Tutors, Testimonials, Pricing teaser, FAQ, Blog, Become CTA, Guarantee, Contact | `.tutor-*`, `.subj-*`, `.faq-*`, `.rv-*`, glass nav, marquee | Dark theme copy, guarantee band | tutor_name, rating, price, stats, review_text | Fraunces + Plus Jakarta, CSS vars `--ap/--as/--ag` | Hardcoded roster + pricing | **Adopt** motion/glass patterns; **reject** embedded tutor rows |
| `nextgentutors_complete (1).html` | Multi/tutor | Directory, profile patterns, intake | Tutor cards, filters | Extended marketplace copy | Same as above | Shared with final | Duplicate sections | **Merge** filter UX into marketplace; map copy to CMS |
| `nextgen_tutors_redesign.html` | Home | Hero, subject explorer, pricing, FAQ | Lighter section set | "Everything for / Expert tutors" headings | price, stats | Light variant tokens | Overlaps v2 | **Pick** lighter hero for A/B via CMS |
| `nextgen_tutors_full_redesign_v2.html` | Home | Hero, subjects, tutors, pricing, FAQ, CTA bands | Combines final + redesign | Become tutor + guarantee CTAs | price, stats | Hybrid | 6 duplicate headings | **Primary** visual reference for home CMS sections |

### Shared design tokens (research)

| Token | final.html | Current BI (`tokens.css`) | Normalized `--ng-*` |
|-------|------------|---------------------------|---------------------|
| Primary | `#020617` bg / sky accent | `#092746` navy | `--ng-color-primary` |
| Accent | `#38bdf8` | `#aece61` lime | `--ng-color-accent` |
| Display font | Fraunces | Space Grotesk | `--ng-font-heading` |
| Body font | Plus Jakarta | Open Sans | `--ng-font-body` |
| Radius | `16px` | `16px` | `--ng-radius-md` |

**Decision:** Keep **brand navy/lime** as production primary; expose research sky/indigo as optional dark-section accents (`--ng-color-ap/as`).

---

## Old theme analysis (selected items)

| Old Theme Item | Type | Purpose | Current Equivalent | Keep | Merge | Replace | Discard | Reason | Data Mapping |
|----------------|------|---------|-------------------|------|-------|---------|---------|--------|--------------|
| `assets/css/tokens.css` | CSS | Brand tokens | `assets/ngt/css/tokens.css` | ✓ | | | | Already merged in BI | CSS vars |
| `template-parts/home/hero.php` | Partial | Home hero | `inc/kinetic-home.php` + CMS | | ✓ | | | Wire to `NGC_Section_CMS` hero | `home.hero_*` |
| `template-parts/home/tutors-carousel.php` | Partial | Featured tutors | `bi_render_tutors_carousel` | | ✓ | | | Use `NGC_UI_Tutor_Data_Provider` | `tutors` CPT |
| `template-parts/tutor/card.php` | Partial | Tutor tile | `template-parts/components/tutor-card.php` | ✓ | | | | UI library delegates here | CPT meta |
| `template-parts/pricing-card.php` | Partial | Package card | `ng-ui/pricing-card.php` | | ✓ | | | WooCommerce products | `ngc_get_pricing_tiers` |
| `assets/js/tutors.js` | JS | Client filters | Marketplace + REST | | | ✓ | | Replace with provider-driven filters | REST `ngc/v1` |
| `assets/js/data.js` | JS | Static tutor JSON | **Deprecated** | | | | ✓ | Violates data rule | — |
| `inc/page-content.php` | PHP | Hardcoded copy | `inc/defaults/*.php` + CMS | | | ✓ | ✓ | Content → backend fields | Section CMS |
| `single-tutors.php` | Template | Tutor profile | `single-tutors.php` (BI) | ✓ | | | | Preserve slug/CPT | Post + meta |
| `setup-wizard` assets | Admin | Onboarding | `page-wordpress-setup.php` | ✓ | | | | Admin-only | — |

---

## Duplicate content report

| Duplicate Item | Source A | Source B | Similarity | Recommended Action |
|--------------|----------|----------|------------|-------------------|
| Flat monthly packages. | final | redesign, v2 | exact | Single `home.pricing_teaser` CMS field |
| Common questions | final | redesign, v2 | exact | Single `home.faq_teaser` |
| Want to become | final | v2 | exact | `home.become_tutor_cta` |
| Love the lesson | final | v2 | exact | `guarantee` page section |
| Everything for | redesign | v2 | exact | Hero subtitle variant — human review |
| Expert tutors. | redesign | v2 | exact | Hero title variant — human review |

---

## UI pattern inventory (extracted)

1. **Hero** — split grid, gradient navy, optional particle/canvas (kinetic-home)
2. **Marquee trust chips** — `bi_get_marquee_items()` → CMS/static labels only
3. **Subject explorer** — taxonomy-driven cards
4. **Tutor grid** — CPT + filters (grade, subject, mode, area)
5. **Review carousel** — `ngc_reviews` table
6. **Pricing tiers** — WooCommerce / pricing manager
7. **FAQ accordion** — CMS accordion + `ng-ui.js`
8. **Dashboard KPI strip** — Studio dashboards / role payload
9. **Glass navigation** — research only; BI uses existing chrome
10. **Stats band** — `NGC_UI_Analytics_Data_Provider`

---

## Component inventory

| Component | Source | Provider | Partial | Status |
|-----------|--------|----------|---------|--------|
| hero | final + old home/hero | page_content | `hero.php` | Implemented |
| stats-band | final + old stats-guarantee | analytics | `stats-band.php` | Implemented |
| tutor-card | final + old tutor/card | tutor | `tutor-card.php` | Implemented |
| subject-card | final subj-* | subject | `subject-card.php` | Implemented |
| pricing-card | final + old pricing-card | pricing | `pricing-card.php` | Implemented |
| review-card | final testi-* | review | `review-card.php` | Implemented |
| dashboard-kpi | old dashboard | dashboard | `dashboard-kpi.php` | Implemented |
| empty-state | — | — | `empty-state.php` | Implemented |
| booking-list | old tutor-dash | booking | `booking-list.php` | Implemented |
| calendar-grid | old profile-calendar | calendar | `calendar-grid.php` | Implemented |
| achievement-badge | GamiPress | gamification | `achievement-badge.php` | Implemented |

---

## Content migration plan

1. **Inventory** — `node scripts/analyze-ui-artifacts.mjs`
2. **Dedupe** — review `inventories/duplicates.json`
3. **Map** — static headings → `NGC_Section_CMS` keys (see PAGE-COMPONENT-MATRIX.md)
4. **Import** — Admin → UI Import & Merge (dry-run first)
5. **Verify** — `php NextGenTutors-Companion/scripts/verify-ui-library.php`
6. **Deprecate** — mark old `assets/js/data.js` patterns as deprecated in theme docs
7. **Human review** — conflicting hero variants (redesign vs v2)

**Never import:** tutor names, photos, bios, ratings, prices, phone numbers from HTML.

---

## Final implementation status

| Deliverable | Status |
|-------------|--------|
| HTML files analyzed | ✓ (4/4) |
| Old theme scanned | ✓ (181 files) |
| Design tokens `--ng-*` | ✓ |
| Data providers (11) | ✓ |
| Component registry | ✓ |
| Template partials | ✓ (8 core) |
| Admin Import & Merge | ✓ |
| Duplicate detection | ✓ |
| REST verify endpoint | ✓ |
| Elementor widget | ✓ |
| WPBakery element | ✓ |
| Hardcode verification | ✓ PASS |
| Full page enhancements wired | Done — home, pricing, find-a-tutor, dashboards, profile |
| Booking/calendar/badge partials | Done |
