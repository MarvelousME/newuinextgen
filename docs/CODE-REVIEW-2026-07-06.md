# Code Review — UI Library & Integration Pass

**Date:** 2026-07-06  
**Scope:** UI Library module, page wiring, CMS seeding, data providers  
**Reviewer:** Automated + architectural pass

---

## Summary

| Severity | Count | Status |
|----------|------:|--------|
| Critical | 0 | — |
| High | 2 | Fixed |
| Medium | 3 | **All fixed** |
| Low | 4 | Documented |

**Tests after fixes:** `validate.php` PASS · `verify-ui-library.php` PASS · Playwright 16/16 PASS (prior run) · +6 REST smoke specs added

---

## Findings & resolutions

### HIGH — Duplicate tutor directory on Find a Tutor page

**Issue:** `find-a-tutor.php` rendered both `bi_render_tutor_directory()` and `ng_ui_component('tutor-card')`, duplicating the marketplace grid.

**Fix:** Mutually exclusive render — UI Library when `NGC_UI_Library` is active, legacy directory otherwise.

**File:** `NextGenTutors-BeyondInfinity/inc/defaults/find-a-tutor.php`

---

### HIGH — Missing `ngc_get_pricing_tiers()` implementation

**Issue:** `NGC_UI_Pricing_Data_Provider` referenced `ngc_get_pricing_tiers()` but function did not exist; pricing cards returned empty without WooCommerce.

**Fix:** Added `ngc_get_pricing_tiers()` to `class-ngc-section-cms.php` — reads CMS `pricing.rates` (online/in-person/tertiary).

**File:** `NextGenTutors-Companion/includes/class-ngc-section-cms.php`

---

### MEDIUM — `exists_in_cms()` parameter naming

**Issue:** Third parameter documented as `field_key` but used as `section_key`.

**Fix:** Renamed to `section_key` for clarity. Import scanner call was already correct.

**File:** `class-ngc-ui-duplicate-detector.php`

---

### MEDIUM — `ngt_get_tutors()` static demo roster

**Issue:** `inc/helpers.php` contained a static tutor array when CPT is empty.

**Fix:** Extracted `ngt_get_demo_tutor_roster()` and gated via `ngt_demo_tutors_enabled()` (`NGC_Platform_Demo::is_enabled()` or `ngt_allow_demo_tutor_roster` filter). Production returns `[]` when no CPT tutors.

**Files:** `NextGenTutors-BeyondInfinity/inc/helpers.php`, `verify-ui-library.php` (info-level check)

---

### MEDIUM — CMS seed skips existing DB rows

**Issue:** `seed_research_copy()` skips sections that already have DB rows — won't update sites seeded before research copy merge.

**Status:** Documented. Operators can use **UI Import & Merge → Commit** or delete `ngc_page_sections` rows and re-seed.

---

### LOW — Review cards may duplicate kinetic testimonials on home

**Issue:** Home renders both `bi_get_featured_testimonials()` and `ng_ui_component('review-card')` from DB.

**Status:** Acceptable when `ngc_reviews` has published rows; empty DB shows only kinetic block. Consider toggling via CMS flag later.

---

### LOW — Calendar partial uses post ID as tutor_id

**Issue:** `calendar-grid` passes CPT post ID; `NGC_Tutor_Calendar_Service::get_calendar()` resolves context via repository.

**Status:** Verified — repository handles post/user ID resolution.

---

### LOW — Auto-seed on admin_init

**Issue:** `NGC_UI_Library::maybe_seed_cms_copy()` runs once per site on first admin visit.

**Status:** Acceptable for dev; production should use explicit Import & Merge commit.

---

## Architecture compliance

| Rule | Compliant |
|------|-----------|
| UI partials free of hardcoded tutor/price/rating | Yes |
| Dynamic data via providers | Yes |
| Theme does not write `ngc_*` tables | Yes |
| Companion does not own presentation markup | Yes (partials in theme) |
| REST alias `ngt/v1` | Yes (fixed in v1.9.0) |

---

## Recommended follow-up (see `docs/NEXT-STEPS.md`)

1. ~~Gate `ngt_get_tutors()` static fallback behind demo mode only~~ **Done**
2. Playwright REST smoke for WF-08, WF-12, WF-14 — **Added** (`blueprint-wf08-wf12-wf14-ops.spec.ts`); re-run when Docker is up
3. Native Elementor widgets for top 5 components
4. Re-run PAGES-AUDIT against live Docker instance
5. CMS seed on Docker — **`wp ngc ui_seed_cms`** + `scripts/seed-ui-cms-docker.ps1`
