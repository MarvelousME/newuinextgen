# 05 — Content Inventory

## Brand narrative (`inc/brand-content.php`)

| Block | Source | Migrated |
|-------|--------|----------|
| Tagline | NGT Design PDFs + ref home | ✓ |
| Positioning / trust line | PDFs + ref hero copy | ✓ |
| Story (Our story) | Ref about + PDFs | ✓ Merged into about + home |
| Mission & values | Ref about page | ✓ |
| Become-a-tutor earnings | PDF + ref prototype | ✓ |
| Contact departments | PDF contact spec | ✓ |

## Page default content merges (completed)

| Page | Default file | Merge source |
|------|--------------|--------------|
| Home | `inc/defaults/home.php`, `inc/defaults-production/home.php` | Ref `index-body.php`, kinetic sections |
| About | `inc/defaults/about.php`, `inc/defaults-production/about.php` | Ref `about-body.php`, `bi_brand_content()` |
| Become a Tutor | `inc/defaults/become-a-tutor.php`, `inc/defaults-production/become-a-tutor.php` | Ref `become-a-tutor-body.php` |
| Pricing | `inc/defaults/pricing.php` | Ref `pricing-body.php` + live rates |
| Contact | `inc/defaults/contact.php` | Ref `contact-body.php` FAQ |
| Find a Tutor | `inc/defaults/find-a-tutor.php` | Ref directory intro copy |
| Safety Guide | `inc/defaults/safety-guide.php` | Ref `safety-guide-body.php` |
| Tutor Vetting | `inc/defaults/tutor-vetting.php` | Ref vetting steps |
| Guarantee | `inc/defaults/guarantee.php` | Ref guarantee process |
| Terms | `inc/defaults/terms.php` | Ref `terms-body.php` |
| Privacy | `inc/defaults/privacy-policy.php` | Ref `privacy-body.php` |
| Support | `inc/defaults/support.php` | Ref support FAQ |
| Blog | `inc/defaults/blog.php` | Ref blog intro |
| Child Safety | `inc/defaults/child-safety.php` | BI-only (POPIA); extends ref safety |

## Footer copy (verbatim from ref)

| Element | Text |
|---------|------|
| Brand paragraph | "South Africa's premier online tutoring platform, connecting Grade 1–12 and varsity students with verified, SACE-registered tutors across all nine provinces." |
| Chip | Proudly South African |
| Explore heading | Explore |
| Company heading | Company |
| Contact heading | Get In Touch |

## Static → dynamic replacements

| Ref placeholder | Target helper |
|-----------------|---------------|
| +27 (0)12 345 6789 | `bi_get_phone()` |
| hello@nextgentutors.co.za | `bi_get_support_email()` |
| 123 Education St, Sandton | `bi_get_service_area()` |
| © 2026 static | `gmdate('Y')` + `bloginfo('name')` |
| POPIA `#` | `/child-safety` |

## Content not duplicated

| Ref content | Reason |
|-------------|--------|
| Demo tutor JSON (`data.js`) | Replaced by Companion tutor CPT + REST |
| Hard-coded dashboard metrics | Companion dashboard REST |
| Setup wizard demo passwords | Demo mode only (Phase 14 policy) |
| Ref "contact tutor" modal copy | Single booking flow via NGC |
