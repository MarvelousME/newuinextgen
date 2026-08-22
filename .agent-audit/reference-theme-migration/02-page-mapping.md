# 02 — Page Mapping

**Ref → Target:** `nextgen-tutors-theme` → `NextGenTutors-BeyondInfinity`  
**Status:** All reference pages accounted for (2026-08-09)

| Ref page / route | Target slug | Target template | Action | Notes |
|------------------|-------------|-----------------|--------|-------|
| Home (`front-page.php`) | home | `front-page.php` + kinetic sections | **Merge** | BI kinetic home; ref section order + brand copy merged |
| Find a Tutor | find-a-tutor | `page-find-tutor.php` | **Enhance** | Keep NGC marketplace matching; ref directory UI as visual layer |
| Pricing | pricing | `page-pricing.php` | **Merge** | Live rate helpers + ref tier layout |
| Become a Tutor | become-a-tutor | `page-become-a-tutor.php` | **Merge** | Ref steps + `ngc_become_tutor_form` |
| About | about | `page-about.php` | **Merge** | `inc/brand-content.php` + ref story blocks |
| Contact | contact | `page-contact.php` | **Enhance** | Live contact helpers; ref FAQ accordion |
| Blog | blog | `page-blog.php` | **Keep** | WP posts loop |
| Support | support | `page-support.php` | **Keep** | Footer Company column; not in ref primary nav |
| Safety Guide | safety-guide | `page-safety-guide.php` | **Keep** | Nav label unchanged |
| Terms | terms | `page-terms.php` | **Keep** | Nav label **Terms & Conditions** |
| Privacy | privacy-policy | `page-privacy-policy.php` | **Keep** | Ref slug `privacy` → BI `privacy-policy` |
| Tutor Vetting | tutor-vetting | `page-tutor-vetting.php` | **Keep** | Compliance dropdown |
| Guarantee | guarantee | `page-guarantee.php` | **Keep** | Nav label **1st Lesson Guarantee** |
| Onboarding | onboarding | `page-onboarding.php` | **Keep** | Admin flow; not in public nav |
| Tutor profile | — (CPT) | `single-tutors.php` | **Keep** | Companion tutor records |
| Parent dashboard | parent-dashboard | `page-parent-dashboard.php` | **Keep** | Companion domain |
| Student dashboard | student-dashboard | `page-student-dashboard.php` | **Keep** | Companion domain |
| Tutor dashboard | tutor-dashboard | `page-tutor-dashboard.php` | **Keep** | Companion domain |
| Admin dashboard | admin-dashboard | `admin-dashboard.php` | **Keep** | Companion domain |
| Register | register | `page-register.php` | **Keep** | No nav link; reachable from flows |
| Login / Sign In | login | `page-login.php` | **Keep** | Header CTA target |
| — | child-safety | `page-child-safety.php` | **BI-only** | Footer POPIA link; not in ref nav |
| — | parent-checkout | `page-parent-checkout.php` | **BI-only** | Booking commerce |
| — | thank-you | `page-thank-you.php` | **BI-only** | Post-checkout |
| — | wordpress-setup | `page-wordpress-setup.php` | **BI-only** | Admin |

## Slug deltas

| Ref | Target | Reason |
|-----|--------|--------|
| `privacy` | `privacy-policy` | WP / BI legal slug convention |
| `privacy.html` hrefs | `/privacy-policy` | Menu sync resolves page object |

## Action legend

| Action | Meaning |
|--------|---------|
| Merge | Ref content/structure absorbed into BI defaults or brand-content |
| Enhance | BI domain features retained; ref UX/copy layered |
| Keep | Already aligned; no ref duplication required |
| BI-only | Target extension not present in reference theme |
