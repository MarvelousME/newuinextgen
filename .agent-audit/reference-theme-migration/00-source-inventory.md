# 00 — Source Inventory

**Theme:** `nextgen-tutors-theme`  
**Path:** `C:\Users\marvi\Desktop\nextgen-tutors-theme`  
**Evidence:** `evidence/source-file-list.txt` (334 tracked paths)  
**Audit date:** 2026-08-09

## Summary

| Metric | Value |
|--------|-------|
| Tracked paths | 334 |
| Unique theme sources (excl. `dist/` duplicates) | ~167 |
| Page templates | 13 |
| Dashboard entrypoints | 3 (+ admin via setup) |
| Chrome injection | `assets/js/chrome.js` (nav, footer, drawer, transitions) |

## Page templates (`page-templates/`)

| Template | Slug / route |
|----------|--------------|
| `about.php` | about |
| `become-a-tutor.php` | become-a-tutor |
| `blog.php` | blog |
| `contact.php` | contact |
| `find-a-tutor.php` | find-a-tutor |
| `guarantee.php` | guarantee |
| `onboarding.php` | onboarding |
| `pricing.php` | pricing |
| `privacy.php` | privacy |
| `safety-guide.php` | safety-guide |
| `support.php` | support |
| `terms.php` | terms |
| `tutor-vetting.php` | tutor-vetting |

Additional routes: `front-page.php` (home), `single-tutors.php` (tutor profile CPT).

## Directory map (root, non-dist)

| Area | Key paths |
|------|-----------|
| Core | `functions.php`, `header.php`, `footer.php`, `front-page.php`, `style.css`, `theme.json` |
| Inc | `inc/menus.php`, `inc/enqueue.php`, `inc/rest-api.php`, `inc/woocommerce.php`, `inc/workflows.php`, `inc/setup-wizard.php`, … |
| Dashboards | `dashboard/admin-dashboard.php`, `dashboard/student-dashboard.php`, `dashboard/tutor-dashboard.php` |
| Prototypes | `prototypes/*-body.php` (16 page bodies) |
| Template parts | `template-parts/nav.php`, `footer.php`, `home/*`, `find-a-tutor/*`, `tutor/*`, `pages/*` |
| Assets CSS | `assets/css/tokens.css`, `components.css`, `pages.css`, `content.css`, `floating.css`, `admin.css` |
| Assets JS | `assets/js/chrome.js`, `home.js`, `tutors.js`, `dashboard*.js`, `pricing.js`, `onboarding.js`, … |
| Integrations | `integrations/{woocommerce,elementor,fluentcrm,fluent-forms,gamipress,masterstudy-lms,workflows}/` |
| WooCommerce | `woocommerce/checkout/form-checkout.php`, `woocommerce/myaccount/dashboard.php` |
| Bundled plugins | `bundled-plugins/nextgentutors-workflows/` |
| Build artifacts | `dist/nextgen-tutors-theme/`, `dist/nextgen-tutors-theme-py/` (mirror copies) |

## Reference NAV (verbatim from `chrome.js` NAV_LINKS)

1. Find a Tutor  
2. Pricing  
3. Become a Tutor  
4. About  
5. Contact  
6. **Compliance** dropdown: Safety Guide, Terms & Conditions, Privacy Policy, Tutor Vetting, 1st Lesson Guarantee  
7. Blog  

**CTA (source):** Sign In + **Get Started** (primary button). Migration target removes Get Started.

## Reference footer columns (`buildFooter()`)

| Column | Links |
|--------|-------|
| Brand | Logo, SA positioning copy, social icons, Proudly South African chip |
| Explore | Find a Tutor, Pricing, Become a Tutor, Blog, Subjects (`#subjects`) |
| Company | About Us, Contact, Safety & Trust, Tutor Vetting, Lesson Guarantee, Help & Support |
| Get In Touch | Phone, email, address (static placeholders in ref) |
| Legal bar | Privacy Policy, Terms, POPIA Compliance |

## Notable source-only behaviours

- Static HTML hrefs (`.html` suffix) injected client-side by `chrome.js`
- Demo contact placeholders in footer (not wired to WP options)
- Tutor marketplace UI in `find-a-tutor` + `tutors.js` (prototype data layer)
- No `child-safety` page (POPIA link in footer was `#` placeholder)
