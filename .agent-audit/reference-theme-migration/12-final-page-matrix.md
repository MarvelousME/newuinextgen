# 12 — Final Page Matrix

**Canonical target:** NextGenTutors-BeyondInfinity  
**Nav schema:** `2026-08-09-reference-nav-v1`  
**Date:** 2026-08-09

## Public pages

| # | Title | Slug | Template | In primary nav | In footer | Ref source | Status |
|---|-------|------|----------|----------------|-----------|------------|--------|
| 1 | Home | home | front-page.php | — (logo) | — | front-page.php | Merge ✓ |
| 2 | Find a Tutor | find-a-tutor | page-find-tutor.php | ✓ #1 | Explore | find-a-tutor.php | Enhance ✓ |
| 3 | Pricing | pricing | page-pricing.php | ✓ #2 | Explore | pricing.php | Merge ✓ |
| 4 | Become a Tutor | become-a-tutor | page-become-a-tutor.php | ✓ #3 | Explore | become-a-tutor.php | Merge ✓ |
| 5 | About | about | page-about.php | ✓ #4 | Company | about.php | Merge ✓ |
| 6 | Contact | contact | page-contact.php | ✓ #5 | Company | contact.php | Enhance ✓ |
| 7 | Blog | blog | page-blog.php | ✓ #7 | Explore | blog.php | Keep ✓ |
| 8 | Safety Guide | safety-guide | page-safety-guide.php | Compliance | Company | safety-guide.php | Keep ✓ |
| 9 | Terms of Service | terms | page-terms.php | Compliance† | Legal | terms.php | Keep ✓ |
| 10 | Privacy Policy | privacy-policy | page-privacy-policy.php | Compliance | Legal | privacy.php | Keep ✓ |
| 11 | Tutor Vetting | tutor-vetting | page-tutor-vetting.php | Compliance | Company | tutor-vetting.php | Keep ✓ |
| 12 | Guarantee | guarantee | page-guarantee.php | Compliance‡ | Company | guarantee.php | Keep ✓ |
| 13 | Support | support | page-support.php | — | Company | support.php | Keep ✓ |

† Nav label: **Terms & Conditions**  
‡ Nav label: **1st Lesson Guarantee**

## Auth & utility

| Title | Slug | Template | Header CTA | Ref | Status |
|-------|------|----------|------------|-----|--------|
| Login | login | page-login.php | Sign In target | login | Keep ✓ |
| Register | register | page-register.php | — | register | Keep ✓ |
| Parent Checkout | parent-checkout | page-parent-checkout.php | — | — | BI-only ✓ |
| Thank You | thank-you | page-thank-you.php | — | — | BI-only ✓ |

## Legal (BI extension)

| Title | Slug | Template | Nav | Footer | Status |
|-------|------|----------|-----|--------|--------|
| Child Safety | child-safety | page-child-safety.php | — | POPIA link | BI-only ✓ |

## Dashboards (Companion)

| Title | Slug | Template | Header | Ref dashboard | Status |
|-------|------|----------|--------|---------------|--------|
| Parent Dashboard | parent-dashboard | page-parent-dashboard.php | minimal | student-dashboard.php* | Keep ✓ |
| Student Dashboard | student-dashboard | page-student-dashboard.php | minimal | student-dashboard.php | Keep ✓ |
| Tutor Dashboard | tutor-dashboard | page-tutor-dashboard.php | minimal | tutor-dashboard.php | Keep ✓ |
| Admin Dashboard | admin-dashboard | admin-dashboard.php | minimal | admin-dashboard.php | Keep ✓ |

\* Ref had separate parent flow via dashboard routing; BI uses dedicated parent slug.

## Admin

| Title | Slug | Template | Public | Status |
|-------|------|----------|--------|--------|
| Onboarding | onboarding | page-onboarding.php | No | Keep ✓ |
| WordPress Setup | wordpress-setup | page-wordpress-setup.php | No | BI-only ✓ |

## CPT / archives

| Type | Template | Nav | Status |
|------|----------|-----|--------|
| Tutor profile | single-tutors.php | Via marketplace | Keep ✓ |
| Blog posts | single.php / archive | Via Blog page | Keep ✓ |

## Coverage summary

| Metric | Count |
|--------|-------|
| Reference page templates | 13 |
| Mapped to BI | 13 |
| BI additional pages | 5 (child-safety, parent-checkout, thank-you, wordpress-setup, register‡) |
| Unmapped reference pages | **0** |
| Primary nav items (excl. CTA) | 7 |
| Compliance sub-items | 5 |

‡ Register exists in ref flows but not as dedicated page template in ref `page-templates/`.

## Nav quick reference (target)

```
Find a Tutor | Pricing | Become a Tutor | About | Contact | Compliance ▾ | Blog | [Sign In]
                              Compliance ▾ → Safety Guide, Terms & Conditions, Privacy Policy,
                                             Tutor Vetting, 1st Lesson Guarantee
```

**Get Started:** removed site-wide from header chrome.
