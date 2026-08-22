# 03 — Header & Footer Analysis

## Primary navigation

### Reference (`assets/js/chrome.js` NAV_LINKS)

| # | Label | Href | Type |
|---|-------|------|------|
| 1 | Find a Tutor | find-a-tutor.html | link |
| 2 | Pricing | pricing.html | link |
| 3 | Become a Tutor | become-a-tutor.html | link |
| 4 | About | about.html | link |
| 5 | Contact | contact.html | link |
| 6 | Compliance | — | dropdown |
| 6a | Safety Guide | safety-guide.html | sub |
| 6b | Terms & Conditions | terms.html | sub |
| 6c | Privacy Policy | privacy.html | sub |
| 6d | Tutor Vetting | tutor-vetting.html | sub |
| 6e | 1st Lesson Guarantee | guarantee.html | sub |
| 7 | Blog | blog.html | link |

**CTA (ref):** Sign In (ghost) + **Get Started** (primary) → register/onboarding.

### Target (`inc/nav-menu.php` → `bi_nav_menu_structure()`)

| # | Label | Slug | Type |
|---|-------|------|------|
| 1 | Find a Tutor | find-a-tutor | link |
| 2 | Pricing | pricing | link |
| 3 | Become a Tutor | become-a-tutor | link |
| 4 | About | about | link |
| 5 | Contact | contact | link |
| 6 | Compliance | — | dropdown |
| 6a | Safety Guide | safety-guide | sub |
| 6b | Terms & Conditions | terms | sub |
| 6c | Privacy Policy | privacy-policy | sub |
| 6d | Tutor Vetting | tutor-vetting | sub |
| 6e | 1st Lesson Guarantee | guarantee | sub |
| 7 | Blog | blog | link |

**CTA (target):** Sign In only via `bi_render_header_sign_in_cta()` — **Get Started removed**. Logged-in users see Dashboard (role home URL).

**Schema version:** `2026-08-09-reference-nav-v1` — triggers `bi_sync_grouped_primary_menu()` on mismatch.

### Header implementation

| Concern | Reference | Target |
|---------|-----------|--------|
| Render | JS inject (`buildNav()`) | PHP templates |
| Default header | `template-parts/nav.php` + chrome | `templates/header/default.php` |
| Transparent hero | `nav--transparent` class in JS | `templates/header/transparent.php` |
| Mobile drawer | JS-built `#drawer` | `assets/js/nav-menu.js` + existing markup |
| Active state | `data-page` body attr | WP `current-menu-item` + compliance slug group |
| Scheme toggle | — | `bi_scheme_toggle_button()` (BI extension) |

## Footer

### Column parity

| Column | Reference links | Target links | Delta |
|--------|-----------------|--------------|-------|
| **Brand** | Static logo, SA copy, socials, chip | Logo, same copy (i18n), chip; socials omitted (intentional) | Live logo helpers |
| **Explore** | Find a Tutor, Pricing, Become a Tutor, Blog, Subjects | Same (Subjects → `/#subjects`) | ✓ |
| **Company** | About Us, Contact, Safety & Trust, Tutor Vetting, Lesson Guarantee, Help & Support | Same slugs | ✓ |
| **Get In Touch** | Static phone/email/address | `bi_get_phone()`, `bi_get_support_email()`, `bi_get_service_area()` | Live options |
| **Legal bar** | Privacy, Terms, POPIA (`#`) | Privacy, Terms, POPIA → `/child-safety` | POPIA wired |

### Footer implementation

| Concern | Reference | Target |
|---------|-----------|--------|
| Render | JS inject (`buildFooter()`) | `templates/footer/default.php` |
| WP menu | — | `bi_sync_footer_menu()` (utility slugs) |
| Dashboard pages | Full footer | `templates/footer/minimal.php` |

## Deliberate deviations

| Item | Decision |
|------|----------|
| Get Started CTA | Removed from nav, drawer, and `assets/ngt/js/chrome.js` |
| Footer social icons | Not migrated (placeholder `#` in ref) |
| POPIA link | Points to BI `child-safety` page |
| Support | Footer Company only; not in primary nav (matches ref nav) |
