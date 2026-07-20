# PAGES-AUDIT-REPORT.md

**Theme:** `beyondinfinity` v1.9.0  
**Plugin:** `nextgencompanion` v1.9.0  
**Date:** 2026-07-06  
**Status:** PARTIAL — filesystem audit VERIFIED; Playwright **28 tests** across 8 specs (requires Docker @ :8899); live production UAT **NOT VERIFIED**

---

## Summary

| Metric | Before | After |
|--------|--------|-------|
| Launch pages in `page-map.json` | 22 | **23** |
| `pages-to-review` HTML prototypes | 17 | 17 (all mapped) |
| Missing theme page templates | 2 (`wordpress-setup`, `onboarding`) | **0** |
| Pages with enriched defaults (this pass) | — | 7 |
| SEO meta descriptions (dashboards + setup) | 4 missing | **0 missing** |
| Footer nav gaps (guarantee, blog, support) | 3 missing | **0 missing** |

---

## pages-to-review → Theme mapping

| Prototype | Theme slug / artifact | Action this pass |
|-----------|----------------------|------------------|
| `index.html` | `home` (`front-page.php` + `inc/defaults/home.php`) | **Merged** — hero subject/location search form |
| `find-a-tutor.html` | `find-a-tutor` | No change (already merged) |
| `become-a-tutor.html` | `become-a-tutor` | No change |
| `about.html` | `about` | No change |
| `contact.html` | `contact`, `support` | No change |
| `pricing.html` | `pricing` | No change |
| `safety-guide.html` | `safety-guide`, `child-safety` | No change |
| `tutor-vetting.html` | `tutor-vetting` | No change |
| `guarantee.html` | `guarantee` | No change |
| `blog.html` | `blog` | No change |
| `terms.html` | `terms` | **Merged** — 12 numbered legal sections from prototype |
| `privacy.html` | `privacy-policy` | **Merged** — POPIA badges + expanded sections |
| `tutor-dashboard.html` | `tutor-dashboard` | No change (intro strip exists) |
| `dashboard.html` | `student-dashboard`, `parent-dashboard` | **Merged** — welcome strip + KPI cards + panel labels |
| `onboarding.html` | `onboarding` | **Fixed** — added missing `page-onboarding.php` |
| `tutor-profile.html` | `single-tutors.php` (CPT) | No change — not a WP page |
| `wordpress-setup.html` | `wordpress-setup` | **Added** — new page, default, map entry, admin-only guard |

### Theme-only pages (not in prototypes)

| Slug | Purpose |
|------|---------|
| `register` | Parent/student registration (`ngc_*` forms) |
| `login` | Login + forgot password |
| `thank-you` | Form confirmation |
| `child-safety` | Legal (sourced from safety-guide concepts) |
| `admin-dashboard` | Mission control (`ngc_admin_dashboard`) |

---

## Changed values audit

### New files

| File | Purpose |
|------|---------|
| `page-wordpress-setup.php` | Page template |
| `inc/defaults/wordpress-setup.php` | Default content from `wordpress-setup.html` |
| `page-onboarding.php` | **Repair** — was in page-map but missing on disk |
| `inc/pages-registry.php` | Central inventory + `bi_pages_touchpoint_audit()` |

### Modified files

| File | Change |
|------|--------|
| `content/page-map.json` | Added `wordpress-setup` (23 pages) |
| `functions.php` | Loads `inc/pages-registry.php` |
| `inc/security.php` | `wordpress-setup` → administrator only |
| `inc/seo.php` | Meta for dashboards + `wordpress-setup` |
| `inc/admin.php` | Touchpoint audit table on Sync Launch Pages screen |
| `inc/template-tags.php` | `bi_hero_search_form`, `bi_popia_badges`, `bi_learner_dashboard_intro`, `bi_compat_grid`, `bi_plugin_grid`, `bi_db_table_grid`, `bi_setup_progress_percent` |
| `inc/defaults/home.php` | Hero search form |
| `inc/defaults/student-dashboard.php` | Dashboard.html intro + KPIs |
| `inc/defaults/parent-dashboard.php` | Dashboard.html intro + KPIs |
| `inc/defaults/privacy-policy.php` | POPIA badges + legal sections |
| `inc/defaults/terms.php` | 12-section legal layout |
| `inc/defaults/admin-dashboard.php` | Link to WordPress Setup |
| `footer.php` | Guarantee, Blog, Support in Quick Links |
| `assets/css/components.css` | Hero search, KPI, legal, setup grids |

### Touchpoint matrix (expected post-sync)

| Slug | page-map | template | default | SEO | shortcodes |
|------|----------|----------|---------|-----|------------|
| home | ✓ | `front-page.php` | ✓ | ✓ | — |
| find-a-tutor | ✓ | `page-find-tutor.php` | ✓ | ✓ | `ngc_find_tutor_form` |
| become-a-tutor | ✓ | `page-become-a-tutor.php` | ✓ | ✓ | `ngc_become_tutor_form` |
| about | ✓ | `page-about.php` | ✓ | ✓ | — |
| contact | ✓ | `page-contact.php` | ✓ | ✓ | `ngc_contact_support_form` |
| support | ✓ | `page-support.php` | ✓ | ✓ | `ngc_contact_support_form` |
| blog | ✓ | `page-blog.php` | ✓ | ✓ | — |
| guarantee | ✓ | `page-guarantee.php` | ✓ | ✓ | — |
| register | ✓ | `page-register.php` | ✓ | ✓ | `ngc_parent_register_child_form`, `ngc_student_register_form` |
| login | ✓ | `page-login.php` | ✓ | ✓ | `ngc_login_form`, `ngc_forgot_password_form` |
| pricing | ✓ | `page-pricing.php` | ✓ | ✓ | — |
| tutor-vetting | ✓ | `page-tutor-vetting.php` | ✓ | ✓ | — |
| safety-guide | ✓ | `page-safety-guide.php` | ✓ | ✓ | — |
| thank-you | ✓ | `page-thank-you.php` | ✓ | ✓ | — |
| privacy-policy | ✓ | `page-privacy.php` | ✓ | ✓ | — |
| terms | ✓ | `page-terms.php` | ✓ | ✓ | — |
| child-safety | ✓ | `page-child-safety.php` | ✓ | ✓ | — |
| parent-dashboard | ✓ | `page-parent-dashboard.php` | ✓ | ✓ | `ngc_parent_dashboard` |
| student-dashboard | ✓ | `page-student-dashboard.php` | ✓ | ✓ | `ngc_student_dashboard` |
| tutor-dashboard | ✓ | `page-tutor-dashboard.php` | ✓ | ✓ | `ngc_tutor_dashboard` |
| admin-dashboard | ✓ | `admin-dashboard.php` | ✓ | ✓ | `ngc_admin_dashboard` |
| onboarding | ✓ | `page-onboarding.php` | ✓ | ✓ | `ngc_admin_dashboard` |
| wordpress-setup | ✓ | `page-wordpress-setup.php` | ✓ | ✓ | — |

### Security / auth touchpoints

| Slug | Roles allowed |
|------|---------------|
| `parent-dashboard` | parent, parent_guardian, administrator |
| `student-dashboard` | student, subscriber, administrator |
| `tutor-dashboard` | tutor, administrator |
| `admin-dashboard` | administrator |
| `onboarding` | administrator |
| `wordpress-setup` | administrator |

### Navigation touchpoints

| Location | Links added/verified |
|----------|---------------------|
| Footer Quick Links | `/guarantee`, `/blog`, `/support` |
| Admin dashboard CTA | `/wordpress-setup` |
| WordPress Setup page | Admin sync screen, plugins screen, `/admin-dashboard`, `/contact` |

### Shortcodes (companion + UI library + theme)

`ngc_find_tutor_form`, `ngc_match_tutor`, `ngc_become_tutor_form`, `ngc_contact_support_form`, `ngc_parent_register_child_form`, `ngc_student_register_form`, `ngc_login_form`, `ngc_forgot_password_form`, `ngc_parent_dashboard`, `ngc_student_dashboard`, `ngc_tutor_dashboard`, `ngc_admin_dashboard`, `ng_ui_component`, `ngc_ui_component`

Theme-owned: `[bi_tutors_carousel]`

Elementor: **NG Hero**, **NG Tutor Cards**, **NG Pricing Cards** widgets (`nextgen-tutors` category)

---

## Known gaps

1. **Static previews** — `previews/build.py` still generates ~14 pages, not all 23.
2. **Amelia booking plugin** — not bundled in Docker zips; WF-10 Amelia sync needs manual install.
3. **E2E on Docker** — run `powershell -File scripts/run-playwright.ps1` (28 tests) when stack is up.
4. **Aspirational frameworks** — PageFormsRegistry, Audit, Self-Healing partial (Companion has NGC_Audit, NGC_Self_Healing).

---

## Deploy verification checklist

1. Upload `beyondinfinity` + `nextgencompanion`; activate both.
2. **Appearance → Sync Launch Pages** — confirm 23 pages, touchpoint table all ✓, shortcodes all ✓.
3. Visit `/wordpress-setup` as admin — builder grid, plugin cards, DB table list.
4. Visit `/student-dashboard` and `/parent-dashboard` logged in — intro + KPI strip + REST mount.
5. Visit `/privacy-policy` and `/terms` — POPIA badges and numbered sections.
6. Home hero — subject dropdown + location field submits to `/find-a-tutor`.

---

*Generated as part of pages-to-review consolidation. Re-run audit via Appearance → Sync Launch Pages → Page touchpoint audit table.*
