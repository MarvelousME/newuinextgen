# 01 — Target Inventory

**Theme:** `NextGenTutors-BeyondInfinity` (canonical)  
**Path:** `NextGenTutors-BeyondInfinity/`  
**Files:** ~361  
**Audit date:** 2026-08-09

## Summary

| Metric | Value |
|--------|-------|
| Registered pages (`content/page-map.json`) | 24 |
| Page templates (`page-templates/`) | 17 |
| Root page handlers (`page-*.php`) | 22 |
| Default content packs (`inc/defaults/`) | 24 |
| Nav schema version | `2026-08-09-reference-nav-v1` |

## Page registry (`bi_pages_registry()` + `page-map.json`)

| Slug | Template | Type |
|------|----------|------|
| home | `front-page.php` | public |
| find-a-tutor | `page-find-tutor.php` | public |
| become-a-tutor | `page-become-a-tutor.php` | public |
| about | `page-about.php` | public |
| contact | `page-contact.php` | public |
| support | `page-support.php` | public |
| blog | `page-blog.php` | public |
| guarantee | `page-guarantee.php` | public |
| register | `page-register.php` | auth |
| login | `page-login.php` | auth |
| pricing | `page-pricing.php` | public |
| parent-checkout | `page-parent-checkout.php` | auth |
| tutor-vetting | `page-tutor-vetting.php` | trust |
| safety-guide | `page-safety-guide.php` | trust |
| thank-you | `page-thank-you.php` | utility |
| privacy-policy | `page-privacy-policy.php` | legal |
| terms | `page-terms.php` | legal |
| child-safety | `page-child-safety.php` | legal (BI-only) |
| parent-dashboard | `page-parent-dashboard.php` | dashboard |
| student-dashboard | `page-student-dashboard.php` | dashboard |
| tutor-dashboard | `page-tutor-dashboard.php` | dashboard |
| admin-dashboard | `admin-dashboard.php` | dashboard |
| onboarding | `page-onboarding.php` | admin |
| wordpress-setup | `page-wordpress-setup.php` | admin |

**CPT:** `single-tutors.php` — tutor profile (not a page slug).

## Header / footer system

| File | Role |
|------|------|
| `templates/header/default.php` | Standard nav + Sign In CTA |
| `templates/header/transparent.php` | Hero overlay nav + Sign In CTA |
| `templates/footer/default.php` | Reference column footer (Explore / Company / Get In Touch) |
| `templates/footer/minimal.php` | Dashboard minimal chrome |
| `inc/nav-menu.php` | `bi_nav_menu_structure()`, menu sync, Sign In CTA |

## Asset layers

| Layer | Path | Notes |
|-------|------|-------|
| Reference carry-over | `assets/ngt/{css,js,img}/` | Ported ref tokens, chrome, page scripts |
| BI extensions | `assets/css/bi-*.css`, `assets/js/bi-*.js` | Kinetic home, cinematic hero, scheme toggle |
| Motion pack | `assets/css/motion/` | Entrance, parallax, theme bridge |
| Skins | `assets/css/skins/beyond-infinity.css` | Canonical skin |
| Brand | `assets/brand/`, `inc/brand-content.php` | PDF-sourced narrative merged with ref copy |

## Companion integration touchpoints

| Page / area | Companion shortcodes / REST |
|-------------|----------------------------|
| find-a-tutor | `ngc_tutor_marketplace`, `ngc_find_tutor_form` |
| become-a-tutor | `ngc_become_tutor_form` |
| contact / support | `ngc_contact_support_form` |
| register / login | `ngc_*_form` family |
| dashboards | `ngc_{parent,student,tutor,admin}_dashboard` |
| parent-checkout | `ngc_parent_checkout` |

## BI-only pages (not in ref nav)

| Slug | Purpose |
|------|---------|
| child-safety | POPIA / child protection (footer legal; replaces ref `#` POPIA link) |
| parent-checkout | WooCommerce booking checkout |
| thank-you | Post-purchase confirmation |
| wordpress-setup | Admin setup wizard |
| register | Auth (Sign In CTA only in nav; no Get Started) |
